<?php
/*
require "/opt/hlstatsx-community-edition/heatmaps/config.inc.php";

mysql_connect(DB_HOST,DB_USER,DB_PASS);
mysql_select_db(DB_NAME);

$dbprefix = isset($_REQUEST['dbprefix']) ? $_REQUEST['dbprefix'] : 'hlstats';
*/


$files = glob("data/maps/navmesh/*.json");
require_once "include/functions.php";
//Open all files and add gamemodes and other map info to array
//var_dump($files);
foreach ($files as $file) {
	$map = basename($file,".json");
	ParseMap($map);
}
function ParseMap($map)
{
	echo "Starting {$map}... ";
	if (file_exists("data/maps/navmesh/{$map}.png")) {
		if ((filemtime("data/maps/navmesh/{$map}.json")) < (filemtime("data/maps/navmesh/{$map}.png"))) {
			echo "<i>Not processing {$map}, no new data</i><br>\n";
			return;
		}
	}
	$data = json_decode(file_get_contents("data/maps/navmesh/{$map}.json"),true);
/*
	$tile = imagecreatetruecolor(32,16);
	$black = imagecolorallocatealpha($tile, 0, 0, 0, 64);
	$yellow = imagecolorallocatealpha($tile, 255, 255, 0, 64);
	imagefilledrectangle($tile,0,0,32,16,$black);
	imagefilledrectangle($tile,0,0,16,16,$yellow);
	$canvas = imagecreatetruecolor(1024*2,1024*2);
	imagesettile($canvas,$tile);
	imagefilledrectangle($canvas, 0, 0, 2048, 2048, IMG_COLOR_TILED);
	$rotated = imagerotate($canvas,45,imageColorAllocateAlpha($canvas, 0, 0, 0, 127));
	$overlay = imagecreatetruecolor(1024,1024);
	imagecopy ($overlay,$rotated,0,0,936,936,1024,1024);
*/
	$overlay = imagecreatefrompng('images/navmesh-gradient.png');
	imagealphablending($overlay, false);
	$black = imagecolorallocatealpha($overlay, 0, 0, 0, 64);
	$red = imagecolorallocatealpha($overlay, 255, 0, 0, 64);
	$white = imagecolorallocatealpha($overlay, 255, 255, 255, 64);
	$trans_color = imagecolorallocatealpha($overlay, 255, 255, 255, 127);

	foreach ($data['Areas'] as $id => $row) {
		$xmin = $row['pos_x'];
		$ymin = $row['pos_y'];
		$xmax = $xmin + $row['pos_width'];
		$ymax = $ymin + $row['pos_height'];
		for( $x = $xmin; $x <= $xmax; $x++ ){
												for( $y = $ymin; $y < $ymax; $y++ ){
																imagesetpixel( $overlay, $x, $y, $trans_color );
												}
		}
		//$rects[$obj['pos_name']] = $obj;
	}
	imagesavealpha($overlay, true);
	imagepng($overlay,"data/maps/navmesh/{$map}.png");
	imagedestroy($overlay);
	echo "<b>Parsed {$map}</b><br>\n";
	//exit;
}
/*
echo "<div style='background-image: url(\"data/materials/overviews/{$map}.png\");'>
<img src='data/maps/navmesh/{$map}.png'>
</div>";
require "include/PolygonMaker.php";
//				foreach ($rects as $name => $data) {
$data = $rects['MainNavmesh'];
								$polygonMaker = new PolygonMaker(1024,1024);
								$polygons = $polygonMaker->findPolygonsPoints($data);
								foreach ($polygons as $polygon)
								{
										$polygonMaker->drawPolygon($polygon, 0x00, 0x00, 0x00);
								}
								$polygonMaker->display();
								exit;
//				}
*/
?>
