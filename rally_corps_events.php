<?php
require_once __DIR__ . '/rally_corps.php';

/**
 * One rally day row from RMP → keys inside events.rallies (stored in RC schedule_json).
 * Prefix with events. on each day field — do not nest a separate top-level "events.rallies" partner key
 * for adapter mapping; the parent row carries events.rallies as a JSON array.
 */
function RallyObjectToArray($rally): array {
	$rallyData = [];
	$rallyData['rally_ID'] = $rally->rally_ID;
	// rally_name: legacy; safe to omit later
	$rallyData['rally_name'] = $rally->rally_name;
	$rallyData['date'] = $rally->date;
	$rallyData['sub_rally_name'] = $rally->sub_rally_name;
	$rallyData['day'] = $rally->day;
	$rallyData['event_order'] = $rally->event_order;
	$rallyData['vol_default_on'] = $rally->vol_default_on;
	return $rallyData;
}

/**
 * POST one RMP event (+ rally days) to RallyCorps.
 * Stable external event id: events.event_ID (maps to RC source_eid).
 * Group: events.group_ID (maps to RC partner_group_id).
 */
function EventToRC($rdb, $group_ID, $event): string {
	$rallies = [];
	$sql = "SELECT rally_ID, rally_name, date, sub_rally_name, day, event_order, vol_default_on
		FROM rallies
		WHERE event='" . mysqli_real_escape_string($rdb->dbLink, $event->event_ID) . "'
		ORDER BY date";
	$result = $rdb->query($sql);
	while ($rally = mysqli_fetch_object($result)) {
		$rallies[] = RallyObjectToArray($rally);
	}

	// Single partner_rows[0] object — table.column keys (not a flat envelope without partner_rows).
	$partnerRow = [
		'events.group_ID' => (string) $group_ID,
		'events.event_ID' => (string) $event->event_ID,
		'events.event_name' => $event->event_name,
		'events.start_date' => SQLDateFromStandard($event->start_date),
		'events.end_date' => SQLDateFromStandard($event->end_date),
		'events.registration_open' => $event->registration_open ?? 0,
		'events.rallies' => $rallies,
	];

	$url = 'https://rallycorps.onrender.com/v1/webhooks/import/events';
	return CurlToRC($url, $partnerRow);
}

?>
