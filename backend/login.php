<?php
// backend/login.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Remove in production, restrict to your domain
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include database connection
require_once __DIR__ . '/db_connect.php';

// Get and decode JSON input
$json_data = file_get_contents("php://input");
$request = json_decode($json_data);

// Validate input
if (!$request || empty($request->email) || empty($request->password)) {
    http_response_code(400);
    echo json_encode([
        "success" => false, 
        "message" => "Email and password are required"
    ]);
    exit;
}

$email = trim($request->email);
$password = $request->password;

try {
    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password (if using password_hash())
        if (password_verify($password, $user['password'])) {
            // Return user data (exclude password)
            echo json_encode([
                "success" => true,
                "user" => [
                    "id" => $user['id'],
                    "name" => $user['name'],
                    "email" => $user['email']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode([
                "success" => false, 
                "message" => "Invalid password"
            ]);
        }
    } else {
        http_response_code(401);
        echo json_encode([
            "success" => false, 
            "message" => "No account found with this email"
        ]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Server error: " . $e->getMessage()
    ]);
}

$conn->close();
?>