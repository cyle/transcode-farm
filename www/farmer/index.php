<?php

// farming API for farmers

if (!isset($_GET['t']) || trim($_GET['t']) == '') {
	die('nope');
}

require_once('../../config/config.php');

$action = strtolower(trim($_GET['t']));

// get requester's IP
$farmer_ip = '';
if (isset($_SERVER['REMOTE_ADDR']) && trim($_SERVER['REMOTE_ADDR']) != '' && trim($_SERVER['REMOTE_ADDR']) != '127.0.0.1') {
	$farmer_ip = trim($_SERVER['REMOTE_ADDR']); // ip address of viewer
} else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && trim($_SERVER['HTTP_X_FORWARDED_FOR']) != '' && trim($_SERVER['HTTP_X_FORWARDED_FOR']) != '127.0.0.1') {
	$farmer_ip = trim($_SERVER['HTTP_X_FORWARDED_FOR']); // ip address of viewer
}

require_once('../../www-includes/dbconn_mongo.php');

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
		if ($farmer['e'] == false) { // do not allow disabled farmers
			echo json_encode(array('jobs' => 0));
			die();
		}
		$farmer_id = $farmer['_id'];
	}
	
	// get new job
	$get_jobs_query = array('s' => 0, 'o' => 2); // get job with status 0 (waiting) and origin #2 (general transcoding)
	
	// if farmer tier level is set, use it to determine the max they can do
	if (isset($farmer['t']) && $farmer['t'] * 1 == 1) {
		// so low tier jobs only
		$get_jobs_query_low = $get_jobs_query;
		$get_jobs_query_low['vb'] = array('$lte' => 600);
		$get_jobs = $farmdb->jobs->find($get_jobs_query_low);
		if ($get_jobs->count() == 0) { // if you can't find any for low bitrate, go ahead and try something bigger
			unset($get_jobs);
			$get_jobs = $farmdb->jobs->find($get_jobs_query);
		}
	} else {
		$get_jobs = $farmdb->jobs->find($get_jobs_query);
	}
	
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
		// if error, send an email to admins
		require_once('Mail.php');
		$smtp_params['host'] = $mail_smtp_server;
		$mailer = Mail::factory('smtp', $smtp_params);
		$subject = '[Farm Mail] Error encoding entry...';
		$from = $admin_email;
		$to = $admin_email;
		$headers = array('From' => $from, 'Subject' => $subject);
		$body = 'There was an error encoding entry #'.$job['eid'].' (job id #'.$jobid.') from node '.$farmer_ip.' with message: '."\n\n".trim($json['m']);
		$mailer->send($to, $headers, $body);
	}
	
	if ($json['s'] * 1 == 2) {
		// ok now enable the path within the entry...
		$entry = $farmdb->entries->findOne(array('_id' => $job['eid']));
		if (!isset($entry)) {
			die('error, no master entry for that job');
		}
		$updated_entry = array();
		$updated_entry['pa'] = array();
		$updated_entry['pa']['c'] = array();
		$updated_entry['pa']['in'] = $entry['pa']['in'];
		foreach ($entry['pa']['c'] as $media_path) {
			$new_media_path = $media_path;
			if (trim($new_media_path['p']) == trim($job['out'])) {
				$new_media_path['e'] = true;
				// get filesize
				error_reporting(0);
				$filesize_new = filesize($new_media_path['p']) * 1;
				error_reporting(1);
				$new_media_path['fs'] = $filesize_new;
			}
			$updated_entry['pa']['c'][] = $new_media_path;
		}
		// update!
		$update_entry = $farmdb->entries->update(array('_id' => $job['eid']), array('$set' => $updated_entry), array('safe' => true));
		// send the user some mail about their new entry
		require_once('Mail.php');
		$smtp_params['host'] = $mail_smtp_server;
		$mailer = Mail::factory('smtp', $smtp_params);
		$subject = '[Farm Mail] Done encoding one version!';
		$from = $admin_email;
		$to = $entry['em'];
		$headers = array('From' => $from, 'Subject' => $subject);
		$body = 'One version of your Open Transcoding Farm entry "'.$entry['fn'].'" has finished encoding. Please log in to '.$home_url.' to download the file!'."\n\n".' - FarmBot';
		$mailer->send($to, $headers, $body);
		/*
		
		see if all the entries are done. if so, start the timer...
		
		*/
		unset($entry, $media_path); // clear these just in case
		$entry = $farmdb->entries->findOne(array('_id' => $job['eid']));
		$all_enabled = true;
		foreach ($entry['pa']['c'] as $media_path) {
			if (!isset($media_path['e']) || $media_path['e'] == false) {
				$all_enabled = false;
			}
		}
		if ($all_enabled) {
			$set_expiry = $farmdb->entries->update(array('_id' => $job['eid']), array('$set' => array('ex' => strtotime('+'.$expire_hours.' hours'))), array('safe' => true));
			$subject = '[Farm Mail] Your files are ready!';
			$headers = array('From' => $from, 'Subject' => $subject);
			$body = 'All versions of your Open Transcoding Farm entry "'.$entry['fn'].'" have finished encoding. Please log in to '.$home_url.' to download the files! They will expire and be automatically deleted from the system in '.$expire_hours.' hours.'."\n\n".' - FarmBot';
			$mailer->send($to, $headers, $body);
		}
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
	't' => 0,					// tier level of hardware, 0 is any input, 1 is low-end only (for VMs, for example)
	
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