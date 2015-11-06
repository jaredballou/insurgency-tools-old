<?php
/*
This tool takes a number of mutators (settings per section to change) and
snippets (small segments of a theater file) and combines them to generate one
complete theater. This is integrated with another SourceMod plugin which
includes most of this functionality as an in-game menu that admins can use to
generate custom theaters on the fly. It is still very much in-progress and help
would be welcomed on this one.
*/
require_once "include/Spyc.php";
$title="Theater Creator";
$css_content = "
.title {
	align: center;
	font-size: 200%;
	font-weight: bold;
}
.help {
	margin: 15px;
}
.section {
	font-size: 175%;
	font-weight: bold;
}
.subsection {
	font-size: 150%;
	font-weight: bold;
}
.desc {
	font-style: italic;
}
";
//$css_content = "div { margin: 5px; }\n";
$sections = array();
include "include/header.php";
$snippet_path = "{$rootpath}/theaters/snippets";
startbody();
function ShowWeaponOptions($wpntype) {
	$str = "<select name='weapon_groups[{$wpntype}]'>\n";
	foreach (array('Ignore','Disable','AllClasses','OnlyThese') as $option) {
		$checked = ($_GET["weapon_groups[{$wpntype}]"] == $option) ? ' SELECTED' : '';
		$str.="<option{$checked}>{$option}</option>\n";
	}
	$str.="</select>\n";
	return $str;
}
function LoadSnippets(&$snippets,$path='') {
	global $sections,$snippet_path,$version;
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
						$snippets[$path_parts['filename']] = getfile($file,$version,$path_parts['dirname']);
						break;
				}
			}
		}
	}
}
function DisplayTheaterCreationMenu() {
	global $snippets,$sections,$theaters,$theatername,$theaterfile,$version,$versions;
	$str = "<div><form action='theater.php' method='GET'>\n";
	$str.="<div class='title'>Theater Generator</div>\n";
	$str.="<div class='help'>This tool is designed to give average users and server admins the ability to create custom theater files for their servers, without needing to understand how to modify them. Theater files are the way that Insurgency tracks practically all player/item/weapon stats and settings, allowing a good amount of customization and changing of gameplay to your tastes. The tool works in two ways. \"Mutators\" that simply read the theater files directly from game data and make changes to them based upon some simple rules. \"Snippets\" are short sections of theater files that make a tweak to gameplay in a more detailed manner, such as giving all players a specific kit or removing the ability to slide, for example. As more players use this tool, we will be accepting snippets and mutators from the community to increase the utility of this tool, so please feel free to <a href='http://steamcommunity.com/id/jballou'>add me on steam</a> if you want to contribute.</div>\n";
	//Theater selection
	$str.="<div class='theaterselect'><b>Base Theater:</b> <select name='theater'>";
	foreach ($theaters as $theatername) {
		$sel = (($theatername == $theaterfile) || ($theatername == $_REQUEST['theater'])) ? ' SELECTED' : '';
		$str.="					<option{$sel}>{$theatername}</option>\n";
	}
	$str.="</select>\n";
	$str.="<b>Version:</b> <select name='version'>\n";
	foreach ($versions as $vid) {
		$sel = ($vid == $version) ? ' SELECTED' : '';
		$str.="<option{$sel}>{$vid}</option>\n";
	}
	$str.="</select></div>\n";

	foreach ($snippets as $sname => $sdata) {
		//Skip if this is a directory
		if (in_array($sname,$sections)) {
		} else {
			$name = (isset($sdata['name'])) ? $sdata['name'] : $sname;
			$desc = (isset($sdata['desc'])) ? "{$sdata['desc']}<br>" : '';
			$str.="<div class='section'>{$name}</div>\n<div class='desc'>{$desc}</div>\n";
			switch ($sname) {
				case 'mutators':
					foreach ($sdata['settings'] as $mutator => $mdata) {
						$name = (isset($mdata['name'])) ? $mdata['name'] : $mutator;
						$desc = (isset($mdata['desc'])) ? "{$mdata['desc']}<br>" : '';
						$str.="<div class='subsection'><input type='checkbox' name='mutator[{$mutator}]'>{$name}</div>\n<div class='desc'>{$desc}</div>\n";
						foreach ($mdata['settings'] as $section => $sdata) {
							$str.="<ul>\n";
							foreach ($sdata as $setting => $default) {
								$str.="<li>{$section}.{$setting}: <input type='text' name='setting[{$mutator}][{$section}][{$setting}]' value='{$default}'></li>";
							}
							$str.="</ul>\n";
						}
						$str.="</div>";
					}
					break;
				case 'weapon_groups':
					foreach ($sdata['settings'] as $wpntype => $weapons) {
						$str.="<div class='subsection'>{$wpntype}: ".ShowWeaponOptions($wpntype)."</div>\n<ul>\n";
						foreach ($weapons as $weapon) {
							$str.="<li>{$weapon}</li>";
						}
						$str.="</ul>";
					}
					break;
				default:
					break;
			}
			$str.="</div>";
	}
	}
	$str.="<h2>Theater Snippets</h2>";
	foreach ($sections as $sname) {
		$str.="<div><h3>{$sname}</h3><select name='section[{$sname}]'><option value=''>--None--</option>";
		foreach ($snippets[$sname] as $tname => $tdata) {
			$str.="<option>{$tname}</option>";
		}
		$str.="</select></div>";
	}
	$str.="<div><input type='submit' name='go' value='Generate Theater'></form></div></div>";
	return $str;
}
$snippets = array();
LoadSnippets($snippets);
//var_dump($snippets);
//$tidy = new tidy;
//echo $tidy->repairString('<div class="container">'.DisplayTheaterCreationMenu().'</div>');
function GenerateTheater() {
	global $theater,$version,$theaterfile,$snippet_path,$snippets;
	$data = array();
	$hdr="#Theater generated\n";
	foreach ($_GET['section'] as $section=>$snippet) {
		if (!strlen($snippet)) {
			continue;
		}
		$hdr.="#Load {$snippet_path}/{$section}/{$snippet}.theater\n";
//echo "Before<br>\n";
//var_dump($data);
		$data = array_merge_recursive(getfile("{$snippet}.theater",$version,"{$snippet_path}/{$section}"),$data);
//echo "After<br>\n";
//var_dump($data);
	}
	foreach ($_GET['mutator'] as $mname => $mdata) {
		if (!(strlen($mdata))) {
			continue;
		}
		foreach ($_GET['setting'][$mname] as $section=>$settings) {
			foreach ($settings as $key => $val) {
				$hdr.="#Change {$section}.{$key} to {$val}\n";
				foreach ($theater[$section] as $iname=>$idata) {
					$data[$section][$iname][$key] = $val;
				}
			}
		}
	}
	foreach ($_GET['weapon_groups'] as $gname => $gstatus) {
		if ($gstatus == 'Ignore') {
			continue;
		}
		$hdr.="#Change weapon group {$gname} to {$gstatus}\n";
		$weapons = $snippets['weapon_groups'][$gname];
		foreach ($theater['player_templates'] as $cname=>$cdata) {
			$items = ($gstatus == 'OnlyThese') ? array() : $cdata['allowed_items'];
			foreach ($weapons as $weapon) {
				$match = -1;
				foreach ($items as $idx=>$idata) {
					foreach ($idata as $type=>$name) {
						if ($name == $weapon) {
							$match = $idx;
							break;
						}
					}
				}
				switch ($gstatus) {
					case 'Disable':
						if ($match > -1) {
							unset($items[$match]);
						}
						break;
					case 'AllClasses':
					case 'OnlyThese':
						if ($match == -1) {
							$items[] = array('weapon' => $weapon);
						}
						break;
				}
			}
			if ($items != $cdata['allowed_items']) {
				$data['player_templates'][$cname]['allowed_items'] = $items;
			}
		}
	}
	$kvdata = kvwrite(array('theater' => $data));
	//var_dump($kvdata);
	return $hdr."\n".$kvdata;
}
if ($_REQUEST['go'] == "Generate Theater") {
	echo "<textarea rows='20' cols='120'>".GenerateTheater()."</textarea>";
} else {
	echo DisplayTheaterCreationMenu();
}
include "include/footer.php";
exit;
?>
