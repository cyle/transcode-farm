<?php

/*

	THERE WAS AN ERROR!
		cyle gage, emerson college, 2012

*/

$login_required = false;
require_once('../www-includes/login_check.php');

?>
<?php
$page_title = 'Error';
require_once('pagepieces/head.php');
?>
</head>
<body>

	<div class="container" id="error-page">

<?php 
$where_are_we = 'error';
require_once('pagepieces/header.php');
?>

		<!-- start unique page content -->
		
		<div class="row">
			<div class="twelve columns">
				<h2>There was an error!</h2>
				<?php
				if (isset($error_message) && trim($error_message) != '') {
					echo '<p class="error">'.$error_message.'</p>'."\n";
				}
				?>
				<p>Please go back, or if you're still having trouble, call the <a href="http://www.emerson.edu/about-emerson/offices-departments/help-desk" target="_blank">IT Help Desk</a>.</p>
			</div>
		</div>
		
		<!-- end unique page content -->
	
<?php require_once('pagepieces/footer.php'); ?>
	
	</div>

<?php require_once('pagepieces/foot.php'); ?>

</body>
</html>