#!/usr/bin/env php
<?php
/*
================================================================================
mapdata.php
(c) 2015,2016 Jared Ballou <insurgency@jballou.com>

This is a tool to parse the map data files (Decompiled source, Overview, and
CPSetup text file) into the JSON format for the web viewer. It does a lot of
modification to the data, converts all coordinates to map to the 1024x1024
overview image, and adds in some information about the entities and points.
================================================================================
*/

// Set paths
$scriptpath = realpath(dirname(__FILE__));
$rootpath=dirname(dirname($scriptpath));

// Include key-value reader
require_once "{$rootpath}/include/functions.php";
require_once "{$rootpath}/working/theater/kvreader2.php";

// Set linebreak character
if (php_sapi_name() == "cli") {
	$mapfilter = (isset($argv[1])) ? $argv[1] : '*';
	$force = (isset($argv[2])) ? $argv[2] : 0;
	$linebreak="\n";
} else {
	$mapfilter = isset($_REQUEST['mapfilter']) ? $_REQUEST['mapfilter'] : '*';
	$force = (isset($_REQUEST['force']));
	$linebreak="<br>\n";
}

// Get all map text files. This could probably be safer.
$files = glob("{$datapath}/resource/overviews/{$mapfilter}.txt");

// Open all files and add gamemodes and other map info to array
foreach ($files as $file) {
	$mapname = basename($file,".txt");
	ParseMap($mapname,$force);
}

exit;
// Parse the map into JSON
function recur_ksort(&$array) {
   foreach ($array as &$value) {
      if (is_array($value)) recur_ksort($value);
   }
   return ksort($array);
}
function ParseMap($mapname,$force)
{
	global $datapath,$linebreak,$gametypelist;
	echo "Checking {$mapname}... ";
	$controlpoints = array();
	$map_objects = array();
	$map = array();
	$reader = new KVReader();
	//Check if we need to run the parser. Unless forced, this will not run if the JSON output is newer than the cpsetup.txt file
	$dstfile = "{$datapath}/maps/parsed/{$mapname}.json";
	if (file_exists($dstfile)) {
		$dstdata = json_decode(file_get_contents($dstfile),true);
	}
	$srcfiles = array(
		"CPSetup"    => "{$datapath}/maps/{$mapname}.txt",
		"Overview"   => "{$datapath}/resource/overviews/{$mapname}.txt",
		"VMF Source" => "{$datapath}/maps/src/{$mapname}_d.vmf",
	);
	// Check source files
	foreach ($srcfiles as $name => $file) {
		if (!file_exists($file)) {
			echo "FAIL: Missing {$name} \"{$file}\"!{$linebreak}";
			return;
		}
		// Get MD5 of source file
		$map['source_files'][$name] = md5_file($file);
		// If the MD5 sums don't match up, force it
		if (file_exists($dstfile)) {
			if ($dstdata['source_files'][$name] != $map['source_files'][$name])
				$force=1;
		}
	}
	if (file_exists($dstfile) && (!$force)) {
		echo "OK: no new data.{$linebreak}";
		return;
	}

//TODO: Proper KeyValues parser!!!
	//Load cpsetup.txt
	$data = $reader->read(strtolower(file_get_contents("{$datapath}/maps/{$mapname}.txt")));

	foreach ($data as $name=>$item) {
		if ($name == "#base") {
			$data = array_merge_recursive($reader->read(strtolower(file_get_contents("{$datapath}/maps/{$item}"))),$data);
			unset($data[$name]);
		}
	}
	foreach ($data as $name=>$item) {
		if (is_array($item)) {
			foreach ($item as $key=>$val) {
				if (in_array($key,array_keys($gametypelist))) {
					$map['gametypes'][$key] = $val;
				} else {
					$map[$key] = $val;
				}
			}
		}
	}
	//Get overview information (file, position, scale)
	$lines = file("{$datapath}/resource/overviews/{$mapname}.txt", FILE_IGNORE_NEW_LINES);
	foreach ($lines as $line) {
		$data = explode("\t",preg_replace('/\s+/', "\t",str_replace('"','',trim($line))));
		if (isset($data[1])) {
			$map['overview'][$data[0]] = (is_numeric($data[1])) ? (float)$data[1] : $data[1];
		}
	}

	//Parse the decompiled VMF file
	if (file_exists("{$datapath}/maps/src/{$mapname}_d.vmf")) {
		// Remove non-printable characters to make processing easier
                $data =  preg_replace('/[\x00-\x08\x14-\x1f]+/', '', strtolower(file_get_contents("{$datapath}/maps/src/{$mapname}_d.vmf")));
                //Change to lowercase to make array indexing simpler
                $data = preg_replace('/(\s*)([a-zA-Z0-9]+)(\s*{)/','${1}"${2}"${3}',$data);
                //Get all nested objects
		preg_match_all('~[^{}]+ { ( (?>[^{}]+) | (?R) )* } ~x',$data,$matches);
                //Process entities
                foreach ($matches[0] as $rawent) {
                        //Read in KV
                        $object = $reader->read($rawent);
			$type = implode('',array_keys($object));
			if ($type == "entity") {
				$entity=$object[$type];
			} else {
				continue;
			}
//			if ($object

			//Only interested in certain entities
			$classnames = array(
//				"trigger_capture_zone",
				"point_controlpoint",
				"obj_weapon_cache",
				"ins_spawnzone",
				"ins_blockzone"
			);

			if (in_array($entity['classname'],$classnames) !== false) {
				//Special processing for capture zone
				if ($entity['classname'] == "trigger_capture_zone") {
//					continue;
					$entity['targetname'] = $entity['controlpoint'];
					$entity['classname'] = 'point_controlpoint';
				}
				// Create data structure for point
				$point = CreatePoint($entity,$map);
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
								$vector = round(abs(($xyz[0] - $map['overview']['pos_x']) / $map['overview']['scale'])).','.round(abs(($xyz[1] - $map['overview']['pos_y']) / $map['overview']['scale']));//.','.round($xyz[2]/$map['overview']['scale']);
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
						// Count the sides to see if this is a square or not.
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
					if (!isset($map['points'][$entname][$key])) {
						$map['points'][$entname][$key] = $val;
					}
				}
			}
		}
	}
	//Process game type data for this map
	foreach ($map['gametypes'] as $gtname => $gtdata) {
		//Create an array called cps with the names of all the control points for this mode
		if (!isset($gtdata['controlpoint'])) {
			continue;
		}
		if (!is_array($gtdata['controlpoint']))
			$map['gametypes'][$gtname]['controlpoint'] = array($gtdata['controlpoint']);
		$cps = $map['gametypes'][$gtname]['controlpoint'];
		//Process any entities in the gamedata text file.
		$entlist = array();
		if (!isset($gtdata['entities'])) {
			continue;
		}
		foreach ($gtdata['entities'] as $entname => $entity) {
			//KV reader now handles multiple like-named resources by creating a numerically indexed array
			//When doing that, the is_multiple_array flag is set
			if (isset($entity['is_multiple_array'])) {
				//If multiple items, send each to the array
				foreach ($entity as $subent) {
					if (is_array($subent)) {
						$subent['classname'] = $entname;
						$entlist[] = CreatePoint($subent,$map);
					}
				}
			} else {
				//Otherwise, pack the single item
				$entity['classname'] = $entname;
				$entlist[] = CreatePoint($entity,$map);
			}
		}
		//Process all gamedata entities that are referenced by the controlpoints list
		foreach ($entlist as $id => $entity) {
			if (!isset($entity['pos_name'])) {
				continue;
			}
			$cp = $entity['pos_name'];
//(isset($entity['controlpoint'])) ? $entity['controlpoint'] : $entity['targetname'];
			foreach ($entity as $key => $val) {
				if ((!isset($map['gametypes'][$gtname]['points'][$cp][$key])) || ((@$entity['targetname'] == $cp) && ($key != 'classname')) || ((@$entity['targetname'] != $cp) && ($key == 'classname'))) {
					$map['gametypes'][$gtname]['points'][$cp][$key] = $val;
				}
			}
		}
		//chr 65 is uppercase A. This lets me 'increment' letters
		$chr = 65;
		// Loop through control points and name them
		foreach ($cps as $idx => $cp) {
			$cpname = chr($chr);
			unset($map['gametypes'][$gtname]['controlpoint'][$idx]);
			$map['gametypes'][$gtname]['controlpoint'][$cpname] = (isset($map['gametypes'][$gtname]['points'][$cp])) ? $map['gametypes'][$gtname]['points'][$cp] : $map['points'][$cp];
			//Set point name to the letter of the objective
			//$map['gametypes'][$gtname]['points'][$cp]
			$map['gametypes'][$gtname]['controlpoint'][$cpname]['pos_name'] = $cpname;
			if (isset($gtdata['attackingteam'])) {
				$map['gametypes'][$gtname]['controlpoint'][$cpname]['pos_team'] = ($gtdata['attackingteam'] == 'security') ? 3 : 2;
			}
			$chr++;
		}
		//Bullshit to add teams to points, Skirmish game logic does it instead of saving it in the maps.
		if ($gtname == 'skirmish') {
			$map['gametypes'][$gtname]['controlpoint']['B']['pos_team'] = 2;
			$map['gametypes'][$gtname]['controlpoint']['D']['pos_team'] = 3;
		}
		//Same deal for Firefight
		if ($gtname == 'firefight') {
			$map['gametypes'][$gtname]['controlpoint']['A']['pos_team'] = 2;
			$map['gametypes'][$gtname]['controlpoint']['C']['pos_team'] = 3;
		}

		//Parse spawn zones. This is tricky because there will usually be two zones with the same targetname
		// but different teamnum. This is to allow spawning to move as the game changes I believe.
		if (isset($gtdata['spawnzones'])) {
			foreach ($gtdata['spawnzones'] as $szid => $szname) {
				if (is_numeric($szid)) {
					unset($map['gametypes'][$gtname]['spawnzones'][$szid]);
					$sz = array();
					foreach (array('_team2','_team3') as $suffix) {
						if (isset($map['points']["{$szname}{$suffix}"]))
							$sz["{$szname}{$suffix}"] = $map['points']["{$szname}{$suffix}"];
					}
					$map['gametypes'][$gtname]['spawnzones'][$szname] = $sz;
				}
			}
		}
		// Remove the points and entities sections from the finished data structure. We no longer need them.
		if (@is_array($map['gametypes'][$gtname]['points'])) {
			unset($map['gametypes'][$gtname]['points']);
		}
		if (@is_array($map['gametypes'][$gtname]['entities'])) {
			unset($map['gametypes'][$gtname]['entities']);
		}
	}
	recur_ksort($map);
	$json = prettyPrint(json_encode($map));
	file_put_contents("{$datapath}/maps/parsed/{$mapname}.json",$json);
	echo "OK: Parsed {$mapname}{$linebreak}";
}

//Process an entity and prepare it for display on the map
function CreatePoint($entity,$mapname) {
	$point = array();
	if (isset($entity['origin'])) {
		$coords = preg_split('/\s+/',$entity['origin']);
		if (!isset($point['pos_x']))
			$point['pos_x'] = round(abs(($coords[0] - $mapname['overview']['pos_x']) / $mapname['overview']['scale']));
		if (!isset($point['pos_y']))
			$point['pos_y'] = round(abs(($coords[1] - $mapname['overview']['pos_y']) / $mapname['overview']['scale']));
	}
	// This array maps point fields to the source data fields from the entity.
	// These get processed in order, so if one is found the processing takes that value and skips the rest.
	$fields = array(
		'pos_classname' => 'classname',
		'pos_blockzone' => 'blockzone',
		'pos_type' => 'classname',
		'pos_team' => array(
			'teamnumber',
			'teamnum',
			'TeamNum',
		),
		'pos_name' => array(
			'controlpoint',
			'targetname'
		),
	);
	foreach ($fields as $pf => $ef) {
		// If we already have a value, no need to search
		if (isset($point[$pf])) continue;

		// Set $ef to array to make looping simple
		if (!is_array($ef)) $ef=array($ef);

		foreach ($ef as $efe) {
			if (isset($entity[$efe])) {
				$point[$pf] = $entity[$efe];
				continue 2;
			}
		}
	}

	// These fields need to be integers, and set to 0 if missing.
	foreach (array('pos_team','pos_x','pos_y') as $field) {
		$point[$field] = (isset($point[$field])) ? (int)$point[$field] : 0;
	}	
	if ($entity['classname'] == "ins_spawnzone") {
		$point['pos_name'] = "{$entity['targetname']}_team{$point['pos_team']}";
	}
	return $point;
}

