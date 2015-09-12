<?php
include "config.php";
$langfiles = glob("data/resource/insurgency_*.txt");
$langfiles = glob("data/resource/insurgency_english.txt");
$lang = array();
$data = trim(preg_replace('/[\x00-\x08\x0E-\x1F\x80-\xFF]/s', '', file_get_contents('data/sourcemod/configs/languages.cfg')));
$data = parseKeyValues($data);//$reader->read($data);
$langcode = array();
$ordered_fields = array('squads','buy_order','allowed_weapons','allowed_items');

foreach ($data['Languages'] as $code => $name) {
	$name = strtolower($name);
	$langcode[$name] = $code;
}

foreach ($langfiles as $langfile) {
	$data = trim(preg_replace('/[\x00-\x08\x0E-\x1F\x80-\xFF]/s', '', file_get_contents($langfile)));
	$data = parseKeyValues($data);//$reader->read($data);
	foreach ($data["lang"]["Tokens"] as $key => $val) {
		if ($_REQUEST['command'] != 'smtrans') {
			$key = "#".strtolower($key);
		}
		$key = trim($key);
		if ($key) {
			//Sometimes NWI declares a strint twice!
			if (is_array($val))
				$val = $val[0];
			$lang[$data["lang"]["Language"]][$key] = $val;
		}
	}
}
$language = "English";
if ($_REQUEST['language']) {
	if (in_array($_REQUEST['language'],$lang)) {
		$language = $_REQUEST['language'];
	}
}

//Load versions
$versions = array();
$dirs = glob("data/theaters/*");
foreach ($dirs as $dir) {
	if (is_dir($dir)) {
		$versions[] = basename($dir);
	}
}
asort($versions);
$newest_version = $version = end($versions);

if ($_REQUEST['version']) {
	if (in_array($_REQUEST['version'],$versions)) {
		$version = $_REQUEST['version'];
	}
}
$version_compare = $version;
if ($_REQUEST['version_compare']) {
	if (in_array($_REQUEST['version_compare'],$versions)) {
		$version_compare = $_REQUEST['version_compare'];
	}
}

$range_units = array(
	'U' => 'Game Units',
	'M' => 'Meters',
	'FT' => 'Feet',
	'YD' => 'Yards',
	'IN' => 'Inches'
);
$range_unit = 'M';
if ($_REQUEST['range_unit']) {
	if (array_key_exists($_REQUEST['range_unit'],$range_units)) {
		$range_unit = $_REQUEST['range_unit'];
	}
}
$range = 10;
if ($_REQUEST['range']) {
	$_REQUEST['range'] = dist($_REQUEST['range'],$range_unit,'IN',0);
	if (($_REQUEST['range'] >= 0) && ($_REQUEST['range'] <= 20000)) {
		$range = $_REQUEST['range'];
	}
}
$files = glob("data/theaters/{$version}/*.theater");
foreach ($files as $file) {
	if ((substr(basename($file),0,5) == "base_") || (substr(basename($file),-5,5) == "_base")) {
		continue;
	}
	$theaters[] = basename($file,".theater");
}
foreach ($custom_theater_paths as $name => $path) {
	if (file_exists($path)) {
		$ctfiles = glob("{$path}/*.theater");
		foreach ($ctfiles as $ctfile) {
			$label = basename($ctfile,".theater");
			$theaters[] = "{$name} {$label}";
		}
	}
}

//Load theater files
$theaterfile = "default";
if ($_REQUEST['theater']) {
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
//Load theater now so we can create other arrays and validate
$theater = getfile("{$theaterfile}.theater",$version,$theaterpath);


function rglob($pattern, $flags = 0) {
	$files = glob($pattern, $flags); 
	foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
		$files = array_merge($files, rglob($dir.'/'.basename($pattern), $flags));
	}
	return $files;
}
function delTree($dir='') {
	if (strlen($dir) < 2)
		return false;
	$files = array_diff(scandir($dir), array('.','..'));
	foreach ($files as $file) {
		(is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
	}
	return rmdir($dir);
}
function is_numeric_array($array) {
	foreach ($array as $key => $value) {
		if (!is_numeric($value)) return false;
	}
	return true;
}
function kvwrite($arr) {
	$str = "";
	kvwriteSegment($str, $arr);
	return $str;
}
function kvwriteFile($file, $arr) {
	$contents = kvwrite($arr);
	$fh = fopen($file, 'w');
	fwrite($fh, $contents);
	fclose($fh);
}
function kvwriteSegment(&$str, $arr, $tier = 0,$tree=array('theater')) {
	global $ordered_fields;
	$indent = str_repeat(chr(9), $tier);
	// TODO check for a certain key to keep it in the same tier instead of going into the next?
//var_dump($str,$arr,$tier,$tree);
	foreach ($arr as $key => $value) {
		if (is_array($value)) {
			$tree[$tier+1] = $key;
			$key = '"' . $key . '"';
			$str .= $indent . $key  . "\n" . $indent. "{\n";
			if (((in_array($tree[3],$ordered_fields) !== false) || (in_array($tree[4],$ordered_fields) !== false)) && (is_numeric_array(array_keys($value)))) {
//				echo "Ordered<br>\n";
				foreach ($value as $idx=>$item) {
					foreach ($item as $k => $v) {
						$str .= chr(9) . $indent . '"' . $k . '"' . chr(9) . '"' . $v . "\"\n";
					}
				}
//var_dump($tree,$key,$value);
			} else {
//				echo "Array<br>\n";
				kvwriteSegment($str, $value, $tier+1,$tree);
			}
			$str .= $indent . "}\n";
			unset($tree[$tier+1]);
		} else {
//			echo "String<br>\n";
//var_dump($tree,$key,$value);
			$str .= $indent . '"' . $key . '"' . chr(9) . '"' . $value . "\"\n";
		}
	}
//var_dump($str);
	return $str;
}
function parseKeyValues($KVString,$debug=false)
{
	global $ordered_fields;
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
						//Make ordered array for these items
						if (in_array($tree[3],$ordered_fields)) {
							$ptr[] = array($quoteKey => $quoteValue);
						} else {
							if (isset($ptr[$quoteKey])) {
								if (!is_array($ptr[$quoteKey])) {
									$ptr[$quoteKey] = array($ptr[$quoteKey]);
								}
								$ptr[$quoteKey][] = $quoteValue;
							} else {
								$ptr[$quoteKey] = $quoteValue;
							}
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
	return $stack;
}
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
                case '}': case ']':
                    $level--;
                    $ends_line_level = NULL;
                    $new_line_level = $level;
                    break;

                case '{': case '[':
                    $level++;
                case ',':
                    $ends_line_level = $level;
                    break;

                case ':':
                    $post = " ";
                    break;

                case " ": case "\t": case "\n": case "\r":
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
if (!function_exists('recurse'))
{
    function recurse($array, $array1)
    {  
      foreach ($array1 as $key => $value)
      {
        // create new key in $array, if it is empty or not an array
        if (!isset($array[$key]) || (isset($array[$key]) && !is_array($array[$key])))
        {
          $array[$key] = array();
        }
  
        // overwrite the value in the base array
        if (is_array($value))
        {
          $value = recurse($array[$key], $value);
        }
        $array[$key] = $value;
      }
      return $array;
    }
}
if (!function_exists('array_replace_recursive'))
{
  function array_replace_recursive($array, $array1)
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
        $array = recurse($array, $args[$i]);
      }
    }
    return $array;
  }
}
if(!function_exists('array_replace'))
{
  function array_replace()
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
        trigger_error(__FUNCTION__ .'(): Argument #'.($i+1).' is not an array', E_USER_WARNING);
        return NULL;
      }
    }
    return $res;
  }
}
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

//stats functions
function multi_diff($name1,$arr1,$name2,$arr2) {
	$result = array();
	$merged = $arr1+$arr2;//array_merge($arr1,$arr2);
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
Takes flat filename and parses it. If #base directives are included, pull those and merge contents on top
*/
function getfile($filename,$version='',$path='') {
	global $custom_theater_paths,$newest_version,$theaterpath;
	if ($version == '')
		$version = $newest_version;
	$filepath = file_exists("{$path}/".basename($filename)) ? $path : (file_exists("{$theaterpath}/".basename($filename)) ?  $theaterpath: "data/theaters/{$version}");
	$filepath.="/".basename($filename);
	$data = file_get_contents($filepath);
	$thisfile = parseKeyValues($data);
//var_dump($filename,$version,$path,$filepath);//,$data,$thisfile);
	$theater = $thisfile["theater"];
	//If the theater sources another theater, process them in order using a merge which blends sub-array values from bottom to top, recursively replacing.
	//This appears to be the way the game processes these files it appears.
	if (isset($thisfile["#base"])) {
		$basedata = array();
		if (is_array($thisfile["#base"])) {
			$bases = $thisfile["#base"];
		} else {
			$bases = array($thisfile["#base"]);
		}
		foreach ($bases as $base) {
			$basedata = array_merge_recursive(getfile($base,$version,$path),$basedata);
		}
		$theater = array_replace_recursive($basedata,$theater);
	}
	//Include parts that might be conditional in their parents, basically put everything in flat arrays
	//This isn't congruent with how the game handles them, I believe this ougght to be a selector in the UI that can handle this better
	foreach ($theater as $sec => $data) {
		foreach ($data as $key => $val) {
			if (($key[0] == '?') && (is_array($val))) {
				unset($theater[$sec][$key]);
				$theater[$sec] = array_replace_recursive($theater[$sec],$val);
			}
		}
	}
	return $theater;
}
/* getvgui
Display the icon for an object
*/
function getvgui($name,$type='img',$path='vgui/inventory') {
	$rp = "data/materials/{$path}/{$name}";
	if (file_exists("{$rp}.vmt")) {
//echo "found file<br>";
		$vmf = file_get_contents("{$rp}.vmt");
//var_dump($vmf);
		preg_match_all('/basetexture[" ]+([^"\s]*)/',$vmf,$matches);
//var_dump($matches);
		$rp = "data/materials/".$matches[1][0];
	}

//var_dump($rp);
	if (file_exists("{$rp}.png")) {
		if ($type == 'img')
			return "<img src='{$rp}.png' alt='{$name}' height='128' width='256'/><br>";
		if ($type == 'bare')
			return "{$rp}.png";
		if ($type == 'css')
			return " style=\"background-image: url('{$rp}.png');\" class='vgui'";
	}
}

?>

