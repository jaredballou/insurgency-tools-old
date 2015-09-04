<?php
//Maps array holds all map data
$maps = array();
$controlpoints = array();
$map_objects = array();
//Include key-value reader
//require_once "include/kvreader2.php";
//require_once "include/functions.php";
//$reader = new KVReader();


//Get all map text files. This could probably be safer.
$files = glob("data/maps/parsed/*.json");
//Open all files and add gametypes and other map info to array
foreach ($files as $file) {
	$mapname = basename($file,".json");
	if (in_array($mapname,$excludemaps)) {
		continue;
	}
	$maps[$mapname] = json_decode(file_get_contents($file),true);
}

//var_dump($maps);
if ($_REQUEST['command'] == 'hlstats') {
	echo "<pre>\n";
	$dbprefix = $_REQUEST['dbprefix'] ? $_REQUEST['dbprefix'] : 'hlstats';
	$tables = array(
		'Heatmap_Config' => array(
			'allfields' => array('game','map','xoffset','yoffset','flipx','flipy','rotate','days','brush','scale','font','thumbw','thumbh','cropx1','cropy1','cropx2','cropy2'),
			'fields'	=> array('xoffset','yoffset','flipx','flipy','rotate','days','brush','scale','font','thumbw','thumbh','cropx1','cropy1','cropx2','cropy2')
		),
	);
	$values = array();
	foreach ($maps as $mapname => $mapdata) {
		$xoffset = abs($mapdata['overview']['pos_x']);
		$yoffset = abs($mapdata['overview']['pos_y']);
		$flipx = ($mapdata['overview']['pos_x'] < 0) ? 1 : 0;
		$flipy = ($mapdata['overview']['pos_y'] < 0) ? 1 : 0;
		$values['Heatmap_Config'][] = "('insurgency','{$mapname}','{$xoffset}','{$yoffset}','{$flipx}','{$flipy}','{$mapdata['overview']['rotate']}','30','small','{$mapdata['overview']['scale']}','10','0.170312', '0.170312', '0', '0', '0', '0')";
	}
	foreach ($tables as $table => $tdata) {
	echo "--\n-- Update {$dbprefix}_{$table}\n--\n\n";
		$fields = array();
		foreach ($tdata['fields'] as $field) {
			$fields[] = "{$field} = VALUES({$field})";
		}
		asort($values[$table]);
		echo "INSERT INTO `{$dbprefix}_{$table}`\n	(`".implode('`, `',$tdata['allfields'])."`)\n	 VALUES\n		 ".implode(",\n		 ",$values[$table])."\n	 ON DUPLICATE KEY UPDATE\n		".implode(",\n		",$fields).";\n";
	}
	exit;
}
if ($_REQUEST['command'] == 'symlinks') {
//var_dump($maps);
	echo "<pre>\n";
	echo "#!/bin/bash\n";
	foreach ($maps as $mapname => $mapdata) {
		echo "if [ -e {$mapname}.bsp ]; then\n";
//var_dump($mapdata);
		foreach ($mapdata['gametypes'] as $gametype => $gtdata) {
			echo "\tln -s {$mapname}.bsp \"{$mapname} {$gametype}.bsp\"\n";
		}
		echo "fi\n";
	}
	exit;
}
if ($_REQUEST['command'] == 'mapcycle') {
	include "include/header.php";
	$maps = array();
	$mldata = json_decode(file_get_contents("data/maps/maplist.json"),true);

//var_dump($mldata);
	$gtlist = json_decode(file_get_contents("data/maps/gamemodes.json"),true);
	$maplist = array();
	$files = glob("data/maps/*.txt");
	foreach ($files as $file) {
		$mapname = basename($file,".txt");
		if (in_array($mapname,$excludemaps)) {
			continue;
		}
//		$mapdata = $reader->read(strtolower(file_get_contents($file)));
		$mapdata = parseKeyValues(strtolower(file_get_contents($file)));
		$maplist[$mapname] = 1;
		$maps[$mapname]['gametypes'] = array_shift(array_values($mapdata));
	}
	$gametypes = (isset($_REQUEST["gametypes"])) ? $_REQUEST["gametypes"] : array('checkpoint','hunt');
	$mc.="";//Mapcycle generated for gametypes ".implode(',',$gametypes)."\n\n";
	foreach ($maps as $mapname => $mapdata) {
		if (in_array($mapname,$excludemaps)) {
			//echo "//Skipping {$mapname}\n";
			unset($maplist[$mapname]);
			continue;
		}
		if (isset($_REQUEST['maps'])) {
			if (!in_array($mapname,$_REQUEST['maps'])) {
				$maplist[$mapname] = 0;
				continue;
			}
		}
		foreach ($mapdata['gametypes'] as $gametype => $gtdata) {
			if (!is_array($gtdata)) {
				continue;
			}
			if ($gametype == 'theater_conditions') {
				continue;
			}
			$gametypelist[$gametype]++;
			if (in_array($gametype,$gametypes)) {
				$mc.="{$mapname} {$gametype}\n";
			}
		}
	}
	ksort($gametypelist);
	ksort($maplist);
	echo "<script language='JavaScript'>
	jQuery(document).ready(
		function($) {
			$('.section h3 input:checkbox').click(
				function(){
					var p = $(this).parents('.section');
					p.find('div.subsection input:checkbox').prop('checked', this.checked);
				}
			);
			$('.mapselector').click(
				function(){
					var id = $(this).attr('id');
					var checked = this.checked;
					$( '#mapbox option' ).each(
						function() {
							if ($(this).hasClass(id)) {
								if (checked) {
									$(this).attr('selected','selected');
								} else {
									$(this).removeAttr('selected');
								}
							}
						}
					);
				}
			);
		}
	);
</script>
	";
	startbody();
	echo "<form><input type='hidden' name='command' value='mapcycle'>\n<div style='border: 1px solid black; float: left; display: block; text-align: center; margin: 10px;'><h2>Game Types</h2>\n";
	foreach ($gtlist as $type => $tlist) {
		echo "<div style='border: 1px solid black; position: relative; float: left; text-align: center; margin: 10px;' id='gametypes-{$type}' class='section'><h3><input type='checkbox'>{$type}</h3><div class='subsection'>\n";
		foreach ($tlist as $gametype) {
			$sel = (in_array($gametype,$gametypes)) ? ' CHECKED' : '';
			echo "<input type='checkbox' name='gametypes[]' value='{$gametype}'{$sel}>{$gametype} \n";
		}
		echo "</div></div>\n";
	}
	echo "<div style='border: 1px solid black; position: relative; float: left; text-align: center; margin: 10px;'>\n<h2>Maps</h2>";
	foreach ($mldata as $author => $mymaps) {
		echo "<input type='checkbox' id='{$author}' class='mapselector' CHECKED>{$author}<br>\n";
		foreach ($mymaps as $mymap => $mymapdata) {
			foreach ($mymapdata['versions'] as $version => $mapfiles) {
				$authors[$version] = $author;
			}
		}
	}
	echo "<input type='checkbox' id='custom' class='mapselector' CHECKED>custom<br>\n";
	echo "<select name='maps[]' multiple size='10' id='mapbox'>";
	foreach ($maps as $mapname => $mapdata) {
		$sel = ($maplist[$mapname]) ? ' SELECTED' : '';
		$dispname = $mapname;
		$author = (isset($authors[$mapname])) ? $authors[$mapname] : 'custom';
		echo "<option value='{$mapname}' class='{$author}'{$sel}>{$mapname} [{$author}]</option>\n";
	}
	echo "</select>\n</div>\n";
	echo "<br><input type='submit' value='Generate'>\n</form>\n</div>\n<textarea style='width: 100%; height: 80%; min-height: 80%;resize:vertical;'>\n{$mc}</textarea>\n";
	exit;
}
//Only set variables if a valid map was selected. This should do better input validation.
if (isset($_REQUEST['map'])) {
	if (is_array($maps[$_REQUEST['map']])) {
		$map = $_REQUEST['map'];
		$gametype = $_REQUEST['gametype'];
		$overlays = $_REQUEST['overlays'];
	}
}
//Function to display SVG object
function svg($svg,$layer='common') {
	if ($svg) {
		echo "							<svg id='svg-{$layer}' xmlns='http://www.w3.org/2000/svg' height='1024' width='1024' style='position: absolute; left: 0px; top: 0px; z-index: 1;' class='map-overlay-svg'>\n";
		echo "								<defs>\n";
		echo "									<marker id='arrowhead' viewBox='0 0 10 10' refX='1' refY='5' markerUnits='strokeWidth' orient='auto' markerWidth='4' markerHeight='3'><polyline points='0,0 10,5 0,10 1,5' fill='darkblue' /></marker>\n";
		echo "								</defs>\n";
		echo $svg;
		echo "							</svg>\n";
	}
}

//Start output
$title = "Map Viewer";
require "include/header.php";
?>
<style type="text/css">
	.map-image: {
		position: absolute;
		left: 0px;
		top: 0px;'
		z-index: 0;
	}
	#navmesh-overlay,#heatmap-overlay,#grid-overlay: {
		pointer-events:none;
		position: absolute;
		left: 0px;
		top: 0px;
	}
	#grid-overlay: {
		z-index: -3;
	}
	#navmesh-overlay {
		z-index: -2;
	}
	#heatmap-overlay: {
		z-index: -1;
	}
	*{
		font-family: Verdana;
	}
	.minwidth {
		min-width:100px;
		width: auto !important;
		width: 100px;
	}
	body {
		white-space: nowrap;
	}
	.overlay, .map{
		top: 0;
		left: 0;
	}
	.map, .key{
//		float: left;
		white-space: normal;
		vertical-align: top;
		display: inline-block;
		position: relative;
	}
	.overlay{
		position: absolute;
	}
	.obj {
		pointer-events: none;
		width: 64px;
		height: 64px;
		margin-left: -32px;
		margin-top: -32px;
		vertical-align:middle;
		text-align:center;
		word-wrap: break-word;
		color: #fff;
		font-weight:bold;
		font-size:1.5em;
		line-height: 64px;
		z-index: 100;
	}
<?php

$colors = array('noteam' => '255,255,255', 'neutral' => '132,150,28', 'friend' => '83,157,178', 'enemy' => '172,64,41', 'maparea' => '109,96,80');

foreach ($colors as $color => $rgb) {
	$code = explode(',',$rgb);
	echo "
	.{$color} {
		background-color: rgb({$rgb});
	}
	.obj_{$color}_bg {
		background-color: rgba({$rgb},0.5);
//		-webkit-mask-image: url('images/c_obj_cache_{$color}.png');
		-webkit-mask-image: url('data/materials/vgui/hud/obj_cache_{$color}.png');
	}
	.obj_{$color}_fg {
		background-image: url('images/c_obj_{$color}.png');
	}
	.obj_cache_{$color}_bg {
		background-color: rgba({$rgb},0.5);
//		-webkit-mask-image: url('images/c_obj_cache_{$color}.png');
		-webkit-mask-image: url('data/materials/vgui/hud/obj_cache_{$color}.png');
	}
	.obj_cache_{$color}_fg {
		background-image: url('images/c_obj_cache_{$color}.png');
	}
";
}

?>

.canvas {
	top: 0;
	left: 0;
	z-index: -10;
	position: absolute;
	width: 100%;
	height: 1024px;
}
.area{
	vertical-align: middle;
	-moz-box-sizing: border-box;
	-webkit-box-sizing: border-box;
	box-sizing: border-box;
	pointer-events: none;
	border:3px solid #000;
//	margin: -6px;
	z-index: 20;
}
.maplabel,.friend-label,.enemy-label,.neutral-label,.noteam-label,.area-label,.area{
	vertical-align:middle;
	text-align:center;
	word-wrap: break-word;
	color: #fff;
	font-weight:bold;
	font-size:1.5em;
	line-height: 64px;
	z-index: 100;
}

.maplabel,.label,.maplabel {
	line-height: 4em;
	position: relative;
	top: 50%;
	transform: translateY(-50%);
}
.label{
	border: 1px solid;
}
</style>
<script type="text/javascript">
<!--
function changeGTLayer() {
	var obj = document.getElementById('gametype');
	for (i=0; i < obj.length; i++) {
		hideLayer('layer-' + obj.options[i].value);
	}
	var sgt = document.getElementById('gametypes').value;
	if (sgt != 'OFF'){
		showLayer('layer-' + obj.value);
	}
}
function toggleLayer(id){
	var el = document.getElementById(id);
	if (el){
		el.style.display = (el.style.display != 'none' ? 'none' : 'block' );
	}
}
function hideLayer(id){
	var el = document.getElementById(id);
	if (el){
		el.style.display = 'none';
	}
}
function showLayer(id){
	var el = document.getElementById(id);
	if (el){
		el.style.display = 'block';
	}
}
function toggleButton(obj){
	obj.value = (obj.value == 'ON' ? 'OFF' : 'ON');
	if (obj.id == 'gametypes'){
		changeGTLayer();
	} else {
		toggleLayer('layer-' + obj.id);
	}
}

function getlookat()
{
	var map = document.getElementById("map");
	var mouse_at_x = map.get("mouse.x");
	var mouse_at_y = map.get("mouse.y");
	document.getElementById("mouse_x").innerHTML = mouse_at_x + "px";
	document.getElementById("mouse_y").innerHTML = mouse_at_y + "px";
}
var lookat_interval = setInterval('getlookat()', 66);
// disable text selection to avoid cursor flickering
//window.onload = function() 
//{
//	document.onselectstart = function() {return false;} // ie
//	document.onmousedown = function() {return false;} // mozilla
//}

//-->
</script>

<script type="text/javascript">
<!--
function FindPosition(oElement)
{
	if(typeof( oElement.offsetParent ) != "undefined")
	{
		for(var posX = 0, posY = 0; oElement; oElement = oElement.offsetParent)
		{
			posX += oElement.offsetLeft;
			posY += oElement.offsetTop;
		}
			return [ posX, posY ];
		}
		else
		{
			return [ oElement.x, oElement.y ];
		}
}
function GetCoordinates(e)
{
	var PosX = 0;
	var PosY = 0;
	var ImgPos;
	ImgPos = FindPosition(myImg);
	if (!e) var e = window.event;
	if (e.pageX || e.pageY)
	{
		PosX = e.pageX;
		PosY = e.pageY;
	}
	else if (e.clientX || e.clientY)
		{
			PosX = e.clientX + document.body.scrollLeft
				+ document.documentElement.scrollLeft;
			PosY = e.clientY + document.body.scrollTop
				+ document.documentElement.scrollTop;
		}
	PosX = PosX - ImgPos[0];
	PosY = PosY - ImgPos[1];
	document.getElementById("x").innerHTML = PosX;
	document.getElementById("y").innerHTML = PosY;
}
//-->
</script>
<?php startbody(); ?>
<div class="minwidth">
<div id="map" class="map">
<?php
//Display map if selected
if ($map) {
	//Load gametype data
	unset($maps[$map]['gametypes']['theater_conditions']);
	$gametypes = array_keys($maps[$map]['gametypes']);
	//Display map overview
	echo "						<img src='data/materials/{$maps[$map]['overview']['material']}.png' class='map-image' id='map-image' alt='{$map}' style='z-index: 0;'/><br />\n";
	//Try to open decompiled map file to get entity data

	if (file_exists("data/maps/overlays/{$map}.txt")) {
//		$data = $reader->read(strtolower(file_get_contents("data/maps/overlays/{$map}.txt")));
		$data = parseKeyValues(strtolower(file_get_contents("data/maps/overlays/{$map}.txt")));
//var_dump($data);
		foreach ($data as $layername => $layerdata) {
			foreach ($layerdata as $pname => $pdata) {
				if ($pdata['pos_name'] == '') {
					$pdata['pos_name'] = $pname;
				}
				$map_objects[$layername][] = $pdata;
			}
		}
	}
	$navmesh = $map;
	foreach ($maps[$map]['gametypes'] as $gtname => $gtdata) {
		if ($gtname == 'theater_conditions') {
			continue;
		}
		if (isset($gtdata['navfile'])) {
			$navmesh = $gtdata['navfile'];
		}
		//Loop over each point in this gametype
		foreach ($gtdata['controlpoint'] as $cp => $cpdata) {
			$map_objects[$gtname][] = $cpdata;
		}
		foreach ($gtdata['spawnzones'] as $szid => $szdata) {
			if (is_array($gtdata['spawnzones'][$szid])) {
				foreach ($gtdata['spawnzones'][$szid] as $szname => $szdata) {
					$idx = (($gtname == 'checkpoint') || ($gtname == 'outpost') || ($gtname == 'hunt') || ($gtname == 'survival')) ? "{$gtname}_spawns" : $gtname;//"spawns";
					$map_objects[$idx][] = $szdata;
				}
			}
		}
	}
	if (file_exists("{$hlstatsx_heatmaps}/{$map}-kill.png")) {
		$map_objects['heatmap'] = array();
	}
	if (file_exists("data/maps/navmesh/{$navmesh}.png")) {
		$map_objects['navmesh'] = array();
	}
	$map_objects['grid'] = array();

//var_dump($map_objects);
	$teams = array_keys($colors);
	$svg = "";
	foreach ($map_objects as $layername => $layerdata) {
		//Add this layer to the array
		$layers[] = $layername;
		//Display 'common' and first gametype in the list for this map.
		// TODO: Allow URL 'gametype' setting to be set here
		if (($layername == 'common') || ($layername == $gametypes[0])) {
			$display = 'block';
		} else {
			//Hide all other layers
			$display = 'none';
		}
		//Create the layer div
		echo "						<div id='layer-{$layername}' class='overlay' style='display: {$display}'>\n";
		if ($layername == 'navmesh') {
			echo "<img height='1024' width='1024' id='navmesh-overlay' src='data/maps/navmesh/{$navmesh}.png'>\n";
		}
		if ($layername == 'grid') {
			$ovalpha = 48;
			$ovcolor = $maps[$map]['overview']['grid_dark'] ? 'black' : 'white';
			$image_width=1024;
			$image_height=1024;
			$gridsize = $image_width/
			$grid_divisions = isset($maps[$map]['overview']['grid_divisions']) ? $maps[$map]['overview']['grid_divisions'] : 8;
			$margin_top = 16;
			$margin_left = 8;

			echo "
<svg width='{$image_width}' height='{$image_height}' xmlns='http://www.w3.org/2000/svg' id='grid-overlay'>
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
			echo "</svg>\n";
		}






		if ($layername == 'heatmap') {
			echo "<img height='1024' width='1024' id='heatmap-overlay' src='hlstatsx/hlstatsimg/games/insurgency/heatmaps/{$map}-kill.png'>\n";
		}
		foreach ($layerdata as $row) {
			$tclass = $teams[$row['pos_team']];
			//Collect points. This is primarily used for areas and arrows
			//Process based on type of entity
			$mclass = '';
			$eclass = '';
			//Just a hack. Pray for a fix soon....
//var_dump($row);
			switch($row['pos_type']) {
				//Area is a shape with a callout to the legend
				case 'area':
					if (!$row['pos_team'])
						$tclass = 'maparea';
					if (!isset($row['pos_points'])) {
						$min = array($row['pos_x'],$row['pos_y']);
						$max = array(($row['pos_x'] + $row['pos_width']),($row['pos_y'] + $row['pos_height']));
					} else {
						$points = explode(' ',trim($row['pos_points']));
						$min = array(1025,1025);
						$max = array(-1,-1);
						foreach ($points as $pair) {
							$coords = explode(',',$pair);
							if ($min[0] > $coords[0]) $min[0] = $coords[0];
							if ($min[1] > $coords[1]) $min[1] = $coords[1];
							if ($max[0] < $coords[0]) $max[0] = $coords[0];
							if ($max[1] < $coords[1]) $max[1] = $coords[1];
						}
//						var_dump('points',$points,'min',$min,'max',$max,'mid',$mid);
						if ((!isset($row['pos_x'])) || (!isset($row['pos_y']))) {
							$start = explode(',',array_shift($points)); //current(explode(" ",($row['pos_points']))));
							$row['pos_x'] = $start[0];
							$row['pos_y'] = $start[1];
						}
						if (count($points) == 1) {
							$end = explode(',',array_pop($points));
							$row['pos_width'] = abs($row['pos_x'] - $end[0]);
							$row['pos_height'] = abs($row['pos_y'] - $end[1]);
							$row['pos_shape'] = 'rect';
						}
					}
					if (!isset($row['pos_height'])) {
						$row['pos_height'] = abs($max[1] - $min[1]);
					}
					if (!isset($row['pos_width'])) {
						$row['pos_width'] = abs($max[0] - $min[0]);
					}
					$mid = array(round(($max[0]+$min[0])/2),round(($max[1]+$min[1])/2));
					$printname = str_replace('_',' ',$row['pos_name']);
//var_dump($row);
					if ($row['pos_shape'] == 'poly') {
						$svg.="								 <g><polygon id='layer-{$layername}-{$row['pos_name']}-{$tclass}' points='{$row['pos_points']}' style='fill: rgba({$colors[$tclass]},0.5); stroke: black; stroke-width: 2px;'>\n";
						$svg.="									 <title>{$row['pos_name']}</title>\n";
//						$svg.="									 <text x='50%' y='50%' style='font-weight: bold; font-size: 1.5em; text-anchor: middle;'>{$row['pos_name']}</text>\n";
						$svg.="								 </polygon>\n";
//						$svg.="								 <text id='layer-{$layername}-{$row['pos_name']}-callout-text' x='{$mid[0]}' y='{$mid[1]}' style='text-color: #fff; text-anchor: middle;'>{$printname}</text></g>\n";
					} else {
						$svg.="								 <g><rect id='layer-{$layername}-{$row['pos_name']}-{$tclass}' x='{$row['pos_x']}' y='{$row['pos_y']}' height='{$row['pos_height']}' width='{$row['pos_width']}' style='fill: rgba({$colors[$tclass]},0.5); stroke: black; stroke-width: 2px;'>\n";
						$svg.="									 <title>{$row['pos_name']}</title>\n";
//						$svg.="									 <text x='50%' y='50%' style='font-weight: bold; font-size: 1.5em; text-anchor: middle;'>{$row['pos_name']}</text>\n";
						$svg.="								 </rect></g>\n";

/*
						$points = ($row['pos_points']) ? ' L'.implode(' ',explode(',',implode(' L',explode(' ',$row['pos_points'])))) : " L{$row['pos_x']} {$row['pos_y']}";

						echo "								<div id='{$layername}-{$row['pos_type']}-{$row['pos_name']}-{$tclass}' class='area {$tclass}' style='position: absolute; left: {$row['pos_x']}px; top: {$row['pos_y']}px; width: {$row['pos_width']}px; line-height: {$row['pos_height']}px; height: {$row['pos_height']}px;'>";
						if (strlen($row['pos_name']) == 1)
							echo "{$row['pos_name']}";
						echo "</div>\n";
//							echo "									<div id='{$layername}-{$row['pos_type']}-{$row['pos_name']}-{$tclass}-label' class='maplabel {$tclass}' style='width: 100%; height: 100%;'>{$printname}</div>\n";
*/


					}
					$margin = 0;
					if ($row['pos_height'] > 50) {
						echo "								<div id='{$layername}-{$row['pos_type']}-{$row['pos_name']}-{$tclass}-label' style='position: absolute; left: ".($min[0] + $margin)."px; top: ".($mid[1] - 24 + $margin)."px; width: ".($row['pos_width'] - ($margin*2))."px; z-index: 10; text-align: center; vertical-align: middle; font-weight: bold; color: #fff;'>{$printname}</div>\n";
					} else {
						echo "								<div id='{$layername}-{$row['pos_type']}-{$row['pos_name']}-{$tclass}-label' style='position: absolute; left: ".($min[0] + $margin)."px; top: ".($min[1] + $margin)."px; height: ".($row['pos_height'] - ($margin*2))."px; line-height: ".($row['pos_height'] - ($margin*2))."px; width: ".($row['pos_width'] - ($margin*2))."px; z-index: 10; text-align: center; vertical-align: middle; display: table-cell;'><div style='position: relative; font-weight: bold; color: #fff; z-index: 10; float: auto; margin: auto; display: block; line-height: 16px;'>{$printname}</div></div>\n";
					}

					break;
				case 'point':
					if ($row['pos_type'] == 'point') {
						$keypoints[$layer][] = $row['pos_name'];
						$svg.="								 <g><circle id='layer-{$layername}-{$row['pos_name']}-point' cx='{$row['pos_x']}' cy='{$row['pos_y']}' r='10'	 style='fill: red; stroke: black; stroke-width: 2;'>\n";
						$svg.="									 <title>{$row['pos_name']}</title>\n";
						$svg.="									 <text x='50%' y='50%' style='font-weight: bold; font-size: 1.5em; text-anchor: middle;'>{$row['pos_name']}</text>\n";
						$svg.="								 </circle></g>\n";
					}



					if (strlen($row['pos_name']) > 1) {
/*
						$svg.="								 <path id='layer-{$layername}-{$row['pos_name']}-callout-line' d='M {$row['pos_x']} {$row['pos_y']} L 1024 {$row['pos_y']}' style='fill: black; stroke: black; stroke-width: 2;' />\n";
						$svg.="								 <path id='layer-{$layername}-{$row['pos_name']}-callout-line-dots' d='M {$row['pos_x']} {$row['pos_y']} L 1024 {$row['pos_y']}' stroke-dasharray='5,5' style='fill: black; stroke: white; stroke-width: 2;' />\n";
						$svg.="								 <rect id='layer-{$layername}-{$row['pos_name']}-callout-box' x='1024' y='{$row['pos_y']}' height='40' width='150' style='fill: white; stroke: black; stroke-width: 2;' />\n";
						$svg.="								 <text id='layer-{$layername}-{$row['pos_name']}-callout-text' x='1112' y='".($row['pos_y'] + 30)."' style='font-weight: bold; font-size: 1.5em; text-anchor: middle;'>{$row['pos_name']}</text>\n";
//						$svg.="								 <path id='layer-{$layername}-{$row['pos_name']}' d='M {$row['pos_x']} {$row['pos_y']}{$points}' style='fill: rgba(0,255,0,0.5); stroke: black; stroke-width: 2px;' />\n";
*/
					}




					break;
					//Arrow is a group of lines that can have multiple waypoints in between
				case 'arrow':
					$svg.="								 <path id='layer-{$layername}-{$row['pos_name']}' d='M {$row['pos_points']}' style='fill:none; stroke:blue; stroke-width:10; marker-start: url(#arrowhead); marker-mid: url(#arrowhead); marker-end: url(#arrowhead);'>\n";
					$svg.="									 <title>{$row['pos_name']}</title>\n";
					$svg.="								 </path>\n";
					break;
					//Spawn zone
				case 'ins_blockzone':
				case 'ins_spawnzone':
					echo "								<div id='{$layername}-{$row['pos_type']}-{$row['pos_name']}-{$tclass}' class='{$tclass} area' style='position: absolute; left: {$row['pos_x']}px; top: {$row['pos_y']}px; height: {$row['pos_height']}; width: {$row['pos_width']};'></div>\n";
					echo "								<div id='{$layername}-{$row['pos_type']}-{$row['pos_name']}-{$tclass}-label' class='area-label' style='position: absolute; left: {$row['pos_x']}px; top: {$row['pos_y']}px; height: {$row['pos_height']}; width: {$row['pos_width']};'>{$row['pos_name']}</div>\n";
					break;
					//Caches get the hashed icon
				case 'obj_weapon_cache':
				case 'cache':
					$mclass = "obj_cache_{$tclass}";// hatched-{$tclass}";
					//Default is the diamond icon on the map
				case 'controlpoint':
				case 'point_controlpoint':
				default:
					$mclass = ($mclass != '') ? $mclass : "obj_{$tclass}";
/*
					$svg.="								 <g><rect id='layer-{$layername}-{$row['pos_name']}-{$tclass}' mask='url(#{$mclass})' x='{$row['pos_x']}' y='{$row['pos_y']}' height='32' width='32' style='fill: rgba({$colors[$tclass]},0.75);'>\n";
					$svg.="									 <title>{$row['pos_name']}</title>\n";
					$svg.="									 <text x='50%' y='50%' style='font-weight: bold; font-size: 1.5em; text-anchor: middle;'>{$row['pos_name']}</text>\n";
					$svg.="								 </rect></g>\n";
*/
					echo "								<div id='{$layername}-{$row['pos_type']}-{$row['pos_name']}-{$tclass}' class='obj {$mclass}_bg' style='position: absolute; left: {$row['pos_x']}px; top: {$row['pos_y']}px;'></div>\n";
					echo "								<div id='{$layername}-{$row['pos_type']}-{$row['pos_name']}-{$tclass}' class='obj {$mclass}_fg' style='position: absolute; left: {$row['pos_x']}px; top: {$row['pos_y']}px;'>{$row['pos_name']}</div>\n";
			}
		}
		if ($svg) {
			svg($svg,$layername);
			$svg = "";
		}
		echo "						</div>\n";
	}
	echo "					</div>\n";
}

	//Right column
	echo "					<div id='key' class='key'>
								<form>Map: <select name='map'>";
	foreach ($maps as $mapname => $mapdata) {
		$sel = ($mapname == $map) ? ' SELECTED' : '';
		echo "<option{$sel}>{$mapname}</option>\n";
	}
	echo "</select><input type='submit' value='Go'></form>\n";
if ($map) {
?>
						<h2>MAP OVERLAYS</h2>
						<form><input id='gametypes' type='button' value='ON' onclick="toggleButton(this)" />
						<select id='gametype' onchange='changeGTLayer()'>
<?php
	foreach ($gametypes as $gt) {
		echo "							<option value='{$gt}'>{$gt}</option>\n";
	}
	echo "						</select><br />\n";
	//Display any other layers as a name and a toggle button
	$ngt = array_diff($layers,$gametypes);
	foreach ($ngt as $ngl) {
		if ($ngl == 'common') { $state = 'ON'; } else { $state = 'OFF'; }
		echo "						<input id='{$ngl}' type='button' value='{$state}' onclick=\"toggleButton(this)\" />{$ngl}<br />\n";
	}
echo "		</form>\n";
}
?>
		<h2>MAP KEY</h2>
								<input type='text' name='regcoords' id='regcoords' size='40' /><div id='coords'><span>x=</span><span id='x'></span><br /><span>y=</span><span id='y'></span><br /></div><br />
</div>
</div>
<script type="text/javascript"><!--
/*
 Here add the ID of the HTML elements for which to show the mouse coords
 Within quotes, separated by comma.
 E.g.:	 ['imgid', 'divid'];
*/
var elmids = ['map-image','grid-overlay','navmesh-overlay','heatmap-overlay'<?php if (count($layers)) { echo ",'svg-".implode("','svg-",$layers)."'"; } ?>];

var x, y = 0;				// variables that will contain the coordinates

// Get X and Y position of the elm (from: vishalsays.wordpress.com)
function getXYpos(elm) {
	x = elm.offsetLeft;				 // set x to el offsetLeft
	y = elm.offsetTop;				 // set y to el offsetTop

	elm = elm.offsetParent;		 // set elm to its offsetParent

	//use while loop to check if elm is null
	// if not then add current elmoffsetLeft to x
	//offsetTop to y and set elm to its offsetParent
	while(elm != null) {
		x = parseInt(x) + parseInt(elm.offsetLeft);
		y = parseInt(y) + parseInt(elm.offsetTop);
		elm = elm.offsetParent;
	}

	// returns an object with "xp" (Left), "=yp" (Top) position
	return {'xp':x, 'yp':y};
}

// Get X, Y coords, and displays Mouse coordinates
function getCoords(e) {
 // coursesweb.net/
	var xy_pos = getXYpos(this);

	// if IE
	if(navigator.appVersion.indexOf("MSIE") != -1) {
		// in IE scrolling page affects mouse coordinates into an element
		// This gets the page element that will be used to add scrolling value to correct mouse coords
		var standardBody = (document.compatMode == 'CSS1Compat') ? document.documentElement : document.body;

		x = event.clientX + standardBody.scrollLeft;
		y = event.clientY + standardBody.scrollTop;
	}
	else {
		x = e.pageX;
		y = e.pageY;
	}

	x = x - xy_pos['xp'];
	y = y - xy_pos['yp'];

	// displays x and y coords in the #coords element
	document.getElementById('coords').innerHTML = 'X= '+ x+ ' ,Y= ' +y;
}

// register onmousemove, and onclick the each element with ID stored in elmids
for(var i=0; i<elmids.length; i++) {
	if(document.getElementById(elmids[i])) {
		// calls the getCoords() function when mousemove
		document.getElementById(elmids[i]).onmousemove = getCoords;

		// execute a function when click
		document.getElementById(elmids[i]).onclick = function() {
			document.getElementById('regcoords').value = document.getElementById('regcoords').value + x + ',' + y + ' ';
		};
	}
}
--></script>

<?php
require "include/footer.php";
//echo "<pre>\n";
//var_dump($maps);
//var_dump($map_objects);

?>
