<?php

//ini_set('display_errors', 1);
//error_reporting(E_ALL);

require_once '../app_top.php';

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
    $result = json_encode(['error' => 'Unauthorized']);
	error_log ( $result );
	echo $result;
    exit;
}

// --- Step 3: Read and validate the JSON body ---
$rawBody = file_get_contents('php://input');

if ($rawBody === false || $rawBody === '') {
    http_response_code(400);
    $result = json_encode(['error' => 'Empty request body']);
	error_log ( $result );
	echo $result;
    exit;
}

$data = json_decode($rawBody);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    $result = json_encode(['error' => 'Invalid JSON: ' . json_last_error_msg()]);
	error_log ( $result );
	echo $result;
    exit;
}

// --- Step 4: (Optional) Validate expected fields ---
if (!isset($data->source_system) || !isset($data->event_type)) {
    http_response_code(422);
    $result = json_encode(['error' => 'Missing required fields']);
	error_log ( $result );
	echo $result;
    exit;
}

// --- Step 5: Do something with the data ---
// e.g. save to database, process, etc.

error_log ( PHP_EOL . date( DATE_RFC850 ) . ' ' . json_encode($data), 3, 'update_log' );

//	The included scripts populate the $response array

switch ( $data->event_type ) {
	case 'volunteer_event_registration':
		include 'volunteer_registration.php';
		break;
	default:
		$response['message'] = 'Unknown event type';
		break;
}

error_log ( PHP_EOL . json_encode($response), 3, 'update_log' );

http_response_code(200);
echo json_encode([
    'status' => 'success',
    'received' => $data,
	'response' => $response
]);

?>
