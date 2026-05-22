<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'db_connect.php';

try {
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE is_available = 1 ORDER BY category, name");
    $stmt->execute();
    $items = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data'    => $items,
        'count'   => count($items)
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
