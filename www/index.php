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
			<div class="twelve columns">
				<?php
				
				// if logged in, show user's pending/completed stuff here
				
				$entries = getUserEntries($current_user['username']);
				if ($entries == false || count($entries) == 0) {
					echo '<p>You do not have any entries uploaded at this time.</p>';
				} else {
					foreach ($entries as $entry) {
						//echo '<pre>'.print_r($entry, true).'</pre>';
						echo '<div class="entry">'."\n";
						echo '<p><b>'.$entry['fn'].'</b> - created '.date('m-d-Y h:ia', $entry['tsc']).'</p>'."\n";
						foreach ($entry['pa']['c'] as $filejob) {
							echo '<p>'.bitrateToFriendly($filejob['b']).' - '.(($filejob['e'] == false) ? 'not ready' : 'ready!').'</p>'."\n";
						}
						if (isset($entry['ex']) && $entry['ex'] * 1 > 0) {
							echo '<p>Entry will expire in '.getRelativeTime($entry['ex']).', exactly: '.date('m-d-Y h:ia', $entry['ex']).'.</p>'."\n";
						} else {
							echo '<p>Entry will expire 48 hours after all versions have been transcoded.</p>'."\n";
						}
						echo '</div>'."\n";
					}
				}
				
				?>
				<p><a href="/farming.php">Public Farm Status Page</a></p>
				<?php if ($current_user['userlevel'] == 1) { ?><p><a href="/admin/farming.php">Admin Farm Status Page</a></p><?php } ?>
				<?php if ($current_user['loggedin'] == true) { ?><p><a href="/upload/">Upload Videos!</a></p><?php } ?>
			</div>
		</div>
		
		<!-- end unique page content -->
	
<?php require_once('pagepieces/footer.php'); ?>
	
	</div>

<?php require_once('pagepieces/foot.php'); ?>

</body>
</html>