<?php
require_once 'includes.php';
require_once 'test/TestRoute.php';

date_default_timezone_set("Asia/Shanghai");

//temop to org
assert_options(ASSERT_CALLBACK, 'my_assert_handler');
function my_assert_handler($file, $line, $code, $desc=null)
{
	$message = "Assertion Failed: Line {$line} in {$file}: $code";
	$message .= $desc ? "[desc]".$desc : "";
	Flight::sendRouteResult(false, null, $message, 402);
}

try{
	//client
	$_SERVER['HTTP_N_SOURCE_CLIENT'] = isset($_SERVER['HTTP_N_SOURCE_CLIENT']) ? $_SERVER['HTTP_N_SOURCE_CLIENT'] : "CUSTOMER";
	application\Client::RegisterClient($_SERVER['HTTP_N_SOURCE_CLIENT']);
	//session
	Flight::register('session', 'application\\Session',
		array($_SERVER['HTTP_N_SOURCE_CLIENT'],
			isset($_SERVER['HTTP_N_ACCESS_TOKEN']) ? $_SERVER['HTTP_N_ACCESS_TOKEN'] : "",
			isset($_SERVER['HTTP_N_DEVICE_TOKEN']) ? $_SERVER['HTTP_N_DEVICE_TOKEN'] : "",
			isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "",
			isset($_SERVER['REQUEST_TIME']) ? date("Y-m-d H:i:s",$_SERVER['REQUEST_TIME']) : "0000-00-00 00:00:00",
		)
	);
	Flight::session();

	Logger::getLogger('Route')->debug(Flight::request());
	Flight::start();
}
catch(Exception $e){
	Flight::sendRouteResult(false, null, $e->getMessage(), 401);
}
?>
