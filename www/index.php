<?php

/*

	THE FARM
		cyle gage, emerson college, 2012

*/

$login_required = false;
require_once('../www-includes/login_check.php');

require_once('../www-includes/entry_functions.php');

?>
<?php
$page_title = 'The Open Transcoding Farm';
require_once('pagepieces/head.php');
?>
</head>
<body>

	<div class="container" id="index-page">

<?php 
$where_are_we = 'index';
require_once('pagepieces/header.php');
?>

		<!-- start unique page content -->
		
		<div class="row">
			<div class="three columns" id="left-nav">
				<p><a href="/upload/" class="button large radius success">Upload Videos &raquo;</a></p>
				<p><a href="/farming.php" class="button large radius">Public Farm Status Page &raquo;</a></p>
				<?php if ($current_user['userlevel'] == 1) { ?><p><a href="/admin/farming.php" class="button large radius secondary">Admin Farm Status Page &raquo;</a></p><?php } ?>
			</div>
			<div class="nine columns">
				<?php
				// if logged in, show user's pending/completed stuff here
				if ($current_user['loggedin']) {
					echo '<h3>Your Entries</h3>'."\n";
					$entries = getUserEntries($current_user['username']);
					if ($entries == false || count($entries) == 0) {
						echo '<p>You do not have any entries uploaded at this time.</p>';
					} else {
						foreach ($entries as $entry) {
							//echo '<pre>'.print_r($entry, true).'</pre>';
							echo '<div class="entry">'."\n";
							echo '<p><b>'.$entry['fn'].'</b> - created '.date('m-d-Y h:ia', $entry['tsc']).'</p>'."\n";
							foreach ($entry['pa']['c'] as $filejob) {
								echo '<p>'.bitrateToFriendly($filejob['b']).' - '.(($filejob['e'] == false) ? 'not ready' : 'ready! <a class="button small success radius" href="/download/'.$entry['_id'].'/'.$filejob['b'].'/">Download!</a>').'</p>'."\n";
							}
							if (isset($entry['ex']) && $entry['ex'] * 1 > 0) {
								echo '<p>Entry will expire in '.getRelativeTime($entry['ex']).', exactly: '.date('m-d-Y h:ia', $entry['ex']).'.</p>'."\n";
							} else {
								echo '<p>Entry will expire 48 hours after all versions have been transcoded.</p>'."\n";
							}
							echo '</div>'."\n";
						}
					}
				}
				?>
			</div>
		</div>
		
		<!-- end unique page content -->
	
<?php require_once('pagepieces/footer.php'); ?>
	
	</div>

<?php require_once('pagepieces/foot.php'); ?>

</body>
</html>