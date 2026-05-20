<?php
// ============================================================
// place_order.php - JJES Food Court
// PURPOSE: Save a new order with payment details to database
// RETURNS: JSON response with order ID and payment info
// CALLED BY: JavaScript fetch() on checkout form submit
// ============================================================

// 🔧 Configuration - Adjust paths as needed
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'jjes_foodcourt');

// 🔧 CORS & Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // 🔐 Restrict in production
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 🔧 Error Reporting (Disable in production)
// ini_set('display_errors', 0);
// error_reporting(E_ALL);
// log_errors to file instead:
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// ============================================================
// DATABASE CONNECTION
// ============================================================
function getDatabaseConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        error_log("DB Connection Error: " . $e->getMessage());
        return null;
    }
}

$conn = getDatabaseConnection();
if (!$conn) {
    echo json_encode([
        "success" => false, 
        "message" => "Database connection failed. Please try again later."
    ]);
    exit();
}

// ============================================================
// REQUEST VALIDATION
// ============================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["success" => false, "message" => "Invalid request method. Use POST."]);
    $conn->close();
    exit();
}

// Read and parse JSON input
$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

if (json_last_error() !== JSON_ERROR_NONE || !$data) {
    echo json_encode([
        "success" => false, 
        "message" => "Invalid JSON data received. Error: " . json_last_error_msg()
    ]);
    $conn->close();
    exit();
}

// ============================================================
// EXTRACT & SANITIZE INPUT
// ============================================================
// User & Customer Info
$user_id       = isset($data['user_id']) && is_numeric($data['user_id']) ? (int) $data['user_id'] : null;
$customer_name = isset($data['customer_name']) ? trim(strip_tags($data['customer_name'])) : '';
$contact       = isset($data['contact']) ? trim(strip_tags($data['contact'])) : '';

// Order Details
$order_type    = isset($data['order_type']) ? trim(strtolower($data['order_type'])) : '';
$table_number  = isset($data['table_number']) ? trim(strip_tags($data['table_number'])) : null;
$address       = isset($data['address']) ? trim(strip_tags($data['address'])) : null;

// Payment Info 🔧 NEW
$payment_method    = isset($data['payment_method']) ? trim(strtolower($data['payment_method'])) : 'cash';
$payment_reference = isset($data['payment_reference']) && !empty($data['payment_reference']) 
                     ? trim(strip_tags($data['payment_reference'])) 
                     : null;

// Pricing
$total_price   = isset($data['total_price']) && is_numeric($data['total_price']) 
                 ? round((float) $data['total_price'], 2) 
                 : 0;
$cart_items    = isset($data['cart_items']) && is_array($data['cart_items']) ? $data['cart_items'] : [];

// ============================================================
// VALIDATION
// ============================================================
$errors = [];

// Required fields
if (empty($customer_name) || strlen($customer_name) < 2) {
    $errors[] = "Customer name is required (min 2 characters).";
}
if (empty($contact) || !preg_match('/^09[0-9]{9}$|^\+63[0-9]{9}$/', $contact)) {
    $errors[] = "Valid Philippine contact number required (e.g., 09123456789).";
}
if (empty($order_type) || !in_array($order_type, ['dine-in', 'delivery'])) {
    $errors[] = "Valid order type required: 'dine-in' or 'delivery'.";
}
if ($order_type === 'dine-in' && (empty($table_number) || strlen($table_number) < 1)) {
    $errors[] = "Table number is required for dine-in orders.";
}
if ($order_type === 'delivery' && (empty($address) || strlen($address) < 10)) {
    $errors[] = "Complete delivery address required (min 10 characters).";
}
if (empty($cart_items) || !is_array($cart_items) || count($cart_items) === 0) {
    $errors[] = "Your cart is empty. Please add items before ordering.";
}
if ($total_price <= 0) {
    $errors[] = "Invalid order total. Please refresh and try again.";
}

// 🔧 PAYMENT VALIDATION - UPDATED WITH CASH ON DELIVERY
$valid_payment_methods = ['cash', 'cash_on_delivery', 'gcash', 'maya', 'paypal', 'bank_transfer'];
if (!in_array($payment_method, $valid_payment_methods)) {
    $errors[] = "Invalid payment method selected.";
}

// Optional: Require reference for e-wallets on large orders (NOT for COD)
if (in_array($payment_method, ['gcash', 'maya', 'bank_transfer']) && $total_price > 500 && empty($payment_reference)) {
    $errors[] = "Payment reference required for {$payment_method} orders over ₱500.";
}

// Validate cart items structure
foreach ($cart_items as $index => $item) {
    if (!isset($item['id']) || !is_numeric($item['id']) || $item['id'] <= 0) {
        $errors[] = "Invalid item ID in cart (item #" . ($index + 1) . ").";
        break;
    }
    if (!isset($item['quantity']) || !is_numeric($item['quantity']) || $item['quantity'] <= 0) {
        $errors[] = "Invalid quantity for item #" . ($index + 1) . ".";
        break;
    }
    if (!isset($item['price']) || !is_numeric($item['price']) || $item['price'] < 0) {
        $errors[] = "Invalid price for item #" . ($index + 1) . ".";
        break;
    }
}

// Return validation errors
if (!empty($errors)) {
    http_response_code(400); // Bad Request
    echo json_encode([
        "success" => false, 
        "message" => "Validation failed.",
        "errors" => $errors
    ]);
    $conn->close();
    exit();
}

// ============================================================
// DATABASE TRANSACTION - All-or-Nothing
// ============================================================
$conn->begin_transaction();

try {
    // 🔧 Determine initial payment status
    // Cash & COD: 'pending' until pickup/delivery confirmation
    // E-wallets: 'pending' until manual/API verification
    $payment_status = 'pending';
    
    // STEP 1: Insert order header into `orders` table
    $order_stmt = $conn->prepare(
        "INSERT INTO orders (
            user_id, customer_name, contact, order_type, table_number, address, 
            total_price, payment_method, payment_status, payment_reference, status
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
    );
    
    if (!$order_stmt) {
        throw new Exception("Failed to prepare order statement: " . $conn->error);
    }
    
    // Bind parameters: i=int, s=string, d=double
// Columns: user_id, customer_name, contact, order_type, table_number, 
//          address, total_price, payment_method, payment_status, payment_reference
// That's 10 parameters total
$order_stmt->bind_param(
    "isssssdsss",  // ← 10 characters: 1i + 7s + 1d + 2s
    $user_id,
    $customer_name,
    $contact,
    $order_type,
    $table_number,
    $address,
    $total_price,
    $payment_method,
    $payment_status,
    $payment_reference
);
    
    if (!$order_stmt->execute()) {
        throw new Exception("Failed to insert order: " . $order_stmt->error);
    }
    
    $order_id = $conn->insert_id;
    $order_stmt->close();
    
    // STEP 2: Insert each cart item into `order_details` table
    $detail_stmt = $conn->prepare(
        "INSERT INTO order_details (order_id, menu_item_id, quantity, price)
         VALUES (?, ?, ?, ?)"
    );
    
    if (!$detail_stmt) {
        throw new Exception("Failed to prepare order details statement: " . $conn->error);
    }
    
    foreach ($cart_items as $item) {
        $menu_item_id = (int) $item['id'];
        $quantity     = (int) $item['quantity'];
        $price        = round((float) $item['price'], 2);
        
        $detail_stmt->bind_param("iiid", $order_id, $menu_item_id, $quantity, $price);
        
        if (!$detail_stmt->execute()) {
            throw new Exception("Failed to insert order item: " . $detail_stmt->error);
        }
    }
    $detail_stmt->close();
    
    // STEP 3: Insert into `payments` table for detailed tracking (optional but recommended)
    // Skip for cash/COD since no external verification needed
    if (!empty($payment_method) && !in_array($payment_method, ['cash', 'cash_on_delivery'])) {
        $payment_details = json_encode([
            'reference' => $payment_reference,
            'initiated_at' => date('Y-m-d H:i:s'),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ], JSON_UNESCAPED_SLASHES);
        
        $payment_stmt = $conn->prepare(
            "INSERT INTO payments (
                order_id, payment_method, amount, payment_status, 
                payment_details, created_at
             ) VALUES (?, ?, ?, 'pending', ?, NOW())"
        );
        
        if ($payment_stmt) {
            $payment_stmt->bind_param("isds", $order_id, $payment_method, $total_price, $payment_details);
            $payment_stmt->execute();
            $payment_stmt->close();
        }
    }
    
    // STEP 4: Commit transaction - Save all changes
    $conn->commit();
    
    // Prepare success response
    $response = [
        "success"  => true,
        "message"  => "Order placed successfully! Your order is being prepared.",
        "order_id" => $order_id,
        "payment_method" => $payment_method,
        "payment_status" => $payment_status,
        "total_price" => $total_price,
        "next_step" => in_array($payment_method, ['cash', 'cash_on_delivery']) 
            ? "Pay upon pickup/delivery" 
            : "Complete payment verification"
    ];
    
    // 🔧 Log successful order (for analytics/debugging)
    error_log("✅ Order #$order_id placed by user_$user_id - ₱$total_price via $payment_method");
    
    echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // 🔧 Rollback on error - Undo all database changes
    $conn->rollback();
    
    // Log error securely (never expose to user)
    $error_msg = "Order placement failed: " . $e->getMessage();
    error_log("❌ Order Error (Order ID: " . ($order_id ?? 'N/A') . "): " . $error_msg);
    
    // Return user-friendly error
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to place order. Please try again or contact support.",
        "error_code" => "ORDER_" . strtoupper(substr(md5($e->getMessage()), 0, 6)) // Unique but safe
    ]);
}

// ============================================================
// CLEANUP
// ============================================================
$conn->close();
exit();
?>