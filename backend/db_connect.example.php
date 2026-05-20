<?php
// ============================================================
// db_connect.php
// PURPOSE: Connect to the MySQL database using mysqli
// This file is included by all other PHP files
// ============================================================

// --- Database Configuration ---
$host     = "localhost";   // XAMPP default host
$username = "root";        // XAMPP default username
$password = "";            // XAMPP default password (empty)
$database = "jjes_foodcourt";

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