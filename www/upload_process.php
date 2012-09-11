<?php

// this script processes the uploaded files

require_once('../config/config.php');
require_once('../www-includes/error_functions.php');
require_once('../www-includes/log_functions.php');

if (!isset($_POST['j']) || trim($_POST['j']) == '') {
	batchBailout('No POST data given.');
}

$json = trim($_POST['j']);
$upload_info = json_decode($json, true);

if ($upload_info == null) {
	batchBailout('Invalid JSON data given.', null, null, $json);
}

if (count($upload_info) == 0) {
	batchBailout('Batch array had no items.', null, null, oneLinePrintArray($upload_info));
}

/*

$upload_info will look like...

Array( 
	[0] => Array(
		"name" => "whatever.mp4",
		"path" => "/farm/upload_tmp/amwdawd.mp4",
		"username" => "cyle_gage",
		"presets" => "high,medium,small"
	),
	[1] => ....
)

*/

//die(json_encode(array('info' => $upload_info)));

require_once('../www-includes/farm_functions.php');
require_once('../www-includes/file_functions.php');
require_once('../www-includes/entry_functions.php');

$return_info = array();

foreach ($upload_info as $batched) {
	
	// see if a name is given
	if (isset($batched['name']) && trim($batched['name']) != '') {
		$batch_title = trim($batched['name']);
	} else {
		$batch_title = 'Unknown File';
	}
	
	if (!isset($batched['username']) || trim($batched['username']) == '') {
		$result_status = 200;
		$result_message = 'No username was given for the entry.';
		$return_info[] = array('title' => $batch_title, 'eid' => 0, 'status' => $result_status, 'status_message' => $result_message);
		continue;
	}
	
	$username = strtolower(trim($batched['username']));
	
	// make sure path is set for each
	if (!isset($batched['path']) || trim($batched['path']) == '') {
		$result_status = 200;
		$result_message = 'The file was not uploaded successfully.';
		$return_info[] = array('title' => $batch_title, 'eid' => 0, 'status' => $result_status, 'status_message' => $result_message);
		continue;
	}
	
	$batch_path = trim($batched['path']);
	
	// check for what presets to do
	$selected_presets = array();
	if (isset($batched['presets']) && trim($batched['presets']) != '') {
		$presets = explode(',', trim($batched['presets']));
		foreach ($presets as &$preset) {
			$preset = strtolower(trim($preset));
		}
		$selected_presets = array_unique($presets);
	} else {
		// if nothing was given, try doing all of them, i guess
		$selected_presets = array('max', 'ultra', 'high', 'medium', 'small');
	}
	
	// clear some variables
	$result_status = 0; // the final status of the upload
	$result_message = 'Nothing yet.'; // the message to go along with the status
	
	// make sure it's a valid file type
	$uploaded_extension = strtolower(strrchr($batch_path, '.'));
	$video_extensions = array('.flv', '.avi', '.wmv', '.divx', '.dv', '.mov', '.mp4', '.mpeg', '.mpg');	

	// check to make sure it's a supported video extension
	if (!in_array($uploaded_extension, $video_extensions)) {
		$result_status = 200;
		$result_message = 'The file uploaded is an unknown or unsupported file type.';
		$return_info[] = array('title' => $batch_title, 'eid' => 0, 'status' => $result_status, 'status_message' => $result_message);
		continue;
	}
	// ok so at least it's a valid type
		
	// let's run some checks first before we actually move the file
	$video_info = getVideoFileInfo($batch_path);
	if (!is_array($video_info)) {
		// there was an error of some kind!
		$video_error = $video_info;
		switch ($video_error) {
			case -100: // no such file
			$result_status = 200;
			$result_message = 'There was an error checking the file.';
			break;
			case -101: // no duration or bitrate info
			$result_status = 200;
			$result_message = 'The server could not retrieve duration or bitrate information.';
			break;
			case -102: // no video info
			$result_status = 200;
			$result_message = 'The server could not retrieve any video information.';
			break;
			case -103: // apple proprietary footage
			$result_status = 200;
			$result_message = 'The file was encoded with Apple Proprietary codecs, so it cannot be transcoded.';
			break;
			case -104: // quicktime reference file, or otherwise no streams
			$result_status = 200;
			$result_message = 'The server could not retrieve any video information, possibly because the file is a Quicktime Reference file.';
			break;
		}
		writeToLog('ERROR: '.$result_message, $batch_log, $uid);
		$return_info[] = array('title' => $batch_title, 'eid' => 0, 'status' => $result_status, 'status_message' => $result_message);
		continue;
	}
	
	/*

		if it survived that, let's actually do things with it!

	*/
	
	$updated_entry = array(); // this is what we'll be updating the media document with
	
	$result_status = 100; // totally cool, we can use the file!
	$result_message = 'File uploaded successfully.';
	
	// generate a new entry ID for this
	$eid = generateNewEntry($username, $batch_title);
	
	if (!$eid) {
		$result_status = 200;
		$result_message = 'Could not generate a new entry ID for this.';
		$return_info[] = array('title' => $batch_title, 'eid' => 0, 'status' => $result_status, 'status_message' => $result_message);
		continue;
	}
		
	// determine hashes and file in location
	$folder_path_in = $farm_files_path.'in/';
	$folder_path_out = $farm_files_path.'out/'.$eid.'/';
	$file_path_in = $folder_path_in.$eid.$uploaded_extension;
		
	// if the folders don't exist, make it! www-data must have write permission to /farm/files and all subdirs
	if (!file_exists($folder_path_in)) {
		mkdir($folder_path_in, 0775, true);
		chmod($folder_path_in, 0775);
	}
	
	if (!file_exists($folder_path_out)) {
		mkdir($folder_path_out, 0775, true);
		chmod($folder_path_out, 0775);
	}
	
	// ok, now move the file
	$move_file = rename($batch_path, $file_path_in);
	
	if (!$move_file) {
		// uh oh, could not move the file for some reason!
		$result_status = 200;
		$result_message = 'There was an error initially moving the file, uh oh.';
		$return_info[] = array('title' => $batch_title, 'eid' => $eid, 'status' => $result_status, 'status_message' => $result_message);
		continue;
	}
		
	// there will be an array of media paths
	$media_paths = array();
	
	// what versions will actually be made
	$versions_queued = array();
	
	// check how many versions to make
	$video_make_max = true;
	$video_make_ultra = true;
	$video_make_high = true;
	$video_make_medium = true;
	$video_make_small = true;
	
	if ($video_info['vw'] < 1280) {
		$video_make_max = false;
		$video_make_ultra = false;
		$video_make_high = false;
	} else {
		if ($video_info['b'] < 1800) {
			$video_make_max = false;
			$video_make_ultra = false;
		} else if ($video_info['b'] < 2200) {
			$video_make_max = false;
		}
	}
	
	$media_original_ratio = $video_info['vw']/$video_info['vh'];
	
	if ($video_make_max && in_array('max', $selected_presets)) {
		//writeToLog('Making a MAX version.', $batch_log, $uid, $eid);
		$tier_max_bitrate = $tiers['max']['vb'] + $tiers['max']['ab'];
		$file_path_max_out = $folder_path_out.$eid.'_'.$tier_max_bitrate.'kbps.mp4';
		$media_max_height = round($tiers['max']['vw']/$media_original_ratio);
		$farm_result = addFarmingJob($eid, array('in' => $file_path_in, 'out' => $file_path_max_out), 'max');
		if (!$farm_result) {
			//writeToLog('Could not add MAX farming job. Weird.', $batch_log, $uid, $eid);
		} else {
			$media_paths[] = array('b' => $tier_max_bitrate, 'p' => $file_path_max_out, 'w' => $tiers['max']['vw'], 'h' => $media_max_height, 'e' => false);
			$versions_queued[] = 'max';
		}
	}
	
	if ($video_make_ultra && in_array('ultra', $selected_presets)) {
		//writeToLog('Making an ULTRA version.', $batch_log, $uid, $eid);
		$tier_ultra_bitrate = $tiers['ultra']['vb'] + $tiers['ultra']['ab'];
		$file_path_ultra_out = $folder_path_out.$eid.'_'.$tier_ultra_bitrate.'kbps.mp4';
		$media_ultra_height = round($tiers['ultra']['vw']/$media_original_ratio);
		$farm_result = addFarmingJob($eid, array('in' => $file_path_in, 'out' => $file_path_ultra_out), 'ultra');
		if (!$farm_result) {
			//writeToLog('Could not add ULTRA farming job. Weird.', $batch_log, $uid, $eid);
		} else {
			$media_paths[] = array('b' => $tier_ultra_bitrate, 'p' => $file_path_ultra_out, 'w' => $tiers['ultra']['vw'], 'h' => $media_ultra_height, 'e' => false);
			$versions_queued[] = 'ultra';
		}
	}
	
	if ($video_make_high && in_array('high', $selected_presets)) {
		//writeToLog('Making an HIGH version.', $batch_log, $uid, $eid);
		$tier_high_bitrate = $tiers['high']['vb'] + $tiers['high']['ab'];
		$file_path_high_out = $folder_path_out.$eid.'_'.$tier_high_bitrate.'kbps.mp4';
		$media_high_height = round($tiers['high']['vw']/$media_original_ratio);
		$farm_result = addFarmingJob($eid, array('in' => $file_path_in, 'out' => $file_path_high_out), 'high');
		if (!$farm_result) {
			//writeToLog('Could not add HIGH farming job. Weird.', $batch_log, $uid, $eid);
		} else {
			$media_paths[] = array('b' => $tier_high_bitrate, 'p' => $file_path_high_out, 'w' => $tiers['high']['vw'], 'h' => $media_high_height, 'e' => false);
			$versions_queued[] = 'high';
		}
	}
	
	if ($video_make_medium && in_array('medium', $selected_presets)) {
		//writeToLog('Making an MEDIUM version.', $batch_log, $uid, $eid);
		$tier_medium_bitrate = $tiers['medium']['vb'] + $tiers['medium']['ab'];
		$file_path_medium_out = $folder_path_out.$eid.'_'.$tier_medium_bitrate.'kbps.mp4';
		$media_medium_height = round($tiers['medium']['vw']/$media_original_ratio);
		$farm_result = addFarmingJob($eid, array('in' => $file_path_in, 'out' => $file_path_medium_out), 'medium');
		if (!$farm_result) {
			//writeToLog('Could not add MEDIUM farming job. Weird.', $batch_log, $uid, $eid);
		} else {
			$media_paths[] = array('b' => $tier_medium_bitrate, 'p' => $file_path_medium_out, 'w' => $tiers['medium']['vw'], 'h' => $media_medium_height, 'e' => false);
			$versions_queued[] = 'medium';
		}
	}
	
	if ($video_make_small && in_array('small', $selected_presets)) {
		//writeToLog('Making an SMALL version.', $batch_log, $uid, $eid);
		$tier_small_bitrate = $tiers['small']['vb'] + $tiers['small']['ab'];
		$file_path_small_out = $folder_path_out.$eid.'_'.$tier_small_bitrate.'kbps.mp4';
		$media_small_height = round($tiers['small']['vw']/$media_original_ratio);
		$farm_result = addFarmingJob($eid, array('in' => $file_path_in, 'out' => $file_path_small_out), 'small');
		if (!$farm_result) {
			//writeToLog('Could not add SMALL farming job. Weird.', $batch_log, $uid, $eid);
		} else {
			$media_paths[] = array('b' => $tier_small_bitrate, 'p' => $file_path_small_out, 'w' => $tiers['small']['vw'], 'h' => $media_small_height, 'e' => false);
			$versions_queued[] = 'small';
		}
	}
	
	// add duration to the media entry
	$updated_entry['du'] = $video_info['d'];
	
	// add the paths to the updated entry
	$updated_entry['pa'] = array();
	$updated_entry['pa']['in'] = $file_path_in;
	$updated_entry['pa']['c'] = $media_paths;
	
	// update timestamp
	$updated_entry['tsu'] = time();
	
	// enable it
	$updated_entry['e'] = true;
	
	// write the contact email address
	$updated_entry['em'] = $username.'@emerson.edu';
	
	//writeToLog('Updated entry info: '.oneLinePrintArray($updated_entry), $batch_log, $uid, $eid);
	
	$eid_db = new MongoId($eid);
	
	// update entry document with media paths!
	try {
		$update = $farmdb->entries->update(array('_id' => $eid_db), array('$set' => $updated_entry), array('safe'=>true));
	} catch(MongoCursorException $e) {
		// error.....
		$result_status = 200;
		$result_message = 'Error updating the Mongo record.';
		//writeToLog('ERROR: Could not update Mongo for some reason.', $batch_log, $uid, $eid);
	}
	
	$return_info[] = array('title' => $batch_title, 'eid' => $eid, 'status' => $result_status, 'status_message' => $result_message, 'versions' => $versions_queued);
}

// return an array of media IDs for each...
if (count($return_info) == 0) {
	echo json_encode(array('error' => 'No media actually uploaded.'));
	//writeToLog('ERROR: No media actually uploaded, weird.', $batch_log, $uid, $eid);
} else {
	echo json_encode($return_info);
	//writeToLog('Info returned to client: '.oneLinePrintArray($return_info), $batch_log, $uid, $eid);
}

?>
