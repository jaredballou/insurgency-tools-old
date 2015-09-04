<?php
include "config.php";
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
function parseKeyValues($KVString,$debug=false)
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
?>
