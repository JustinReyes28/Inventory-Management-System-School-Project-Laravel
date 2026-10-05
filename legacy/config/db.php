<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'inventory_system');
define('DB_USER', 'root');
define('DB_PASS', 'root');

$servername = DB_HOST;
$db_username = DB_USER;
$db_password = DB_PASS;
$dbname     = DB_NAME;

class myDB
{
    private $servername;
    private $username;
    private $password;
    private $dbname;
    private $conn;

    public function __construct($servername, $username, $password, $dbname)
    {
        $this->servername = $servername;
        $this->username   = $username;
        $this->password   = $password;
        $this->dbname     = $dbname;

        try {
            $this->conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);
            if ($this->conn->connect_error) {
                die("Connection failed: " . $this->conn->connect_error);
            }
        } catch (Exception $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function getConn()
    {
        return $this->conn;
    }

    public function insert($table, $data)
    {
        try {
            $columns = implode(", ", array_keys($data));
            $placeholders = implode(", ", array_fill(0, count($data), "?"));
            $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                die("Prepare failed: " . $this->conn->error);
            }

            $types = "";
            $values = [];
            foreach ($data as $key => $value) {
                $types .= strtolower(substr(gettype($value), 0, 1));
                $values[] = $value;
            }

            $stmt->bind_param($types, ...$values);
            $stmt->execute();
            $insert_id = $this->conn->insert_id;
            $stmt->close();
            return $insert_id;
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function select($table, $row, $where = null)
    {
        try {
            if ($where !== null) {
                $whereClause = implode(" AND ", array_map(function ($key) {
                    return "$key = ?";
                }, array_keys($where)));
                $sql = "SELECT $row FROM $table WHERE $whereClause";
            } else {
                $sql = "SELECT $row FROM $table";
            }

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                die("Prepare failed: " . $this->conn->error);
            }

            if ($where !== null) {
                $types = "";
                $values = [];
                foreach ($where as $key => $value) {
                    $types .= strtolower(substr(gettype($value), 0, 1));
                    $values[] = $value;
                }
                $stmt->bind_param($types, ...$values);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];
            while ($rows = $result->fetch_assoc()) {
                $data[] = $rows;
            }
            $stmt->close();
            return $data;
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }
}
