<?php
	$theaterpath = "../data/theaters/2.1.1.2/";
	$theaterfile="default_coop_shared.theater";
	$theater = new Theater($theaterfile,"2.1.1.2",$theaterpath);
	//var_dump(getobject('weapons','weapon_m249'));
	var_dump($theater);
	
	
	
class Theater {
	public $ordered_fields = array('squads','buy_order','allowed_weapons','allowed_items');

	public function __construct() {
	}
	/* getobject
	Take a type (weapon, ammo, explosive, etc), key (name of item), and boolean for recursing
	*/
	
	public function getobject($type, $key, $level=0) {
		// Get object from theater. This has a case insensitive failsafe, since theater keys sometimes aren't the same case.
		if (isset($this->theater[$type][$key])) {
			$object = $this->theater[$type][$key];
		} else {
			foreach ($this->theater[$type] as $ikey=>$item) {
				if (strtolower($key) == strtolower($ikey)) {
					$object = $item;
					break;
				}
			}
		}
		// Merge in imports
		if (isset($object['import'])) {
			echo "Importing {$object['import']}\n";
			// Merge using replacement of like items, which will not merge sub-array values like damagehitgroups or ammo_clip if the object also defines the section. This appears to be the way the game processes these sections.
			$import = getobject($type, $object['import'], $level+1);
			unset($import['IsBase']);
	//		var_dump("type",$type,"key",$key,"import_key",$object['import']);
			$object = merge_theaters($import,$object);
		}
		return $object;
	}
	
	/*
	merge theaters:
	Take two inputs, $base and $add
	Iterate through array_keys($add[$ptr])
	If $base[$ptr] isn't set, simply copy $add[$ptr]
	For string keys (sections):
		If $base[$ptr] is set, copy each element of $add[$ptr]
	For numeric keys (key/values):
		Run through $base[$ptr] to get one-element arrays of $key=>$val
		If found, set $base[$ptr][$match][$key] = $val
		Else, $base[$ptr][][$key] = $val
	*/
	public function merge_theaters($base,$add) {
		foreach ($add as $key => $val) {
			// If val is an array, which it practically always should be
			if (is_array($val)) {
				// If $base[$key] exists, merge $val, otherwise simply copy it
				if (isset($base[$key])) {
					$base[$key] = merge_theaters($base[$key],$val);
				} else {
					$base[$key] = $val;
				}
			} else {
				$base[$key] = $val;
			}
		}
		return $base;
	}
	
	
	/* getfile
	Takes a KeyValues file and parses it. If #base directives are included, pull those and merge contents on top
	*/
	public function getfile($filename,$version='',$path='',$collapse_conditionals=true) {
		global $custom_theater_paths,$newest_version,$theaterpath,$rootpath;
		if ($version == '')
			$version = $newest_version;
		// If file exists at $path, use that as the path.
		// Next, try the specified theater path for this file
		// Finally, try the stock theaters for the current version
		$filepath = file_exists("{$path}/".basename($filename)) ?
			$path :
			(file_exists("{$this->theaterpath}/".basename($filename)) ?
				$this->theaterpath :
				"{$rootpath}/data/theaters/{$version}");
		$filepath.="/".basename($filename);
		$data = file_get_contents($filepath);
		$thisfile = parseKeyValues($data);
		
		$bases = isset($thisfile["#base"]) ? (array)$thisfile["#base"] : array();
		$basedata = array();
		//If the theater sources another theater, process them in order using a merge which blends sub-array values from bottom to top, recursively replacing.
		//This appears to be the way the game processes these files it appears.
		if (count($bases)) {
			foreach ($bases as $base) {
				$basedata = merge_theaters($basedata,getfile($base,$version,$path,$collapse_conditionals));
			}
			$this->theater = merge_theaters($basedata,$thisfile["theater"]);
		}
		//Include parts that might be conditional in their parents, basically put everything in flat arrays
		//This isn't congruent with how the game handles them, I believe this ougght to be a selector in the UI that can handle this better
		if ($collapse_conditionals) {
			foreach ($this->theater as $sec => $data) {
				foreach ($data as $key => $val) {
					if (($key[0] == '?') && (is_array($val))) {
						unset($this->theater[$sec][$key]);
						$this->theater[$sec] = $val;//theater_array_replace_recursive($this->theater[$sec],$val);
					}
				}
			}
		}
		return $this->theater;
	}
	
	
}
	
class KeyValues
{
	public function __construct($KVString,$fixquotes=true,$debug=false)
	{
		global $ordered_fields;
		// Escape all non-quoted values
		if ($fixquotes)
			$KVString = preg_replace('/^(\s*)([a-zA-Z]+)/m','${1}"${2}"',$KVString);
		$KVString = preg_replace('/^(\s+)/m','',$KVString);
		$KVLines = preg_split('/\n|\r\n?/', $KVString);
		$len = strlen($KVString);
		//if ($debug) $len = 2098;
	
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
									$ptr[] = "{$quoteKey}::{$quoteValue}";
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
						// Add key to tree
						$tree[] = $quoteKey;
						$sequential = (array_intersect($tree,$ordered_fields));
						// Update path in tree
						$path = implode("/",$tree);
						// Update parents array with current pointer in the new path location
						$parents[$path] = &$ptr;
						// For conditional keys like "?nightmap", create single-element array to contain data
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
					// Comment "//" or "/*"
					if (($KVString[$i+1] == "/") || ($KVString[$i+1] == "*"))
					{
						$comment = "";
						// Get comment type
						$ctype = $KVString[$i+1];
						while($i < $len) {
							// If type is "//" stop processing at newline
							if (($ctype == '/') && ($KVString[$i+1] == "\n")) {
	//							$i+=2;
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
	//		var_dump("ptr: ",$ptr);
		}
	//	var_dump($comments);
		return $stack;
	}
}
