<?php

//ini_set('display_errors', 1);
//error_reporting(E_ALL);

include '../app_top.php';

// --- Configuration ---
$expectedSecret = getenv('RALLYCORPS_WEBHOOK_IMPORT_SECRET'); // set this in your server environment

// --- Step 1: Get the header ---
// Apache/PHP-FPM sometimes strips custom headers depending on config,
// so check a couple of common ways to retrieve it.
function getSecretHeader() {
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $name => $value) {
            if (strcasecmp($name, 'X-Secret-Key') === 0) {
                return $value;
            }
        }
    }
    // Fallback for servers where getallheaders() isn't available
    if (isset($_SERVER['HTTP_X_SECRET_KEY'])) {
        return $_SERVER['HTTP_X_SECRET_KEY'];
    }
    return null;
}

$providedSecret = getSecretHeader();

// --- Step 2: Verify the secret using a timing-safe comparison ---
if ($providedSecret === null || !hash_equals($expectedSecret, $providedSecret)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// --- Step 3: Read and validate the JSON body ---
$rawBody = file_get_contents('php://input');

if ($rawBody === false || $rawBody === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Empty request body']);
    exit;
}

$data = json_decode($rawBody, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON: ' . json_last_error_msg()]);
    exit;
}

// --- Step 4: (Optional) Validate expected fields ---
if (!isset($data['source_system']) || !isset($data['event_type'])) {
    http_response_code(422);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// --- Step 5: Do something with the data ---
// e.g. save to database, process, etc.

http_response_code(200);
echo json_encode([
    'status' => 'success',
    'received' => $data
]);

?>