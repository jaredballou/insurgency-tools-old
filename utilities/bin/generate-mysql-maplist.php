<?php
$mappath = '/home/insserver/serverfiles/insurgency/maps';
// List of matching fields, and values that would be accepted for this field.
$matches = array(
	'edition' => array('coop','night','outpost','survival','day','hunt'),
	'version' => array('old','beta','v','b','a','rc'),
);

$maplist = file("../../data/thirdparty/maplist.txt", FILE_IGNORE_NEW_LINES);
$maps = array();

foreach ($maplist as $filename) {
	$fbits = explode(".",$filename);

	//Make sure this map is in the array so we can merge below
	if (!isset($maps[$fbits[0]])) {
		$maps[$fbits[0]] = array();
	}

	//Add this file to the files list for this map
	$maps[$fbits[0]]['files'][] = $filename;

	//Only process BSP files
	if ($fbits[1] != 'bsp') {
		continue;
	}

	$bits = explode("_",$fbits[0]);
	$cur_field = 'name';
	$map = array();
	$remainder = '';
	$match = '';
	foreach ($bits as $bit) {
		//Only do this loop if we already have a name, in case someone names their map v2rocket or something like that
		if (@$map['name'] != '') {
			foreach ($matches as $field => $values) {
				foreach ($values as $value) {
					//Match fields starting with the string, possibly with some additional bits tacked on that are not properly separated
					if (preg_match("/^{$value}[0-9bav]*$/",$bit)) {
						$cur_field = $field;
						//Get the rest of the string
						$match = $value;
						$remainder = str_replace($value,'',$bit);
					}
				}
			}
		}
		//Use array values to make combining them easier
		if ($cur_field == 'edition' && $remainder) {
			$map[$cur_field][] = $match;
			$cur_field = 'version';
			$map[$cur_field][] = $remainder;
		} else {
			$map[$cur_field][] = $bit;
		}
	}
	//Convert all arrays to strings
	foreach ($map as $field => $parts) {
		$maps[$fbits[0]][$field] = implode("_",$parts);
	}
}
ksort($maps);
foreach ($maps as $map=>$data) {
	foreach ($data as $field=>$val) {
		if ($field == 'files') {
			foreach ($val as $file) {
				if (preg_match('/\.txt$/',$file)) {
					$fn = $mappath."/".$file;
					if (file_exists($fn)) {
						var_dump($map,$fn);
					}
				}
			}
		}
		if (is_array($val)) {
			$data[$field] = $val = implode(",",$val);
		}
		$update[] = "{$key}='{$val}'";
//		echo "'{$val}'";
	}
	$keys = implode(',',array_keys($data));
	$values = implode("','",array_values($data));
	$update = array();
	foreach ($data as $key=>$val) {
	}
//	echo "INSERT INTO maps ({$keys}) VALUES ('{$values}') ON DUPLICATE KEY UPDATE ".implode(',',$update).";\n";
	if (isset($data['files'])) {
		$files = explode(',',$data['files']);
		foreach ($files as $file) {
//			echo "UPDATE files SET map=(SELECT id FROM maps WHERE ".implode(' AND ',$update).") WHERE filename='{$file}';\n";
		}
	}
}
