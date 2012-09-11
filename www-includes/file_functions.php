<?php

/*

	FUNCTIONS THAT HAVE TO DO WITH FILES
		cyle gage, emerson college, 2012
	
	
	getVideoFileInfo([string] $path, [bool] $debug)
	isVideoFlashCompatible([array] $video_info)
	
*/

require_once('dbconn_mongo.php');
require_once('../config/config.php');

function getVideoFileInfo($path = '', $debug = false) {
	// run ffprobe and get video file info for the path provided
	
	if (!isset($path) || trim($path) == '') {
		return false;
	}
	
	global $ffprobe_cmd;
	
	$path = trim($path);
	
	$ffprobe_result = shell_exec($ffprobe_cmd.' '.$path.' 2>&1 1> /dev/null');
		
	if (preg_match('/no such file/i', $ffprobe_result) != 0) {
		return -100;
	}
	
	// ok, get bitrate and duration info
	if (preg_match('/Duration: ([^,]+).*bitrate: (\d+ \S+)/i', $ffprobe_result, $matches1)) {
		$duration_raw = $matches1[1];
		$duration_pieces = explode(':', $duration_raw);
		$seconds = ($duration_pieces[0]*60*60) + ($duration_pieces[1]*60) + (round($duration_pieces[2]));
		$bitrate_raw = $matches1[2];
		preg_match('/(\d+) (.+)/', $bitrate_raw, $bitrate_pieces);
		$bitrate_kbps = $bitrate_pieces[1];
	} else {
		return -101;
	}
	
	if (preg_match('/Video: (.+)/i', $ffprobe_result, $matches2)) {
		$video_params = explode(',', $matches2[1]);
		//print_r($video_params);
		$video_codec = trim($video_params[0]);
		preg_match('/(\d+)x(\d+)/i', $video_params[2], $dimension_results);
		$video_width = $dimension_results[1];
		$video_height = $dimension_results[2];
		preg_match('/(\d+) kb\/s/', $video_params[3], $video_bitrate_results);
		if (isset($video_bitrate_results[1])) {
			$video_bitrate = $video_bitrate_results[1];
		} else {
			$video_bitrate = 0;
		}
	} else {
		return -102;
	}
	
	if (preg_match('/prores/i', $video_codec) || strtolower($video_codec) == 'apple intermediate codec' || preg_match('/xdcam/i', $video_codec) || strtolower($video_codec) == 'hdv 720p24') {
		// apple-proprietary footage, no good
		return -103;
	}
	
	if (preg_match('/stream 0, error opening file/i', $ffprobe_result)) {
		// quicktime ref file, most likely
		return -104;
	}
	
	if (preg_match('/Audio: ([^,]+), (\d+) \S+, [^,]*, [^,]*, (\d+).*/i', $ffprobe_result, $matches3)) {
		$audio_codec = $matches3[1];
		$audio_samplerate = $matches3[2];
		$audio_bitrate = $matches3[3];
	} else {
		$audio_codec = 'none';
	}
	
	$media_info = array();
	$media_info['d'] = (int) $seconds * 1;
	$media_info['b'] = (int) $bitrate_kbps * 1;
	$media_info['vb'] = (int) $video_bitrate * 1;
	$media_info['ab'] = (int) $audio_bitrate * 1;
	$media_info['vc'] = strtolower(trim($video_codec));
	$media_info['ac'] = strtolower(trim($audio_codec));
	$media_info['vw'] = (int) $video_width * 1;
	$media_info['vh'] = (int) $video_height * 1;
	if ($debug) {
		$media_info['debug'] = $ffprobe_result;
	}
	
	return $media_info;
	
}

?>