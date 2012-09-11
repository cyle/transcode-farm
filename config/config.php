<?php

/*

	FARMING CONFIG FILE
		This affects the web stuff and the cron job.


	NOTES:
		- include trailing slash on all directory paths

*/

// for permalinks
$home_url = 'http://farm.emerson.edu/'; // the URL to the Farm

// paths + files
$farm_files_path = '/farm/files/'; // where to store files, needs "in" and "out" subdirectories
$logs_dir = '/farm/logs/'; // where to store log files
$error_page = '/farm/www/error.php'; // the error page utilized for reporting problems
$ffprobe_cmd = '/usr/bin/ffprobe'; // need this to get video information

// mail settings
$admin_email = 'cyle_gage@emerson.edu'; // set this to whoever gets error emails + who to send as
$mail_smtp_server = 'owa.emerson.edu'; // what SMTP server to use

// remove_expired.php cron job settings
$delete_expired = true; // you don't actually have to delete expired, but you probably should
$expire_hours = 48; // how many hours before expiring the finished entries

/*

	this is the "default user" when someone is not logged in
	
	how you log users in and set this in login_check.php is up to you

*/

$current_user = array(
	'loggedin' => false,
	'username' => 'nobody',
	'userlevel' => 6
);

?>