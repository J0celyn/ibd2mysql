<?php
/**
 * @package ibd2mysql
 * @author Jocelyn Flament
 *
 * Various functions used throughout the project
 */

namespace j0celyn\ibd2mysql;

use PDO;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use j0celyn\ibd2mysql\sql\ForeignKeys;
use function is_array;
use function is_object;
use function is_string;

function writelog(string|array|object $msg, bool $debug = false): void
{
    if ($debug) {
        $dbg = debug_backtrace(0, 2);
        printf("Called by %s (%d)\n", basename($dbg[1]['file']), $dbg[1]['line']);
    }
    if (is_array($msg) || is_object($msg)) {
        $msg = var_export($msg, true);
    }
    printf("[%s] %s\n", date('Y-m-d H:i:s'), ($debug ? '[DEBUG] ' : '') . $msg);
}

/**
 * creates a directory if necessary
 */
function createdir(string $dir): void
{
    if (!is_dir($dir) && !mkdir($dir) && !is_dir($dir)) {
        throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
    }
}

/**
 * checks if a given database exists
 */
function database_exists(PDO $db, string $database): bool
{
    $stmt = $db->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?');
    $stmt->execute([$database]);
    return ($stmt->fetchColumn() === 1);
}

/**
 * checks if a given table exists in a specific database
 */
function table_exists(PDO $db, string $database, string $table): bool
{
    $stmt = $db->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?');
    $stmt->execute([$database, $table]);
    return ($stmt->fetchColumn() === 1);
}

/**
 * removes the partition and subpartition parts (if present) from a filename and returns the table name
 */
function get_table_from_filename(string $filename): string
{
    $parts = explode('#p#', basename($filename));
    return $parts[0];
}

/**
 * @param array|string $dirs directories to browse for matching files
 * @param bool $group_by_folder if true, will return a hierarchical array instead of a flat array
 * @param bool $allSubFolders
 * @param string $ext if not empty, only returns filenames with this extension
 * @param string $regex if not empty, only returns filenames matching the regular expression
 * @param bool $invert_regex if true, only returns filenames NOT matching the regular expression `$regex`
 * @return array list of all files found, excluding ignored files
 */
function list_files(array|string $dirs, bool $group_by_folder = true, bool $allSubFolders = true, string $ext = '', string $regex = '', bool $invert_regex = false): array
{
    // list of files to ignore
    \define('IGNORED_FILES', ['.', '..']);

    if (is_string($dirs)) {
        $dirs = [$dirs];
    }
    $ext = strtolower($ext);

    $files = [];

    if ($allSubFolders) {
        $root_dir = $dirs[0];
        $dirs = scandir($root_dir);
        $dirs = array_diff($dirs, IGNORED_FILES);
        foreach ($dirs as $id => $dir) {
            $path = $root_dir . DIRECTORY_SEPARATOR . $dir;
            if (is_dir($path)) {
                $dirs[$id] = $path;
            } else {
                unset($dirs[$id]);
            }
        }
    }

    foreach ($dirs as $dir) {
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));


        /** @var SplFileInfo $file */
        foreach ($rii as $file) {

            if ($file->isDir() || $file->getBasename() === ForeignKeys::FILENAME) {
                continue;
            }

            if (($ext !== '') && (strtolower($file->getExtension()) !== $ext)) {
                continue;
            }

            if ($regex === '' || ($invert_regex && preg_match($regex, $file->getBasename()) === 0) || (!$invert_regex && preg_match($regex, $file->getBasename()))) {
                if ($group_by_folder) {
                    $files[$dir][] = $file->getPathname();
                } else {
                    $files[] = $file->getPathname();
                }
            }
        }
    }
    return $files;
}
