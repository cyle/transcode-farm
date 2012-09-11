#!/usr/bin/php
<?php

// you can disable this feature if you want...
$delete_expired = true;
$file_out_path = '/farm/files/out/';

if (!defined('STDIN') || php_sapi_name() != 'cli' || !empty($_SERVER['REMOTE_ADDR'])) {
	die('This is only usable via CLI.');
}

if (!$delete_expired) {
	die(); // we are not deleting expired videos, apparently
}

require_once('../www-includes/dbconn_mongo.php');

$now = time();

$get_expired_entries = $farmdb->entries->find( array( 'ex' => array( '$lte' => $now ) ) );

if ($get_expired_entries->count() > 0) {
	
	foreach ($get_expired_entries as $expired_entry) {
		
		echo 'removing '.$expired_entry['_id']."\n";
		
		$updated_entry = array();
		$updated_entry['e'] = false; // disable the entry
		$updated_entry['tsu'] = time();
		
		// delete original in file
		if (file_exists($expired_entry['pa']['in'])) {
			echo 'removing '.$expired_entry['pa']['in']."\n";
			unlink($expired_entry['pa']['in']);
		}
		
		// go through and delete each version
		foreach ($expired_entry['pa']['c'] as $out_job) {
			if (file_exists($out_job['p'])) {
				echo 'removing '.$out_job['p']."\n";
				unlink($out_job['p']);
			}
		}
		$folder = $file_out_path.$expired_entry['_id'];
		echo 'removing folder '.$folder."\n";
		rmdir($folder);
		
		// update entry
		$update = $farmdb->entries->update( array('_id' => $expired_entry['_id']), array('$set' => $updated_entry), array('safe' => true) );
		// remove file references from entry
		$unset = $farmdb->entries->update( array('_id' => $expired_entry['_id']), array('$unset' => array('pa' => 1) ), array('safe' => true) );
		
		echo 'removed entry'."\n";
		
	}
	
}

?>