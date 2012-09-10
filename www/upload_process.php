<?php

// this script processes the uploaded files

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

// do stuff !

?>
