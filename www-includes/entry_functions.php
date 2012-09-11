<?php

/*

	FUNCTIONS THAT HAVE TO DO WITH ENTRIES
		cyle gage, emerson college, 2012

		
		generateNewEntry($username, $filename)
		getUserEntries($username)
		bitrateToFriendly($bitrate)
		plural($num)
		getRelativeTime($timestamp, $suffix)

*/

require_once('dbconn_mongo.php');

function generateNewEntry($username = '', $filename = '') {
	
	if (!isset($username) || trim($username) == '') {
		return false;
	}
	
	if (!isset($filename) || trim($filename) == '') {
		return false;
	}
	
	global $farmdb;
	
	$new_entry = array();
	$new_entry['tsc'] = time(); // timestamp of creation
	$new_entry['tsu'] = time(); // timestamp of last update
	$new_entry['un'] = strtolower(trim($username)); // the user this is for
	$new_entry['e'] = false; // starts disabled (does not show up anywhere yet)
	$new_entry['fn'] = trim($filename); // the filename of the incoming file
	
	try {
		$result = $farmdb->entries->insert($new_entry, array('safe' => true));
	} catch(MongoCursorException $e) {
		return false;
	}
	
	return ''.$new_entry['_id'].'';
	
}

function getUserEntries($username = '') {
	if (!isset($username) || trim($username) == '') {
		return false;
	}
	
	$username = strtolower(trim($username));
	
	global $farmdb;
	$farmdb->setSlaveOkay();
	
	$entries = array();
	
	$get_entries = $farmdb->entries->find(array('un' => $username, 'e' => true));
	if ($get_entries->count() > 0) {
		$get_entries->sort( array('tsc' => 1) );
		foreach ($get_entries as $entry) {
			$entries[] = $entry;
		}
	}
	
	return $entries;
	
}

function bitrateToFriendly($bitrate = 0) {
	if (!is_numeric($bitrate)) {
		return false;
	}
	
	$bitrate = (int) $bitrate * 1;
	
	switch ($bitrate) {
		case 364:
		$friendly_string = 'Small/Mobile';
		break;
		case 696:
		$friendly_string = 'Medium';
		break;
		case 1328:
		$friendly_string = 'High';
		break;
		case 1828:
		$friendly_string = 'Ultra';
		break;
		case 2396:
		$friendly_string = 'Max';
		break;
		default:
		$friendly_string = $bitrate.'kbps';
	}
	
	return $friendly_string;
}

function plural($num) {
	if ($num != 1)
		return "s";
}

function getRelativeTime($timestamp = 0, $suffix = '') {
	//$diff = time() - strtotime($timestamp);
	$diff = abs(time() - $timestamp);
	if ($diff<60)
		return $diff . " second" . plural($diff) . $suffix;
	$diff = round($diff/60);
	if ($diff<60)
		return $diff . " minute" . plural($diff) . $suffix;
	$diff = round($diff/60);
	if ($diff<24)
		return $diff . " hour" . plural($diff) . $suffix;
	$diff = round($diff/24);
	if ($diff<7)
		return $diff . " day" . plural($diff) . $suffix;
	$diff = round($diff/7);
	if ($diff<4)
		return $diff . " week" . plural($diff) . $suffix;
	return "on " . date('F j, Y', $timestamp);
}


?>