<?php
$basic_dir = __DIR__;

require_once $basic_dir.'/flight/Flight.php';
Flight::set('flight.views.path', $basic_dir.'/../views');
Flight::set('flight.log_errors', true);
Flight::map('error', function(Exception $ex){
    echo $ex->getTraceAsString();
});
Flight::map('notFound', function(){
	Flight::sendRouteResult(false, null, "喵了个咪的，页面找不到了", 404);
});

//tools for route
Flight::map('sendRouteResult', function($success, $data, $message = "", $err_code = 200){
	$data = $data ? (is_object($data)? get_object_vars($data) : $data) : array();
	$data['success'] = $success ? "true" : "false";
	$data['message'] = $message;
	$result = array(
		'code' => $err_code,
	    'data' => $data
	);
	//print_r($result);die();
	convertArrayDataToString($result);
	Logger::getLogger("Route")->debug($result);
	Flight::json($result);
});
function convertArrayDataToString(&$data){
	if(is_array($data)){
		foreach ($data as $key => &$value) {
			convertArrayDataToString($value);
		}
	}else{
		$data = $data===null ? "" : $data;
		$data = (string)$data;
	}
}



require_once($basic_dir.'/log4php/Logger.php');
Logger::configure($basic_dir.'/../config/logger_config.xml');


$dir_list = array(
	$basic_dir."/data_struct",
	$basic_dir."/monitor",
	$basic_dir."/model",
	$basic_dir."/tools",
	);
foreach ($dir_list as $dir) {
	foreach(glob($dir.'/*.php') as $file)
	{
	    if (file_exists($file)) {
	        require_once $file;
	    }
	}
}

?>
