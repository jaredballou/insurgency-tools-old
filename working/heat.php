<pre>
<?php
	mysql_connect("localhost","hlstatsx","1WHbqyE7SjUkV6pq");
	mysql_select_db("hlstatsx");
$map = "ministry_coop";

//Maps array holds all map data
$maps = array();
$map_objects = array();
//Get all map text files. This could probably be safer.
$files = glob("maps/overviews/*.txt");
//Include key-value reader
require_once "kvreader2.php";
$reader = new KVReader();
//Open all files and add gamemodes and other map info to array
foreach ($files as $file) {
	$mapname = basename($file,".txt");
	$data = $reader->read(strtolower(file_get_contents("maps/{$mapname}.txt")));
	$maps[$mapname]['gametypes'] = current($data);
	if (isset($maps[$mapname]['gametypes']['theater_conditions'])) {
		$maps[$mapname]['theater_conditions'] = $maps[$mapname]['gametypes']['theater_conditions'];
		unset($maps[$mapname]['gametypes']['theater_conditions']);
	}
	//Get overview information (file, position, scale)
	$lines = file("maps/overviews/{$mapname}.txt", FILE_IGNORE_NEW_LINES);
	foreach ($lines as $line) {
		$data = explode("\t",preg_replace('/\s+/', "\t",str_replace('"','',trim($line))));
		if (isset($data[1])) {
			$maps[$mapname]['overview'][$data[0]] = $data[1];
//var_dump($data);
		}
	}
}
//var_dump($maps);
foreach ($maps as $mapname => $data) {
	$flipx = ($data['overview']['pos_x'] < 0) ? 1 : 0;
	$flipy = ($data['overview']['pos_y'] < 0) ? 1 : 0;
	$xoffset = abs($data['overview']['pos_x']);
	$yoffset = abs($data['overview']['pos_y']);
	echo "insert into hlstats_Heatmap_Config (map,game,xoffset,yoffset,flipx,flipy,scale) VALUES('{$mapname}','insurgency','{$xoffset}','{$yoffset}','{$flipx}','{$flipy}','{$data['overview']['scale']}');\n";
}
exit;
echo "<img src='maps/{$maps[$map]['overview']['material']}.png' class='map' id='map-image' alt='{$map}'/>\n";

echo "							<svg xmlns='http://www.w3.org/2000/svg' height='1024' width='1600' style='position: absolute; left: 0px; top: 0px;' id='map-overlay'>\n";
echo "								<defs>\n";
echo "									<marker id='arrowhead' viewBox='0 0 10 10' refX='1' refY='5' markerUnits='strokeWidth' orient='auto' markerWidth='4' markerHeight='3'><polyline points='0,0 10,5 0,10 1,5' fill='darkblue' /></marker>\n";
echo "								</defs>\n";


$result = mysql_query("SELECT * FROM hlstats_Events_Frags WHERE map='{$map}'");
while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
	$entity = array();
	$entity['pos_x'] = round(abs(($row['pos_x'] - $maps[$map]['overview']['pos_x']) / $maps[$map]['overview']['scale']));
	$entity['pos_y'] = round(abs(($row['pos_y'] - $maps[$map]['overview']['pos_y']) / $maps[$map]['overview']['scale']));
	$entity['pos_victim_x'] = round(abs(($row['pos_victim_x'] - $maps[$map]['overview']['pos_x']) / $maps[$map]['overview']['scale']));
	$entity['pos_victim_y'] = round(abs(($row['pos_victim_y'] - $maps[$map]['overview']['pos_y']) / $maps[$map]['overview']['scale']));
//var_dump($row);
//var_dump($entity);
echo "								<circle id='layer-{$layer}-{$row['id']}-point' cx='{$entity['pos_victim_x']}' cy='{$entity['pos_victim_y']}' r='10'	 style='fill: red; stroke: black; stroke-width: 2;' />\n";
}
echo "							</svg>\n";
