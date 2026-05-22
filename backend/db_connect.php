<?php
// ============================================================
// db_connect.php - PDO Database Connection for Railway
// ============================================================

// Railway provides MYSQL_URL or individual env vars
$url = getenv('MYSQL_URL') ?: getenv('MYSQL_PUBLIC_URL') ?: null;

if ($url) {
    $parts = parse_url($url);
    $host  = $parts['host'];
    $port  = $parts['port'] ?? 3306;
    $user  = $parts['user'];
    $pass  = rawurldecode($parts['pass']);
    $db    = ltrim($parts['path'], '/');
} else {
    // Fallback to individual Railway env vars
    $host = getenv('MYSQLHOST')     ?: 'localhost';
    $port = getenv('MYSQLPORT')     ?: '3306';
    $db   = getenv('MYSQLDATABASE') ?: 'railway';
    $user = getenv('MYSQLUSER')     ?: 'root';
    $pass = getenv('MYSQLPASSWORD') ?: '';
}

// --- Create PDO Connection ---
try {
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed: " . $e->getMessage()
    ]);
    exit();
}
?>
