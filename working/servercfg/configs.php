<?php
/*
This tool reads the data/cfg directory and parses a config. It shows the 
changes, and lists any errors.
*/
$title = "Insurgency Config Parser";
require_once "include/header.php";
$configs = array();

function AddConfigPath($path,$level=0) {
	global $configs;
	$dirname = dirname($path);
	$pattern = basename($path);
	if (!$patern) {
		$pattern = '*';
		$path.="/*";
	}
	$dirs = glob("{$path}");
	foreach ($dirs as $dir) {
		if (is_dir($dir)) {
			AddConfigPath("{$dir}/{$pattern}",$level+1);
		} else {
			$configs[$dir] = basename($dir,'.cfg');
		}
	}
}
function ParseConfig($config) {
	$data = file_get_contents($config);
	
}
AddConfigPath("{$rootpath}/data/cfg");
var_dump($configs);
