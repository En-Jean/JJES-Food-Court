<?php
// ============================================================
// place_order.php - JJES Food Court (PDO version)
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit(); }

ini_set('log_errors', 1);

require_once __DIR__ . '/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Invalid request method. Use POST."]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
if (json_last_error() !== JSON_ERROR_NONE || !$data) {
    echo json_encode(["success" => false, "message" => "Invalid JSON data."]);
    exit();
}

// Extract & sanitize
$user_id           = isset($data['user_id']) && is_numeric($data['user_id']) ? (int)$data['user_id'] : null;
$customer_name     = trim(strip_tags($data['customer_name'] ?? ''));
$contact           = trim(strip_tags($data['contact'] ?? ''));
$order_type        = trim(strtolower($data['order_type'] ?? ''));
$table_number      = trim(strip_tags($data['table_number'] ?? '')) ?: null;
$address           = trim(strip_tags($data['address'] ?? '')) ?: null;
$payment_method    = trim(strtolower($data['payment_method'] ?? 'cash'));
$payment_reference = trim(strip_tags($data['payment_reference'] ?? '')) ?: null;
$total_price       = isset($data['total_price']) && is_numeric($data['total_price']) ? round((float)$data['total_price'], 2) : 0;
$cart_items        = is_array($data['cart_items'] ?? null) ? $data['cart_items'] : [];

// Validate
$errors = [];
if (strlen($customer_name) < 2)  $errors[] = "Customer name required (min 2 characters).";
if (!preg_match('/^09[0-9]{9}$|^\+63[0-9]{9}$/', $contact)) $errors[] = "Valid Philippine contact number required.";
if (!in_array($order_type, ['dine-in', 'delivery'])) $errors[] = "Valid order type required: 'dine-in' or 'delivery'.";
if ($order_type === 'dine-in' && empty($table_number)) $errors[] = "Table number required for dine-in.";
if ($order_type === 'delivery' && strlen($address ?? '') < 10) $errors[] = "Complete delivery address required.";
if (empty($cart_items)) $errors[] = "Cart is empty.";
if ($total_price <= 0) $errors[] = "Invalid order total.";
$valid_payments = ['cash', 'cash_on_delivery', 'gcash', 'maya', 'paypal', 'bank_transfer'];
if (!in_array($payment_method, $valid_payments)) $errors[] = "Invalid payment method.";

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Validation failed.", "errors" => $errors]);
    exit();
}

try {
    $pdo->beginTransaction();

    // Insert order
    $order_stmt = $pdo->prepare(
        "INSERT INTO orders (user_id, customer_name, contact, order_type, table_number, address,
         total_price, payment_method, payment_status, payment_reference, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, 'pending')"
    );
    $order_stmt->execute([
        $user_id, $customer_name, $contact, $order_type, $table_number,
        $address, $total_price, $payment_method, $payment_reference
    ]);
    $order_id = $pdo->lastInsertId();

    // Insert order items
    $detail_stmt = $pdo->prepare(
        "INSERT INTO order_details (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)"
    );
    foreach ($cart_items as $item) {
        $detail_stmt->execute([
            $order_id,
            (int)$item['id'],
            (int)$item['quantity'],
            round((float)$item['price'], 2)
        ]);
    }

    // Optional payments table entry for e-wallets
    if (!in_array($payment_method, ['cash', 'cash_on_delivery'])) {
        $payment_details = json_encode([
            'reference'    => $payment_reference,
            'initiated_at' => date('Y-m-d H:i:s'),
        ]);
        $pay_stmt = $pdo->prepare(
            "INSERT INTO payments (order_id, payment_method, amount, payment_status, payment_details, created_at)
             VALUES (?, ?, ?, 'pending', ?, NOW())"
        );
        $pay_stmt->execute([$order_id, $payment_method, $total_price, $payment_details]);
    }

    $pdo->commit();

    echo json_encode([
        "success"        => true,
        "message"        => "Order placed successfully! Your order is being prepared.",
        "order_id"       => $order_id,
        "payment_method" => $payment_method,
        "payment_status" => "pending",
        "total_price"    => $total_price,
        "next_step"      => in_array($payment_method, ['cash', 'cash_on_delivery'])
                            ? "Pay upon pickup/delivery" : "Complete payment verification"
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Order Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Failed to place order. Please try again."]);
}
exit();
?>
