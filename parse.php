<?php
/*
This is the prototype of the new parser I am working on
Primarily designed to deal with the weird KeyValues data that can have multiple
keys associated in one item, and need to retain the ordering. This is a work in
progress.
*/
$theater_root = '/home/insserver/serverfiles/insurgency/insurgency-data/theaters';
$version = '1.9.7.6';

$theater = LoadTheaterFile('default_singleplayer.theater');
//var_dump($theater);
var_dump(getobject('weapons','weapon_m9'));

/*
MergeTheaters
$base: Base theater to start with
$current: Current theater contents to lay on top
These must be arrays that have already been parsed.
*/
function MergeTheaters(&$base,&$current,$key='',$level=0) {
//	if ($key
	//If arrays have 'theater' section, make them the top level
	$base = (isset($base['theater'])) ? $base['theater'] : $base;
	$current = (isset($current['theater'])) ? $current['theater'] : $current;
	//Loop through theater sections, I believe this merge behavior is the way the game does it
	$keys = array_merge(array_keys($base),array_keys($current));
	foreach ($keys as $key) {
		if (is_array($base[$key]) || is_array($current[$key])) {
			MergeTheaters($base[$key],$current[$key],$key,$level+1);
		} else {
			$base[$key] = $val;
		}
	}
	//Return combined value
	$current = $base;
//	return $base;
}

/* getobject
Take a type (weapon, ammo, explosive, etc), key (name of item), and boolean for recursing
*/
function getobject($type,$key,$recurse=0) {
	global $theater;
	//Get object from theater
	$object = $theater[$type][$key];
//var_dump($object);
//	$isbase = @$object['IsBase'];
	//Merge in imports
	if (isset($object["import"])) {
		$import = getobject($type,$object["import"],$recurse);
		unset($import['IsBase']);
		foreach ($object as $key => $val) {
			$import[$key] = $val;
		}
	}
	return $object;
}

/*
LoadTheaterFile
$theaterfile: theater file to parse. If path is not complete or file is not found, default to stock theater paths
$level: Which level of recursion we are on (for loading base files). Only do the final decode step at the end of level 0 run.
This function reads a theater file, imports all #base directives, and then loads the data structures into an array.
This does not parse in "import" directives simply because we want to keep this structure as close as possible to the actual theater file for comparison and export purposes.
*/
function LoadTheaterFile($theaterfile,$path='',$level=0) {
	global $theater_root,$version;

	//Regular expression matches

	//Remove all bases once parsed
	$match_base = '/"#base"[^\n"]*"([^\n"]+)"/';
	$replace_base = '';

	//Replace all '"key" "val"' with "key:val" "val"' to preserve ordering of multiple-key arrays
	$match_keyval = '/"([^\n"]+)"([^\n"]*)"([^\n"]+)"/';
	$replace_keyval = '"$1:$3"$2"$3"';

	//Default path for theater files
	$default_path = "${theater_root}/${version}";

	//Set path to default if empty. We will later change this to the path of the actual theater we are parsing.
	$path = ($path == '') ? $default_path : $path;

	//Get base theater file name
	$basename = basename($theaterfile);

	//If file does not exist (or is simple file name), check stock theater path
	if (!file_exists($theaterfile)) {
		foreach (array($path,$default_path) as $tp) {
			if (file_exists("${tp}/${basename}")) {
				$theaterfile = "${tp}/${basename}";
				break;
			}			
		}
	}

	//Get directory name of theater file
	$dirname = dirname($theaterfile);

	//If the dirname is not the default, set the path variable. This will mean if we load a custom path theater, which includes a stock theater, which in turn includes a theater that we have in the custom path, the custom path will carry through.
	$path = ($dirname == $default_path) ? $path : $dirname;

	//Load the theater
	$data = file_get_contents($theaterfile);

	//Get all #base directives
	preg_match_all($match_base,$data,$matches);

	//Remove #base lines now that we have processed them
	$data = preg_replace($match_base, $replace_base, $data);

	//Replace "key" "val" pairs with "key:val"
	$data = preg_replace($match_keyval, $replace_keyval, $data);

	//Parse KeyValues data into array
	$data = ParseKeyValues($data,'theater');

	//Apply #base directives
	if (isset($matches[1])) {
		foreach ($matches[1] as $base) {
			//Load base theater file
			$base_theater = LoadTheaterFile($base,$path,$level+1);
			//Merge theaters
			$data = MergeTheaters($base_theater,$data);
		}
	}
	//Send array back
	return $data;
}
function ParseKeyValues($KVString,$index='',$debug=false)
{
	$len = strlen($KVString);
	if ($debug) $len = 2098;

	$stack = array();

	$isInQuote = false;
	$key = "";
	$value = "";
	$quoteKey = "";
	$quoteValue = "";
	$quoteWhat = "key";
	$ptr = &$stack;
	$c="";
	$parents = array(&$ptr);
	$tree = array();
	for ($i=0; $i<$len; $i++)
	{
		$l = $c;
		$c = $KVString[$i]; // current char
		switch ($c)
		{
			case "\r":
				break;
			case "\n":
				if (strlen(trim($key))) {
					$ptr[$key] = $value;
					$key = "";
				}
				break;
			case "\"":
				if ($isInQuote) // so we are CLOSING key or value
				{
					if (strlen($quoteKey) && strlen($quoteValue))
					{
						if (isset($ptr[$quoteKey])) {
							if (!is_array($ptr[$quoteKey])) {
								$ptr[$quoteKey] = array($ptr[$quoteKey]);
							}
							$ptr[$quoteKey][] = $quoteValue;
						} else {
							$ptr[$quoteKey] = $quoteValue;
						}
						$quoteKey = "";
						$quoteValue = "";
					}
					
					if ($quoteWhat == "key")
						$quoteWhat = "value";
					else if ($quoteWhat == "value")
						$quoteWhat = "key";
				}
				$isInQuote = !$isInQuote;
				break;
			case "{":
				if (strlen($quoteKey)) {
					$tree[] = $quoteKey;
					$path = implode("/",$tree);
					$parents[$path] = &$ptr;
					$ptr = &$ptr[$quoteKey];
					$quoteKey = "";
					$quoteWhat = "key";
				}
				break;
			case "}":
				$ptr = &$parents[$path];
				$lastkey = array_pop($tree);
				$path = implode("/",$tree);
				break;
				
			case "\t":
				break;
			case "/":
				if ($KVString[$i+1] == "/") // Comment "//"
				{
					while($i < $len && $KVString[$i] != "\n")
						$i++;
					continue;
				}
			default:
				if (!$isInQuote && strlen(trim($c)))
				{
					$key .= $c;
				}
				
				if ($isInQuote)
					if ($quoteWhat == "key")
						$quoteKey .= $c;
					else
						$quoteValue .= $c;
		}
	}
	
	if ($debug) {
		echo "<hr><pre>";
		var_dump("stack: ",$stack);
//		var_dump("ptr: ",$ptr);
	}
	if ($index) {
		$index = (is_array($index)) ? $index : array($index);
		foreach ($index as $level) {
			if (isset($stack[$level])) {
				$stack = $stack[$level];
			} else {
				//Handle error
			}
		}
	}
//var_dump($stack);
	return $stack;
}

