<?php
/**
 * @package ibd2mysql
 * @author Jocelyn Flament
 */

namespace j0celyn\ibd2mysql;

use j0celyn\ibd2mysql\sql\Charset;
use j0celyn\ibd2mysql\sql\Table;
use j0celyn\ibd2mysql\sql\ForeignKeys;
use PDO;
use function is_array;

/**
 * Class used with option --sql
 * creates SQL files from SDI files
 */
class SQL
{
    /**
     * @param array<Charset> $charsets
     */
    protected array $charsets;
    protected array $ibdFiles = [];
    protected array $sqlTables = [];
    protected array $sqlFkeys = [];

    public function __construct(protected PDO $db, protected Paths $paths, protected array $databases)
    {
        $this->loadCharsets();
    }

    protected function loadCharsets(): void
    {
        $stmt = $this->db->query('SELECT col.ID, col.COLLATION_NAME, col.CHARACTER_SET_NAME, cs.MAXLEN
FROM INFORMATION_SCHEMA.COLLATIONS col
INNER JOIN INFORMATION_SCHEMA.CHARACTER_SETS cs ON col.CHARACTER_SET_NAME = cs.CHARACTER_SET_NAME
ORDER BY col.ID');
        $this->charsets = [];
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->charsets[$r['ID']] = new Charset($r['ID'], $r['COLLATION_NAME'], $r['CHARACTER_SET_NAME'], $r['MAXLEN']);
        }
    }

    public function dumpFiles(bool $write = true): void
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

        $files = list_files($dirs, true, $all_sub_folders, 'sdi');

        foreach ($files as $database => $files2) {
            $db_name = basename($database);
            foreach ($files2 as $file) {
                $this->sdi2sql($db_name, file_get_contents($file), $write);
            }
        }

        // write a single file for the entire database structure (tables only)
        foreach ($this->sqlTables as $database => $tables) {
            $sql_file = $this->paths->getOutputDir() . DIRECTORY_SEPARATOR . $database . '.sql';
            writelog(sprintf('Creating SQL file for database: %s', $database));
            file_put_contents($sql_file, sprintf("USE `%s`;\n\n", $database) . implode("\n", $tables));
        }

        // for each database, write a file for foreign keys definitions
        foreach ($this->sqlFkeys as $database => $fkeys) {
            $sql_file = $this->paths->getOutputPath($database, ForeignKeys::FILENAME, '');
            writelog(sprintf('Creating SQL file for foreign keys (database %s)', $database));
            file_put_contents($sql_file, sprintf("USE `%s`;\n\n", $database) . implode("\n\n", $fkeys));
        }
        $timer->stop();
        writelog(sprintf('End of SQL files creation (%s)', $timer->format()));
    }

    /**
     * @throws \JsonException
     */
    public function sdi2sql(string $db_name, string $sdi, bool $write): ?string
    {
        $json = json_decode($sdi, JSON_OBJECT_AS_ARRAY, 512, JSON_THROW_ON_ERROR);
        if (isset($json[0])) {
            // InnoDB
            foreach ($json as $cell) {
                if (!is_array($cell)) {
                    continue;
                }
                switch ($cell['object']['dd_object_type']) {
                    case 'Table':
                        $table_data = $cell['object'];
                        break;

                    case 'Tablespace':
                        $tablespace_data = $cell['object'];
                        break;
                }
            }
        } else {
            // MyISAM
            $table_data = $json;
        }
        if (!isset($table_data)) {
            return null;
        }

        $dd_object = $table_data['dd_object'];
        $dbname = $dd_object['schema_ref'];
        $table = new Table($dd_object, $this->charsets);

        $this->ibdFiles[$dbname][$table->getName()][] = $table->getName();

        $fkeys = $table->getForeignKeys()->__toString();
        if ($fkeys !== '') {
            $this->sqlFkeys[$dbname][$table->getName()] = $fkeys;
        }

        $sql_table = $table->__toString();

        $this->sqlTables[$dbname][$table->getName()] = $sql_table;
        if ($write) {
            // write a file for each table structure
            $sql_file = $this->paths->getOutputPath($db_name, $table->getName(), 'sql');

            createdir($this->paths->getOutputDir() . DIRECTORY_SEPARATOR . $dbname);

            writelog(sprintf("Creating SQL file for database '%s', table '%s'", $dbname, $table->getName()));
            file_put_contents($sql_file, $sql_table);
            return null;
        }

        return $sql_table;
    }
}