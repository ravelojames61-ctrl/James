<?php
/**
 * Visitor Log Management System (VLMS)
 * Database Connection using PDO
 */

// Load custom/production database configuration if present (e.g. on InfinityFree)
if (file_exists(__DIR__ . '/db.custom.php')) {
    require_once __DIR__ . '/db.custom.php';
}

// Database Connection Settings (with fallback to local XAMPP environment)
if (!defined('DB_HOST'))    define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
if (!defined('DB_NAME'))    define('DB_NAME', getenv('DB_NAME') ?: 'vlms_db');
if (!defined('DB_USER'))    define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS'))    define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
if (!defined('DB_PORT'))    define('DB_PORT', getenv('DB_PORT') ?: '3306');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 5,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Friendly error message for users
            die("<div style='font-family:sans-serif;max-width:600px;margin:50px auto;padding:24px;background:#1e1e24;color:#ff6b6b;border-radius:12px;border:1px solid #ff4444;'>
                    <h2 style='margin-top:0;'>Database Connection Error</h2>
                    <p style='color:#ccc;'>Could not establish connection to MariaDB/MySQL database. Please ensure the MySQL service is started in XAMPP.</p>
                    <p style='font-size:12px;color:#888;'>Details: " . htmlspecialchars($e->getMessage()) . "</p>
                 </div>");
        }
    }
    return $pdo;
}

// Global handle
$pdo = getDB();
