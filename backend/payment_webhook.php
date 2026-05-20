<?php
// Define these BEFORE they're used
define('WEBHOOK_SECRET', 'whsec_your_secret_here'); // Get from PayMongo
define('LOG_FILE', __DIR__ . '/logs/webhook.log');

// Create logs directory if not exists
if (!is_dir(dirname(LOG_FILE))) {
    mkdir(dirname(LOG_FILE), 0755, true);
}

// Logging function MUST be defined first
function logWebhook($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message\n";
    file_put_contents(LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);
}

// Verify signature function
function verifyWebhookSignature($payload, $signature, $secret) {
    $expected = hash_hmac('sha256', $payload, $secret);
    return hash_equals($expected, $signature);
}

// Headers first
header('Content-Type: application/json');

// Get signature
$signature = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? null;

// Get payload
$rawPayload = file_get_contents('php://input');

// Verify signature (if exists)
if ($signature && !verifyWebhookSignature($rawPayload, $signature, WEBHOOK_SECRET)) {
    http_response_code(401);
    logWebhook('Invalid signature', 'ERROR');
    echo json_encode(['error' => 'Invalid signature']);
    exit();
}

// NOW your existing code starts...
$event = json_decode($rawPayload, true);