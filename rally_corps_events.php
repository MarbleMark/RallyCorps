<?php
function RallyObjectToArray ( $rally ) {
	//	Some of fields need some explanation
	
	$rallyData['rally_ID'] = $rally->rally_ID; 
	
	//	rally_name is a carryover from years past, when rallies were linked into a weekend, rather than assigned to an event.  I think in the new version of Rallymaster Pro I should just ignore it and use the event name, instead.  You can ignore rally_name for now
	$rallyData['rally_name'] = $rally->rally_name; 
	
	//	date is the date you want for the rally day.  Events only have start and end dates.  Sorry that I used a PHP function name as a field name back in 2000.
	$rallyData['date'] = $rally->date; 
	
	//	sub_rally_name is used for example when a regional rally is running concurrently with a national rally.  For instance, Sno Drift used to have Sno on Friday and Drift on Saturday.  Ojibwe had Paul Bunyan's Ride on Friday and 10,000 Lakes on Saturday.  This may no longer be necessary, and we should discuss it with the group
	$rallyData['sub_rally_name'] = $rally->sub_rally_name; 
	
	//	day is the label that marks the name of the rally day, like "Day One" or "Friday Night".  It should be discussed in the group just like sub_rally_name
	$rallyData['day'] = $rally->day; 
	
	//	event_order is the order of competition days and is probably not needed for Rally Corps. Non-competition days have an event_order of 0.  Sort by date, not by event_order.
	$rallyData['event_order'] = $rally->event_order; 
	
	//	vol_default_on is important.  When someone goes to the event registration page, these rally days should be checked on by default.  That's because we need nearly all volunteers on competition days, but not as many on non-competition days. For example, you'll likely find something like Thursday off by default, Friday and Saturday on, and Sunday off.  That way, we don't have a lot of people accidentally signing up for days they don't plan to attend.  You might think that this does the same as event_order, but it does not.  You might have a single spectator stage Thursday night, but you only need a few volunteers, so you'll see event_order having a positive value and vol_default_on being false.
	$rallyData['vol_default_on'] = $rally->vol_default_on; 
	
	return $rallyData;
}

function EventToRC ( $rdb, $group_ID, $event ) {
	//	Send an event to RallyCorps
	$sql = "SELECT rally_ID, rally_name, date, sub_rally_name, day, event_order, vol_default_on 
		FROM rallies 
		WHERE event='$event->event_ID' 
		ORDER BY date";
	$result = $rdb->query ( $sql );
	while ( $rally = mysqli_fetch_object ( $result ) ) {
		$rallies[] = RallyObjectToArray ( $rally );
	}
	
	$eventData['events.group_ID'] = $group_ID;
	$eventData['events.event_ID'] = $event->event_ID;
	$eventData['events.event_name'] = mysqli_real_escape_string($rdb->dbLink, $event->event_name);
	$eventData['events.start_date'] = SQLDateFromStandard($event->start_date);
	$eventData['events.end_date'] = SQLDateFromStandard($event->end_date);
	$eventData['events.registration_open'] = $event->registration_open?? 0;
	$eventData['events.rallies'] = $rallies;
//	print_r($eventData);
//	printf('<br>');

	$url = 'https://rallycorps.onrender.com/v1/webhooks/import/events';

	return CurlToRC($url, $eventData);
}

?>