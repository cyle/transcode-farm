<?php

/*

	FUNCTIONS THAT HAVE TO DO WITH ERROR REPORTING
		cyle gage, emerson college, 2012

		
		bailout($error_message, $username, $jid, $additional_data, $display_page) -- done
		batchBailout($error_message, $username, $jid, $additional_data) -- done
		writeToErrorLog($error_message, $username, $jid, $additional_data) -- done
		
*/

require_once('dbconn_mongo.php');

function bailout($error_message = 'There was an error of some kind, sorry!', $username = '', $jid = '', $additional_data = '', $display_page = true) {
	// uhh -- formatting?
	//echo $error_message;
	// log that shit
	
	global $farmdb;
	
	$new_error = array();
	$new_error['ts'] = time();
	$new_error['u'] = trim($_SERVER['REQUEST_URI']);
	$new_error['m'] = $error_message;
	if (isset($additional_data) && trim($additional_data) != '') {
		$new_error['a'] = $additional_data;
	}
	if (isset($username) && trim($username) != '') {
		$new_error['un'] = strtolower(trim($username));
	}
	if (isset($jid) && trim($jid) != '') {
		$new_error['jid'] = trim($jid);
	}
	
	$save_error = $farmdb->error_log->insert($new_error);
	
	if (isset($display_page) && $display_page == true) {
		require_once('/farm/www/error.php');
	} else {
		echo $error_message;
	}
	die();
}

function batchBailout($error_message = 'There was an error of some kind, sorry!', $username = '', $jid = '', $additional_data = '') {
	global $farmdb;
	
	$new_error = array();
	$new_error['ts'] = time();
	$new_error['u'] = trim($_SERVER['REQUEST_URI']);
	$new_error['m'] = $error_message;
	if (isset($additional_data) && trim($additional_data) != '') {
		$new_error['a'] = $additional_data;
	}
	if (isset($username) && trim($username) != '') {
		$new_error['un'] = strtolower(trim($username));
	}
	if (isset($jid) && trim($jid) != '') {
		$new_error['jid'] = trim($jid);
	}
	
	$save_error = $farmdb->error_log->insert($new_error);
	
	echo json_encode(array('error' => $error_message));
	die();
}

function writeToErrorLog($error_message = 'there was an error of some kind', $username = '', $jid = '', $additional_data = '') {
	global $farmdb;
	
	$new_error = array();
	$new_error['ts'] = time();
	$new_error['u'] = trim($_SERVER['REQUEST_URI']);
	$new_error['m'] = $error_message;
	if (isset($additional_data) && trim($additional_data) != '') {
		$new_error['a'] = $additional_data;
	}
	if (isset($username) && trim($username) != '') {
		$new_error['un'] = strtolower(trim($username));
	}
	if (isset($jid) && trim($jid) != '') {
		$new_error['jid'] = trim($jid);
	}
	
	$save_error = $farmdb->error_log->insert($new_error);
	
}

?>