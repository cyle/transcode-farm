<?php

/*

	THE NEW UPLOAD WIZARD. ONE WIZARD TO RULE THEM ALL.
		introduced for median 5
		cyle gage, emerson college, 2012
	
*/

$login_required = true;
require_once('../www-includes/login_check.php');

$page_title = 'The Uploader';
require_once('pagepieces/head.php');
?>
</head>
<body>

	<!-- container -->
	<div class="container" id="upload-page">

		<?php 
		$where_are_we = 'uploader';
		require_once('pagepieces/header.php');
		?>

		<!-- content starts here -->
		
		<div class="row show-for-small">
			<div class="twelve columns">
				<div class="alert-box alert">Sorry, this page is not available for mobile devices.</div>
			</div>
		</div>
		
		<div class="row hide-for-small">			
			
			<div class="twelve columns">
				
				<form action="/submit/" method="post" id="the-form">
				
				<input type="hidden" id="un" name="un" value="<?php echo $current_user['username']; ?>" />
				
				<div id="file-list" class="upload-panel">
					<div id="upload-step">
						<div class="panel">
							<p><b>What versions do you want the farm to make of your files?</b></p>
							<p>All versions are encoded to H.264 video and AAC audio. If a version you select is higher bitrate than the video you upload, it will not be created.</p>
							<div class="select-preset"><label for="preset-max"><input checked="checked" type="checkbox" class="preset" value="max" id="preset-max" /> MAX - 1080p, 2200 kbps video, 196 kbps 44.1hz audio.</label></div>
							<div class="select-preset"><label for="preset-ultra"><input checked="checked" type="checkbox" class="preset" value="ultra" id="preset-ultra" /> ULTRA - 720p, 1700 kbps video, 128 kbps 44.1hz audio.</label></div>
							<div class="select-preset"><label for="preset-high"><input checked="checked" type="checkbox" class="preset" value="high" id="preset-high" /> HIGH - 720p, 1200 kbps video, 128 kbps 44.1hz audio. Desktop-friendly.</label></div>
							<div class="select-preset"><label for="preset-medium"><input checked="checked" type="checkbox" class="preset" value="medium" id="preset-medium" /> MEDIUM - 480p, 600 kbps video, 96 kbps 44.1hz audio. Tablet-friendly.</label></div>
							<div class="select-preset"><label for="preset-small"><input checked="checked" type="checkbox" class="preset" value="small" id="preset-small" /> SMALL - 260p, 300 kbps video, 64 kbps 44.1hz audio. Mobile-friendly.</label></div>
							<p>Want to learn more about these presets? <a href="/help/presets.php">Learn more here!</a></p>
						</div>
						<div class="panel">
							<p>This accepts video files only (MP4, MOV, AVI, WMV, MPG), 4GB individual file limit.</p>
							<p>Proprietary codecs are not supported (i.e. Apple codecs, HDV, XDCAM, etc).</p>
						</div>
						<div id="file-entry-list">
							<div class="file-entry">
								<div class="remove-this-file">&times;</div>
								<p>Select a file to upload! <input name="entry-file[]" class="entry-file" type="file" /></p>
							</div>
							<div id="file-entry-template" style="display:none;">
								<div class="remove-this-file">&times;</div>
								<p>And another file! <input name="entry-file[]" class="entry-file" type="file" /></p>
							</div>
						</div>
						<div class="panel">
							<p>You can add <b>up to 5 files</b> in a single batch upload job. Please be aware all the settings will apply to all files submitted at once.</p>
						</div>
						<p><a href="#" class="button small radius" id="add-another-file">Add another file to upload!</a></p>
						<p>When you are done adding files, upload them:</p>
						<p><a href="#" id="upload-btn" class="button large success radius">UPLOAD!</a></p>
					</div>
					<div id="uploading-step" style="display:none;">
						<div class="panel"><p>Uploading! Please be patient. How long this takes depends on the size of the files you've selected and your connection speed.</p></div>
						<div class="row" id="progress-bar">
							<div class="twelve columns">
								<div id="bar-outer"><div id="bar-inner" style="width:0%;">&nbsp;</div></div>
							</div>
						</div>
					</div>
					<div id="done-step" style="display:none;">
						<p>Your video(s) are done uploading. Check out the status of each upload below. You can check out their transcoding status on <a href="/farming.php" target="_blank">the Farm page</a> (opens in a new window).</p>
						<div id="upload-result"></div>
						<p>You will be emailed when your transcoded files are ready.</p>
						<p><a href="/" class="button radius large success">&laquo; go back</a></p>
					</div>
				</div>
								
				</form>
				
			</div>
			
		</div>
		
		<!-- content ends here -->
		
		<?php require_once('pagepieces/footer.php'); ?>
		
	
	</div>
	<!-- container -->

	<?php require_once('pagepieces/foot.php'); ?>

</body>
</html>