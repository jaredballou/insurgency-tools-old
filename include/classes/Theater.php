<?php
// Theater
$snippets = array();
$sections = array();
$snippet_path = "{$rootpath}/theaters/snippets";

function ShowItemGroupOptions($groupname) {
	echo "<select name='item_groups[{$groupname}]'>\n";
	foreach (array('Ignore','Disable','AllClasses','OnlyThese') as $option) {
		$checked = ($_GET["item_groups[{$groupname}]"] == $option) ? ' SELECTED' : '';
		echo "<option{$checked}>{$option}</option>\n";
	}
	echo "</select>\n";
}
function LoadSnippets(&$snippets,$path='') {
	global $sections,$snippet_path,$version,$mod,$mods;
	if ($path == '') { $path = $snippet_path; }
	$files = glob("{$path}/*");
	foreach ($files as $file) {
		$path_parts = pathinfo($file);
		if($path_parts['basename'] !="." && $path_parts['basename'] !="..") {
//			echo "{$file}\n";
			if (is_dir($file)) {
				$sections[$path_parts['basename']] = $path_parts['basename'];
				LoadSnippets($snippets[$path_parts['basename']],$file);
			} else {
				switch ($path_parts['extension']) {
					case 'yaml':
						$snippets[$path_parts['filename']] = Spyc::YAMLLoad($file);
						break;
					case 'theater':
						$lines = preg_split('/\n|\r\n?/', file_get_contents($file));
						$header = "";
						foreach ($lines as $line) {
							$line = trim($line);
							if (($line[0] != $line[1]) || ($line[1] != '/')) {
								break;
							}
							$line = trim(preg_replace('/^[\/ \t]*/','',$line));
							$header.="{$line}\n";

						}
						$snippets[$path_parts['filename']] = array(
							'name'		=> $path_parts['filename'],
							'desc'		=> trim($header),
							'settings'	=> getfile($file,$mod,$version,$path_parts['dirname']),
						);
						break;
				}
			}
		}
	}
}
function ProcessItemGroup($group) {
/*
	global $theater;
	if (isset($group['filters'])) {
	}
	foreach ($group as $field => $items) {
		if (!isset($theater[$field]))
			continue;
							echo "<li>{$field}<br>\n";
							echo "<ul>\n";
							foreach ($items as $item) {
								echo "<li>{$item}</li>";
							}
							echo "</ul>\n</li>\n";
*/
	return $group;
}
function DisplayTheaterCreationMenu() {
	global $mods,$snippets,$sections,$theaters,$theatername,$theaterfile,$version,$versions,$theater;
//var_dump($mods);
	echo "<div><form action='theater.php' method='GET'>\n";
	echo "<div class='title'>Theater Generator</div>\n";
	echo "<div class='help'>
This tool is designed to give average users and server admins the ability to create custom theater files for their servers, without needing to understand how to
 modify them. Theater files are the way that Insurgency tracks practically all player/item/weapon stats and settings, allowing a good amount of customization and
 changing of gameplay to your tastes. The tool has several types of resources.<ul><li><b>Item Groups:</b> These are groups which can be modified to make changes to all player loadouts based on item grouping. So, it could be used to make a theater with only pistols and grenades, or give all players LMGs for example.</li><li><b>Mutators:</b> Simple scripts that change all settigs in a theater based upon rules. For example, \"Set all weapon weight to 0\".</li><li><b>Snippets:</b> Modulat theater files that make a tweak to gameplay in a more detailed manner,
 such as:<ul><li>Giving all players a specific kit</li><li>Removing the ability to slide</li><li>Adding new weapons</li><li>Adjusting team and player class composition</li></ul></li></ul>As more players use this tool, we will be accepting snippets and 
mutators from the community to increase the utility of this tool, so please feel free to <a href='http://steamcommunity.com/id/jballou'>add me on steam</a> if you 
want to contribute.</div>\n";

	//Theater selection
	echo "<div class='theaterselect'>\n";
	echo DisplayModSelection();
	echo "<input type='checkbox' name='include_all_theaters'> Include all base theaters into one standalone theater? Will create a bigger theater, but it will have no dependencies.</div>\n";

	// Merge snippets
	foreach ($snippets as $sname => $sdata) {
		//Skip if this is a directory, since we will handle it later in the Theater Snippet section
		if (in_array($sname,$sections)) {
		} else {
			$name = (isset($sdata['name'])) ? $sdata['name'] : $sname;
			$desc = (isset($sdata['desc'])) ? "{$sdata['desc']}<br>" : '';
			$style="";//style='display: none;'";
			echo "<div class='section toggle-section' id='header-section-{$sname}'>{$name}</div>\n<div class='desc'>{$desc}</div>\n<div id='section-{$sname}'{$style}>\n";

			switch ($sname) {
				case 'mutators':
					foreach ($sdata['settings'] as $mutator => $mdata) {
						$name = (isset($mdata['name'])) ? $mdata['name'] : $mutator;
						$desc = (isset($mdata['desc'])) ? "{$mdata['desc']}<br>" : '';
						echo "<div class='subsection toggle-section' id='header-section-{$sname}-{$mutator}'>{$name}</div>\n";
						echo "<div class='desc'><input type='checkbox' name='mutator[{$mutator}]'>{$desc}</div>\n";
						echo "<div id='section-{$sname}-{$mutator}'>\n";
						foreach ($mdata['settings'] as $section => $sdata) {
							echo "<ul>\n";
							foreach ($sdata as $setting => $default) {
								echo "<li>{$section}.{$setting}: <input type='text' name='setting[{$mutator}][{$section}][{$setting}]' value='{$default}'></li>";
							}
							echo "</ul>\n";
						}
						echo "</div>";
					}
					break;
				case 'item_groups':
					foreach ($sdata['settings'] as $groupname => $group) {
						echo "<div class='subsection'>{$groupname}: ";
						ShowItemGroupOptions($groupname);
						echo "</div>\n<ul>\n";
						$group = ProcessItemGroup($group);
						foreach ($group as $field => $items) {
							if (!isset($theater[$field]))
								continue;
							echo "<li>{$field}<br>\n";
							echo "<ul>\n";
							foreach ($items as $item) {
								echo "<li>{$item}</li>";
							}
							echo "</ul>\n</li>\n";
						}
						echo "</ul>";
					}
					break;
				default:
					break;
			}
			echo "</div>\n";
//</div>\n";
		}
	}
	echo "<div class='toggle-section section' id='header-section-theater-snippets'>Theater Snippets</div>\n";
	echo "<div class='desc'>These are pieces of other mods that can be assembled into a finished theater file</div>\n";
	echo "<div id='section-theater-snippets'>\n";
	foreach ($sections as $section) {


		echo "<div class='toggle-section subsection' id='header-section-theater-snippets-{$section}'>{$section}</div>\n<div id='section-theater-snippets-{$section}'>\n";
		if ($display == 'select') {
			echo "<select name='section[{$section}]'>\n<option value=''>--None--</option>\n";
				foreach ($snippets[$section] as $sname => $sdata) {
					echo "<option>{$sname}</option>\n";
				}
				echo "</select>\n";
		} else {
			foreach ($snippets[$section] as $sname => $sdata) {
/*
					foreach ($sdata['settings'] as $sname => $sdata) {
						$name = (isset($sdata['name'])) ? $sdata['name'] : $mutator;
						$desc = (isset($sdata['desc'])) ? "{$sdata['desc']}<br>" : '';
						echo "<div class='subsection toggle-section' id='header-section-{$section}-{$mutator}'>\n<input type='checkbox' name='mutator[{$mutator}]'>{$name}</div>\n<div id='section-{$section}-{$mutator}'>\n<div class='desc'>{$desc}</div>\n";
						foreach ($sdata['settings'] as $section => $sdata) {
							echo "<ul>\n";
							foreach ($sdata as $setting => $default) {
								echo "<li>{$section}.{$setting}: <input type='text' name='setting[{$mutator}][{$section}][{$setting}]' value='{$default}'></li>";
							}
							echo "</ul>\n";
						}
						echo "</div>";
					}
*/
				echo "<span>\n";
//				echo "<span class='toggle-section' id='header-section-{$section}-{$sname}'>\n";
				echo "<input type='checkbox' name='section[{$section}][{$sname}]'>\n";
				echo "<b>{$sname}:</b>\n";
//				echo "</span>\n";
//				echo "<span id='section-{$section}-{$sname}'>\n";
				echo str_replace("\n","<br>\n",$sdata['desc']);
//				echo "</span>\n";
				echo "</span>\n";
				echo "<br>\n";
			}

		}
		echo "</div>\n";
	}
	echo "</div>\n";
	echo "<div>\n<input type='submit' name='go' value='Generate Theater'\n></form>\n</div>\n</div>\n";
}

function GenerateTheater() {
	global $theater,$version,$theaterfile,$snippet_path,$snippets,$mod,$mods;
	$data = array();
	$hdr = array("// Theater generated");
	$ib = ($_REQUEST['include_all_theaters'] == 'on');
//$basedata = array_merge_recursive(getfile($base,$mod,$version,$path,$base_theaters),$basedata);
//$theater = theater_array_replace_recursive($basedata,$theater);
	if ($ib) {
		$hdr[]="// Load {$theaterfile}.theater";
		$data = $theater;
	} else {
		array_unshift($hdr,"\"#base\" \"{$theaterfile}.theater\"");
	}
	foreach ($_GET['section'] as $section=>$snippet) {
//var_dump($section,$snippet);
		if (is_array($snippet)) {
		} else {
			if (!strlen($snippet)) {
				echo "not parsing {$section}\n";
				continue;
			}
			$snippet=array($snippet => "on");
		}
		foreach ($snippet as $sname=>$sval) {
			$hdr[]="// Load {$section}/{$sname}.theater";
			$data = theater_array_replace_recursive(getfile("{$sname}.theater",$mod,$version,"{$snippet_path}/{$section}"),$data);
		}
	}
	foreach ($_GET['mutator'] as $mname => $mdata) {
		if (!(strlen($mdata))) {
			continue;
		}
		foreach ($_GET['setting'][$mname] as $section=>$settings) {
			foreach ($settings as $key => $val) {
				$hdr[]="// Change {$section}.{$key} to {$val}";
				foreach ($theater[$section] as $iname=>$idata) {
					$data[$section][$iname][$key] = $val;
				}
			}
		}
	}
	$onlythese=array();
	foreach ($_GET['item_groups'] as $gname => $gstatus) {
		if ($gstatus == 'Ignore') {
			continue;
		}
		$hdr[]="// Change weapon group {$gname} to {$gstatus}";
		$gdata = $snippets['item_groups']['settings'][$gname];
//		$weapons = (isset($gdata['weapons'])) ? $gdata['weapons'] : array();
//		$weapon_upgrades = (isset($gdata['weapon_upgrades'])) ? $gdata['weapon_upgrades'] : array();
//		$player_gear = (isset($gdata['player_gear'])) ? $gdata['player_gear'] : array();
//		$filters = (isset($gdata['filters'])) ? $gdata['filters'] : array();
		foreach ($theater['player_templates'] as $cname=>$cdata) {
			$allowed_items = ($gstatus == 'OnlyThese') ? $onlythese : $cdata['allowed_items'];
			foreach ($gdata as $field => $items) {
				if ((!isset($theater[$field])) && (!isset($data[$field])))
					continue;
				foreach ($items as $item) {
					$match = -1;
					foreach ($allowed_items as $idx=>$pair) {
						foreach ($pair as $type=>$name) {
//var_dump($type,$field,$name,$item);
							if ((($type == $field) || ("{$type}s" == $field)) && ($name == $item)) {
								$match = $idx;
								break;
							}
						}
					}
					switch ($gstatus) {
						case 'Disable':
							if ($match > -1) {
								unset($allowed_items[$match]);
							}
							break;
						case 'AllClasses':
						case 'OnlyThese':
							if ($match == -1) {
								$allowed_items[] = array($field => $item);
							}
							break;
					}
				}
			}
//var_dump($allowed_items);
			if ($allowed_items != $cdata['allowed_items']) {
				if ($gstatus == 'OnlyThese') {
					$onlythese = $allowed_items;
				}

				$data['player_templates'][$cname]['allowed_items'] = $allowed_items;
			}
		}
	}
	$kvdata = kvwrite(array('theater' => $data));
	//var_dump($kvdata);
	return implode("\n",$hdr)."\n".$kvdata;
}
?>
