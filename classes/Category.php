<?php

require_once __DIR__ . '/../config/db.php';

class Category
{
    private $myDB;
    private $conn;

    public function __construct()
    {
        $this->myDB = new myDB(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $this->conn = $this->myDB->getConn();
    }

    public function getAll(): array
    {
        try {
            $stmt = $this->conn->prepare('
                SELECT c.id, c.category_name, COUNT(i.id) AS product_count
                FROM categories c
                LEFT JOIN items i ON c.id = i.category_id AND i.is_deleted = 0
                GROUP BY c.id, c.category_name
                ORDER BY c.category_name ASC
            ');
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->conn->prepare('SELECT id, category_name FROM categories WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return $row ?: null;
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function existsName(string $name, ?int $excludeId = null): bool
    {
        try {
            if ($excludeId !== null) {
                $stmt = $this->conn->prepare('SELECT COUNT(*) FROM categories WHERE LOWER(category_name) = LOWER(?) AND id != ?');
                $stmt->bind_param('si', $name, $excludeId);
            } else {
                $stmt = $this->conn->prepare('SELECT COUNT(*) FROM categories WHERE LOWER(category_name) = LOWER(?)');
                $stmt->bind_param('s', $name);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_row();
            return (int) $row[0] > 0;
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function create(string $name): int|bool
    {
        try {
            $stmt = $this->conn->prepare('INSERT INTO categories (category_name) VALUES (?)');
            $stmt->bind_param('s', $name);
            $stmt->execute();
            return $stmt->affected_rows > 0 ? $this->conn->insert_id : false;
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function update(int $id, string $name): bool
    {
        try {
            $stmt = $this->conn->prepare('UPDATE categories SET category_name = ? WHERE id = ?');
            $stmt->bind_param('si', $name, $id);
            $stmt->execute();
            return $stmt->affected_rows >= 0;
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function delete(int $id): array
    {
        try {
            $stmtCheck = $this->conn->prepare('SELECT COUNT(*) FROM items WHERE category_id = ? AND is_deleted = 0');
            $stmtCheck->bind_param('i', $id);
            $stmtCheck->execute();
            $resultCheck = $stmtCheck->get_result();
            $rowCheck = $resultCheck->fetch_row();
            $itemCount = (int) $rowCheck[0];

            if ($itemCount > 0) {
                return [
                    'success' => false,
                    'message' => "Cannot delete category. There are {$itemCount} active product(s) linked to this category."
                ];
            }

            $stmt = $this->conn->prepare('DELETE FROM categories WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                return ['success' => true, 'message' => 'Category deleted successfully.'];
            }

            return ['success' => false, 'message' => 'Failed to delete category from database.'];
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }
}
