<?php

// CHANGE THIS TOKEN BEFORE DEPLOYING
$API_TOKEN = "SUPERSECRET_DEVTOKEN_123456789";

function verify_token() {
    global $API_TOKEN;

    $sent = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';
    if ($sent !== $API_TOKEN) {
        http_response_code(401);
        echo json_encode(["error" => "Invalid API token"]);
        exit;
    }
}
