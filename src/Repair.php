<?php
/**
 * @package ibd2mysql
 * @author Jocelyn Flament
 */

namespace j0celyn\ibd2mysql;

use PDO;
use function dirname;
use function in_array;

/**
 * Class used with option --repair
 * Uses SQL files to recreate MySQL tables and imports InnoDB tablespaces to enable access to the data to recover
 */
class Repair
{
    public function __construct(protected PDO $db, protected Paths $paths, protected array $databases) { }

    /**
     * Create table based on the schema of original table (only if the table doesn't already exists)
     */
    public function repairTables(): void
    {
        ($timer = new Timer())->start();

        if (empty($this->databases)) {
            $all_sub_folders = true;
            $dirs = [$this->paths->getOutputDir()];
        } else {
            $all_sub_folders = false;
            $dirs = [];
            foreach ($this->databases as $dir) {
                $dirs[] = $this->paths->getOutputDir() . DIRECTORY_SEPARATOR . $dir;
            }
        }

        $files = list_files($dirs, true, $all_sub_folders, 'sql');

        foreach ($files as $folder => $files2) {
            $database = basename($folder);
            if (!database_exists($this->db, $database)) {
                writelog(sprintf("Creating database '%s'", $database));
                $this->db->query(sprintf('CREATE DATABASE `%s`', $database));
            }

            $this->db->query(sprintf('USE `%s`', $database));

            foreach ($files2 as $file) {
                if (dirname($file) === OUTPUT_DIR) {
                    continue;
                }
                $table = get_table_from_filename(basename($file, '.sql'));
                $this->repairTable($database, $table);
            }
        }
        $timer->stop();
        writelog(sprintf('End of MySQL tables creation (%s)', $timer->format()));
    }

    protected function repairTable(string $database, string $table): void
    {
        $this->db->query(sprintf('USE `%s`', $database));

        if (table_exists($this->db, $database, $table)) {
            writelog(sprintf('Database %s: table %s already exists', $database, $table));
            return;
        }
        writelog(sprintf('Database: %s ; creating table: %s', $database, $table));
        $sql_structure = file_get_contents($this->paths->getOutputPath($database, $table, 'sql'));
        $this->db->query($sql_structure);

        preg_match('/ENGINE=([^ ]+)/', $sql_structure, $matches);
        $engine = strtoupper($matches[1]);

        if ($engine === 'INNODB') {
            writelog('Deleting the newly created tablespace');
            $this->db->query(sprintf('ALTER TABLE `%s` DISCARD TABLESPACE', $table));

            writelog('Copying old IBD files');
            $this->copyIbdFiles($database, $table);

            writelog('Importing the old tablespace');
            $this->db->query(sprintf('ALTER TABLE `%s` IMPORT TABLESPACE', $table));
        } elseif ($engine === 'MYISAM') {
            writelog('Copying old MYI/MYD files to MySQL data directory');
            $this->copyMyisamFiles($database, $table);
        } elseif ($engine === 'ARCHIVE') {
            writelog('Copying old ARCHIVE files');
            $this->copyArchiveFiles($database, $table);
        }

        if (!$this->checkTable($database, $table)) {
            // if problem found while checking the table, optimize the table
            writelog('Optimizing table');
            $this->db->query(sprintf('OPTIMIZE TABLE `%s`', $table));
        }
    }

    public function copyIbdFiles(string $database, string $table): void
    {
        $ibd_files = list_files($this->paths->getBackupDir() . DIRECTORY_SEPARATOR . $database, false, false, 'ibd', sprintf('/^%s(#p#[^\.]+)?\.ibd$/', preg_quote($table, '/')));
        foreach ($ibd_files as $ibd_file) {
            $dest_path = $this->paths->getMysqlDataDirPath($database, basename($ibd_file), '');
            writelog(basename($ibd_file));
            copy($ibd_file, $dest_path);
        }
    }

    public function copyMyisamFiles(string $database, string $table): void
    {
        foreach (['myi', 'myd'] as $ext) {
            $source_file = $this->paths->getBackupPath($database, $table, $ext);
            if (file_exists($source_file)) {
                $dest_file = $this->paths->getMysqlDataDirPath($database, $table, $ext);
                writelog(basename($source_file));
                copy($source_file, $dest_file);
            } else {
                writelog(sprintf('File not found: %s', $source_file));
            }
        }
    }

    public function copyArchiveFiles(string $database, string $table): void
    {
        $source_file = $this->paths->getBackupPath($database, $table, 'ARZ');
        if (file_exists($source_file)) {
            $dest_file = $this->paths->getMysqlDataDirPath($database, $table, 'ARZ');
            writelog(basename($source_file));
            copy($source_file, $dest_file);
        } else {
            writelog(sprintf('File not found: %s', $source_file));
        }
    }

    /**
     * checks a MySQL table and returns false if a problem was reported
     */
    public function checkTable(string $database, string $table): bool
    {
        $this->db->query(sprintf('USE `%s`', $database));
        $stmt = $this->db->query(sprintf('CHECK TABLE `%s`', $table));

        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (in_array(strtolower($r['Msg_type']), ['warning', 'error'])) {
                return false;
            }
        }
        return true;
    }
}