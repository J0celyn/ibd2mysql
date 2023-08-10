<?php
/**
 * @package ibd2mysql
 * @author Jocelyn Flament
 */

namespace j0celyn\ibd2mysql;

/**
 * Helper class to easily retrieve paths to the various directories used by the script
 */
class Paths
{
    public function __construct(protected string $ibd2sdi_path, protected string $backup_dir, protected string $output_dir,
                                protected string $mysql_data_dir)
    {
        if (!is_executable($this->ibd2sdi_path)) {
            die(sprintf('ERROR: ibd2sdi program "%s" could not be located or the file is not executable, check config.', $this->ibd2sdi_path));
        }

        if (!is_dir($this->backup_dir)) {
            die(sprintf('ERROR: backup directory "%s" does not exist, check config.', $this->backup_dir));
        }

        if (!@mkdir($this->output_dir, 0777, true) && !is_dir($this->output_dir)) {
            die(sprintf('ERROR: Output directory "%s" could not be created, check config.', $this->output_dir));
        }
    }

    public function getIbd2sdiPath(): string
    {
        return $this->ibd2sdi_path;
    }

    public function getBackupDir(): string
    {
        return $this->backup_dir;
    }

    public function getOutputDir(): string
    {
        return $this->output_dir;
    }

    public function getOutputPath(string $database, string $table, string $ext = 'sdi'): string
    {
        return $this->getPath($this->output_dir, $database, $table, $ext);
    }

    protected function getPath(string $base_dir, string $database, string $table, string $ext): string
    {
        return $base_dir . DIRECTORY_SEPARATOR . $database . DIRECTORY_SEPARATOR . $table . ($ext !== '' ? '.' . $ext : '');
    }

    public function getBackupPath(string $database, string $table, string $ext = 'ibd'): string
    {
        return $this->getPath($this->backup_dir, $database, $table, $ext);
    }

    public function getMysqlDataDirPath(string $database, string $table, string $ext = 'ibd'): string
    {
        return $this->getPath($this->mysql_data_dir, $database, $table, $ext);
    }
}