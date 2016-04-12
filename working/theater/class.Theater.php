<?php
	$theaterpath = "../data/theaters/2.1.1.2/";
	$theaterfile="default_coop_shared.theater";
	$theater = new Theater($theaterfile,"2.1.1.2",$theaterpath);
	//var_dump(getobject('weapons','weapon_m249'));
	var_dump($theater);
	
function GenerateStatTable() {
	global $stats_tables, $theater, $upgrades, $armors, $range;
	$armors = array('No Armor' => ($theater['player_settings']['damage']['DamageHitgroups']));
	foreach ($theater["player_gear"] as $gearname => $data) {
		$gear = getobject("player_gear", $gearname);
		$img = getvgui($gearname,'css');
		$thisitem = array();
		$thisitem['Img'] = $img;
		$thisitem['Name'] = getlookup($gear['print_name']);
		$thisitem['Team'] = printval($gear,"team_access");
		$thisitem['Slot'] = printval($gear,"gear_slot");
		$thisitem['Cost'] = printval($gear,"gear_cost");
		$thisitem['Weight'] = printval($gear,"gear_weight");
		$thisitem['Ammo'] = printval($gear,"extra_ammo");
		if (isset($gear["DamageHitgroups"])) {
			$thisitem['DamageHitgroups'] = getbodygraph($gear, $gear["DamageHitgroups"],'',0,2);
		}
		$stats_tables['Gear']['items'][$gearname] = $thisitem;
		if ($data["gear_slot"] == 'armor') {
			$armors[$thisitem['Name']] = $gear["DamageHitgroups"];
		}
	}
	foreach ($theater["weapons"] as $wpnname => $data) {
		if (isset($data["IsBase"])) {
			continue;
		}
		$thisitem = array();
		$item = getobject("weapons", $wpnname);
		$pn = getlookup($item["print_name"]);
		$img = getvgui($wpnname,'css');
		$thisitem['Img'] = $img;
		$thisitem['Name'] = getlookup($item["print_name"]);
		$thisitem['Class'] = printval($item,"weapon_class");
		$thisitem['CR'] = (printval($item,"class_restricted")) ? printval($item,"class_restricted") : 0;
		$thisitem['Length'] = dist($item["barrel_length"],'IN');
		$thisitem['Cost'] = printval($item,"weapon_cost");
		$thisitem['Slot'] = printval($item,"weapon_slot");
		$thisitem['Weight'] = printval($item,"weapon_weight");
		$thisitem['RPM'] = printval($item,"rounds_per_minute");
		$thisitem['Sway'] = printval($item,"sway");
		$thisitem['Damage'] = 0;
		if (isset($item["ballistics"])) {
			$thisitem['Fire Modes'] = printval($item["ballistics"],"FireModes");
		} else {
			$thisitem['Fire Modes'] = 'single';
		}
		if (isset($item["explosives"])) {
			$thisitem['Ammo'] = printval($item["explosives"],"entity",1);
			$expammo = getobject("ammo", $item["ammo_clip"]["ammo_type"]);
			$thisitem['Carry'] = printval($expammo,"carry");
			$thisitem['Carry Max'] = printval($expammo,"carry");
		} elseif (isset($item["ammo_clip"])) {
			$ammo = getobject("ammo", $item["ammo_clip"]["ammo_type"]);
			$dmg = damageatrange($ammo['Damage'], $range);
			$thisitem['Damage'] = $dmg;
			if ($ammo['bulletcount'] > 1) {
				$thisitem['Damage']=($dmg*$ammo['bulletcount'])." max ({$ammo['bulletcount']} pellets)";
			}
			$thisitem['DamageChart'] = printval($ammo,"Damage");
			$thisitem['Spread'] = getspreadgraph($item["ballistics"]['spread'])."<br>{$item["ballistics"]['spread']}";
			$thisitem['Recoil'] = getrecoilgraph($item['recoil']);
			$thisitem['Ammo'] = printval($item["ammo_clip"],"ammo_type",1);
			if (($item["ammo_clip"]["clip_max_rounds"] > 1) && (!($item["ballistics"]["singleReload"]))) {
				$thisitem['Magazine'] = printval($item["ammo_clip"],"clip_max_rounds");
				$thisitem['Carry'] = printval($item["ammo_clip"],"clip_default")." (".($item["ammo_clip"]["clip_max_rounds"]*$item["ammo_clip"]["clip_default"]).")\n";
				$thisitem['Carry Max'] =printval($item["ammo_clip"],"clip_max")." (".($item["ammo_clip"]["clip_max_rounds"]*$item["ammo_clip"]["clip_max"]).")\n";
			} else {
				$thisitem['Magazine'] = printval($item["ammo_clip"],"clip_max_rounds");
				$thisitem['Carry'] = printval($item["ammo_clip"],"clip_default");
				$thisitem['Carry Max'] = printval($item["ammo_clip"],"clip_max");
			}
		} elseif (isset($item["melee"])) {
			$thisitem['Damage'] = printval($item["melee"],"MeleeDamage");
		}
		$stats_tables['Weapons']['items'][$wpnname] = $thisitem;
	}
	foreach ($theater["weapon_upgrades"] as $upname => $data) {
		if (isset($data["IsBase"])) {
			continue;
		}

//		if ((substr($upname,0,5) == "base_") || (substr($upname,-5,5) == "_base")) {
//			continue;
//		}
		$upgrade = getobject("weapon_upgrades", $upname,1);
		$img = getvgui($upname,'css');
		$thisitem = array();
		if (isset($upgrade['attach_weapon'])) {
			if (isset($stats_tables['Weapons']['items'][$upgrade['attach_weapon']])) {
				$stats_tables['Weapons']['items'][$upgrade['attach_weapon']]['Img'] = $img;
			}
		}
		if (substr($upname,0,5) == "ammo_") {
			$link = "<br><a href='#{$upgrade['ammo_type_override']}'>{$upgrade['ammo_type_override']} [{$upgrade['upgrade_cost']}]</a>";
			$fn = 'Ammo';
		} else {
			$link = "<a href='#{$upname}'>".getlookup($upgrade['print_name'])." [{$upgrade['upgrade_cost']}]</a><br>";
			$fn = 'Upgrades';
		}
		// Add ammo and upgrade links to weapon items
		if (isset($upgrade['allowed_weapons']['weapon'])) {
			$tmp = $upgrade['allowed_weapons']['weapon'];
			$upgrade['allowed_weapons'] = (is_array($tmp)) ? $tmp : array($tmp);
			
		}
		$aw = array();
		if (isset($upgrade['allowed_weapons'])) {
			foreach ($upgrade['allowed_weapons'] as $order => $witem) {
				$aw[] = "<a href='#{$witem}'>{$stats_tables['Weapons']['items'][$witem]['Name']}</a>";
				$stats_tables['Weapons']['items'][$witem][$fn].=$link;
			}
		}
		
		$thisitem['Img'] = $img;
		$thisitem['Name'] = getlookup($upgrade['print_name']);
		$thisitem['Slot'] = printval($upgrade,"upgrade_slot");
		$thisitem['CR'] = printval($upgrade,"class_restricted");
		$thisitem['Cost'] = printval($upgrade,"upgrade_cost");
		$thisitem['Ammo Type'] = printval($upgrade,"ammo_type_override",1);
		$thisitem['Abilities'] = printval($upgrade,"weapon_abilities");
		
		$thisitem['Weapons'] = implode("<br>", $aw);
		$stats_tables['Upgrades']['items'][$upname] = $thisitem;
	}
	foreach ($theater["ammo"] as $ammoname => $data) {
		// Hide rockets and grenades (so we can link to them in #explosives), and other items we don't want to see at all
		if ((substr($ammoname,0,7) == "rocket_") || (substr($ammoname,0,8) == "grenade_") || ($ammoname == 'default') || ($ammoname == 'no_carry')) {
			continue;
		}
		$ammo = getobject("ammo", $ammoname);
		if (!isset($ammo['carry']))
			continue;
		$thisitem = array();
		$thisitem['Name'] = "<a id='{$ammoname}'>{$ammoname}</a>";
		$thisitem['Carry'] = printval($ammo,"carry");
		$thisitem['Mag'] = printval($ammo,"magsize");
		$dmg = damageatrange($ammo['Damage'], $range);
		if ($ammo['bulletcount'] > 1) {
			$dmg=($dmg*$ammo['bulletcount'])." max ({$ammo['bulletcount']} pellets)";
		}
		$thisitem['Damage'] = $dmg;
		$thisitem['DamageGraph'] = printval($ammo,"Damage");
		$thisitem['PenetrationPower'] = damageatrange($ammo["PenetrationPower"], $range);
		$thisitem['PenetrationGraph'] = printval($ammo,"PenetrationPower");
		$thisitem['Tracer'] = "Type: {$ammo["tracer_type"]}<br>Frequency: {$ammo["tracer_frequency"]}<br>Low Ammo: {$ammo["tracer_lowammo"]}";
		$thisitem['Suppression'] = printval($ammo,"SuppressionIncrement");
		$thisitem['DamageHitgroups'] = getbodygraph($ammo, $ammo["DamageHitgroups"],array_keys($armors));
		$stats_tables['Ammo']['items'][$ammoname] = $thisitem;
	}
	foreach ($theater["explosives"] as $explosivename => $data) {
		if (isset($data["IsBase"])) {
			continue;
		}
		$explosive = getobject("explosives", $explosivename);
		$thisitem = array();
		$thisitem['Name'] = "<a id='{$explosivename}'>{$explosivename}</a>";
		$thisitem['Class'] = printval($explosive,"entity_class");
		$thisitem['FuseTime'] = printval($explosive,"FuseTime");
		if (isset($explosive["Cookable"])) {
			$thisitem['Cookable'] = printval($explosive,"Cookable");
		} else {
			$thisitem['Cookable'] = 1;
		}
		if (isset($explosive["RocketStartSpeed"])) {
			$speeds = array($explosive["RocketStartSpeed"]);
			if (isset($explosive["RocketTopSpeed"])) {
				if (isset($explosive["RocketAcceleration"])) {
					for ($i=(($explosive["RocketStartSpeed"])+($explosive["RocketAcceleration"]));$i<$explosive["RocketTopSpeed"];$i+=($explosive["RocketAcceleration"])) {
						$speeds[] = $i;
					}
				}
				$speeds[] = $explosive["RocketTopSpeed"];
			}
		}
		if (count($speeds) > 1) {
			$thisitem['Speed'] = getgraph($speeds,'Speed','Time');
		} else {
			$thisitem['Speed'] = dist($explosive["RocketStartSpeed"],'IN')."/s";
		}
		$dmg = ($range < $explosive["DetonateDamageRadius"]) ? round(($explosive["DetonateDamage"]) * ($explosive["DetonateDamageRadius"] / ($explosive["DetonateDamageRadius"] - $range)),2) : 0;
		$thisitem['Damage'] = $dmg;
		if (isset($explosive["AreaDamageAmount"])) {
			$thisitem['DamageGraph'] = "AreaDamageTime: {$explosive["AreaDamageTime"]}<br>AreaDamageFrequency: {$explosive["AreaDamageFrequency"]}<br>AreaDamageMinRadius: ".dist($explosive["AreaDamageMinRadius"],'IN')."<br>AreaDamageMaxRadius: ".dist($explosive["AreaDamageMaxRadius"],'IN')."<br>AreaDamageGrowSpeed: {$explosive["AreaDamageGrowSpeed"]}<br>AreaDamageAmount: {$explosive["AreaDamageAmount"]}<br>DamageType: {$explosive["DamageType"]}";
		} else {
			$thisitem['DamageGraph'] =	getcirclegraph($explosive)."<br>DetonateDamage: {$explosive["DetonateDamage"]}<br>DetonatePenetrationRadius: ".dist($explosive["DetonatePenetrationRadius"],'IN')."<br>DetonateDamageRadius: ".dist($explosive["DetonateDamageRadius"],'IN');
			if (isset($explosive["DetonateFlashDuration"])) {
				$thisitem['DamageGraph'].= "<br>DetonateFlashDuration: {$explosive["DetonateFlashDuration"]}";
			}
		}
		$stats_tables['Explosives']['items'][$explosivename] = $thisitem;
	}
	foreach ($theater['player_templates'] as $classname => $classdata) {
		$thisitem = array();
		$thisitem['Name'] = getlookup($classdata['print_name']);
		$thisitem['Team'] = printval($classdata,"team");
		$thisitem['Models'] = printval($classdata,"models");
		if (isset($classdata["buy_order"])) {
			foreach ($classdata["buy_order"] as $order => $buyitem) {
				foreach ($buyitem as $type => $item) {
					$thisitem['Buy order'].= "<a href='#{$item}'>{$item}</a><br>";
				}
			}
		}
		if (isset($classdata["allowed_items"])) {
			foreach ($classdata["allowed_items"] as $order => $aitem) {
				foreach ($aitem as $type => $item) {
					if (is_array($item)) {
						foreach ($item as $it => $in) {
							$thisitem['Allowed Items'].= "<a href='#{$in}'>[{$type}] {$in}</a><br>";
						}
					} else {
						$thisitem['Allowed Items'].= "<a href='#{$item}'>{$item}</a><br>";
					}
				}
			}
		}
		$stats_tables['Classes']['items'][$classname] = $thisitem;
	}
	// Teams are goofy because of display method
	foreach ($theater['teams'] as $teamname=>$teamdata) {
		$thisitem = '<table>';
		$tn = getlookup($teamdata['name']);
		$stats_tables['Teams']['fields'][$tn] = 1;
		if (isset($teamdata['logo'])) {
			$thisitem.="<div style='text-align: center;'><img src='data/materials/vgui/{$teamdata['logo']}.png' style='width: 64px; height: 64px;'></div>\n";
		}
		if (isset($teamdata['squads'])) {
			foreach ($teamdata['squads'] as $squad => $squaddata) {
				$sn = getlookup($squad);
				$thisitem.="<tr><td><h3>{$sn}</h3></td></tr><tr><td>\n";
				foreach ($squaddata as $order => $slot) {
					foreach ($slot as $title => $role) {
						$label = getlookup($title);
						$thisitem.="<a href='#{$role}'>{$label}<br>\n";
					}
				}
				$thisitem.="</td></tr>\n";
			}
		}
		$thisitem.="</table>\n";
		$stats_tables['Teams']['items'][0][$tn] = $thisitem;
	}
	// Do cleanup and make links between items here
	foreach (array_keys($stats_tables) as $sectionname) {
		ksort($stats_tables[$sectionname]['items']);
	}
}
$stats_tables = array(
	'Weapons' => array(
		'fields' => array(
			'Name' => 1,
			'Class' => 0,
			'CR' => 0,
			'Length' => 0,
			'Cost' => 1,
			'Slot' => 0,
			'Weight' => 0,
			'RPM' => 1,
			'Fire Modes' => 0,
			'Damage' => 1,
			'DamageChart' => 1,
			'Spread' => 0,
			'Recoil' => 0,
			'Sway' => 0,
			'Ammo' => 1,
			'Magazine' => 1,
			'Carry' => 1,
			'Carry Max' => 0,
			'Upgrades' => 1
		)
	),
	'Upgrades' => array(
		'fields' => array(
			'Name' => 1,
			'Slot' => 0,
			'CR' => 0,
			'Cost' => 1,
			'Ammo Type' => 1,
			'Abilities' => 0,
			'Weapons' => 1
		)
	),
	'Ammo' => array(
		'fields' => array(
			'Name' => 1,
			'Carry' => 0,
			'Mag' => 0,
			'Damage' => 1,
			'DamageGraph' => 1,
			'PenetrationPower' => 1,
			'PenetrationGraph' => 1,
			'Tracer' => 0,
			'Suppression' => 1,
			'DamageHitgroups' => 1
		)
	),
	'Explosives' => array(
		'fields' => array(
			'Name' => 1,
			'Class' => 0,
			'FuseTime' => 1,
			'Cookable' => 1,
			'Speed' => 0,
			'Damage' => 1,
			'DamageGraph' => 1
		)
	),
	'Gear' => array(
		'fields' => array(
			'Name' => 1,
			'Team' => 0,
			'Slot' => 0,
			'Cost' => 1,
			'Weight' => 0,
			'Ammo' => 1,
			'DamageHitgroups' => 1
		)
	),
	'Teams' => array(),
	'Classes' => array(
		'fields' => array(
			'Name' => 1,
			'Team' => 1,
			'Models' => 1,
			'Buy order' => 1,
			'Allowed Items' => 1
		)
	)
);
	

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
	
	
	/* ParseTheaterFile
	Takes a KeyValues file and parses it. If #base directives are included, pull those and merge contents on top
	*/
	public function ParseTheaterFile($filename,$version='',$path='',$collapse_conditionals=true) {
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
				$basedata = merge_theaters($basedata,ParseTheaterFile($base,$version,$path,$collapse_conditionals));
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
