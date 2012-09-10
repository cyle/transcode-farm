<?php

// the header of every page

?>

<div class="row" id="top-nav">
	<div class="four columns offset-by-eight">
		<p class="right"><?php if ($current_user['loggedin'] == false) { ?><a href="/login.php">login</a><?php } else { ?><a href="/logout.php">logout</a><?php } ?></p>
	</div>
</div>

<div class="row" id="header">
	<div class="twelve columns">
		<h1><a href="/">The Open Transcoding Farm</a></h1>
		<hr />
	</div>
</div>