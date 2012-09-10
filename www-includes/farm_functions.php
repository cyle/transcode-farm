<?php

/*

	FUNCTIONS THAT HAVE TO DO WITH FARMING
		cyle gage, emerson college, 2012
	
	
	getFarmingStatus($mid)
	getFarmingStatusByOutPath($path)
	addFarmingJob($mid, $paths, $options)
	
*/

require_once('dbconn_mongo.php');

// presets...
$tiers = array(
	'ultra' => array('vb' => 1700, 'vw' => 1280, 'vh' => 720, 'ab' => 128),
	'high' => array('vb' => 1200, 'vw' => 1280, 'vh' => 720, 'ab' => 128),
	'medium' => array('vb' => 600, 'vw' => 720, 'vh' => 480, 'ab' => 96),
	'small' => array('vb' => 300, 'vw' => 400, 'vh' => 260, 'ab' => 64)
);

function getFarmingStatus($mid = 0) {
	// get full status of all media being transcoded for the MID
	
	if (!isset($mid) || !is_numeric($mid) || $mid < 1) {
		return false;
	}
	
	$mid = (int) $mid * 1;
	
	global $m;
	$farmdb = $m->farm;
	
	$jobs = array();
	
	$find_jobs = $farmdb->jobs->find(array('mid' => $mid));
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
	
	global $m;
	$farmdb = $m->farm;
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

function addFarmingJob($mid = 0, $paths = array(), $options = '') {
	// add a new farming job for mid with options...
	// if options is a string, use that as a preset
	// if options is an array, use those explicit settings
	
	global $tiers;
	
	if (!isset($mid) || !is_numeric($mid) || $mid < 1) {
		return false;
	}
	
	$mid = (int) $mid * 1;
	
	if (!isset($paths) || !is_array($paths)) {
		return false;
	}
	
	if (!isset($paths['in']) || !isset($paths['out'])) {
		return false;
	}
	
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
	
	global $m;
	$farmdb = $m->farm;
	
	$new_job = array();
	$new_job['mid'] = $mid;
	$new_job['p'] = 1; // priority 1 for median jobs
	$new_job['o'] = 1; // origin id #1 for median
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
	'tsu' => 1393939222				// last updated -- index'd
	
)

*/

?>