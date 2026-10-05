<?php

require_once __DIR__ . '/../config/db.php';

class Item
{
    private $myDB;
    private $conn;

    public function __construct()
    {
        $this->myDB = new myDB(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $this->conn = $this->myDB->getConn();
    }

    public function getTotalProducts(): int
    {
        try {
            $stmt = $this->conn->prepare('SELECT COUNT(*) FROM items WHERE is_deleted = 0');
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_row();
            return (int) $row[0];
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function getTotalInventoryValue(): float
    {
        try {
            $stmt = $this->conn->prepare('SELECT COALESCE(SUM(price * quantity), 0) FROM items WHERE is_deleted = 0');
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_row();
            return (float) $row[0];
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function getLowStockCount(): int
    {
        try {
            $stmt = $this->conn->prepare('SELECT COUNT(*) FROM items WHERE is_deleted = 0 AND quantity <= low_stock_threshold');
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_row();
            return (int) $row[0];
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function getNearExpiryCount(int $days = 30): int
    {
        try {
            $stmt = $this->conn->prepare('SELECT COUNT(*) FROM batches WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)');
            $stmt->bind_param('i', $days);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_row();
            return (int) $row[0];
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function getStockByCategory(): array
    {
        try {
            $stmt = $this->conn->prepare('
                SELECT c.category_name, COALESCE(SUM(i.quantity), 0) as total_stock
                FROM items i
                JOIN categories c ON i.category_id = c.id
                WHERE i.is_deleted = 0
                GROUP BY c.id, c.category_name
                ORDER BY total_stock DESC
            ');
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function getExpiringBatches(int $limit = 5): array
    {
        try {
            $stmt = $this->conn->prepare('
                SELECT i.sku, i.name, b.batch_number, b.quantity, b.expiry_date,
                       DATEDIFF(b.expiry_date, CURDATE()) as days_until_expiry
                FROM batches b
                JOIN items i ON b.item_id = i.id
                WHERE b.expiry_date >= CURDATE() AND i.is_deleted = 0
                ORDER BY b.expiry_date ASC
                LIMIT ?
            ');
            $stmt->bind_param('i', $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function getAll(?string $search = null, ?int $categoryId = null, bool $lowStockOnly = false): array
    {
        try {
            $sql = '
                SELECT i.id, i.sku, i.name, i.category_id, c.category_name, i.price, i.quantity, i.low_stock_threshold, i.created_at, i.updated_at
                FROM items i
                JOIN categories c ON i.category_id = c.id
                WHERE i.is_deleted = 0
            ';
            $types = "";
            $params = [];

            if (!empty($search)) {
                $sql .= ' AND (i.sku LIKE ? OR i.name LIKE ?)';
                $types .= 'ss';
                $searchTerm = '%' . trim($search) . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            if (!empty($categoryId) && $categoryId > 0) {
                $sql .= ' AND i.category_id = ?';
                $types .= 'i';
                $params[] = $categoryId;
            }

            if ($lowStockOnly) {
                $sql .= ' AND i.quantity <= i.low_stock_threshold';
            }

            $sql .= ' ORDER BY i.id DESC';

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

    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->conn->prepare('
                SELECT i.id, i.sku, i.name, i.category_id, c.category_name, i.price, i.quantity, i.low_stock_threshold, i.is_deleted, i.created_at, i.updated_at
                FROM items i
                JOIN categories c ON i.category_id = c.id
                WHERE i.id = ? AND i.is_deleted = 0
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

    public function existsSku(string $sku, ?int $excludeId = null): bool
    {
        try {
            if ($excludeId !== null) {
                $stmt = $this->conn->prepare('SELECT COUNT(*) FROM items WHERE LOWER(sku) = LOWER(?) AND id != ? AND is_deleted = 0');
                $stmt->bind_param('si', $sku, $excludeId);
            } else {
                $stmt = $this->conn->prepare('SELECT COUNT(*) FROM items WHERE LOWER(sku) = LOWER(?) AND is_deleted = 0');
                $stmt->bind_param('s', $sku);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_row();
            return (int) $row[0] > 0;
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function create(array $data): int|bool
    {
        try {
            $stmt = $this->conn->prepare('
                INSERT INTO items (sku, name, category_id, price, quantity, low_stock_threshold)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $sku = trim($data['sku']);
            $name = trim($data['name']);
            $categoryId = (int) $data['category_id'];
            $price = (float) $data['price'];
            $quantity = (int) ($data['quantity'] ?? 0);
            $threshold = (int) ($data['low_stock_threshold'] ?? 10);
            $stmt->bind_param('ssidii', $sku, $name, $categoryId, $price, $quantity, $threshold);
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
                UPDATE items
                SET sku = ?,
                    name = ?,
                    category_id = ?,
                    price = ?,
                    quantity = ?,
                    low_stock_threshold = ?
                WHERE id = ? AND is_deleted = 0
            ');
            $sku = trim($data['sku']);
            $name = trim($data['name']);
            $categoryId = (int) $data['category_id'];
            $price = (float) $data['price'];
            $quantity = (int) $data['quantity'];
            $threshold = (int) $data['low_stock_threshold'];
            $stmt->bind_param('ssidiii', $sku, $name, $categoryId, $price, $quantity, $threshold, $id);
            $stmt->execute();
            return $stmt->affected_rows >= 0;
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function delete(int $id): bool
    {
        try {
            $stmt = $this->conn->prepare('UPDATE items SET is_deleted = 1 WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            return $stmt->affected_rows > 0;
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }
}
