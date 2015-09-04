<pre><?php
$title = "Insurgency Playlist Parser";
$tableclasses = "table table-striped table-bordered table-condensed table-responsive";
require_once "include/header.php";
$dirs = glob("data/playlists/*");

foreach ($dirs as $dir) {
	$versions[] = basename($dir);
}
asort($versions);
$newest_version = $version = end($versions);
if ($_REQUEST['version']) {
	if (in_array($_REQUEST['version'],$versions)) {
		$version = $_REQUEST['version'];
	}
}
$version_compare = $version;
if ($_REQUEST['version_compare']) {
	if (in_array($_REQUEST['version_compare'],$versions)) {
		$version_compare = $_REQUEST['version_compare'];
	}
}
$data=array();
$files = rglob("data/playlists/{$version}/*.playlist");
foreach ($files as $file) {
	$data = array_merge_recursive($data,parseKeyValues(file_get_contents($file)));
}
var_dump($data);
