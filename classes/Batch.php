<?php

require_once __DIR__ . '/../config/db.php';

class Batch
{
    private $myDB;
    private $conn;

    public function __construct()
    {
        $this->myDB = new myDB(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $this->conn = $this->myDB->getConn();
    }

    public function getAll(?int $itemId = null): array
    {
        try {
            $sql = '
                SELECT b.id, b.item_id, i.name AS item_name, b.batch_number, b.quantity,
                       b.expiry_date, b.created_at,
                       CASE
                           WHEN DATEDIFF(b.expiry_date, CURDATE()) < 0  THEN \'Expired\'
                           WHEN DATEDIFF(b.expiry_date, CURDATE()) <= 30 THEN \'Near Expiry\'
                           ELSE \'Safe\'
                       END AS status
                FROM batches b
                JOIN items i ON b.item_id = i.id
                WHERE 1=1
            ';
            $types = "";
            $params = [];

            if (!empty($itemId) && $itemId > 0) {
                $sql .= ' AND b.item_id = ?';
                $types .= 'i';
                $params[] = $itemId;
            }

            $sql .= ' ORDER BY b.id DESC';

            $stmt = $this->conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function getNearExpiry(int $days = 30): array
    {
        try {
            $stmt = $this->conn->prepare('
                SELECT b.id, b.item_id, i.name AS item_name, b.batch_number, b.quantity,
                       b.expiry_date, b.created_at,
                       DATEDIFF(b.expiry_date, CURDATE()) AS days_until_expiry
                FROM batches b
                JOIN items i ON b.item_id = i.id
                WHERE b.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                ORDER BY b.expiry_date ASC
            ');
            $stmt->bind_param('i', $days);
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
            $stmt = $this->conn->prepare('
                SELECT b.id, b.item_id, i.name AS item_name, b.batch_number, b.quantity,
                       b.expiry_date, b.created_at,
                       CASE
                           WHEN DATEDIFF(b.expiry_date, CURDATE()) < 0  THEN \'Expired\'
                           WHEN DATEDIFF(b.expiry_date, CURDATE()) <= 30 THEN \'Near Expiry\'
                           ELSE \'Safe\'
                       END AS status
                FROM batches b
                JOIN items i ON b.item_id = i.id
                WHERE b.id = ?
            ');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return $row ?: null;
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function create(array $data): int|bool
    {
        try {
            $stmt = $this->conn->prepare('
                INSERT INTO batches (item_id, batch_number, quantity, expiry_date)
                VALUES (?, ?, ?, ?)
            ');
            $itemId = (int) $data['item_id'];
            $batchNumber = trim($data['batch_number']);
            $quantity = (int) ($data['quantity'] ?? 0);
            $expiryDate = $data['expiry_date'];
            $stmt->bind_param('isis', $itemId, $batchNumber, $quantity, $expiryDate);
            $stmt->execute();
            return $stmt->affected_rows > 0 ? $this->conn->insert_id : false;
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function update(int $id, array $data): bool
    {
        try {
            $stmt = $this->conn->prepare('
                UPDATE batches
                SET item_id = ?,
                    batch_number = ?,
                    quantity = ?,
                    expiry_date = ?
                WHERE id = ?
            ');
            $itemId = (int) $data['item_id'];
            $batchNumber = trim($data['batch_number']);
            $quantity = (int) $data['quantity'];
            $expiryDate = $data['expiry_date'];
            $stmt->bind_param('isisi', $itemId, $batchNumber, $quantity, $expiryDate, $id);
            $stmt->execute();
            return $stmt->affected_rows >= 0;
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function delete(int $id): bool
    {
        try {
            $stmt = $this->conn->prepare('DELETE FROM batches WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            return $stmt->affected_rows > 0;
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }
}
