<?php
//Set paths
$scriptpath = realpath(dirname(__FILE__));
$rootpath=dirname(dirname($scriptpath));

//Include key-value reader
require_once "{$rootpath}/include/kvreader2.php";
require_once "{$rootpath}/include/functions.php";

//Get all map text files. This could probably be safer.
$mapfilter = isset($_REQUEST['mapfilter']) ? $_REQUEST['mapfilter'] : '*';
$files = glob("{$rootpath}/data/resource/overviews/{$mapfilter}.txt");

if (php_sapi_name() == "cli") {
	$linebreak="\n";
} else {
	$linebreak="<br>\n";
}
//Open all files and add gamemodes and other map info to array
foreach ($files as $file) {
	$map = basename($file,".txt");
	ParseMap($map,isset($_REQUEST['force']));
}
//Parse the map into JSON
function ParseMap($map,$force)
{
	global $rootpath,$linebreak;
	echo "Checking {$map}...{$linebreak}";
	$maps = array();
	$controlpoints = array();
	$map_objects = array();
	$reader = new KVReader();
	//Load cpsetup.txt for map
	if (!file_exists("{$rootpath}/data/maps/{$map}.txt")) {
		return;
	}
	//Check if we need to run the parser. Unless forced, this will not run if the JSON output is newer than the cpsetup.txt file
	if (file_exists("{$rootpath}/data/maps/parsed/{$map}.json") && (!$force)) {
		if (
			((filemtime("{$rootpath}/data/maps/{$map}.txt")) < (filemtime("{$rootpath}/data/maps/parsed/{$map}.json")))
			&& ((filemtime("{$rootpath}/data/resource/overviews/{$map}.txt")) < (filemtime("{$rootpath}/data/maps/parsed/{$map}.json")))
			&& ((filemtime("{$rootpath}/data/maps/src/{$map}_d.vmf")) < (filemtime("{$rootpath}/data/maps/parsed/{$map}.json")))
		) {
			echo "Not processing {$map}, no new data{$linebreak}";
			return;
		}
	}

	//Load cpsetup.txt
	$data = $reader->read(strtolower(file_get_contents("{$rootpath}/data/maps/{$map}.txt")));

	//Pull first element from KeyValues (since custom mappers don't reliably use "cpsetup.txt" we don't get it by name)
	$maps[$map]['gametypes'] = current($data);

	//Parse other sections that are not game modes. TODO: Use game modes selector to handle this, so rather than specifying non-gamemode sections, we parse anything
	foreach (array('theater_conditions','navfile','nightlighting') as $section) {
		if (isset($maps[$map]['gametypes'][$section])) {
			$maps[$map][$section] = $maps[$map]['gametypes'][$section];
			if (is_array($maps[$map]['gametypes'][$section])) {
				unset($maps[$map]['gametypes'][$section]);
			}
		}
	}

	//Get overview information (file, position, scale)
	$lines = file("{$rootpath}/data/resource/overviews/{$map}.txt", FILE_IGNORE_NEW_LINES);
	foreach ($lines as $line) {
		$data = explode("\t",preg_replace('/\s+/', "\t",str_replace('"','',trim($line))));
		if (isset($data[1])) {
			$maps[$map]['overview'][$data[0]] = (is_numeric($data[1])) ? (float)$data[1] : $data[1];
		}
	}

	//Parse the decompiled VMF file
	if (file_exists("{$rootpath}/data/maps/src/{$map}_d.vmf")) {
                //Change to lowercase to make array indexing simpler
                $data =  preg_replace('/[\x00-\x08\x14-\x1f]+/', '', strtolower(file_get_contents("{$rootpath}/data/maps/src/{$map}_d.vmf")));
                $data = preg_replace('/(\s)([a-zA-Z0-9]+)(\s+{)/','${1}"${2}"${3}',"\"map\" {\n{$data}}");
		$test = $reader->read($data);
                //Get all entity{} objects, including nested objects
                preg_match_all('/entity[^{]+(\{(?:[^}]++|(?R))*+)\}/',$data,$matches);
                //Process entities
                foreach ($matches[1] as $rawent) {
                        //Encapsulate solid and side object identifiers in quotes so KV reader can process them
                        $rawent = preg_replace('/(\s+)(solid|side)(\s+)/','${1}"${2}"${3}',$rawent);
                        //Read in KV
                        $entity = $reader->read($rawent);
			//Only interested in certain entities
			if (in_array($entity['classname'],array("trigger_capture_zone","point_controlpoint","obj_weapon_cache","ins_spawnzone","ins_blockzone")) !== false) {
				//Special processing for spawnzone
				if ($entity['classname'] == "trigger_capture_zone") {
					continue;
					$entity['targetname'] = $entity['controlpoint'];
					$entity['classname'] = 'point_controlpoint';
				}
				$point = CreatePoint($entity,$maps[$map]);
				$entname = $point['pos_name'];//(isset($entity['controlpoint'])) ? $entity['controlpoint'] : $entity['targetname'];
				if (isset($entity['solid'])) {
					if (isset($entity['solid']['is_multiple_array'])) {
						$entity['solid'] = $entity['solid'][0]; //Temp hack for complex zones
					}
						$point['pos_type'] = 'area';
					//This is silly, but I add together all the coordinates and average them to get the actual location on the map.
					// I think a better way is to actually calculate the difference and average that way.
					// TODO: Send all coord numbers into array, then sort and get min/max to average that way
					$path = array();
					foreach ($entity['solid']['side'] as $side) {
						if (isset($side['plane'])) {
							preg_match_all('#\(([^)]+)\)#',$side['plane'],$coord);
							//Add coordinate to collection
							foreach ($coord[1] as $xyz) {
								$xyz = explode(' ',$xyz);
								$vector = round(abs(($xyz[0] - $maps[$map]['overview']['pos_x']) / $maps[$map]['overview']['scale'])).','.round(abs(($xyz[1] - $maps[$map]['overview']['pos_y']) / $maps[$map]['overview']['scale']));//.','.round($xyz[2]/$maps[$map]['overview']['scale']);
								$path[$vector] = $vector;
							}
						}
					}
					//This is terrible logic that loops through the path points and calculates the high/low points for shape
					if (count($path)) {

						$min = array(0 => -1, 1 => -1);
						$max = array(0 => -1, 1 => -1);
						foreach ($path as $coord) {
							$vector = explode(',',$coord);
							$min[0] = (($vector[0] < $min[0]) || !isset($min[0]) || ($min[0] < 0)) ? $vector[0] : $min[0];
							$min[1] = (($vector[1] < $min[1]) || !isset($min[1]) || ($min[1] < 0)) ? $vector[1] : $min[1];
							$max[0] = (($vector[0] > $max[0]) || !isset($max[0])) ? $vector[0] : $max[0];
							$max[1] = (($vector[1] > $max[1]) || !isset($max[1])) ? $vector[1] : $max[1];
						}
						if (count($path) == 4) {
							$point['pos_x'] = (int)$min[0];
							$point['pos_y'] = (int)$min[1];
							$point['pos_width'] = (int)($max[0] - $min[0]);
							$point['pos_height'] = (int)($max[1] - $min[1]);
							$point['pos_shape'] = 'rect';
						} else {
							$point['pos_shape'] = 'poly';
							if ($point['pos_x'] < 1) {
								unset($path["{$min[0]},{$min[1]}"]);
								$point['pos_x'] = (int)$min[0];
								$point['pos_y'] = (int)$min[1];
							}
							$point['pos_points'] = implode(' ',$path);
						}
					}
				}
				//Hackly logic to allow merging of cache/control point data gracefully no matter what order the entities come in
				foreach ($point as $key => $val) {
					if (!($maps[$map]['points'][$entname][$key])) {
						$maps[$map]['points'][$entname][$key] = $val;
					}
				}
			}
		}
	}
	//Process game type data for this map
	foreach ($maps[$map]['gametypes'] as $gtname => $gtdata) {
		//Create an array called cps with the names of all the control points for this mode
		if (!isset($gtdata['controlpoint'])) {
			continue;
		}
		if (!is_array($gtdata['controlpoint']))
			$maps[$map]['gametypes'][$gtname]['controlpoint'] = array($gtdata['controlpoint']);
		$cps = $maps[$map]['gametypes'][$gtname]['controlpoint'];
		//var_dump($cps);
		//Process any entities in the gamedata text file.
		$entlist = array();
		foreach ($gtdata['entities'] as $entname => $entity) {
			//var_dump($entname);
			//var_dump($entity);
			//KV reader now handles multiple like-named resources by creating a numerically indexed array
			//When doing that, the is_multiple_array flag is set
			if (isset($entity['is_multiple_array'])) {
				//If multiple items, send each to the array
				foreach ($entity as $subent) {
					if (is_array($subent)) {
						$subent['classname'] = $entname;
						$entlist[] = CreatePoint($subent,$maps[$map]);
					}
				}
			} else {
				//Otherwise, pack the single item
				$entity['classname'] = $entname;
				$entlist[] = CreatePoint($entity,$maps[$map]);
			}
		}
//var_dump($entlist);
		//Process all gamedata entities that are referenced by the controlpoints list
var_dump($entlist);
		foreach ($entlist as $id => $entity) {
			$cp = $entity['pos_name'];//(isset($entity['controlpoint'])) ? $entity['controlpoint'] : $entity['targetname'];
			foreach ($entity as $key => $val) {
				if ((!isset($maps[$map]['gametypes'][$gtname]['points'][$cp][$key])) || ((@$entity['targetname'] == $cp) && ($key != 'classname')) || ((@$entity['targetname'] != $cp) && ($key == 'classname'))) {
					$maps[$map]['gametypes'][$gtname]['points'][$cp][$key] = $val;
				}
			}
		}
		//chr 65 is uppercase A. This lets me 'increment' letters
		$chr = 65;
		foreach ($cps as $idx => $cp) {
			$cpname = chr($chr);
			unset($maps[$map]['gametypes'][$gtname]['controlpoint'][$idx]);
			$maps[$map]['gametypes'][$gtname]['controlpoint'][$cpname] = (isset($maps[$map]['gametypes'][$gtname]['points'][$cp])) ? $maps[$map]['gametypes'][$gtname]['points'][$cp] : $maps[$map]['points'][$cp];
			//Set point name to the letter of the objective
			//$maps[$map]['gametypes'][$gtname]['points'][$cp]
			$maps[$map]['gametypes'][$gtname]['controlpoint'][$cpname]['pos_name'] = $cpname;
			if (isset($gtdata['attackingteam'])) {
				$maps[$map]['gametypes'][$gtname]['controlpoint'][$cpname]['pos_team'] = ($gtdata['attackingteam'] == 'security') ? 3 : 2;
			}
			$chr++;
		}
		//Bullshit to add teams to points, Skirmish game logic does it instead of saving it in the maps.
		if ($gtname == 'skirmish') {
			$maps[$map]['gametypes'][$gtname]['controlpoint']['B']['pos_team'] = 2;
			$maps[$map]['gametypes'][$gtname]['controlpoint']['D']['pos_team'] = 3;
		}
		//Same deal for Firefight
		if ($gtname == 'firefight') {
			$maps[$map]['gametypes'][$gtname]['controlpoint']['A']['pos_team'] = 2;
			$maps[$map]['gametypes'][$gtname]['controlpoint']['C']['pos_team'] = 3;
		}

		//Parse spawn zones. This is tricky because there will usually be two zones with the same targetname
		// but different teamnum. This is to allow spawning to move as the game changes I believe.
		foreach ($gtdata['spawnzones'] as $szid => $szname) {
			if (is_numeric($szid)) {
				unset($maps[$map]['gametypes'][$gtname]['spawnzones'][$szid]);
				$sz = array();
				foreach (array('_team2','_team3') as $suffix) {
					if (isset($maps[$map]['points']["{$szname}{$suffix}"]))
						$sz["{$szname}{$suffix}"] = $maps[$map]['points']["{$szname}{$suffix}"];
				}
				$maps[$map]['gametypes'][$gtname]['spawnzones'][$szname] = $sz;
			}
		}
		if (is_array($maps[$map]['gametypes'][$gtname]['points'])) {
			unset($maps[$map]['gametypes'][$gtname]['points']);
		}
		if (is_array($maps[$map]['gametypes'][$gtname]['entities'])) {
			unset($maps[$map]['gametypes'][$gtname]['entities']);
		}
	}
	$json = prettyPrint(json_encode($maps[$map]));
	file_put_contents("{$rootpath}/data/maps/parsed/{$map}.json",$json);
	echo "Parsed {$map}{$linebreak}";
}

exit;
//Process an entity and prepare it for display on the map
function CreatePoint($entity,$map) {
	$point = array();
/*
	$fields = array('classname','targetname','controlpoint','teamnum','origin');
	foreach ($fields as $field)
	{
		if (isset($entity[$field]))
			$point[$field] = $entity[$field];
	}
*/
	if (isset($entity['origin'])) {
		$coords = preg_split('/\s+/',$entity['origin']);
		if (!isset($point['pos_x']))
			$point['pos_x'] = round(abs(($coords[0] - $map['overview']['pos_x']) / $map['overview']['scale']));
		if (!isset($point['pos_y']))
			$point['pos_y'] = round(abs(($coords[1] - $map['overview']['pos_y']) / $map['overview']['scale']));
	}
	if (isset($entity['classname']) && !isset($point['pos_classname'])) {
		$point['pos_classname'] = $entity['classname'];
	}
	if (isset($entity['blockzone']) && !isset($point['pos_blockzone'])) {
		$point['pos_blockzone'] = $entity['blockzone'];
	}
	if (isset($entity['classname']) && !isset($point['pos_type'])) {
		$point['pos_type'] = $entity['classname'];
	}
	if (isset($entity['teamnumber']) && !isset($point['pos_team'])) {
		$point['pos_team'] = $entity['teamnumber'];
	}
	if (isset($entity['teamnum']) && !isset($point['pos_team'])) {
		$point['pos_team'] = $entity['teamnum'];
	}
	if (isset($entity['TeamNum']) && !isset($point['pos_team'])) {
		$point['pos_team'] = $entity['TeamNum'];
	}
	if ($entity['classname'] == "ins_spawnzone") {
		$point['pos_name'] = "{$entity['targetname']}_team{$point['pos_team']}";
	}
	if (isset($entity['controlpoint']) && !isset($point['pos_name'])) {
		$point['pos_name'] = $entity['controlpoint'];
	}
	if (isset($entity['targetname']) && !isset($point['pos_name'])) {
		$point['pos_name'] = $entity['targetname'];
	}
	if (!isset($point['pos_team'])) {
		$point['pos_team'] = 0;
	}
	$point['pos_team'] = (int)$point['pos_team'];
	$point['pos_x'] = (int)$point['pos_x'];
	$point['pos_y'] = (int)$point['pos_y'];
	
	return $point;
}

