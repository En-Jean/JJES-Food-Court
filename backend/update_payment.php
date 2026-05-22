<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

$data     = json_decode(file_get_contents('php://input'), true);
$order_id = $data['order_id'] ?? null;
$action   = $data['action']   ?? null;

if (!$order_id || !in_array($action, ['paid', 'failed', 'refunded'])) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit();
}

try {
    $pdo->beginTransaction();

    $status  = ($action === 'paid') ? 'paid' : $action;
    $paid_at = ($action === 'paid') ? date('Y-m-d H:i:s') : null;

    $stmt = $pdo->prepare("UPDATE orders SET payment_status = ?, paid_at = ? WHERE id = ?");
    $stmt->execute([$status, $paid_at, $order_id]);

    $stmt2 = $pdo->prepare("UPDATE payments SET payment_status = ?, updated_at = NOW() WHERE order_id = ?");
    $stmt2->execute([$status, $order_id]);

    if ($action === 'paid') {
        $stmt3 = $pdo->prepare("UPDATE orders SET status = 'preparing' WHERE id = ? AND status = 'pending'");
        $stmt3->execute([$order_id]);
    }

    $pdo->commit();
    echo json_encode(["success" => true, "message" => "Payment status updated"]);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(["success" => false, "message" => "Update failed: " . $e->getMessage()]);
}
?>
