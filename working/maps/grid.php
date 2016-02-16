<?php
// This creates the grid overlay images for the maps
require "/opt/hlstatsx-community-edition/heatmaps/config.inc.php";

mysql_connect(DB_HOST,DB_USER,DB_PASS);
mysql_select_db(DB_NAME);

$dbprefix = isset($_REQUEST['dbprefix']) ? $_REQUEST['dbprefix'] : 'hlstats';

$ovalpha = 48;
$ovcolor = 'auto';
$grid_divisions = 8;
$alphas = array(0 => '100% Opaque', 32 => '75% Opaque', 48 => '62% Opaque', 64 => '50% Opaque', 96 => '24% Opaque');
$colors = array('auto' => 'Automatic', 'white' => 'White', 'black' => 'Black');
$files = glob("${datapath}/materials/overviews/*.png");

foreach ($files as $file) {
	$mapname = basename($file,".png");
	if (!isset($map))
		$map = $mapname;
	$maps[$mapname] = $mapname;
}
if (isset($_REQUEST['grid_divisions'])) {
	if (is_numeric($_REQUEST['grid_divisions']) && ($_REQUEST['grid_divisions'] > 0) && ($_REQUEST['grid_divisions'] <= 26))
		$grid_divisions = $_REQUEST['grid_divisions'];
}
if (isset($_REQUEST['map'])) {
				if (isset($maps[$_REQUEST['map']]))
								$map = $_REQUEST['map'];
}
if (isset($_REQUEST['ovalpha'])) {
	if (isset($alphas[$_REQUEST['ovalpha']]))
		$ovalpha = $_REQUEST['ovalpha'];
}
if (isset($_REQUEST['ovcolor'])) {
	if (isset($colors[$_REQUEST['ovcolor']]))
		$ovcolor = $_REQUEST['ovcolor'];
}
$mapimg="data/materials/overviews/{$map}.png";
$source = imagecreatefrompng($mapimg);

$image_width=imagesx($source);
$image_height=imagesy($source);
$gridsize = $image_width/$grid_divisions;
$margin_top = 16;//$gridsize/8;
$margin_left = 8;

$contrast = contrast();
$gridimg="images/grid/{$ovcolor}-alpha{$ovalpha}-{$grid_divisions}x{$grid_divisions}.png";

echo "<div>
<svg width='{$image_width}' height='{$image_height}' xmlns='http://www.w3.org/2000/svg'>
	<defs>
		<style type='text/css'><![CDATA[
			text {
				font-family: Arial;
				font-size: 14;
				font-weight: bold;
				fill: {$ovcolor};
			}
		]]></style>
		<pattern id='grid' width='{$gridsize}' height='{$gridsize}' patternUnits='userSpaceOnUse'>
			<rect width='{$gridsize}' height='{$gridsize}' fill='none'/>
			<path d='M {$gridsize} 0 L 0 0 0 {$gridsize}' fill='none' stroke='{$ovcolor}' stroke-width='1'/>
		</pattern>
	</defs>
	<rect width='100%' height='100%' fill='url(#grid)'/>\n";
$idx=0;
for ($pos=$gridsize; $pos<=$image_width; $pos+=$gridsize) {
	$xtext = chr(65+$idx);
	$ytext = $idx+1;
	$textpos = ($pos - ($gridsize/2));
	echo "<text x='{$textpos}' y='{$margin_top}' text-anchor='middle'>{$xtext}</text>\n";
	echo "<text y='{$textpos}' x='{$margin_left}' text-anchor='left'>{$ytext}</text>\n";
	$idx++;
}
echo "</svg>

</div>";








function contrast() {
	global $overlay,$source,$image_width,$image_height,$ovalpha,$ovcolor;
	if ($ovcolor == 'auto') {
		$thumb = imagecreatetruecolor(1,1);
		imagecopyresampled($thumb, $source, 0, 0, 0, 0, 1, 1, $image_width, $image_height);
		$rgb = imagecolorat($thumb,0,0);
		$r = (($rgb >> 16) & 0xFF);
		$g = (($rgb >> 8) & 0xFF);
		$b = ($rgb & 0xFF);
		$yiq = (($r*299)+($g*587)+($b*114))/1000;
		$ovcolor = ($yiq >= 128) ? 'black' : 'white';
	}
	$cc = ($ovcolor == 'black') ? 0 : 255;
	return imagecolorallocatealpha($overlay, $cc, $cc, $cc, $ovalpha);
}

//var_dump($r, $g, $b);
//echo "<br>";
echo "<form>\n	<select name='map'>\n";
foreach ($maps as $mapname) {
	$sel = ($mapname == $map) ? ' SELECTED' : '';
	echo "		<option{$sel}>{$mapname}</option>\n";
}
echo "	</select>\n	 Alpha: <select name='ovalpha'>\n";
foreach ($alphas as $alpha => $label) {
	$sel = ($ovalpha == $alpha) ? ' SELECTED' : '';
//	$pct=round(((127-$alpha)/127)*100,0);
//	echo "		<option value='{$alpha}'{$sel}>{$pct}% Opaque</option>\n";
	echo "		<option value='{$alpha}'{$sel}>{$label}</option>\n";
}
echo "	</select>\n	 Color: <select name='ovcolor'>\n";
foreach ($colors as $color => $label) {
	$sel = ($ovcolor == $color) ? ' SELECTED' : '';
	echo "		<option value='{$color}'{$sel}>{$label}</option>\n";
}
echo "	</select>\n	 Grid Divisions: <select name='grid_divisions'>\n";
for ($x=1;$x<=26;$x++) {
	$sel = ($x == $grid_divisions) ? ' SELECTED' : '';
	$ll = chr(64+$x);
	$px = round($image_width/$x);
	echo "		<option value='{$x}'{$sel}>{$x} (A-{$ll}, {$px}px)</option>\n";
}
echo "</select>\n	 <input type='submit'>\n</form>\n";

if ($createimg) {
$overlay = imagecreatetruecolor($image_width,$image_height);
imagealphablending($overlay, false);
imagesavealpha($overlay, true);

$black = imagecolorallocatealpha($overlay, 0, 0, 0, 64);
$white = imagecolorallocatealpha($overlay, 255, 255, 255, 64);
$trans = imagecolorallocatealpha($overlay, 255, 255, 255, 127);
imagefilledrectangle($overlay, 0, 0,$image_width, $image_height, $trans);
imagealphablending($img, true);
$idx=0;
for ($pos=$gridsize; $pos<=$image_width; $pos+=$gridsize) {
	$xtext = chr(65+$idx);
	$ytext = $idx+1;
	drawtext($overlay,($pos - ($gridsize/2)),16,$xtext,$contrast);
	drawtext($overlay,14,($pos - ($gridsize/2)+1),$ytext,$contrast);
	if ($pos < $image_width) {
		imageline($overlay,$pos,0,$pos,$image_height,$contrast);
		imageline($overlay,0,$pos,$image_width,$pos,$contrast);
	}
	$idx++;
}
function drawtext(&$overlay,$xofs=0,$yofs=0,$text='',$colors=array(),$font = '/usr/share/fonts/msttcore/arial.ttf',$font_size = 11,$angle = 45) {
	if (!is_array($colors)) {
		$colors = array($colors);
	}
	$text_box = imagettfbbox($font_size,$angle,$font,$text);
	$text_width = $text_box[2]-$text_box[0];
	$text_height = $text_box[3]-$text_box[1];
	$tx = ($xofs - ($text_width/2));
	$ty = ($yofs - ($text_height/2));
	foreach ($colors as $color) {
		imagettftext($overlay, $font_size, 0, $tx, $ty, $color, $font, $text);
	}
}
//imagefill($overlay, 0, 0, $white);
//imagealphablending($overlay, false);




imagesavealpha($overlay, true);
imagepng($overlay,$gridimg);
}

/*
require "include/PolygonMaker.php";
//				foreach ($rects as $name => $data) {
$data = $rects['MainNavmesh'];
								$polygonMaker = new PolygonMaker($image_width,$image_width);
								$polygons = $polygonMaker->findPolygonsPoints($data);
								foreach ($polygons as $polygon)
								{
										$polygonMaker->drawPolygon($polygon, 0x00, 0x00, 0x00);
								}
								$polygonMaker->display();
								exit;
//				}
*/

//<img src='{$mapimg}' style='position: absolute;'>
//<img src='{$gridimg}' style='position: absolute;'>
?>
