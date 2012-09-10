<?php

try {
	$m = new Mongo('mongodb://mongo.whatever.com:27017');
	$farmdb = $m->farm;
} catch (MongoCursorException $e) {
	echo 'error message: '.$e->getMessage()."\n";
    echo 'error code: '.$e->getCode()."\n";
}

?>