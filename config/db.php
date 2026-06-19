<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$database_url = getenv('DATABASE_URL');

if ($database_url) {

    $db_config = parse_url($database_url);
    
    $host     = $db_config['host'];
    $port     = $db_config['port'] ?? '5432';
    $dbname   = ltrim($db_config['path'], '/');
    $user     = $db_config['user'];
    $password = $db_config['pass'];
} else {

    $host     = "localhost";
    $port     = "5432";
    $dbname   = "fatikem";
    $user     = "postgres";
    $password = "lossless";
}

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}
