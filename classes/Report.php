<?php

require_once __DIR__ . '/../config/db.php';

class Report
{
    private $myDB;
    private $conn;

    public function __construct()
    {
        $this->myDB = new myDB(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $this->conn = $this->myDB->getConn();
    }

    public function getLowStockReport(?int $categoryId = null): array
    {
        try {
            $sql = '
                SELECT i.id, i.sku, i.name, i.category_id, c.category_name,
                       i.price, i.quantity, i.low_stock_threshold,
                       (i.price * i.quantity) AS stock_value
                FROM items i
                JOIN categories c ON i.category_id = c.id
                WHERE i.is_deleted = 0
                  AND i.quantity <= i.low_stock_threshold
            ';
            $types = "";
            $params = [];

            if (!empty($categoryId) && $categoryId > 0) {
                $sql .= ' AND i.category_id = ?';
                $types .= 'i';
                $params[] = $categoryId;
            }

            $sql .= ' ORDER BY (i.quantity * 1.0 / i.low_stock_threshold) ASC, i.name ASC';

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

    public function getLowStockTotalValue(?int $categoryId = null): float
    {
        try {
            $sql = '
                SELECT COALESCE(SUM(i.price * i.quantity), 0)
                FROM items i
                WHERE i.is_deleted = 0
                  AND i.quantity <= i.low_stock_threshold
            ';
            $types = "";
            $params = [];

            if (!empty($categoryId) && $categoryId > 0) {
                $sql .= ' AND i.category_id = ?';
                $types .= 'i';
                $params[] = $categoryId;
            }

            $stmt = $this->conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_row();
            return (float) $row[0];
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function getExpiryReport(int $days = 30): array
    {
        try {
            $stmt = $this->conn->prepare('
                SELECT b.id, b.batch_number, b.quantity, b.expiry_date, b.created_at,
                       i.id AS item_id, i.sku, i.name AS item_name,
                       c.id AS category_id, c.category_name,
                       DATEDIFF(b.expiry_date, CURDATE()) AS days_until_expiry
                FROM batches b
                JOIN items i ON b.item_id = i.id
                JOIN categories c ON i.category_id = c.id
                WHERE i.is_deleted = 0
                  AND (b.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                       OR b.expiry_date < CURDATE())
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

    public function getExpiryCounts(): array
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    SUM(CASE WHEN b.expiry_date < CURDATE() THEN 1 ELSE 0 END) AS expired,
                    SUM(CASE WHEN DATEDIFF(b.expiry_date, CURDATE()) BETWEEN 1 AND 30 THEN 1 ELSE 0 END) AS within_30,
                    SUM(CASE WHEN DATEDIFF(b.expiry_date, CURDATE()) BETWEEN 31 AND 60 THEN 1 ELSE 0 END) AS within_60,
                    SUM(CASE WHEN DATEDIFF(b.expiry_date, CURDATE()) BETWEEN 61 AND 90 THEN 1 ELSE 0 END) AS within_90
                FROM batches b
                JOIN items i ON b.item_id = i.id
                WHERE i.is_deleted = 0
            ");
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function getActivitySummaryReport(?string $dateFrom = null, ?string $dateTo = null): array
    {
        try {
            $sql = '
                SELECT u.id AS user_id, u.full_name, r.role_name,
                       al.action_type, COUNT(*) AS action_count
                FROM activity_log al
                JOIN users u ON al.user_id = u.id
                JOIN roles r ON u.role_id = r.id
                WHERE 1=1
            ';
            $types = "";
            $params = [];

            if (!empty($dateFrom)) {
                $sql .= ' AND al.created_at >= ?';
                $types .= 's';
                $params[] = $dateFrom . ' 00:00:00';
            }
            if (!empty($dateTo)) {
                $sql .= ' AND al.created_at <= ?';
                $types .= 's';
                $params[] = $dateTo . ' 23:59:59';
            }

            $sql .= ' GROUP BY u.id, u.full_name, r.role_name, al.action_type
                      ORDER BY u.full_name ASC, al.action_type ASC';

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

    public function getTotalActionCount(?string $dateFrom = null, ?string $dateTo = null): int
    {
        try {
            $sql = 'SELECT COUNT(*) FROM activity_log WHERE 1=1';
            $types = "";
            $params = [];

            if (!empty($dateFrom)) {
                $sql .= ' AND created_at >= ?';
                $types .= 's';
                $params[] = $dateFrom . ' 00:00:00';
            }
            if (!empty($dateTo)) {
                $sql .= ' AND created_at <= ?';
                $types .= 's';
                $params[] = $dateTo . ' 23:59:59';
            }

            $stmt = $this->conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_row();
            return (int) $row[0];
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function getReportSummaryMetrics(): array
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    (SELECT COUNT(*) FROM items WHERE is_deleted = 0 AND quantity <= low_stock_threshold) AS low_stock_count,
                    (SELECT COALESCE(SUM(price * quantity), 0) FROM items WHERE is_deleted = 0 AND quantity <= low_stock_threshold) AS low_stock_value,
                    (SELECT COUNT(*) FROM batches b JOIN items i ON b.item_id = i.id WHERE i.is_deleted = 0 AND b.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)) AS expiring_30,
                    (SELECT COUNT(*) FROM activity_log) AS total_actions
            ");
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }
}
