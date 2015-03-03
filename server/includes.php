<?php
$basic_dir = __DIR__;

$dir_list = array(
	$basic_dir."/core",
	$basic_dir."/application",
	$basic_dir."/monitors",
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
