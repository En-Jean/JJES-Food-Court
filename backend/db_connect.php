<?php
ini_set('session.cookie_secure',   '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');

// Railway provides MYSQL_URL in the format:
// mysql://user:password@host:port/database
$url = getenv('MYSQL_URL') ?: getenv('MYSQL_PUBLIC_URL') ?: null;

if ($url) {
    $parts = parse_url($url);
    $host  = $parts['host'];
    $port  = $parts['port'] ?? 3306;
    $user  = $parts['user'];
    $pass  = $parts['pass'];
    $db    = ltrim($parts['path'], '/');
} else {
    // Local fallback
    $host = getenv('MYSQLHOST')     ?: 'localhost';
    $port = getenv('MYSQLPORT')     ?: '3306';
    $db   = getenv('MYSQLDATABASE') ?: 'railway';
    $user = getenv('MYSQLUSER')     ?: 'root';
    $pass = getenv('MYSQLPASSWORD') ?: '';
}

// --- Create Connection ---
$conn = new mysqli($host, $username, $password, $database);

// --- Check if Connection Failed ---
if ($conn->connect_error) {
    // Return error as JSON so JavaScript can read it
    header('Content-Type: application/json');
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed: " . $conn->connect_error
    ]);
    exit(); // Stop the script if we can't connect
}

// --- Set Character Encoding to UTF-8 ---
$conn->set_charset("utf8");

// Connection is now available as $conn in any file that includes this
?>