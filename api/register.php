<?php
header("Content-Type: application/json");

require_once __DIR__ . '/auth.php';

verify_token();

// Read payload
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$license_key  = $data['license_key']  ?? null;
$machine_hash = $data['machine_hash'] ?? null;
$domain       = $data['domain']       ?? null;

if (!$license_key || !$machine_hash) {
    http_response_code(400);
    echo json_encode(["error" => "Missing license_key or machine_hash"]);
    exit;
}

// Load status.json
$path = dirname(__DIR__) . '/status.json';
$json = file_get_contents($path);
$server = json_decode($json, true);

if (!isset($server['licenses'][$license_key])) {
    http_response_code(404);
    echo json_encode(["error" => "License not found"]);
    exit;
}

$lic = &$server['licenses'][$license_key];

// Vendor kill switch
if (!empty($lic['force_disable'])) {
    http_response_code(403);
    echo json_encode(["error" => "License disabled by vendor"]);
    exit;
}

// Unlimited installs?
if ($lic['max_installs'] == -1) {
    if (!in_array($machine_hash, $lic['installed_on'])) {
        $lic['installed_on'][] = $machine_hash;
    }
} else {

    // Already installed?
    if (!in_array($machine_hash, $lic['installed_on'])) {

        // Install limit reached?
        if (count($lic['installed_on']) >= $lic['max_installs']) {
            http_response_code(403);
            echo json_encode(["error" => "Install limit exceeded"]);
            exit;
        }

        // Register new machine
        $lic['installed_on'][] = $machine_hash;
    }
}

// Write updated JSON back
file_put_contents($path, json_encode($server, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// Log action
$logpath = dirname(__DIR__) . '/logs/actions.log';
file_put_contents($logpath, date('c') . " REGISTER {$license_key} {$machine_hash} {$domain}\n", FILE_APPEND);

echo json_encode(["success" => true, "message" => "Machine registered"]);