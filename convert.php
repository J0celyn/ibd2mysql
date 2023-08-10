<?php
/**
 * @package ibd2mysql
 * @author Jocelyn Flament
 *
 * This is the file that must be called in the console
 */

require __DIR__ . '/../../autoload.php';

use j0celyn\ibd2mysql\ibd2mysql;

if (!file_exists(__DIR__ . '/config.php')) {
    die('ERROR: you must set up the config file first. Open config-empty.php and read the comments, then run this script again when the config is ready.');
}

require __DIR__ . '/config.php';
require __DIR__ . '/src/utils.php';

const OPTIONS = [
    'sdi' => [
        'cmd' => '--sdi',
        'text' => 'generate SDI files from IBD data',
    ],
    'sql' => [
        'cmd' => '--sql',
        'text' => 'generate SQL files from SDI files',
    ],
    'repair' => [
        'cmd' => '--repair',
        'text' => 'recreate the tables from SQL files, with their data (using your IBD files)',
    ],
];

$ibd2mysql = new ibd2mysql(DB_HOST, DB_PORT, DB_USER, DB_PASSWORD, IBD2SDI_PATH, BACKUP_DIR, OUTPUT_DIR,
    MYSQL_DATA_DIR, DB_NAMES);

$options = getopt('', array_keys(OPTIONS));
if (empty($options)) {
    echo "Use one or more options to run this script:\n";
    foreach (OPTIONS as $option) {
        printf("%s : %s\n", $option['cmd'], $option['text']);
    }
    exit;
}
if (array_key_exists('sdi', $options)) {
    $ibd2mysql->getSDI()->dumpFiles();
}
if (array_key_exists('sql', $options)) {
    $ibd2mysql->getSQL()->dumpFiles();
}
if (array_key_exists('repair', $options)) {
    $ibd2mysql->getRepair()->repairTables();
}
