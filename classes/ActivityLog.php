<?php

require_once __DIR__ . '/../config/db.php';

class ActivityLog
{
    private $myDB;
    private $conn;

    public function __construct()
    {
        $this->myDB = new myDB(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $this->conn = $this->myDB->getConn();
    }

    public function getRecentLogs(int $limit = 10): array
    {
        try {
            $stmt = $this->conn->prepare('
                SELECT al.id, al.user_id, al.item_id, al.action_type,
                       al.old_quantity, al.new_quantity, al.description, al.created_at,
                       u.full_name AS user_name,
                       i.name AS item_name
                FROM activity_log al
                LEFT JOIN users u ON al.user_id = u.id
                LEFT JOIN items i ON al.item_id = i.id
                ORDER BY al.created_at DESC
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

    public function log(?int $userId, ?int $itemId, string $actionType, ?int $oldQuantity = null, ?int $newQuantity = null, ?string $description = null): bool
    {
        try {
            $stmt = $this->conn->prepare('
                INSERT INTO activity_log (user_id, item_id, action_type, old_quantity, new_quantity, description)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $stmt->bind_param('iisiss', $userId, $itemId, $actionType, $oldQuantity, $newQuantity, $description);
            $stmt->execute();
            return $stmt->affected_rows > 0;
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function getAll(int $page = 1, int $limit = 20, ?int $userId = null, ?string $action = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        try {
            $sql = '
                SELECT al.id, al.user_id, al.item_id, al.action_type,
                       al.old_quantity, al.new_quantity, al.description, al.created_at,
                       u.full_name AS user_name,
                       i.name AS item_name
                FROM activity_log al
                LEFT JOIN users u ON al.user_id = u.id
                LEFT JOIN items i ON al.item_id = i.id
                WHERE 1=1
            ';
            $types = "";
            $params = [];

            if (!empty($userId) && $userId > 0) {
                $sql .= ' AND al.user_id = ?';
                $types .= 'i';
                $params[] = $userId;
            }
            if (!empty($action)) {
                $sql .= ' AND al.action_type = ?';
                $types .= 's';
                $params[] = $action;
            }
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

            $sql .= ' ORDER BY al.created_at DESC';

            $offset = ($page - 1) * $limit;
            $sql .= ' LIMIT ? OFFSET ?';
            $types .= 'ii';
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function countAll(?int $userId = null, ?string $action = null, ?string $dateFrom = null, ?string $dateTo = null): int
    {
        try {
            $sql = '
                SELECT COUNT(*)
                FROM activity_log al
                WHERE 1=1
            ';
            $types = "";
            $params = [];

            if (!empty($userId) && $userId > 0) {
                $sql .= ' AND al.user_id = ?';
                $types .= 'i';
                $params[] = $userId;
            }
            if (!empty($action)) {
                $sql .= ' AND al.action_type = ?';
                $types .= 's';
                $params[] = $action;
            }
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
}
