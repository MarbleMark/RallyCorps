<?php
//require_once __DIR__ . '/rally_corps.php';	

//	Helper function for both types of assignment

function AssignmentHeader ( $groupName, $assignment ): string {
	$history = "($groupName) $assignment->rally_name";
	if ( $assignment->day != '' ) {
		$history .= ", $assignment->day";
	};
	$history .= " $assignment->date: ";
	
	return $history;
}

//	Convert a personnel/volunteer record into a user array for Rally Corps

function VolunteerToArray ( $db, $volunteer ): array {
	//	First get a list of all rally groups
	$result = $db->query ( "SELECT rg_ID, short_name FROM rally_groups ORDER BY short_name" );
	while ( $group = mysqli_fetch_object ( $result ) ) {
		$groups[] = $group;
	}

	//	Build an array of this volunteer's history
	$history = [];

	//	Loop through all rally groups
	foreach ( $groups as $group ) {
		$rdb = new rdb( $db, $group->rg_ID );

		//	Find all the "position" assignments
		$result = $rdb->query ( "SELECT positions.description,
				teams.team_name,
				rallies.rally_ID, rally_name, date, day,
				assignments.no_show, pers_ID 
			FROM worker_positions 
			LEFT JOIN positions ON positions.pos_ID=worker_positions.position
			LEFT JOIN teams ON teams.team_ID=positions.team
			LEFT JOIN rallies ON rallies.rally_ID=teams.rally_ID
			LEFT JOIN assignments ON assignments.rally_ID=rallies.rally_ID AND assignments.pers_ID='$volunteer->pers_ID'
			WHERE worker='$volunteer->pers_ID'
			ORDER BY date DESC" );

		while ( $assignment = mysqli_fetch_object ( $result ) ) {
			if ( $assignment->rally_ID ) {
				$positionAssignments[] = $assignment;
			}
		}

		//	Find all the "special" assignments
		$result = $rdb->query ( "SELECT assign_ID, pers_ID, assignments.rally_ID, assignment, no_show, rally_name, day, date
			FROM assignments
			LEFT JOIN rallies ON rallies.rally_ID=assignments.rally_ID
			WHERE pers_ID='$volunteer->pers_ID'
			ORDER BY date DESC" );

		while ( $assignment = mysqli_fetch_object ( $result ) ) {
			if ( $assignment->assignment != '' ) {
				$specialAssignments[] = $assignment;
			}
		}

		//	List the history by date, position assignments before special assignments
		while ( !empty( $positionAssignments ) || !empty( $specialAssignments ) ) {
			//	Get the first date in each list
			$posDate = $positionAssignments[0]->date?? '0000-00-00'; 
			$specDate = $specialAssignments[0]->date?? '0000-00-00';

			if ( $posDate >= $specDate ) {
				//	First position date is greater or equal to first special date, remove it from the array
				$assignment = array_shift ( $positionAssignments );
				//	Convert it to a history string
				$positionHistory = AssignmentHeader ( $group->short_name, $assignment) ;
				$positionHistory .= "$assignment->team_name - $assignment->description";
				if ( $assignment->no_show ) {
					$positionHistory .= ' NO SHOW!';
				}
				//	Add it to the history array
				$history[] = $positionHistory;
			} else {
				//	First special date is greater than first position date
				$assignment = array_shift ( $specialAssignments );
				//	Convert it to a history string
				$specialAssignment = AssignmentHeader ( $group->short_name, $assignment ) . '***'.$assignment->assignment;
				if ( $assignment->no_show ) {
					$specialAssignment .= ' NO SHOW!';
				}
				//	Add it to the history array
				$history[] = $specialAssignment;
			}
		}
	}
	//	Get the remaining user fields and add the history
	$user = [
		'users.pers_ID' => $volunteer->pers_ID,
		'users.first_name' => $volunteer->first_name,
		'users.last_name' => $volunteer->last_name,
		'users.middle_initial' => $volunteer->m_i,
		'users.e_mail' => $volunteer->e_mail,
		'users.address' => $volunteer->address,
		'users.city' => $volunteer->city,
		'users.state' => $volunteer->state,
		'users.postal_code' => $volunteer->zip,
		'users.country' => $volunteer->country,
		'users.phone' => $volunteer->phone,
		'users.call_sign' => $volunteer->call_sign,
		'users.shirt_size' => $volunteer->shirt_size,
/* last_contact is the last time the person logged in to Rallymaster Pro */			
		'users.last_contact' => $volunteer->last_contact,	
/* private_comments are comments that can be seen by all organizers, but not by the volunteer. */			
/* These are notes such as warnings about the volunteer not showing up without giving notice. */			
		'users.private_comments' => $volunteer->comments,	
		'users.dont_send_email' => $volunteer->dont_send_email,	
/* last_event and last_event_date are not reliable.  They depend on the organizers recording the data */
		'users.last_event' => $volunteer->last_event,	
		'users.last_event_date' => $volunteer->last_event_date,	
/* Volunteer preferences (specialties) */
		'users.pref_registration' => $volunteer->registration,	
		'users.pref_public_relations' => $volunteer->public_relations,	
		'users.pref_hospitality' => $volunteer->hospitality,	
		'users.pref_tech_inspection' => $volunteer->tech_inspection,	
		'users.pref_bannering' => $volunteer->bannering,	
		'users.pref_advance' => $volunteer->advance,	
		'users.pref_course_marshal' => $volunteer->course_marshal,	
		'users.pref_radio_operator' => $volunteer->radio_op,	
		'users.pref_control_worker' => $volunteer->control_worker,	
		'users.pref_stage_captain' => $volunteer->stage_captain,	
		'users.pref_spec_captain' => $volunteer->spec_captain,	
		'users.pref_spectator_marshal' => $volunteer->spectator,	
		'users.pref_service' => $volunteer->service,	
		'users.pref_vip_team' => $volunteer->vip,	
		'users.pref_sweep' => $volunteer->sweep,	
		'users.pref_medical' => $volunteer->medical,	
		'users.medical_training' => $volunteer->med_training,	
		'users.medical_cert' => $volunteer->med_cert,	
		'users.medical_cert_num' => $volunteer->med_cert_num,	
		'users.medical_cert_state' => $volunteer->med_cert_state,	
		'users.medical_cert_expire' => $volunteer->med_cert_expire,	
		'users.pref_other_specialties' => $volunteer->other_spec,	
		'users.history' => $history
	];
	return $user;
}

//	Send a volunteer record to Rally Corps

function VolunteerToRC($db, $volunteer): string {
	//	Convert the volunteer record into an array
	$partnerRows = VolunteerToArray ( $db, $volunteer );
	
	//	Uncomment to debug
//	echo '<pre>'; print_r ( $partnerRow ); echo '</pre><br>'; exit;
	
	//	Send to Rally Corp
	$url = 'https://rallycorps.onrender.com/v1/webhooks/import/users';
	return CurlToRC($url, $partnerRows);
}

//	Send an array of volunteers to Rally Corps

function VolunteersToRC($db, $volunteers): string {	
	//	Loop through every volunteer
	foreach ( $volunteers as $volunteer ) {
		$partnerRows[] = VolunteerToArray ( $db, $volunteer );
	}

	//	Uncomment to debug
//	echo '<pre>'; print_r ( $partnerRows ); echo '</pre><br>'; exit;
	
	$url = 'https://rallycorps.onrender.com/v1/webhooks/import/users';
	return CurlToRC($url, $partnerRows);
}

?>
