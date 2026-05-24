<?php
/**
 * Shared cURL helper for RallyCorps inbound webhooks.
 * Set RALLYCORPS_WEBHOOK_IMPORT_SECRET in the environment (same value as RC WEBHOOK_IMPORT_SECRET).
 */

function CurlToRC(string $url, array $partnerRow): string {
	$data = [
		'source_system' => 'rmp',
		'partner_rows' => [ $partnerRow ],
	];

	// Uncomment while debugging:
	// echo '<pre>' . json_encode($data, JSON_PRETTY_PRINT) . '</pre>'; exit;

	$jsonData = json_encode($data);
	if ($jsonData === false) {
		throw new RuntimeException('json_encode failed: ' . json_last_error_msg());
	}

	$secret = getenv('RALLYCORPS_WEBHOOK_IMPORT_SECRET');
	if ($secret === false || $secret === '') {
		throw new RuntimeException('RALLYCORPS_WEBHOOK_IMPORT_SECRET is not set');
	}

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json',
		'Authorization: Bearer ' . $secret,
	]);
	curl_setopt($curl, CURLOPT_URL, $url);
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

?>
