<?php

/*

	FUNCTIONS THAT HAVE TO DO WITH FARMING
		cyle gage, emerson college, 2012
	
	
	getFarmingStatus($jid)
	getFarmingStatusByOutPath($path)
	addFarmingJob($jid, $paths, $options)
	
*/

require_once('dbconn_mongo.php');

// presets...
$tiers = array(
	'ultra' => array('vb' => 1700, 'vw' => 1280, 'vh' => 720, 'ab' => 128),
	'high' => array('vb' => 1200, 'vw' => 1280, 'vh' => 720, 'ab' => 128),
	'medium' => array('vb' => 600, 'vw' => 720, 'vh' => 480, 'ab' => 96),
	'small' => array('vb' => 300, 'vw' => 400, 'vh' => 260, 'ab' => 64)
);

function getFarmingStatus($jid = '') {
	// get full status of media being transcoded for this ID
	
	if (!isset($jid) || trim($jid) == '') {
		return false;
	}
	
	if (gettype($jid) == 'object' && get_class($jid) == 'MongoId') {
		$jid = $jid;
	} else if (gettype($jid) == 'string') {
		$jid = new MongoId($jid);
	} else {
		return false;
	}
	
	global $farmdb;
	
	$jobs = array();
	
	$find_jobs = $farmdb->jobs->find(array('jid' => $jid));
	if ($find_jobs->count() > 0) {
		foreach ($find_jobs as $job) {
			$jobs[] = $job;
		}
	}
	
	return $jobs;
	
}

function getFarmingStatusByOutPath($path = '') {
	// get the latest status of a particular farming job by its OUT path
	
	if (!isset($path) || trim($path) == '') {
		return false;
	}
	
	$path = trim($path);
	
	global $farmdb;
	$m->setSlaveOkay();
	
	$status = 'Unknown';
	
	$find_jobs = $farmdb->jobs->find(array('out' => $path));
	if ($find_jobs->count() == 0) {
		// no job found for that out path, whoops
		$status = 'No job found for that path.';
		return $status;
	} else if ($find_jobs->count() == 1) {
		$thejob = $find_jobs->getNext();
	} else {
		// sort by latest, take the first one
		$find_jobs->sort(array('tsu' => -1));
		$thejob = $find_jobs->getNext();
	}
	
	if (!isset($thejob['s']) || !is_numeric($thejob['s'])) {
		$status = 'No status found for that path.';
		return $status;
	}
	
	switch ($thejob['s']) {
		case 0:
		$status = 'Pending';
		break;
		case 1:
		$status = 'Transcoding';
		break;
		case 2:
		$status = 'Finished';
		break;
		case 3:
		$status = 'Error';
		break;
		default:
		$status = 'Unknown';
	}
	
	return $status;
}

function addFarmingJob($jid = '', $paths = array(), $options = '') {
	// add a new farming job for jid with options...
	// if options is a string, use that as a preset
	// if options is an array, use those explicit settings
	
	if (!isset($jid) || trim($jid) == '') {
		return false;
	}
	
	if (gettype($jid) == 'object' && get_class($jid) == 'MongoId') {
		$jid = $jid;
	} else if (gettype($jid) == 'string') {
		$jid = new MongoId($jid);
	} else {
		return false;
	}
	
	if (!isset($paths) || !is_array($paths)) {
		return false;
	}
	
	if (!isset($paths['in']) || !isset($paths['out'])) {
		return false;
	}
	
	global $tiers;
	
	$transcode_options = array();
	
	$tier_keys = array_keys($tiers);
	
	if (isset($options) && is_string($options) && in_array(strtolower($options), $tier_keys))  {
		// use the provided preset option
		$transcode_options = $tiers[strtolower($options)];
	} else if (isset($options) && is_array($options)) {
		$transcode_options = $options;
	} else {
		return false;
	}
	
	global $farmdb;
	
	$new_job = array();
	$new_job['jid'] = $jid;
	$new_job['p'] = 100; // priority 100 for general jobs
	$new_job['o'] = 2; // origin id #2 for general transcode farm
	$new_job['s'] = 0; // status of 0 for new jobs
	$new_job['fid'] = 0; // unknown farmer ID as yet
	$new_job['in'] = trim($paths['in']);
	$new_job['out'] = trim($paths['out']);
	$new_job['vw'] = (int) $transcode_options['vw'] * 1;
	$new_job['vh'] = (int) $transcode_options['vh'] * 1;
	$new_job['vb'] = (int) $transcode_options['vb'] * 1;
	$new_job['ab'] = (int) $transcode_options['ab'] * 1;
	$new_job['tsc'] = time();
	$new_job['tsu'] = time();
	
	// ok, add row
	try {
		$result = $farmdb->jobs->insert($new_job, array('safe' => true));
	} catch(MongoCursorException $e) {
		return false;
	}
	
	return true;
	
}

/*

farmer object in mongo

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
	
	'jid' => MongoId('awnda'),		// transcoding farm job ID
	'p' => 100,						// priority (1 for median, 100 for transcode farm) -- index'd
	'o' => 2,						// origin (1 for median, 2 for transcode farm) -- index'd
	's' => 1,						// current status code -- index'd (0 for pending, 1 for being transcoded, 2 for done, 3 for error)
	'fid' => MongoId('901ao21j'),	// farmer mongo ID (if any)
	'in' => '/master/path/in..',	// file input
	'out' => '/master/path/out...',	// file output -- index'd
	'vw' => 1280,					// desired video max width
	'vh' => 720,					// desired video max height
	'vb' => 1200,					// desired video bitrate
	'ab' => 128,					// desired audio bitrate
	'tsc' => 1344333443,			// time created
	'tsu' => 1393939222				// last updated -- index'd
	'm' => 'error message',			// error message (if any)
	
)

*/

?>