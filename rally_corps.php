<?php

function CurlToRC($url, $data) {
	//	Execute a CURL commend
	$jsonData = json_encode($data);

	$curl = curl_init();

	$headers =
	[
	"Content-Type: application/json",
	"Authorization: Bearer " . RALLY_CORPS_SECRET_KEY,
	];
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
	
	$response = curl_exec($curl);

//	printf('curl response: %s <br>', $response );

	// Check for errors
	//if (curl_errno($curl)) echo 'cURL error: ' . curl_error($curl);
	//else printf('Response: %s <br>', $response);

	curl_close($curl);
	
	return $response;
}

function EventToRC ( $db, $group_ID, $event ) {
	//	Send an event to RallyCorps
	$data = [];
	$rows = [];
	$data['source_system'] = "rmp";
	
	$eventData['events.group_ID'] = $group_ID;
	$eventData['events.event_ID'] = $event->event_ID;
	$eventData['events.event_name'] = mysqli_real_escape_string($db->dbLink, $event->event_name);
	$eventData['start_date'] = SQLDateFromStandard($event->start_date);
	$eventData['end_date'] = SQLDateFromStandard($event->end_date);

	$data['partner_rows'] = $eventData;

//	print_r($data);
//	printf('<br>');

	$url = 'https://rallycorps.onrender.com/v1/webhooks/import/events';

	return CurlToRC($url, $data);
}

?>