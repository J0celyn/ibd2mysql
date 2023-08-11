# ibd2mysql

[![Latest Packagist release](https://img.shields.io/packagist/v/j0celyn/ibd2mysql.svg)](https://packagist.org/packages/j0celyn/ibd2mysql)
[![Latest Packagist release](https://img.shields.io/packagist/dependency-v/j0celyn/ibd2mysql/php)](https://packagist.org/packages/j0celyn/ibd2mysql)
[![Latest Packagist release](https://img.shields.io/github/license/j0celyn/ibd2mysql)](https://packagist.org/packages/j0celyn/ibd2mysql)

## Description

MySQL provides the configuration flag [innodb_force_recovery](https://dev.mysql.com/doc/refman/8.0/en/forcing-innodb-recovery.html) to help you fix several kinds of errors.  
You should first check if it can help you fix your problems.

**ibd2mysql** is a utility to try to recover InnoDB tables under the following conditions:
- MySQL Server v8.0.0 or newer
- your tables are using the InnoDB engine
- [innodb_file_per_table](https://dev.mysql.com/doc/refman/8.0/en/innodb-parameters.html#sysvar_innodb_file_per_table) was enabled in the MySQL Server configuration **before** you ran into problems  
  (in the MySQL data directory, you should see at least one IBD file for each of your tables)
- the MySQL data dictionary is corrupted or missing (the server no longer knows what tables each database contains, despite the IBD files still being there)

## Features

- batch extract SDI information from your IBD data files
- batch re-create your tables' SQL structure
- batch re-create your databases and your tables, using your IBD data files

**Note:** This utility is not able to recover MySQL triggers, MySQL events and MySQL user-created routines (functions and procedures).

## Requirements

- PHP 8
- MySQL 8

## Installation

This composer command will create a new directory **ibd2mysql** and install the files there:

    composer create-project j0celyn/ibd2mysql ibd2mysql

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

## Usage
Here are the available options:
- **--sdi**: extracts SDI information from IBD files.  
**Note: Existing SDI files will be overwritten.**
- **--sql**: creates MySQL files from SDI files (table columns, indexes, partitions and foreign keys)  
**Note: Existing SQL files will be overwritten.**  
  
- **--repair**: for each table:
    - runs the MySQL files to create the table
    - moves IBD file(s) to replace the empty tablespace with your saved tablespace
    - if needed, optimizes the table to fix some issues
 
      **Note: Existing MySQL tables will not be altered.**

When the script is called with more than one option, they will always run in the same order: **sdi sql repair**

To run the script, use one or more options.

### Examples:

    php convert.php --sdi
    php convert.php --sdi --sql
    php convert.php --sdi --sql --repair

## Why this utility?

While upgrading my local MySQL server, I chose the wrong option and it initialized a new MySQL dictionary, thus wiping the current MySQL dictionary.  
As I used [innodb_file_per_table](https://dev.mysql.com/doc/refman/8.0/en/innodb-parameters.html#sysvar_innodb_file_per_table) I knew my tables were still there, but the MySQL server had no knowledge of them anymore.  
For most of my local databases I had a backup I could restore them from.  
A few databases didn't contain valuable information and it didn't matter if they were lost.  
And there were a few other databases without backup, I wanted to recover, if still possible.  

I tried to find an existing command or utility to recover my databases, but couldn't find anything.  
The configuration flag [innodb_force_recovery](https://dev.mysql.com/doc/refman/8.0/en/forcing-innodb-recovery.html) was useless to fix my problems.
The utility **ibd2sdi** provided with the MySQL server is useful to get the SDI data from a table (table information in JSON format)...but there's no utility to convert this SDI data into proper MySQL statements that can be run to recreate a table.  

Then I decided to create this utility.

**ibd2mysql** was tested with IBD files created by MySQL 8.0.23 (the version I had before I performed the update and destroyed the MySQL dictionary).
I could successfully recover all my files :
- 30 MySQL databases
- more than 11600 tables in total

As long as there are no breaking changes in the SDI file format, this utility may work with MySQL Server 8.0.23 and newer versions.