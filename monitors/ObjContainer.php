<?php
$dir = __DIR__;

foreach(glob($dir.'/*.php') as $file)
{
    if (file_exists($file)) {
        require_once $file;
    }
}
?>