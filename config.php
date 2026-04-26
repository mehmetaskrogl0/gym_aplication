<?php
declare(strict_types=1);

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'fitbalance';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: 'Mehmet042';

$dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $exception) {
    // Keep startup resilient so UI can still load while DB is being configured.
    $pdo = null;
    $dbConnectionError = $exception->getMessage();
}
