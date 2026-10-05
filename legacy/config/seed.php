<?php

require_once __DIR__ . '/db.php';

try {
    $myDB = new myDB(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn = $myDB->getConn();

    // Check if roles exist, insert default roles if missing
    $stmt = $conn->query('SELECT COUNT(*) FROM roles');
    $row = $stmt->fetch_row();
    if ($row[0] == 0) {
        $conn->query("INSERT INTO roles (id, role_name) VALUES (1, 'Admin'), (2, 'Employee')");
        echo "<p>Roles seeded successfully.</p>";
    }

    // Check if admin user exists
    $stmt = $conn->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
    $adminUsername = 'admin';
    $stmt->bind_param('s', $adminUsername);
    $stmt->execute();
    $result = $stmt->get_result();
    $countRow = $result->fetch_row();

    if ($countRow[0] == 0) {
        $hash = password_hash('Admin@1234', PASSWORD_BCRYPT);
        $insert = $conn->prepare('
            INSERT INTO users (full_name, username, password_hash, role_id)
            VALUES (?, ?, ?, ?)
        ');
        $fullName = 'System Administrator';
        $roleId = 1;
        $insert->bind_param('sssi', $fullName, $adminUsername, $hash, $roleId);
        $insert->execute();
        echo "<div style='font-family: sans-serif; padding: 20px; background: #e6fffa; border: 1px solid #20c9a6; border-radius: 8px;'>";
        echo "<h2 style='color: #0d9488;'>Database Seeded Successfully!</h2>";
        echo "<p>Default admin account created:</p>";
        echo "<ul><li><strong>Username:</strong> admin</li><li><strong>Password:</strong> Admin@1234</li></ul>";
        echo "<p><em>Note: Please delete or secure this file (<code>config/seed.php</code>) after running.</em></p>";
        echo "<a href='../index.php?page=login' style='display: inline-block; padding: 10px 20px; background: #20c9a6; color: white; text-decoration: none; border-radius: 6px;'>Go to Login Page</a>";
        echo "</div>";
    } else {
        echo "<div style='font-family: sans-serif; padding: 20px; background: #fefcbf; border: 1px solid #d69e2e; border-radius: 8px;'>";
        echo "<h3 style='color: #b7791f;'>Admin User Already Exists!</h3>";
        echo "<p>Username <strong>admin</strong> is already present in the database.</p>";
        echo "<a href='../index.php?page=login' style='display: inline-block; padding: 10px 20px; background: #20c9a6; color: white; text-decoration: none; border-radius: 6px;'>Go to Login Page</a>";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<div style='font-family: sans-serif; padding: 20px; background: #fff5f5; border: 1px solid #e53e3e; border-radius: 8px;'>";
    echo "<h3 style='color: #c53030;'>Seeding Failed!</h3>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
