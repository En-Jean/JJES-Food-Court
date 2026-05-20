<?php
// ============================================================
// register.php
// PURPOSE: Register a new customer account
// RETURNS: JSON with success/failure message
// CALLED BY: JavaScript fetch() in main.js
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// Include database connection
require_once 'db_connect.php';

// --- Only accept POST requests ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit();
}

// --- Read JSON input from JavaScript fetch() ---
$raw_input = file_get_contents("php://input");
$data      = json_decode($raw_input, true);

// Support both JSON body and FormData
$name     = isset($data['name'])     ? trim($data['name'])     : trim($_POST['name']     ?? '');
$email    = isset($data['email'])    ? trim($data['email'])    : trim($_POST['email']    ?? '');
$password = isset($data['password']) ? trim($data['password']) : trim($_POST['password'] ?? '');
$contact  = isset($data['contact'])  ? trim($data['contact'])  : trim($_POST['contact']  ?? '');

// --- Validation ---
if (empty($name) || empty($email) || empty($password)) {
    echo json_encode(["success" => false, "message" => "Name, email, and password are required."]);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Please enter a valid email address."]);
    exit();
}

if (strlen($password) < 6) {
    echo json_encode(["success" => false, "message" => "Password must be at least 6 characters long."]);
    exit();
}

// --- Check if email is already registered ---
$check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check_stmt->bind_param("s", $email);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "That email is already registered. Please log in."]);
    $check_stmt->close();
    exit();
}
$check_stmt->close();

// --- Hash the password for security ---
// NEVER store plain-text passwords in the database!
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// --- Insert new user into the database ---
$insert_stmt = $conn->prepare(
    "INSERT INTO users (name, email, password, contact, role) VALUES (?, ?, ?, ?, 'customer')"
);
$insert_stmt->bind_param("ssss", $name, $email, $hashed_password, $contact);

if ($insert_stmt->execute()) {
    // Registration successful!
    $new_user_id = $conn->insert_id; // Get the ID of the newly created user

    echo json_encode([
        "success" => true,
        "message" => "Account created successfully! Welcome to JJES Food Court, " . $name . "!",
        "user" => [
            "id"      => $new_user_id,
            "name"    => $name,
            "email"   => $email,
            "contact" => $contact,
            "role"    => "customer"
        ]
    ]);
} else {
    // Database insert failed
    echo json_encode(["success" => false, "message" => "Registration failed. Please try again."]);
}

$insert_stmt->close();
$conn->close();
?>