<?php
// ============================================================
// KampusStore — Database Configuration
// ============================================================

define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'kampusstore');
define('DB_USER',    'kampusstore');
define('DB_PASS',    'ks_dev_2026');
define('DB_CHARSET', 'utf8mb4');

/**
 * Mengembalikan instance PDO (singleton).
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('DB Connection failed: ' . $e->getMessage());
            http_response_code(503);
            die(json_encode(['error' => 'Database tidak tersedia.']));
        }
    }

    return $pdo;
}
