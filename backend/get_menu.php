<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'db_connect.php';

try {
    $sql = "SELECT * FROM menu_items WHERE is_available = 1 ORDER BY category, name";
    $result = $conn->query($sql);
    
    if ($result) {
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $items,
            'count' => count($items)
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Query failed'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>