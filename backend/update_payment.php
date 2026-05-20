<?php
// update_payment.php - Admin endpoint to mark payment as completed
header('Content-Type: application/json');
require_once 'db_connect.php';

// 🔐 Add admin authentication check here!

$data = json_decode(file_get_contents('php://input'), true);
$order_id = $data['order_id'] ?? null;
$action = $data['action'] ?? null; // 'paid', 'failed', 'refunded'

if (!$order_id || !in_array($action, ['paid', 'failed', 'refunded'])) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit();
}

$conn->begin_transaction();
try {
    // Update orders table
    $status = ($action === 'paid') ? 'paid' : $action;
    $paid_at = ($action === 'paid') ? 'NOW()' : 'NULL';
    
    $stmt = $conn->prepare(
        "UPDATE orders SET payment_status = ?, paid_at = $paid_at WHERE id = ?"
    );
    $stmt->bind_param("si", $status, $order_id);
    $stmt->execute();
    
    // Update payments table if exists
    $stmt2 = $conn->prepare(
        "UPDATE payments SET payment_status = ?, updated_at = NOW() WHERE order_id = ?"
    );
    $stmt2->bind_param("si", $status, $order_id);
    $stmt2->execute();
    
    // If marked as paid, update order status to 'preparing'
    if ($action === 'paid') {
        $stmt3 = $conn->prepare(
            "UPDATE orders SET status = 'preparing' WHERE id = ? AND status = 'pending'"
        );
        $stmt3->bind_param("i", $order_id);
        $stmt3->execute();
    }
    
    $conn->commit();
    echo json_encode(["success" => true, "message" => "Payment status updated"]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => "Update failed: " . $e->getMessage()]);
}
$conn->close();
?>