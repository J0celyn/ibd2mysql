<?php
/**
 * @author Jocelyn Flament
 * @since 12/07/2023
 */

/*
 * Fill in the necessary values below, then rename this file to config.php
 * A valid MySQL connection is necessary to generate the SQL files or create the MySQL tables
 */

const DB_HOST = 'localhost'; // your MySQL server host - change it if necessary
const DB_PORT = 3306; // your MySQL server port - change it if necessary
const DB_USER = ''; // your MySQL server user - it must have database creation and table creation permissions
const DB_PASSWORD = ''; // your MySQL server password
const MYSQL_DATA_DIR = ''; // full path to the MySQL data directory of your server
const IBD2SDI_PATH = ''; // full path to the directory that holds the ibd2sdi program. It was probably installed together with your MySQL server
const BACKUP_DIR = ''; // full path to the directory of backup IBD files (the IBD files that contain your MySQL data)
const OUTPUT_DIR = ''; // full path to the directory where this script will create SDI and SQL files

/*
 * if DB_NAMES is empty, it will process all subdirectories found:
 * - in BACKUP_DIR when using the option --sdi
 * - in OUTPUT_DIR when using the options --sql or --repair
 * otherwise, il will only process the subdirectories (databases) listed below
 *
 * example: ['my_database', 'another_database', 'third_db']
*/
const DB_NAMES = [];