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

/*	event_type tells us which type of event to test
	event_type refers to an update event type being sent from Rally Corps, not a rally event

1 - 'ver'	- A volunteer event registration
2 - 'eu'	- A rally event update
3 -	Unused (for Rally Data 2)
4 - Unused (for Rally Data 2)

*/

$event_type = $_GET['type']?? '';

$rcExport = new stdClass(); 

$rcExport->source_system = 'rmp';	//	Event going to Rallymaster Pro

switch ( $event_type ) {
	case 'ver':	//	A volunteer event registration
		$rcExport->source_eid = 5;	//	Same as events.event_ID in array
		$rcExport->event_type = 'volunteer_event_registration';
		$rcExport->rc_event_id = 42;
		$rcExport->rc_volunteer_id = 26;
		$rcExport->partnerRows = [
			"events.group_ID" => 1,	//	1 for Ojibwe Forests Rally
			"events.event_ID" => 5,	//	5 for 2024 Ojibwe
			"events.rallies" => [
				[
					"rally_ID" => 123	//	123 for 2024 Friday
				],
				[
					"rally_ID" => 124	//	124 for 2024 Saturday
				]
			],
			"personnel.pers_ID" => 49,
			"personnel.first_name" => 'Something',
			"personnel.last_name" => 'Whatever'
		];
		break;
	case 'eu':	//	An event update
		$rcExport->source_eid = 5;	//	Same as events.event_ID in array
		$rcExport->event_type = 'event_update';
		$rcExport->rc_event_id = 42;
		$rcExport->partnerRows = [
			"source_group_ID" => 1,	//	1 for Ojibwe Forests Rally
			"events.event_ID" => 5	//	5 for 2024 Ojibwe
		];
		break;
	default:
		//	This is deprecated and should not be used (no type sent)
		$rcExport->source_eid = 5;	//	Same as events.event_ID in array
		$rcExport->event_type = 'volunteer_event_registration';
		$rcExport->rc_event_id = 42;
		$rcExport->rc_volunteer_id = 22;
		$rcExport->partnerRows = [
			"source_group_ID" => 1,	//	1 for Ojibwe Forests Rally
			"events.event_ID" => 5,
			"personnel.pers_ID" => 505,
			"personnel.first_name" => 'Something',
			"personnel.last_name" => 'Whatever'
		];
		break;
}

//	Target address
$url = 'https://rallymasterpro.org/includes/rally_corps/rmp_update.php';
//	Using the same secret key we use for RC inbound
$secretKey = getenv('RALLYCORPS_WEBHOOK_IMPORT_SECRET');

try {
    $result = sendJsonWithSecret($url, $rcExport, $secretKey);
    print_r( $result );
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

?>
