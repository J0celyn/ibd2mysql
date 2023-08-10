<?php
/**
 * @package ibd2mysql
 * @author Jocelyn Flament
 */

namespace j0celyn\ibd2mysql;

use function count;

/**
 * Class used with option --sdi
 * extracts SDI information from IBD files and stores it in files
 */
class SDI
{
    public const TYPE = [
        'TABLE' => 1,
        'TABLESPACE' => 2,
    ];

    // regular expression to match all IBD files used to store FULLTEXT indexes
    public const FULLTEXT_IBD_FILES = '/^(fts_[a-f0-9]{16}_([a-f0-9]{16}_index_\d+|being_deleted|being_deleted_cache|config|deleted|deleted_cache)\.ibd)$/';

    public function __construct(protected Paths $paths, protected array $databases)
    {
    }

    public function dumpFiles(): void
    {
        ($timer = new Timer())->start();

        if (empty($this->databases)) {
            $all_sub_folders = true;
            $dirs = [$this->paths->getBackupDir()];
        } else {
            $all_sub_folders = false;
            $dirs = [];
            foreach ($this->databases as $dir) {
                $dirs[] = $this->paths->getBackupDir() . DIRECTORY_SEPARATOR . $dir;
            }
        }

        writelog('Copying existing SDI files to the output directory');
        $files = list_files($dirs, true, $all_sub_folders, 'sdi');
        foreach ($files as $folder => $files2) {
            $database = basename($folder);
            createdir($this->paths->getOutputDir() . DIRECTORY_SEPARATOR . $database);
            foreach ($files2 as $file) {
                $sdi_real_file = $this->paths->getOutputPath($database, basename($file), '');
                writelog(basename($file));
                copy($file, $sdi_real_file);
            }
        }

        writelog('Starting SDI files creation');
        $files = list_files($dirs, true, $all_sub_folders, 'ibd', self::FULLTEXT_IBD_FILES, true);
        foreach ($files as $folder => $files2) {
            $dbname = basename($folder);
            writelog('Processing database: ' . $dbname);
            createdir($this->paths->getOutputDir() . DIRECTORY_SEPARATOR . $dbname);
            foreach ($files2 as $file) {
                $this->dumpFile($dbname, $file);
            }
        }
        $timer->stop();
        writelog(sprintf('End of SDI files creation (%s)', $timer->format()));
    }

    /**
     * @throws \JsonException
     */
    protected function dumpFile(string $dbname, string $ibd_file): void
    {
        // extract SDI information for the table structure only
        $cmd = sprintf('"%s" --type=%d "%s"', $this->paths->getIbd2sdiPath(), self::TYPE['TABLE'], $ibd_file);
        exec($cmd, $output);
        $output = implode("\n", $output);
        $json = json_decode($output, JSON_OBJECT_AS_ARRAY, 512, JSON_THROW_ON_ERROR);

        if (isset($json[0]) && (count($json) === 1) && ($json[0] === 'ibd2sdi')) {
            return;
        }
        $table = get_table_from_filename(basename($ibd_file, '.ibd'));
        writelog(sprintf("Creating SDI file for database '%s', table '%s'", $dbname, $table));
        file_put_contents($this->paths->getOutputPath($dbname, $table), $output);
    }
}