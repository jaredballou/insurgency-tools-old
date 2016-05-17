<?php
/*
 * schema.php
 * (C) 2016, Jared Ballou <insurgency@jballou.com>
 * 
 * This script attempts to create a schema file from theaters
*/
//Root Path Discovery
do { $rd = (isset($rd)) ? dirname($rd) : realpath(dirname(__FILE__)); $tp="{$rd}/rootpath.php"; if (file_exists($tp)) { require_once($tp); break; }} while ($rd != '/');
require_once("${includepath}/functions.php");
require_once "kvreader2.php";
// 
$theaterfile="default_checkpoint";
$theater = ParseTheaterFile("{$theaterfile}.theater",$mod,$version,$theaterpath);
//$ordered_fields = array('squads','buy_order','allowed_weapons','allowed_items','weapon_upgrade');
$object_fields=array(
	'theater/*',
	'theater/teams/*/squads',
	'theater/weapon_upgrades/*/world_attachments',
	'theater/weapon_upgrades/*/viewmodel_attachments/*/weapons',
);
function ParseIt($data,$name='theater',$path='',$level=0) {
	global $ordered_fields,$object_fields;
	$out = array();
	// Figure out if we are dealing with named objects, or section names
	// If this is an ordered field, parse the sub-items
	$path = ($path) ? "{$path}/{$name}" : $name;
	$objects = matchTheaterPath($path,$object_fields);
	$ordered = matchTheaterPath($path,$ordered_fields);
//	echo "path: {$path} ordered: {$ordered}\n";
	if ($ordered) {
		$items = array();
		foreach ($data as $idx => $item) {
			foreach ($item as $key=>$val) {
				$items[$key] = $val;
			}
		}
	} else {
		$items = $data;
	}
	foreach ($items as $key => $val) {
		$title = ($objects) ? "@name" : $key;
		if (is_array($val)) {
			$pdata = ParseIt($val,$title,$path,$level+1);
			if (isset($out[$title])) {
				$pdata = theater_array_replace_recursive($out[$title],$pdata);
			}
			$out[$title] = $pdata;
			uksort($out[$title], "strnatcasecmp");
		} else {
			$out[$title] = vartype($val);
		}
	}
	uksort($out, "strnatcasecmp");
	return($out);
}
/*
	var_dump("title path level objects ordered",$title,$path,$level,$objects,$ordered);
	if ($objects) {
		foreach ($data as $key => $val) {
			$title = ($objects) ? "@name" : $key;
//array_pop(array_filter(explode("/",$path)));
			if (is_array($val)) {
//				var_dump("val",$val);
				foreach ($val as $skey=>$sval) {
//					var_dump("skey",$skey);
					$stitle = (matchTheaterPath("{$path}/{$key}/{$skey}",$object_fields)) ? "@name" : $skey;
					$out[$title][$stitle] = vartype($sval);
				}
			} else {
				echo "not_array\n";
				$out[$title] = vartype($val);
			}
		}
	} else {
		// Otherwise, parse the data natively
		foreach ($data as $key => $val) {
			$title = ($objects) ? "@name" : $key;
var_dump("key val",$key,$val);
			if (is_array($val)) {
				$setval = ParseIt($val,"{$path}/{$key}",$level+1,$objects);
				// If ordered field, unserialize items
				if ((matchTheaterPath("{$path}/{$key}",$ordered_fields)) && (is_numeric(implode('',array_keys($setval))))) {
var_dump("ordered and numeric keys");
					foreach ($setval as $idx=>$item) {
						foreach ($item as $skey=>$sval) {
							$stitle = (matchTheaterPath("{$path}/{$key}/{$skey}",$object_fields)) ? "@name" : $skey;
							$out[$title][$stitle] = $sval;
						}
					}
				} else {
var_dump("not ordered or numeric?");
					// Otherwise, process line by line
					foreach ($setval as $skey=>$sval) {
						$stitle = (matchTheaterPath("{$path}/{$key}/{$skey}",$object_fields)) ? "@name" : $skey;

echo "next path: {$path}/{$key}/{$skey}\n";
var_dump("skey",$skey,"stitle",$stitle);
						if (is_array($sval)) {
							$svdata = ParseIt($sval,"{$path}/{$key}/{$skey}",$level+2,$objects);
							foreach ($svdata as $svkey=>$svval) {
								$svtitle = (matchTheaterPath("{$path}/{$key}/{$skey}/{$svkey}",$object_fields)) ? "@name" : $skey;
								$out[$title][$stitle][$svtitle] = 
						} else {
							$out[$title][$stitle] = vartype($sval);
						}
					}
				}
			} else {
				$out[$title] = vartype($val);
			}
		}
	}
	//ksort($out,SORT_NATURAL);
	return($out);
}
*/
function Tree($data,$path='') {
	$out = array();
	foreach ($data as $key=>$val) {
		$idx = "{$path}/{$key}";
		if (is_array($val)) {
			$out[$idx] = Tree($val,"{$path}/{$key}");
		} else {
			$out[$idx] = $val;
		}
	}
	return($out);
}
$tree = Tree($theater);
$schema = ParseIt($theater);
file_put_contents("out/schema.tree",var_export($tree,true));
file_put_contents("out/schema.array",var_export($schema,true));
file_put_contents("out/schema.yaml",Spyc::YAMLDump($schema));
file_put_contents("out/theater.array",var_export($theater,true));

$reader = new KVReader();
$reader->writeFile("out/theater",$theater);
