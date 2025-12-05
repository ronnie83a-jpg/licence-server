<?php
// api/auth.php
// Set a token for your server; match this with core-protector config
$API_TOKEN = "DEV_TOKEN_12345";

function api_verify_token() {
    global $API_TOKEN;
    $sent = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';
    if ($sent !== $API_TOKEN) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid API token']);
        exit;
    }
}
