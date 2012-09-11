<?php
if (!isset($page_title) || trim($page_title) == '') {
	$page_title = 'The Farm';
}
?>
<!DOCTYPE html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
<head>
<!-- 


             /\O
              /\_
             /\
            /  \
          LOL  LOL
          

// -->
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width" />
	<title><?php echo $page_title; ?> - The Farm</title>
	<link rel="stylesheet" href="/stylesheets/foundation.min.css">
	<link rel="stylesheet" href="/stylesheets/farm.css">
	<!--[if lt IE 9]>
		<link rel="stylesheet" href="/stylesheets/ie.css">
	<![endif]-->
	<!-- IE Fix for HTML5 Tags -->
	<!--[if lt IE 9]>
		<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->
<?php
// leave the head tag open so that the page can have custom stuff if need be
?>