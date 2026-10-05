<?php

require_once __DIR__ . '/../config/db.php';

class Notification
{
    private $myDB;
    private $conn;

    public function __construct()
    {
        $this->myDB = new myDB(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $this->conn = $this->myDB->getConn();
    }

    public function getUnreadCount(int $userId): int
    {
        try {
            $stmt = $this->conn->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_row();
            return (int) $row[0];
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function getRecent(int $userId, int $limit = 10): array
    {
        try {
            $stmt = $this->conn->prepare('
                SELECT id, type, title, message, link, is_read, created_at
                FROM notifications
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ?
            ');
            $stmt->bind_param('ii', $userId, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function getAll(int $userId, int $page = 1, int $limit = 20): array
    {
        try {
            $offset = ($page - 1) * $limit;
            $stmt = $this->conn->prepare('
                SELECT id, type, title, message, link, is_read, created_at
                FROM notifications
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ');
            $stmt->bind_param('iii', $userId, $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function countAll(int $userId): int
    {
        try {
            $stmt = $this->conn->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ?');
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_row();
            return (int) $row[0];
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function markAsRead(int $id, int $userId): bool
    {
        try {
            $stmt = $this->conn->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
            $stmt->bind_param('ii', $id, $userId);
            $stmt->execute();
            return $stmt->affected_rows > 0;
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function markAllAsRead(int $userId): bool
    {
        try {
            $stmt = $this->conn->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            return $stmt->affected_rows >= 0;
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function create(int $userId, string $type, string $title, string $message, ?string $link = null): int|bool
    {
        try {
            $stmt = $this->conn->prepare('
                INSERT INTO notifications (user_id, type, title, message, link, is_read)
                VALUES (?, ?, ?, ?, ?, 0)
            ');
            $stmt->bind_param('issss', $userId, $type, $title, $message, $link);
            $stmt->execute();
            return $stmt->affected_rows > 0 ? $this->conn->insert_id : false;
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function createForAllUsers(string $type, string $title, string $message, ?string $link = null): bool
    {
        try {
            $stmt = $this->conn->prepare('SELECT id FROM users WHERE 1=1');
            $stmt->execute();
            $result = $stmt->get_result();
            $userIds = [];
            while ($row = $result->fetch_assoc()) {
                $userIds[] = $row['id'];
            }

            $insertStmt = $this->conn->prepare('
                INSERT INTO notifications (user_id, type, title, message, link, is_read)
                VALUES (?, ?, ?, ?, ?, 0)
            ');
            foreach ($userIds as $uid) {
                $insertStmt->bind_param('issss', $uid, $type, $title, $message, $link);
                $insertStmt->execute();
            }
            return true;
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function hasRecentNotification(int $userId, string $type, ?string $titlePrefix = null, int $withinSeconds = 3600): bool
    {
        try {
            $sql = 'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND type = ? AND created_at >= NOW() - INTERVAL ? SECOND';
            $types = 'isi';
            $params = [$userId, $type, $withinSeconds];

            if ($titlePrefix) {
                $sql .= ' AND title LIKE ?';
                $types .= 's';
                $params[] = $titlePrefix . '%';
            }

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_row();
            return (int) $row[0] > 0;
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function hasRecentGlobalNotification(string $type, int $withinSeconds = 3600): bool
    {
        try {
            $stmt = $this->conn->prepare('
                SELECT COUNT(*) FROM notifications
                WHERE type = ? AND created_at >= NOW() - INTERVAL ? SECOND
                LIMIT 1
            ');
            $stmt->bind_param('si', $type, $withinSeconds);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_row();
            return (int) $row[0] > 0;
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function hasRecentNotificationByMessage(string $type, string $messageContains, int $withinSeconds = 3600): bool
    {
        try {
            $stmt = $this->conn->prepare('
                SELECT COUNT(*) FROM notifications
                WHERE type = ? AND message LIKE ? AND created_at >= NOW() - INTERVAL ? SECOND
                LIMIT 1
            ');
            $search = '%' . $messageContains . '%';
            $stmt->bind_param('ssi', $type, $search, $withinSeconds);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_row();
            return (int) $row[0] > 0;
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }
}
