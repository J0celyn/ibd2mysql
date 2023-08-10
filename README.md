# ibd2mysql

[![Latest Packagist release](https://img.shields.io/packagist/v/j0celyn/ibd2mysql.svg)](https://packagist.org/packages/j0celyn/ibd2mysql)
[![Latest Packagist release](https://img.shields.io/packagist/dependency-v/j0celyn/ibd2mysql/php)](https://packagist.org/packages/j0celyn/ibd2mysql)
[![Latest Packagist release](https://img.shields.io/github/license/j0celyn/ibd2mysql)](https://packagist.org/packages/j0celyn/ibd2mysql)

## Description

MySQL provides the configuration flag [innodb_force_recovery](https://dev.mysql.com/doc/refman/8.0/en/forcing-innodb-recovery.html) to help you fix several kinds of errors.  
You should first check if it can help you fix your problems.

**ibd2mysql** is a tool to try to recover InnoDB tables under the following conditions:
- MySQL Server v8.0.0 or newer
- your tables are using the InnoDB engine
- [innodb_file_per_table](https://dev.mysql.com/doc/refman/8.0/en/innodb-parameters.html#sysvar_innodb_file_per_table) was enabled in the MySQL Server configuration **before** you ran into problems  
  (in the MySQL data directory, you should see at least one IBD file for each of your tables)
- the MySQL data dictionary is corrupted or missing (the server no longer knows what tables each database contains, despite the IBD files still being there)


## Requirements

- PHP 8
- MySQL 8

## Installation

#### Install via composer:
    composer require j0celyn/ibd2mysql

## Setup

⚠️ The IBD file format evolving with each MySQL version, it is highly recommended to run this script using the version of the MySQL Server that created the IBD files you are trying to recover.  
If using a different MySQL version, you will probably still be able to extract the SDI information and create the SQL files. However the **--repair** option is likely to fail with fatal errors when importing your IBD files into the MySQL Server.

Rename `config-empty.php` to `config.php`, then edit its values:
- **DB_HOST**: your MySQL server host
- **DB_PORT**: your MySQL server port
- **DB_USER**: your MySQL server user
- **DB_PASSWORD**: your MySQL server password
- **MYSQL_DATA_DIR**: full path to the MySQL data directory of your server
- **IBD2SDI_PATH**: full path to the directory that holds the [ibd2sdi](https://dev.mysql.com/doc/refman/8.0/en/ibd2sdi.html) program. It was probably installed together with your MySQL server
- **BACKUP_DIR**: full path to the directory of backup IBD files (the IBD files that contain your MySQL data)
- **OUTPUT_DIR**: full path to the directory where this script will create SDI and SQL files

### Usage
Here are the available options:
- **--sdi**: extracts SDI information from IBD files. **Note: Existing SDI files will be overwritten.**
- **--sql**: creates MySQL files from SDI files. **Note: Existing SQL files will be overwritten.**
- **--repair**: for each table:
    - runs the MySQL files to create the table
    - moves IBD file(s) to replace the empty tablespace with your saved tablespace
    - if needed, optimizes the table to fix some issues
 
      **Note: Existing MySQL tables will not be altered.**

When the script is called with more than one option, they will always run in the same order: **sdi sql repair**

To run the script, use one or more options.

#### Examples:

    php vendor/j0celyn/ibd2mysql/convert.php --sdi
    php vendor/j0celyn/ibd2mysql/convert.php --sdi --sql