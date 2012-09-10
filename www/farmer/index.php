<?php

// farming API for farmers

/*

	check in heartbeat
	set status
	get job

*/

if (!isset($_GET['t']) || trim($_GET['t']) == '') {
	die('nope');
}

$action = strtolower(trim($_GET['t']));

// get requester's IP
$farmer_ip = '';
if (isset($_SERVER['REMOTE_ADDR']) && trim($_SERVER['REMOTE_ADDR']) != '' && trim($_SERVER['REMOTE_ADDR']) != '127.0.0.1') {
	$farmer_ip = trim($_SERVER['REMOTE_ADDR']); // ip address of viewer
} else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && trim($_SERVER['HTTP_X_FORWARDED_FOR']) != '' && trim($_SERVER['HTTP_X_FORWARDED_FOR']) != '127.0.0.1') {
	$farmer_ip = trim($_SERVER['HTTP_X_FORWARDED_FOR']); // ip address of viewer
}

require_once('../includes/dbconn_mongo.php');
$farmdb = $m->farm;

if ($action == 'h') {
	// heartbeat!
	
	// check to see if it's already there, based on IP
	$farmer = $farmdb->farmers->findOne(array('ip' => $farmer_ip));
	if (!isset($farmer)) {
		// ok, add it since it's new
		$new_farmer = array();
		$new_farmer['n'] = 'Unnamed';
		$new_farmer['hn'] = 'Unknown';
		$new_farmer['ip'] = $farmer_ip;
		$new_farmer['e'] = false;
		$new_farmer['tsc'] = time();
		$new_farmer['tsh'] = time();
		$insert = $farmdb->farmers->insert($new_farmer, array('safe' => true));
	} else {
		// ok, update its heartbeat
		$update = $farmdb->farmers->update(array('ip' => $farmer_ip), array('$set' => array('tsh' => time())), array('safe' => true));
	}
	
	echo 'ok';
	
} else if ($action == 'j') {
	// get a job!
	
	$farmer = $farmdb->farmers->findOne(array('ip' => $farmer_ip));
	if (!isset($farmer)) {
		// welp...
		die('error');
	} else {
		$farmer_id = $farmer['_id'];
		if ($farmer['e'] == false) { // do not allow disabled farmers
			echo json_encode(array('jobs' => 0));
			die();
		}
	}
	
	// get new job!
	$get_jobs = $farmdb->jobs->find(array('s' => 0, 'o' => 1)); // get job with status 0 (waiting) and origin #1 (median)
	if ($get_jobs->count() > 0) {
		$get_jobs->sort(array('tsc' => 1))->limit(1); // get the oldest first
		$job = $get_jobs->getNext();
		$job['jid'] = ''.$job['_id'].'';
		$job['jobs'] = 1;
		
		// update job to be set to this farmer
		$updated_job = array();
		$updated_job['fid'] = $farmer_id;
		$updated_job['s'] = 1;
		$updated_job['tsu'] = time();
		$update_job = $farmdb->jobs->update(array('_id' => $job['_id']), array('$set' => $updated_job), array('safe' => true));
	} else {
		$job['jobs'] = 0;
	}
	
	// send job info along
	echo json_encode($job);
	
} else if ($action == 'u') {
	// update job!
	
	// get json of job id and new status and potential error message
	if (!isset($_POST['j']) || trim($_POST['j']) == '') {
		die('error, no POST');
	}
	
	$json = json_decode(trim($_POST['j']), true);
	
	if ($json == null || !is_array($json)) {
		die('error, invalid JSON');
	}
	
	if (!isset($json['jid']) || !isset($json['s'])) {
		die('error, no JID or status given');
	}
	
	$jobid = new MongoId($json['jid']);
	$job = $farmdb->jobs->findOne(array('_id' => $jobid));
	if (!isset($job)) {
		die('error, no job with that ID');
	}
	
	$mid = (int) $job['mid'] * 1;
	
	$updated_job = array();
	$updated_job['s'] = (int) $json['s'] * 1;
	$updated_job['tsu'] = time();
	if (isset($json['m']) && trim($json['m']) != '') {
		$updated_job['m'] = trim($json['m']);
	}
	if ($json['s'] * 1 == 2) {
		// how long did that take...?
		$how_long = $updated_job['tsu'] - $job['tsc'];
		$updated_job['hl'] = $how_long;
	}
	
	$update_job = $farmdb->jobs->update(array('_id' => $jobid), array('$set' => $updated_job), array('safe' => true));
	
	if ($json['s'] * 1 == 3) {
		// if error, send an email to median@emerson.edu
		include("Mail.php");
		$smtp_params["host"] = "owa.emerson.edu";
		$mailer = Mail::factory("smtp", $smtp_params);
		$subject = 'Median Mail! Error encoding entry...';
		$from = 'median@emerson.edu';
		$to = 'median@emerson.edu';
		$headers = array('From' => $from, 'Subject' => $subject);
		$body = 'There was an error encoding media entry #'.$mid.' (job id #'.$jobid.') from node '.$farmer_ip.' with message: '."\n\n".trim($json['m']);
		$mailer->send($to, $headers, $body);
	}
	
	if ($json['s'] * 1 == 2) {
		// ok now enable the media entry and also enable the path within that entry...
		$media_entry = $mdb->media->findOne(array('mid' => $mid));
		if (!isset($media_entry)) {
			die('error, no MID for that job');
		}
		$updated_media_entry = array();
		$updated_media_entry['en'] = true;
		$updated_media_entry['pa'] = array();
		$updated_media_entry['pa']['c'] = array();
		$updated_media_entry['pa']['in'] = $media_entry['pa']['in'];
		foreach ($media_entry['pa']['c'] as $media_path) {
			$new_media_path = $media_path;
			if (trim($new_media_path['p']) == trim($job['out'])) {
				$new_media_path['e'] = true;
				// get filesize
				error_reporting(0);
				$filesize_new = filesize($new_media_path['p']) * 1;
				error_reporting(1);
				$new_media_path['fs'] = $filesize_new;
			}
			$updated_media_entry['pa']['c'][] = $new_media_path;
		}
		// update!
		$update_media = $mdb->media->update(array('mid' => $mid), array('$set' => $updated_media_entry), array('safe' => true));
		// add an action log item... why not?
		require_once('../includes/activity_functions.php');
		$total_bitrate = $job['vb'] + $job['ab'];
		addNewAction(array('t' => 'encoded', 'mid' => $mid, 'uid' => 0, 'b' => $total_bitrate));
	}
	
	echo 'ok';
	
} else {
	
	echo 'uh';
	
}

/*

FARMER: 

Array(
	
	'n' => 'Eddard',			// friendly display name
	'hn' => 'median-node-34',	// proper hostname
	'ip' => '199.94.92.91',		// ip address
	'e' => 1,					// enabled or not -- index'd
	'tsc' => 1344350435,		// when created
	'tsh' => 1344350435			// last heartbeat -- index'd
	
)

farming job object in mongo

Array(
	
	'mid' => 20002,					// media ID this is for (if any) -- index'd
	'p' => 1,						// priority (1 for median, 2 for anything else) -- index'd
	'o' => 1,						// origin (1 for median, 2 for transcode farm) -- index'd
	's' => 1,						// current status code -- index'd
	'fid' => MongoId('901ao21j'),	// farmer mongo ID (if any)
	'in' => '/median/in..',			// file input
	'out' => '/median/out...',		// file output -- index'd
	'vw' => 1280,					// desired video max width
	'vh' => 720,					// desired video max height
	'vb' => 1200,					// desired video bitrate
	'ab' => 128,					// desired audio bitrate
	'tsc' => 1344333443,			// time created
	'tsu' => 1393939222,			// last updated -- index'd
	'm' => 'error info?',			// error message info, if needed
	
)

*/

?>