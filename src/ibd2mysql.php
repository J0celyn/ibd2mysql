<?php
/**
 * @package ibd2mysql
 * @author Jocelyn Flament
 */

namespace j0celyn\ibd2mysql;

use PDO;
use PDOException;

/**
 * main class - calls the relevant class(es) depending on the options chosen when running the script
 */
class ibd2mysql
{
    protected PDO $db;

    protected SDI $sdi;
    protected SQL $sql;
    protected Repair $repair;
    protected Paths $paths;

    public function __construct(
        protected string $db_host, protected int $db_port, protected string $db_user, protected string $db_password,
        protected string $ibd2sdi_path, protected string $backup_dir, protected string $output_dir,
        protected string $mysql_data_dir,
        protected array  $databases)
    {
        $this->paths = new Paths($this->ibd2sdi_path, $this->backup_dir, $this->output_dir, $this->mysql_data_dir);
    }

    public function getSDI(): SDI
    {
        if (!isset($this->sdi)) {
            $this->sdi = new SDI($this->paths, $this->databases);
        }
        return $this->sdi;
    }

    public function getSQL(): SQL
    {
        $this->initDB();
        if (!isset($this->sql)) {
            $this->sql = new SQL($this->db, $this->paths, $this->databases);
        }
        return $this->sql;
    }

    public function initDB(): void
    {
        if (isset($this->db)) {
            return;
        }
        try {
            $this->db = new PDO(sprintf('mysql:host=%s;port=%d', $this->db_host, $this->db_port), $this->db_user, $this->db_password);
        } catch (PDOException) {
            die('Unable to connect to the MySQL server. Check config.php then try again. Is your MySQL server running?');
        }

        $this->db->query('SELECT @@datadir')->fetch();
        if (!is_dir($this->mysql_data_dir)) {
            die(sprintf('ERROR: backup directory "%s" does not exist, check config.', $this->backup_dir));
        }
    }

    public function getRepair(): Repair
    {
        $this->initDB();
        if (!isset($this->repair)) {
            $this->repair = new Repair($this->db, $this->paths, $this->databases);
        }
        return $this->repair;
    }

    /*public function debuglog(string|array|object $msg): void
    {
        if ($this->debug) {
            writelog($msg, true);
        }
    }*/
}
