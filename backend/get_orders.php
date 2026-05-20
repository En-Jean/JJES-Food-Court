<?php
// get_orders.php - Fetch user orders
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'db_connect.php';

// Get user_id from query parameter
$user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

if ($user_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid user ID',
        'orders' => []
    ]);
    exit();
}

try {
    // Fetch orders for this user
    $sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        // Fetch order items for each order
        $items_sql = "SELECT od.*, mi.name 
                      FROM order_details od 
                      LEFT JOIN menu_items mi ON od.menu_item_id = mi.id 
                      WHERE od.order_id = ?";
        $items_stmt = $conn->prepare($items_sql);
        $items_stmt->bind_param("i", $row['id']);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        
        $items = [];
        while ($item = $items_result->fetch_assoc()) {
            $items[] = $item;
        }
        
        $row['items'] = json_encode($items);
        $orders[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'count' => count($orders)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching orders: ' . $e->getMessage(),
        'orders' => []
    ]);
}

$conn->close();
?>