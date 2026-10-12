<?php
/**
 * CarePoint HMS - Database Configuration
 * Update these values to match your hosting / local server (XAMPP, etc.)
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'carepoint-hms');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed. Please check config/db.php — ' . $e->getMessage());
}
