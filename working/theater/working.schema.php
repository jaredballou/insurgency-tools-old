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
require_once("${includepath}/class.Spyc.php");
// 
$theaterfile="default_checkpoint";
$theater = getfile("{$theaterfile}.theater",$mod,$version,$theaterpath);
$ordered_fields = array('squads','buy_order','allowed_weapons','allowed_items','weapon_upgrade');
function isAssoc($arr)
{
	return array_keys($arr) !== range(0, count($arr) - 1);
}
function vartype($data) {
	if (is_array($data)) {
		return "array";
	}
	if (is_numeric($data)) {
		if (strpos($data,'.') !== false)
			return "float";
		return "integer";
	}
	if (is_string($data)) {
		if ($data[0] == "#")
			return "translate";
		return "string";
	}
	return "UNKNOWN";
}
function ParseIt($data,$name='',$level=0,$objects=0) {
	global $ordered_fields;
	$out = array('type' => 'object');
	// Figure out if we are dealing with named objects, or section names
	switch ($name) {
		case 'weapon_upgrade':
			$out['sort'] = "unique";
		case 'ammo':
		case 'explosives':
		case 'player_gear':
		case 'player_templates':
		case 'teams':
		case 'squads':
		case 'weapons':
		case 'weapon_upgrades':
			$objects = 1;
			break;
		default:
			$objects = 0;
			break;
	}
	// If this is an ordered field, parse the sub-items

	if (!isAssoc($data)) {
		$out['sort'] = "unique";
		foreach ($data as $key => $val) {
			if (is_array($val)) {
				foreach ($val as $skey=>$sval) {
					$title = ($objects) ? "@name" : $skey;
					$out['items'][$title] = vartype($sval);
				}
			} else {
				$title = ($objects) ? "@name" : $key;
				$out['items'][$title] = vartype($val);
			}
		}
	} else {
		// Otherwise, parse the data natively
		foreach ($data as $key => $val) {
			$title = ($objects) ? "@name" : $key;
			if (is_array($val)) {
				$setval = ParseIt($val,$title,$level+1,$objects);
				foreach ($setval as $skey=>$sval) {
//					if ($skey == "items") {
						$out['items'][$title][$skey] = $sval;
//					} else {
//						$out['items'][$title][$skey] = vartype($sval);
//					}
				}
			} else {
				$out['items'][$title] = vartype($val);
			}
		}
	}
	return($out);
}
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
var_dump($theater['ammo']);
//var_dump($tree);
/*
$schema = Spyc::YAMLDump(ParseIt($theater));
var_dump($schema);
*/
