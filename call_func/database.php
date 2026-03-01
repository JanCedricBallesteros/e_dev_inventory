<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__));
include DOMAIN_PATH . '/config/db_api_data.php';

class Database
{
    private $localhost = HOST;
    private $username = DB_USER;
    private $database = DB_NAME;
    private $password = DB_PASS;

    private $mysqli = "";
    private $result = array();
    private $api_connect = false;

    //connect database using consturcted method
    public function __construct()
    {
        if (!$this->api_connect) {
            $this->mysqli = new mysqli($this->localhost, $this->username, $this->password, $this->database);
            $this->api_connect = true;

            if ($this->mysqli->connect_error) {
                array_push($this->result, $this->mysqli_connection_error);
                return false;
            }
        } else {
            return true;
        }
    }

    // insert data
    public function insert($table, $params = array())
    {
        if ($this->tableExist($table)) {
            $table_column = implode(', ', array_keys($params));
            $table_value = implode("', '", array_values($params));

            $sql = "INSERT INTO $table ($table_column) VALUES ('$table_value')";
            if ($this->mysqli->query($sql)) {
                array_push($this->result, true);
                return true;
            } else {
                array_push($this->result, false);
                return false;
            }
        } else {
            return false;
        }
    }

    // get data
    public function select($table, $row = "*", $join = null, $where = null, $order = null, $limit = null)
    {
        if ($this->tableExist($table)) {
            $sql = "SELECT $row FROM $table";
            if ($join != null) {
                $sql .= " JOIN $join";
            }
            if ($where != null) {
                $sql .= " WHERE $where";
            }
            if ($order != null) {
                $sql .= " ORDER BY $order";
            }
            if ($limit != null) {
                $sql .= " LIMIT $limit";
            }
            $query = $this->mysqli->query($sql);
            if ($query) {
                $this->result = $query->fetch_all(MYSQLI_ASSOC);
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    // update data
    public function update($table, $params = array(), $where = null)
    {
        if ($this->tableExist($table)) {
            $arg = array();
            foreach ($params as $key => $val) {
                $arg[] = "$key = '{$val}'";
            }
            $sql = "UPDATE $table SET " . implode(', ', $arg);
            if ($where != null) {
                $sql .= " WHERE $where";
            }
            if ($this->mysqli->query($sql)) {
                array_push($this->result, true);
                return true;
            } else {
                array_push($this->result, false);
                return false;
            }
        } else {
            return false;
        }
    }
    // delete data
    public function delete($table, $where = null)
    {
        if ($this->tableExist($table)) {
            $sql = "DELETE FROM $table";
            if ($where != null) {
                $sql .= " WHERE $where";
            }
            if ($this->mysqli->query($sql)) {
                array_push($this->result, true);
                return true;
            } else {
                array_push($this->result, false);
                return false;
            }
        } else {
            return false;
        }
    }
    // table exist
    private function tableExist($table)
    {
        $sql = "SHOW TABLES FROM $this->database LIKE '{$table}'";
        $tableInDb = $this->mysqli->query($sql);
        if ($tableInDb) {
            if ($tableInDb->num_rows  == 1) {
                return true;
            } else {
                array_push($this->result, $table . " Does not Exist");
            }
        } else {
            return false;
        }
    }

    // get result
    public function getResult()
    {
        $val = $this->result;
        $this->result = array();
        return $val;
    }

    // close the connection
    public function __destruct()
    {
        if ($this->api_connect) {
            if ($this->mysqli->close()) {
                $this->api_connect = false;
                return true;
            }
        } else {
            return false;
        }
    }

    public function escape($con = "", $str = "")
    {
        global $api_connect;
        $string = mysqli_real_escape_string($api_connect, $str);
        return $string;
    }
}
