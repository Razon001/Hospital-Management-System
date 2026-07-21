<?php
/**
 * Database connection (PDO)
 * ---------------------------------------------------------
 * Update the four values below to match your local setup.
 * Defaults match a typical fresh XAMPP / WAMP / Laragon install
 * (MySQL root user with no password).
 * ---------------------------------------------------------
 */
$DB_HOST = 'localhost';
$DB_NAME = 'hospital_management';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE  => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES    => false,
        ]
    );
} catch (PDOException $e) {
    die('<div style="font-family:sans-serif;max-width:600px;margin:60px auto;padding:24px;border:1px solid #eee;border-radius:8px">'
        . '<h2 style="color:#B3261E;margin-top:0">Database connection failed</h2>'
        . '<p>Please make sure:</p><ul>'
        . '<li>MySQL / MariaDB is running</li>'
        . '<li>The database <code>hospital_management</code> has been imported '
        . '(see <code>database/hospital_management.sql</code>)</li>'
        . '<li>The credentials in <code>config/database.php</code> are correct</li>'
        . '</ul><p style="color:#666">Technical detail: ' . htmlspecialchars($e->getMessage()) . '</p></div>');
}
