<?php
/**
 * db.php - Database Connection (PDO)
 *
 * Sentral database-tilkobling for hele applikasjonen
 */

define('DB_HOST', '158.39.188.217');
define('DB_NAME', 'steg1');
define('DB_USER', 'phplogin');
define('DB_PASS', 'PhpConnect1!');

function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            die("Databasefeil: " . $e->getMessage());
        }
    }

    return $pdo;
}
?>
