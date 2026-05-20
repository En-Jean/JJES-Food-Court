<?php
// test_order.php - Simple test endpoint
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    echo json_encode([
        'success' => true,
        'message' => 'Test endpoint works!',
        'php_version' => phpversion(),
        'received' => json_decode(file_get_contents('php://input'), true),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>