<?php
// These variables must be set before anything else

// includepath is the include directory
$includepath = realpath(dirname(__FILE__));
// rootpath is the insurgency-tools root
$rootpath=dirname($includepath);

// Pull in configuration settings
include "{$includepath}/config.php";

/*
	BEGIN COMMON EXECUTION CODE
	This section is run by every script, so it shouldn't do too much.
*/

// Load custom library paths for include
parseLibPath();

// Connect to HLStatsX database if requested
if (isset($use_hlstatsx_db)) {
	// If HLStatsX config exists, try that first
	if (file_exists($hlstatsx_config)) {
		require $hlstatsx_config;
		mysql_connect(DB_HOST,DB_USER,DB_PASS);
		$mysql_connection = mysql_select_db(DB_NAME);
	}
	// If no database connected (either config missing or failed to connect) use fallback
	if (@!$mysql_connection) {
		mysql_connect($mysql_server,$mysql_username,$mysql_password);
		$mysql_connection = mysql_select_db($mysql_database);
	}
}

// Create cache dir if needed
if (!file_exists($cache_dir)) {
        mkdir($cache_dir);
}

// Loading languages here because we are only loading the core language at this time
LoadLanguages($language);

if (isset($_REQUEST['language'])) {
	if (in_array($_REQUEST['language'],$lang)) {
		$language = $_REQUEST['language'];
	}
}

// Get the command passed to the script
$command = @$_REQUEST['command'];

// Load versions, we use the directory names of data/theaters to get these values.
$versions = array();
$dirs = glob("{$datapath}/theaters/*");
foreach ($dirs as $dir) {
	if (is_dir($dir)) {
		$versions[] = basename($dir);
	}
}
// Set version and newest_version to the latest one
asort($versions);
$newest_version = $version = end($versions);

// If version sent by request, set it as the version if it's valid.
if (isset($_REQUEST['version'])) {
	if (in_array($_REQUEST['version'],$versions)) {
		$version = $_REQUEST['version'];
	}
}

// Set version_compare to our version now that we have handled user input.
// These two need to be identical if we're not doing the version compare dump command
$version_compare = $version;
if (isset($_REQUEST['version_compare'])) {
	if (in_array($_REQUEST['version_compare'],$versions)) {
		$version_compare = $_REQUEST['version_compare'];
	}
}

// Units of measurement
$range_units = array(
	'U' => 'Game Units',
	'M' => 'Meters',
	'FT' => 'Feet',
	'YD' => 'Yards',
	'IN' => 'Inches'
);
// Set range unit, override if valid unit is requested.
$range_unit = 'M';
if (isset($_REQUEST['range_unit'])) {
	if (array_key_exists($_REQUEST['range_unit'],$range_units)) {
		$range_unit = $_REQUEST['range_unit'];
	}
}

// Set range
$range = 10;

if (isset($_REQUEST['range'])) {
	$_REQUEST['range'] = dist($_REQUEST['range'],$range_unit,'IN',0);
	if (($_REQUEST['range'] >= 0) && ($_REQUEST['range'] <= 20000)) {
		$range = $_REQUEST['range'];
	}
}

// Populate $theaters array with all the theater files in the selected version
$files = glob("{$datapath}/theaters/{$version}/*.theater");
foreach ($files as $file) {
	if ((substr(basename($file),0,5) == "base_") || (substr(basename($file),-5,5) == "_base")) {
		continue;
	}
	$theaters[] = basename($file,".theater");
}
// Add all custom theaters to the list, these do NOT depend on version, they will always be added
foreach ($custom_theater_paths as $name => $path) {
	if (file_exists($path)) {
		$ctfiles = glob("{$path}/*.theater");
		foreach ($ctfiles as $ctfile) {
			$label = basename($ctfile,".theater");
			$theaters[] = "{$name} {$label}";
		}
	}
}

// Default theater file to load if nothing is selected
$theaterfile = "default";

// If a theater is specified, find out if it's custom or stock, and set the path accordingly
if (isset($_REQUEST['theater'])) {
	if (strpos($_REQUEST['theater']," ")) {
		$bits = explode(" ",$_REQUEST['theater'],2);
		if (in_array($bits[0],array_keys($custom_theater_paths))) {
			$theaterpath = $custom_theater_paths[$bits[0]];
			$theaterfile = $bits[1];
		}
	} elseif (in_array($_REQUEST['theater'],$theaters)) {
		$theaterfile = $_REQUEST['theater'];
	}
}
// Comparison stuff
$theaterfile_compare = $theaterfile;
$theaterpath_compare = $theaterpath;
if (isset($_REQUEST['theater_compare'])) {
	if (strpos($_REQUEST['theater_compare']," ")) {
		$bits = explode(" ",$_REQUEST['theater_compare'],2);
		if (in_array($bits[0],array_keys($custom_theater_paths))) {
			$theaterpath_compare = $custom_theater_paths[$bits[0]];
			$theaterfile_compare = $bits[1];
		}
	} elseif (in_array($_REQUEST['theater_compare'],$theaters)) {
		$theaterfile_compare = $_REQUEST['theater_compare'];
	}
}

// Load theater now so we can create other arrays and validate
$theater = getfile("{$theaterfile}.theater",$version,$theaterpath);
// echo "<pre>\n";
// var_dump($theater);
// exit;

// Load maplist and gametypes
$mldata = json_decode(file_get_contents("{$rootpath}/data/thirdparty/maplist.json"),true);
$gtlist = json_decode(file_get_contents("{$rootpath}/data/thirdparty/gamemodes.json"),true);
$gametypelist = array();
foreach ($gtlist as $type=>$modes) {
	foreach ($modes as $mode) {
		$gametypelist[$mode] = "{$type}: {$mode}";
	}
}
// explode(":",implode(array_values($gtlist['pvp'] + $gtlist['coop']),":"));



/*
================================================================================
===                                                                          ===
===                                                                          ===
===                             BEGIN FUNCTIONS                              ===
===                                                                          ===
===                                                                          ===
================================================================================
*/
// TODO: Break these out into separate classes and better define them.

// LoadLanguages - Load all the language files from the data directory
// Also loads the language codes from SourceMod (also in data directory)
function LoadLanguages($pattern='English') {
	global $langcode, $lang,$rootpath,$command;
	if (!isset($langcode))
		$langcode = array();
	if (!isset($lang))
		$lang = array();
	$langfile_regex = '/[\x00-\x08\x0E-\x1F\x80-\xFF]/s';
	$langfiles = glob("{$rootpath}/data/resource/insurgency_".strtolower($pattern).".txt");
	$data = trim(preg_replace($langfile_regex, '', file_get_contents("{$rootpath}/data/sourcemod/configs/languages.cfg")));
	$data = parseKeyValues($data);
	// Load languages into array with the key as the proper name and value as the code, ex: ['English'] => 'en'
	foreach ($data['Languages'] as $code => $name) {
		$names = (is_array($name)) ? $name : array($name);
		foreach ($names as $name) {
			$name = strtolower($name);
			$langcode[$name] = $code;
		}
	}
	// Load all language files
	foreach ($langfiles as $langfile) {
		$data = trim(preg_replace($langfile_regex, '', file_get_contents($langfile)));
// var_dump($data);
		$data = parseKeyValues($data,false);
		foreach ($data["lang"]["Tokens"] as $key => $val) {
			if ($command != 'smtrans') {
				$key = "#".strtolower($key);
			}
			$key = trim($key);
			if ($key) {
				// Sometimes NWI declares a string twice!
				if (is_array($val))
					$val = $val[0];
				$lang[$data["lang"]["Language"]][$key] = $val;
			}
		}
	}
// var_dump($lang);
}

// rglob - recursively locate all files in a directory according to a pattern
function rglob($pattern, $files=1,$dirs=0,$flags=0) {
	$dirname = dirname($pattern);
	$basename = basename($pattern);
	$glob = glob($pattern, $flags);
	$files = array();
	$dirlist = array();
	foreach ($glob as $path) {
		if (is_file($path) && (!$files)) {
			continue;
		}
		if (is_dir($path)) {
			$dirlist[] = $path;
			if (!$dirs) {
				continue;
			}
		}
		$files[] = $path;
	}
	foreach (glob("{$dirname}/*", GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
		$dirfiles = rglob($dir.'/'.$basename, $files,$dirs,$flags);
		$files = array_merge($files, $dirfiles);
	}
	return $files;
}

// delTree - recursively DELETE AN ENTIRE DIRECTORY STRUCTURE!!!!
function delTree($dir='') {
	if (strlen($dir) < 2)
		return false;
	$files = array_diff(scandir($dir), array('.','..'));
	foreach ($files as $file) {
		(is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
	}
	return rmdir($dir);
}
// is_numeric_array - test if all values in an array are numeric
function is_numeric_array($array) {
	foreach ($array as $key => $value) {
		if (!is_numeric($value)) return false;
	}
	return true;
}
// kvwrite - 
function kvwrite($arr) {
	$str = "";
	kvwriteSegment($str, $arr);
	return $str;
}
// kvwriteFile - 
function kvwriteFile($file, $arr) {
	$contents = kvwrite($arr);
	$fh = fopen($file, 'w');
	fwrite($fh, $contents);
	fclose($fh);
}
// kvwriteSegment - 
function kvwriteSegment(&$str, $arr, $tier = 0,$tree=array('theater')) {
	global $ordered_fields;
	$indent = str_repeat(chr(9), $tier);
	// TODO check for a certain key to keep it in the same tier instead of going into the next?
// var_dump($str,$arr,$tier,$tree);
	foreach ($arr as $key => $value) {
		if (is_array($value)) {
			$tree[$tier+1] = $key;
			$key = '"' . $key . '"';
			$str .= $indent . $key  . "\n" . $indent. "{\n";
			if (((in_array($tree[3],$ordered_fields) !== false) || (in_array($tree[4],$ordered_fields) !== false)) && (is_numeric_array(array_keys($value)))) {
// 				echo "Ordered<br>\n";
				foreach ($value as $idx=>$item) {
					foreach ($item as $k => $v) {
						$str .= chr(9) . $indent . '"' . $k . '"' . chr(9) . '"' . $v . "\"\n";
					}
				}
// var_dump($tree,$key,$value);
			} else {
// 				echo "Array<br>\n";
				kvwriteSegment($str, $value, $tier+1,$tree);
			}
			$str .= $indent . "}\n";
			unset($tree[$tier+1]);
		} else {
// 			echo "String<br>\n";
// var_dump($tree,$key,$value);
			$str .= $indent . '"' . $key . '"' . chr(9) . '"' . $value . "\"\n";
		}
	}
// var_dump($str);
	return $str;
}
// parseKeyValues - 
function parseKeyValues($KVString,$fixquotes=true,$debug=false)
{
	global $ordered_fields;
	// Escape all non-quoted values
	if ($fixquotes)
		$KVString = preg_replace('/^(\s*)([a-zA-Z]+)/m','${1}"${2}"',$KVString);
	$KVString = preg_replace('/^(\s+)/m','',$KVString);
	$KVLines = preg_split('/\n|\r\n?/', $KVString);
	$len = strlen($KVString);
	// if ($debug) $len = 2098;

	$stack = array();

	$isInQuote = false;
	$quoteKey = "";
	$quoteValue = "";
	$quoteWhat = "key";

	$lastKey = "";
	$lastPath = "";
	$lastValue = "";
	$lastLine = "";

	$keys = array();
	$comments = array();
	$commentLines=1;

	$ptr = &$stack;
	$c="";
	$line = 1;

	$parents = array(&$ptr);
	$tree = array();
	$path="";
	$sequential = 0;
	for ($i=0; $i<$len; $i++)
	{
		$l = $c;
		$c = $KVString[$i]; // current char
		switch ($c)
		{
			case "\"":
				$commentLines=1;
				if ($isInQuote) // so we are CLOSING key or value
				{
					// EDIT: Use quoteWhat as a qualifier rather than quoteValue in case we have a "" value
					if (strlen($quoteKey) && ($quoteWhat == "value"))
					{
						if ($sequential) {
							if (is_array($ptr)) {
								foreach ($ptr as $item) {
									if (isset($item[$quoteKey])) {
										if ($item[$quoteKey] == $quoteValue) {
											$quoteValue = '';
										}
									}
								}
							}
							if ($quoteValue)
								$ptr[] = array($quoteKey => $quoteValue);
						} else {
							// If this value is already set, make it an array
							if (isset($ptr[$quoteKey])) {
								// If the item is not already an array, make it one
								if (!is_array($ptr[$quoteKey])) {
									$ptr[$quoteKey] = array($ptr[$quoteKey]);
								}
								// Add this value to the end of the array
								$ptr[$quoteKey][] = $quoteValue;
							} else {
								// Set the value otherwise
								$ptr[$quoteKey] = $quoteValue;
							}
						}
						$lastLine = $line;
						$lastPath = "{$path}/${quoteKey}";
						$lastKey = $quoteKey;
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
			// Start new section
			case "{":
				$commentLines=1;
				if (strlen($quoteKey)) {
// 					if ($sequential) {
// 						$sequential++;
// 					} elseif ($quoteKey[0] != '?') {
// 						$sequential = (in_array($quoteKey,$ordered_fields) === true);
// 					}
					// Add key to tree
					$tree[] = $quoteKey;
					$sequential = (array_intersect($tree,$ordered_fields));
					// Update path in tree
					$path = implode("/",$tree);
					// Update parents array with current pointer in the new path location
					$parents[$path] = &$ptr;
					// If the object already exists, create an array of objects
					if ($quoteKey[0] == '?') {
						$ptr = &$ptr[][$quoteKey];
					} elseif (isset($ptr[$quoteKey])) {
						// Get all the keys, this assumes that the data will have non-numeric keys.
						$keys = implode('',array_keys($ptr[$quoteKey]));
						// So when we see non-numeric keys, we push the existing data into an array of itself before appending the next object.
						if (!is_numeric($keys)) {
							$ptr[$quoteKey] = array($ptr[$quoteKey]);
						}
						// Move the pointer to a new array under the key
						$ptr = &$ptr[$quoteKey][];
					} else {
						// Just put the object here if there is no existing object
						$ptr = &$ptr[$quoteKey];
					}
					$lastPath = "{$path}/${quoteKey}";
					$lastKey = $quoteKey;
					$quoteKey = "";
					$quoteWhat = "key";
				}
				$lastLine = $line;
				break;
			// End of section
			case "}":
// 				if ($sequential > 1)
// 					$sequential--;
// 				else
// 					$sequential=0;
				$commentLines=1;
				// Move pointer back to the parent
				$ptr = &$parents[$path];
				// Take last element off tree as we back out
				array_pop($tree);
				// Update path now that we have backed out
				$path = implode("/",$tree);
				$lastLine = $line;
				break;
				
			case "\t":
				break;
			case "/":
				// Comment "// " or "/*"
				if (($KVString[$i+1] == "/") || ($KVString[$i+1] == "*"))
				{
					$comment = "";
					// Get comment type
					$ctype = $KVString[$i+1];
					while($i < $len) {
						// If type is "// " stop processing at newline
						if (($ctype == '/') && ($KVString[$i+1] == "\n")) {
// 							$i+=2;
							break;
						}
						// If type is "/*" stop processing at "*/"
						if (($ctype == '*') && ($KVString[$i+1] == "*") && ($KVString[$i+2] == "/")) {
							$i+=2;
							$comment.="*/";
							break;
						}
						$comment.=$KVString[$i];
						$i++;
					}
					$comment = trim($comment);
					// Was this comment inline, or after the last item we processed?
					$where = ($lastLine == $line) ? 'inline' : 'newline';
					// If last line was also a comment, see if we can merge into a multi-line comment
					// Use the commentLines to see how far back this started
					$lcl = ($line-$commentLines);
					if (isset($comments[$lcl])) {
						$lc = $comments[$lcl];
						if ($lc['path'] == $lastPath) {
							$comments[$lcl]['line_text'].="\n{$KVLines[$line-1]}";
							$comments[$lcl]['comment'].="\n{$comment}";
							$comment='';
							$commentLines++;
						}
					}
					// If we have a comment, add it to the list
					if ($comment) {
						$comments[$line] = array('path' => $lastPath, 'where' => $where, 'line' => $line, 'line_text' => $KVLines[$line-1], 'comment' => $comment);
					}
					continue;
				}
			default:
				if ($isInQuote) {
					if ($quoteWhat == "key")
						$quoteKey .= $c;
					else
						$quoteValue .= $c;
				}
				if ($c == "\n")
					$line++;
		}
	}
	
	if ($debug) {
		echo "<hr><pre>";
		var_dump("stack: ",$stack);
// 		var_dump("ptr: ",$ptr);
	}
// 	var_dump($comments);
	return $stack;
}

// prettyPrint - 
function prettyPrint( $json )
{
	$result = '';
	$level = 0;
	$in_quotes = false;
	$in_escape = false;
	$ends_line_level = NULL;
	$json_length = strlen( $json );

	for( $i = 0; $i < $json_length; $i++ ) {
		$char = $json[$i];
		$new_line_level = NULL;
		$post = "";
		if( $ends_line_level !== NULL ) {
			$new_line_level = $ends_line_level;
			$ends_line_level = NULL;
		}
		if ( $in_escape ) {
			$in_escape = false;
		} else if( $char === '"' ) {
			$in_quotes = !$in_quotes;
		} else if( ! $in_quotes ) {
			switch( $char ) {
				case '}':
				case ']':
					$level--;
					$ends_line_level = NULL;
					$new_line_level = $level;
					break;
				case '{':
				case '[':
					$level++;
					case ',':
					$ends_line_level = $level;
					break;

				case ':':
					$post = " ";
					break;

				case " ":
				case "\t":
				case "\n":
				case "\r":
					$char = "";
					$ends_line_level = $new_line_level;
					$new_line_level = NULL;
					break;
			}
		} else if ( $char === '\\' ) {
			$in_escape = true;
		}
		if( $new_line_level !== NULL ) {
			$result .= "\n".str_repeat( "\t", $new_line_level );
		}
		$result .= $char.$post;
	}
	return $result;
}

// theater_recurse - 
function theater_recurse($array, $array1)
{
	foreach ($array1 as $key => $value)
	{
		// create new key in $array, if it is empty or not an array
// 		if (!isset($array[$key])) {
// || (isset($array[$key]) && !is_array($array[$key])))
// 			$array[$key] = array();
// 		}

		// overwrite the value in the base array
		if (is_array($value))
		{
			if (isset($array[$key]))
				$value = theater_recurse($array[$key], $value);
		}
		if ($value !== NULL) {
			$array[$key] = $value;
		}
	}
	return $array;
}
// theater_array_replace_recursive - 
function theater_array_replace_recursive($array, $array1)
{
	// handle the arguments, merge one by one
	$args = func_get_args();
	$array = $args[0];
	if (!is_array($array))
	{
		return $array;
	}
	for ($i = 1; $i < count($args); $i++)
	{
		if (is_array($args[$i]))
		{
			$array = theater_recurse($array, $args[$i]);
		}
	}
	return $array;
}

// theater_array_replace - 
function theater_array_replace()
{
	$args = func_get_args();
	$num_args = func_num_args();
	$res = array();
	for($i=0; $i<$num_args; $i++)
	{
		if(is_array($args[$i]))
		{
			foreach($args[$i] as $key => $val)
			{
				$res[$key] = $val;
			}
		}
		else
		{
			var_dump($args[0]);
			var_dump($args[$i]);
			trigger_error(__FUNCTION__ .'(): Argument #'.($i+1).' is not an array', E_USER_WARNING);
			return NULL;
		}
	}
	return $res;
}
// formatBytes - 
function formatBytes($bytes, $precision = 2) { 
	$units = array('B', 'KB', 'MB', 'GB', 'TB'); 

	$bytes = max($bytes, 0); 
	$pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
	$pow = min($pow, count($units) - 1); 

	// Uncomment one of the following alternatives
	$bytes /= pow(1024, $pow);
	// $bytes /= (1 << (10 * $pow)); 

	return round($bytes, $precision) . ' ' . $units[$pow];
}

// stats functions
/*
multi_diff
Compare two arrays recursively, return an array of differences
Will be an array of differences (key structure identical to source arrays).
Each element is an array that has two values, key is the nameX variable and value is the value from that source array
Elements that are identical in both arrays are omitted
Example:
$array1 = array('object' => array('name' => 'object1', 'size' => 30, 'owner' => 'nobody'));
$array2 = array('object' => array('name' => 'object2', 'size' => 40, 'owner' => 'nobody'));
$result = multi_diff('array1',$array1,'array2',$array2);
$result will be:
array(
	'object' => array(
		'name' => array('array1' => 'object1','array2' => 'object2'),
		'size' => array('array1' => 30,'array2' => 40)
	)
);
*/
function multi_diff($name1,$arr1,$name2,$arr2) {
	$result = array();
	$merged = $arr1+$arr2;// array_merge($arr1,$arr2);
	foreach ($merged as $k=>$v){
		if(!isset($arr2[$k])) {
			$result[$k] = array($name1 => $arr1[$k], $name2 => NULL);
		} else if(!isset($arr1[$k])) {
			$result[$k] = array($name1 => NULL,$name2 => $arr2[$k]);
		} else {
			if(is_array($arr1[$k]) && is_array($arr2[$k])){
				$diff = multi_diff($name1, $arr1[$k], $name2, $arr2[$k]);
				if(!empty($diff)) {
					$result[$k] = $diff;
				}
			} else if ($arr1[$k] !== $arr2[$k]) {
				$result[$k] = array($name1 => $arr1[$k],$name2 => $arr2[$k]);
			}
		}
	}
	return $result;
}
/* getfile
Takes a KeyValues file and parses it. If #base directives are included, pull those and merge contents on top
*/
function getfile($filename,$version='',$path='') {
	global $custom_theater_paths,$newest_version,$theaterpath,$rootpath;
	if ($version == '')
		$version = $newest_version;
	$filepath = file_exists("{$path}/".basename($filename)) ? $path : (file_exists("{$theaterpath}/".basename($filename)) ?  $theaterpath: "{$rootpath}/data/theaters/{$version}");
	$filepath.="/".basename($filename);
	$data = file_get_contents($filepath);
	$thisfile = parseKeyValues($data);
// var_dump($filename,$version,$path,$filepath);// ,$data,$thisfile);
	$theater = $thisfile["theater"];
	// If the theater sources another theater, process them in order using a merge which blends sub-array values from bottom to top, recursively replacing.
	// This appears to be the way the game processes these files it appears.
	if (isset($thisfile["#base"])) {
		$basedata = array();
		if (is_array($thisfile["#base"])) {
			$bases = $thisfile["#base"];
		} else {
			$bases = array($thisfile["#base"]);
		}
		foreach ($bases as $base) {
			$theater['#base'][] = $base;
			$basedata = array_merge_recursive(getfile($base,$version,$path),$basedata);
		}
		$theater = theater_array_replace_recursive($basedata,$theater);
// array_merge_recursive($basedata,$theater);
	}
	// Include parts that might be conditional in their parents, basically put everything in flat arrays
	// This isn't congruent with how the game handles them, I believe this ougght to be a selector in the UI that can handle this better
	foreach ($theater as $sec => $data) {
		foreach ($data as $key => $val) {
			if (($key[0] == '?') && (is_array($val))) {
				unset($theater[$sec][$key]);
				$theater[$sec] = $val;// theater_array_replace_recursive($theater[$sec],$val);
			}
		}
	}
	return $theater;
}
/* getvgui
Display the icon for an object
*/
function getvgui($name,$type='img',$path='vgui/inventory') {
	global $rootpath;
	$rp = "data/materials/{$path}/{$name}";
	if (file_exists("{$rootpath}/{$rp}.vmt")) {
// echo "found file<br>";
		$vmf = file_get_contents("{$rootpath}/{$rp}.vmt");
// var_dump($vmf);
		preg_match_all('/basetexture[" ]+([^"\s]*)/',$vmf,$matches);
// var_dump($matches);
		$rp = "data/materials/".$matches[1][0];
	}

// var_dump($rp);
	if (file_exists("{$rootpath}/{$rp}.png")) {
		if ($type == 'img')
			return "<img src='{$rp}.png' alt='{$name}' height='128' width='256'/><br>";
		if ($type == 'bare')
			return "{$rp}.png";
		if ($type == 'css')
			return " style=\"background-image: url('{$rp}.png');\" class='vgui'";
	}
}

// parseLibPath - Load custom library paths, this should only get called after config is loaded but before any other includes are called
function parseLibPath() {
	global $custom_libpaths;
	if (!is_array($custom_libpaths)) {
		$custom_libpaths = array($custom_libpaths);
	}
	foreach ($custom_libpaths as $path) {
		addLibPath($path);
	}
}
// addLibPath - Add path to include path, this is how we should add new libraries
function addLibPath($path) {	
	global $libpaths;
	if (!in_array($path,$libpaths)) {
		$libpaths[] = $path;
		set_include_path(implode(PATH_SEPARATOR,$libpaths));
	}
}
