<?php
/**
 * COPY THIS FILE to db.php and fill in your real credentials.
 * db.php itself is gitignored so real credentials never get pushed.
 * ---------------------------------------------------------------------
 * SkillBridge.lk — Shared Database Connection
 * ---------------------------------------------------------------------
 * ONE database ("gpss_database") serves all three language folders.
 * Every PHP page in /tamil, /english, /sinhala includes this same file:
 *
 *      require_once __DIR__ . '/../config/db.php';
 *
 * $pdo is then ready to use in that page. Only the UI text differs per
 * language folder — the data and the connection are identical.
 * ---------------------------------------------------------------------
 */

// ---- Connection settings -------------------------------------------------
// Move these into environment variables before deploying to production.
define('DB_HOST', 'localhost');            // TODO: your DB host
define('DB_NAME', 'your_database_name');  // TODO: your DB name
define('DB_USER', 'your_db_username');    // TODO: your DB user
define('DB_PASS', 'your_db_password');    // TODO: your DB password
define('DB_CHARSET', 'utf8mb4');   // required for Tamil / Sinhala script storage

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,   // use real prepared statements
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Never leak DB credentials or raw driver errors to the browser.
    error_log('SkillBridge DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Something went wrong connecting to the database. Please try again shortly.');
}
