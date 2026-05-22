<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'db_connect.php';

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID', 'orders' => []]);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();

    $items_stmt = $pdo->prepare(
        "SELECT od.*, mi.name
         FROM order_details od
         LEFT JOIN menu_items mi ON od.menu_item_id = mi.id
         WHERE od.order_id = ?"
    );

    foreach ($orders as &$row) {
        $items_stmt->execute([$row['id']]);
        $row['items'] = json_encode($items_stmt->fetchAll());
    }

    echo json_encode(['success' => true, 'orders' => $orders, 'count' => count($orders)]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage(), 'orders' => []]);
}
?>
