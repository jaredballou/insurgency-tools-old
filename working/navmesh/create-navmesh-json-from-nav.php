<?php
//Root Path Discovery
do { $rd = (isset($rd)) ? dirname($rd) : realpath(dirname(__FILE__)); $tp="{$rd}/rootpath.php"; if (file_exists($tp)) { require_once($tp); break; }} while ($rd != '/');

require_once "${includepath}/functions.php";
require_once "class.NavMesh.php";
//$files = glob("${datapath}/maps/navmesh/*.json");
$map_paths = array("/home/insserver/serverfiles/insurgency/maps");
//Open all files and add gamemodes and other map info to array
//var_dump($files);

foreach ($map_paths as $path) {
var_dump($path);
	$files = glob("{$path}/buhriz.nav");
	foreach ($files as $file) {
var_dump($file);
		ParseNavmesh($file);
	}
}
function ParseNavmesh($navfile)
{
	global $datapath;
	$map = basename($navfile,".nav");
	echo "Starting {$map}... ";
	$outfile = "${datapath}/maps/navmesh/{$map}.json";
	$md5 = md5($navfile);
	if (file_exists($outfile)) {
		$data = json_decode(file_get_contents($outfile),TRUE);
		if ($data['md5'] == $md5) {
			echo "<i>Not processing {$map}, no new data</i><br>\n";
			return;
		}
	} else {
		$data = array();
	}
	$navmesh = new NavMesh\File($navfile);
	var_dump($navmesh);
	exit;
}
