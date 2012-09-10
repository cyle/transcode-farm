<?php

/*

	FUNCTIONS THAT HAVE TO DO WITH LOGGING
		cyle gage, emerson college, 2012

		
		oneLinePrintArray($array) -- done
		openLogFile($filename) -- done
		closeLogFile($handle) -- done
		writeToLog($message, $log, $username, $jid) -- done

*/

$logs_dir = '/farm/logs/';

require_once('dbconn_mongo.php');

function oneLinePrintArray($array = array()) {
	$string = print_r($array, true);
	$string = str_replace(array("\n", "\t", "\r"), ' ', $string);
	return $string;
}

function openLogFile($file = '') {
	if (!isset($file) || trim($file) == '') {
		return false;
	}
	global $logs_dir;
	if (!file_exists($logs_dir.$file)) {
		return false;
	}
	$log = fopen($logs_dir.$file, 'a+');
	return $log;
}

function closeLogFile($handle = null) {
	if (!isset($handle)) {
		return false;
	}
	fclose($handle);
	return true;
}

function writeToLog($message = 'there was an error', $log = null, $username = '', $jid = '') {
	if (!isset($log)) {
		return false;
	}
	
	global $farmdb;
	
	$new_log = array();
	$new_log['ts'] = time();
	$new_log['u'] = trim($_SERVER['REQUEST_URI']);
	$new_log['m'] = $message;
	if (isset($username) && trim($username) != '') {
		$new_log['un'] = strtolower(trim($username));
	}
	if (isset($jid) && trim($jid) != '') {
		$new_log['jid'] = trim($jid);
	}
	
	$save_error = $farmdb->log->insert($new_log);
	
	$what = '';
	$what .= date('m-d-y G:i:s');
	if (isset($username) && trim($username) != '') {
		$what .= ' U='.$username;
	}
	if (isset($jid) && trim($jid) != '') {
		$what .= ' J='.$jid;
	}
	$what .= ' '.$message;
	$what .= "\n";
	
	fwrite($log, $what);
	return true;
}

?>