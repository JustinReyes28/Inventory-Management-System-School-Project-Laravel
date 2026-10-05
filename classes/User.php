<?php

require_once __DIR__ . '/../config/db.php';

class User
{
    private $myDB;
    private $conn;

    public function __construct()
    {
        $this->myDB = new myDB(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $this->conn = $this->myDB->getConn();
    }

    public function login(string $username, string $password)
    {
        try {
            $stmt = $this->conn->prepare('
                SELECT u.id, u.full_name, u.username, u.password_hash, u.role_id, r.role_name AS role
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.username = ?
                LIMIT 1
            ');
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user && password_verify($password, $user['password_hash'])) {
                unset($user['password_hash']);
                return $user;
            }

            return false;
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function getUserById(int $id)
    {
        try {
            $stmt = $this->conn->prepare('
                SELECT u.id, u.full_name, u.username, u.role_id, r.role_name AS role, u.created_at
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.id = ?
                LIMIT 1
            ');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc() ?: null;
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function getAll(): array
    {
        try {
            $stmt = $this->conn->prepare('
                SELECT u.id, u.full_name, u.username, r.role_name AS role, u.created_at
                FROM users u
                JOIN roles r ON u.role_id = r.id
                ORDER BY u.full_name ASC
            ');
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function createUser(string $fullName, string $username, string $password, int $roleId): int|false
    {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare('
                INSERT INTO users (full_name, username, password_hash, role_id)
                VALUES (?, ?, ?, ?)
            ');
            $stmt->bind_param('sssi', $fullName, $username, $hash, $roleId);
            $stmt->execute();
            return $stmt->affected_rows > 0 ? $this->conn->insert_id : false;
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function updateUser(int $id, string $fullName, string $username, int $roleId, ?string $password = null): bool
    {
        try {
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $this->conn->prepare('
                    UPDATE users SET full_name = ?, username = ?, password_hash = ?, role_id = ?
                    WHERE id = ?
                ');
                $stmt->bind_param('sssii', $fullName, $username, $hash, $roleId, $id);
            } else {
                $stmt = $this->conn->prepare('
                    UPDATE users SET full_name = ?, username = ?, role_id = ?
                    WHERE id = ?
                ');
                $stmt->bind_param('ssii', $fullName, $username, $roleId, $id);
            }
            $stmt->execute();
            return $stmt->affected_rows >= 0;
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function deleteUser(int $id): bool
    {
        try {
            $stmt = $this->conn->prepare('DELETE FROM users WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            return $stmt->affected_rows > 0;
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function countAdmins(): int
    {
        try {
            $stmt = $this->conn->prepare('SELECT COUNT(*) FROM users WHERE role_id = 1');
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_row();
            return (int) $row[0];
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function existsUsername(string $username, ?int $excludeId = null): bool
    {
        try {
            if ($excludeId !== null) {
                $stmt = $this->conn->prepare('
                    SELECT COUNT(*) FROM users WHERE username = ? AND id != ?
                ');
                $stmt->bind_param('si', $username, $excludeId);
            } else {
                $stmt = $this->conn->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
                $stmt->bind_param('s', $username);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_row();
            return (int) $row[0] > 0;
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function getRoles(): array
    {
        try {
            $stmt = $this->conn->prepare('SELECT id, role_name FROM roles ORDER BY id ASC');
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }
}
