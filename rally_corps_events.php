<?php
//require_once __DIR__ . '/rally_corps.php';

/**
 * POST one RMP event (+ rally days) to RallyCorps.
 * Stable external event id: events.event_ID (maps to RC source_eid).
 * Group: events.group_ID (maps to RC partner_group_id).
 */
function EventToRC($rdb, $group_ID, $event): string {
	$rallies = [];
	$sql = "SELECT rally_ID, rally_name, date, sub_rally_name, day, event_order, vol_default_on
		FROM rallies
		WHERE event='$event->event_ID'
		ORDER BY date";
	$result = $rdb->query($sql);
	if ( mysqli_num_rows ( $result ) ) {
//		while ($rally = mysqli_fetch_object($result)) {
//			$rallies[] = RallyObjectToArray($rally);
//		}
		while ( $rally = mysqli_fetch_assoc ( $result ) ) {
			$rally['group_ID'] = $group_ID;
			$rally['teams'] = [];
			$sql = "SELECT team_ID, rally_ID, team_name, team_description, needs, sort_order, stage_team 
				FROM teams 
				WHERE rally_ID='" . $rally['rally_ID'] . "'";
			$tResult = $rdb->query ( $sql );
			if ( mysqli_num_rows ( $result ) ) {
				while ( $team = mysqli_fetch_assoc ( $tResult ) ) {
					$team['group_ID'] = $group_ID;
					$team['positions'] = [];
					$sql = "SELECT pos_ID, team AS team_ID, description, priority, sort_order
						FROM positions 
						WHERE team='" . $team['team_ID'] . "'";
					$pResult = $rdb->query ( $sql );
					if ( mysqli_num_rows ( $pResult ) ) {
						while ( $position = mysqli_fetch_assoc ( $pResult ) ) {
							$position['group_ID'] = $group_ID;
							$team['positions'][] = $position;
						}
					}
					$rally['teams'][] = $team;
				}
			}
			$rallies[] = $rally;
		}
	}

	// Single partner_rows[0] object — table.column keys (not a flat envelope without partner_rows).
	$partnerRow = [
		'events.group_ID' => (string) $group_ID,
		'events.event_ID' => (string) $event->event_ID,
		'events.event_name' => $event->event_name,
		'events.start_date' => $event->start_date,
		'events.end_date' => $event->end_date,
		'events.registration_open' => $event->registration_open ?? 0,
		'events.rallies' => $rallies,
	];
	
	$url = 'https://rallycorps.onrender.com/v1/webhooks/import/events';
	return CurlToRC($url, $partnerRow);
}

?>
