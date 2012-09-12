<?php

/*

	THE FARM
		cyle gage, emerson college, 2012

*/

$login_required = true;
require_once('../../www-includes/login_check.php');

if ($current_user['userlevel'] != 1) {
	die('You do not have permission to view this page.');
}

?>
<?php
$page_title = 'Admin';
require_once('../pagepieces/head.php');
?>
</head>
<body>

	<div class="container" id="admin-page">

<?php 
$where_are_we = 'admin';
require_once('../pagepieces/header.php');
?>

		<!-- start unique page content -->
		
		<div class="row">
			<div class="twelve columns">
				<h2>Admin Stuff</h2>
				<ul>
				<li><a href="farming.php">Admin Farming Page</a></li>
				</ul>
			</div>
		</div>
		
		<!-- end unique page content -->
	
<?php require_once('../pagepieces/footer.php'); ?>
	
	</div>

<?php require_once('../pagepieces/foot.php'); ?>

</body>
</html>