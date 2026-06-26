<?php

require 'app_top.php'; 

function CurlToRMP ( $url, $jsonData ) {
	$secret = getenv('RALLYCORPS_WEBHOOK_IMPORT_SECRET');
	if ($secret === false || $secret === '') {
		throw new RuntimeException('RALLYCORPS_WEBHOOK_IMPORT_SECRET is not set');
	}

	$jsonData = ;

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json',
		'Authorization: Bearer ' . $secret,
	]);
	curl_setopt($curl, CURLOPT_URL, 'rc_update.php' );
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);

	$response = curl_exec($curl);
	if ($response === false) {
		$err = curl_error($curl);
		curl_close($curl);
		throw new RuntimeException('cURL error: ' . $err);
	}
	curl_close($curl);

	return $response;
}

$response = CurlToRMP ( 'rc_update', '{
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
	}' );

echo $response;

?>
