<?php
Flight::route('GET /', function(){
	print_r("Hello O_O");
});

Flight::route('GET /sample', function(){
	Flight::sendRouteResult(true, null, "for test");
});
?>