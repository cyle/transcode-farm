<?php

// download a file using lighttpd's X-LIGHTTPD-send-file

$login_required = true;
require_once('../www-includes/login_check.php');

require_once('../www-includes/error_functions.php');

if (!isset($_GET['eid']) || trim($_GET['eid']) == '') {
	bailout('Sorry, no entry ID was given.', $current_user['username']);
} else {
	$eid = trim($_GET['eid']);
}

if (!isset($_GET['b']) || !is_numeric($_GET['b'])) {
	bailout('Sorry, no bitrate version was given.', $current_user['username'], $eid);
} else {
	$bitrate = (int) $_GET['b'] * 1;
}

$eid_db = new MongoId($eid);

$entry = $farmdb->entries->findOne( array('_id' => $eid_db) );

if ($entry == false) {
	// uhh media does not exist!
	bailout('Sorry, but an entry with that ID does not exist!', $current_user['username'], $eid);
}

if ($entry['e'] == false) {
	bailout('Sorry, that entry is not enabled!', $current_user['username'], $eid);
}

if ($entry['un'] == $current_user['username']) {
	$out_path = null;
	foreach ($entry['pa']['c'] as $path) {
		if ($path['b'] == $bitrate) {
			if (isset($path['e']) && $path['e'] == true) {
				$out_path = $path['p'];
			} else {
				bailout('Sorry, but the version you have selected is not ready to be downloaded.', $current_user['username'], $eid);
			}
		}
	}
	if ($out_path == null) {
		bailout('Sorry, but there is no version of that entry with the selected bitrate.', $current_user['username'], $eid);
	} else {
		$filename = substr(strrchr($out_path, '/'), 1);
		//header('Content-Length: '.filesize($out_path));
		//header('Content-Type: video/mp4');
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header('X-LIGHTTPD-send-file: '.$out_path);
		die();
	}
} else {
	bailout('Sorry, your are not allowed to download this entry.', $current_user['username'], $eid);
}

?>