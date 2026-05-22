<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit(); }

require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit();
}

$data     = json_decode(file_get_contents("php://input"), true) ?: [];
$name     = trim($data['name']     ?? $_POST['name']     ?? '');
$email    = trim($data['email']    ?? $_POST['email']    ?? '');
$password = trim($data['password'] ?? $_POST['password'] ?? '');
$contact  = trim($data['contact']  ?? $_POST['contact']  ?? '');

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

try {
    // Check duplicate email
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        echo json_encode(["success" => false, "message" => "That email is already registered. Please log in."]);
        exit();
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $insert = $pdo->prepare(
        "INSERT INTO users (name, email, password, contact, role) VALUES (?, ?, ?, ?, 'customer')"
    );
    $insert->execute([$name, $email, $hashed, $contact]);
    $new_id = $pdo->lastInsertId();

    echo json_encode([
        "success" => true,
        "message" => "Account created successfully! Welcome to JJES Food Court, {$name}!",
        "user"    => ["id" => $new_id, "name" => $name, "email" => $email, "contact" => $contact, "role" => "customer"]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Registration failed: " . $e->getMessage()]);
}
?>
