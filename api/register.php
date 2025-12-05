<?php
// api/register.php
require_once __DIR__ . '/auth.php';
api_verify_token();

$body = file_get_contents('php://input');
if (!$body) {
    http_response_code(400);
    echo json_encode(['error' => 'Bad request']);
    exit;
}
$payload = @json_decode($body, true);
if (!$payload || !isset($payload['license_key'], $payload['machine_hash'], $payload['domain'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

$statusFile = __DIR__ . '/../status.json';
$raw = @file_get_contents($statusFile);
if ($raw === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Status file missing']);
    exit;
}
$parsed = @json_decode($raw, true);
if (!$parsed || !isset($parsed['licenses'][$payload['license_key']])) {
    http_response_code(404);
    echo json_encode(['error' => 'License not found']);
    exit;
}

$lic = &$parsed['licenses'][$payload['license_key']];

$installed = $lic['installed_on'] ?? [];
// check duplicates
foreach ($installed as $inst) {
    if (isset($inst['machine_hash']) && $inst['machine_hash'] === $payload['machine_hash']) {
        // already installed; nothing to change
        file_put_contents($statusFile, json_encode($parsed, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES), LOCK_EX);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Already registered']);
        exit;
    }
}

// check max installs
$max = intval($lic['max_installs'] ?? 1);
if (count($installed) >= $max) {
    http_response_code(403);
    echo json_encode(['error' => 'Install limit exceeded']);
    exit;
}

// append new install
$installed[] = [
    'machine_hash' => $payload['machine_hash'],
    'domain' => $payload['domain'],
    'installed_at' => gmdate(DATE_ATOM)
];
$lic['installed_on'] = $installed;

// write back
file_put_contents($statusFile, json_encode($parsed, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES), LOCK_EX);
header('Content-Type: application/json');
echo json_encode(['success' => true]);
