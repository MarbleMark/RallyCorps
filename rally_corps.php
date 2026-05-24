<?php

function CurlToRC($url, $data) {
	//	Execute a CURL commend
	$data['source_system'] = "rmp";
	$data['partner_rows'] = $rows;
		
//	echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>"; exit;

	$jsonData = json_encode($data);

	$curl = curl_init();

	$headers =
	[
		"Content-Type: application/json",
		"Authorization: Bearer <shared-secret>"
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

?>
