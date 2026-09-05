<?php

//ini_set('display_errors', 1);
//error_reporting(E_ALL);

require '../app_top.php'; //	Sets the secret key outside the web root

function sendJsonWithSecret($url, $data, $secretKey) {
	//	For debugging only
//	echo '<pre>' . json_encode($data, JSON_PRETTY_PRINT) . '</pre>'; exit;
	
	$jsonPayload = json_encode($data);

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonPayload),
        'X-Secret-Key: ' . $secretKey   // custom header for the secret
    ]);
	curl_setopt($ch, CURLOPT_ENCODING, ''); // tells curl to accept & auto-decode any encoding

	$response = curl_exec($ch);
	
	//	Debugging only
	//	echo "RAW RESPONSE:\n";
	//	var_dump($response);   // shows you exactly what came back, including any hidden whitespace
	//	echo "HTTP CODE: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . "\n";
	//	End temporary

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("cURL error: $error");
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'status' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

// Test code

//	Create an object to match Mirza's example

/*
{
	  "source_system": "rmp",
	  "source_eid": "5",
	  "event_type": "volunteer_event_registration",
	  "partner_rows": [
		{
		  "events.event_ID": "5",
		  "rc_people.PID": "12345"
		}
	  ],
	  "rc_event_id": 42,
	  "rc_volunteer_id": 17
}
*/

$rcExport = new stdClass(); 

$rcExport->source_system = 'rmp';
$rcExport->source_eid = 5;
$rcExport->event_type = 'volunteer_event_registration';
$rcExport->partnerRows = [
	  "events.event_ID" => 5,
	  "rc_people.PID" => 12345
  ];
$rcExport->rc_event_id = 42;
$rcExport->rc_volunteer_id = 17;

//	Target address
$url = 'https://rallymasterpro.org/includes/rally_corps/rmp_update.php';
//	Using the same secret key we use for RC inbound
$secretKey = getenv('RALLYCORPS_WEBHOOK_IMPORT_SECRET');

try {
    $result = sendJsonWithSecret($url, $rcExport, $secretKey);
    print_r($result);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

?>
