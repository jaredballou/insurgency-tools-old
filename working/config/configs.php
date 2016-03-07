<?php
/*
This tool reads the data/cfg directory and parses a config. It shows the 
changes, and lists any errors.
*/
$title = "Insurgency Config Parser";
//Root Path Discovery
do { $rd = (isset($rd)) ? dirname($rd) : realpath(dirname(__FILE__)); $tp="{$rd}/rootpath.php"; if (file_exists($tp)) { require_once($tp); break; }} while ($rd != '/');
require_once "{$includepath}/header.php";
$configs = array();

function AddConfigPath($path,$pattern="*.cfg",$level=0) {
	$configs = array();
	$files = glob("{$path}/{$pattern}");
	foreach ($files as $file) {
		$bn = basename($file);
		$configs[$bn] = ParseConfig($file);
	}
	foreach (glob("{$path}/*", GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
		$bn = basename($dir);
		$configs[$bn] = AddConfigPath("{$dir}",$pattern,$level+1);
	}
	ksort($configs);
	return $configs;
}
function ParseConfig($config) {
	$data = array();
	$lines = array_filter(preg_split('/\n|\r\n?/',preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\')\/\/.*))/', '', file_get_contents($config))));
	foreach ($lines as $line) {
		$words = preg_split('/[ "]+/',preg_replace(array('/^[" \t]+/','/[" \t]+$/'),'',trim($line)));
		if ($words[0] == 'exec') {
			continue;
		}
		if ($words[0] == 'sm_cvar') {
			array_shift($words);
		}
		$var = array_shift($words);
		$data[$var] = trim(implode(" ",$words));
//var_dump($var,$data[$var],$words);
	}
	return $data;
}
$configs = AddConfigPath("{$datapath}/cfg/include");
//var_dump($configs);
