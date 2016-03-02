<?php
/*
This tool takes a number of mutators (settings per section to change) and
snippets (small segments of a theater file) and combines them to generate one
complete theater. This is integrated with another SourceMod plugin which
includes most of this functionality as an in-game menu that admins can use to
generate custom theaters on the fly. It is still very much in-progress and help
would be welcomed on this one.
*/
//Root Path Discovery
do { $rd = (isset($rd)) ? dirname($rd) : realpath(dirname(__FILE__)); $tp="{$rd}/rootpath.php"; if (file_exists($tp)) { require_once($tp); break; }} while ($rd != '/');
require_once "${includepath}/class.Spyc.php";
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
include "${includepath}/header.php";
$snippet_path = "{$rootpath}/theaters/snippets";
?>
<script>
$(document).ready(function(){
    $(".toggle-section").click(function(){
        var target = "#" + $(this).attr('id').replace("header-","");
        $(target).toggle();
    });
});
</script>
<?php
startbody();
echo "<h1>This is still very new, and not much is wired up yet. Let me know what features you'd like</h1>\n";
$snippets = array();
LoadSnippets($snippets);
//var_dump($snippets);
//$tidy = new tidy;
//echo $tidy->repairString('<div class="container">'.DisplayTheaterCreationMenu().'</div>');
$theater = getfile("{$theaterfile}.theater",$mod,$version,$theaterpath);

if ($_REQUEST['go'] == "Generate Theater") {
	echo "<textarea rows='20' cols='120'>".GenerateTheater()."</textarea>";
} else {
	echo DisplayTheaterCreationMenu();
}
include "${includepath}/footer.php";
exit;


function ShowItemGroupOptions($groupname) {
	$str = "<select name='item_groups[{$groupname}]'>\n";
	foreach (array('Ignore','Disable','AllClasses','OnlyThese') as $option) {
		$checked = ($_GET["item_groups[{$groupname}]"] == $option) ? ' SELECTED' : '';
		$str.="<option{$checked}>{$option}</option>\n";
	}
	$str.="</select>\n";
	return $str;
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
						$snippets[$path_parts['filename']] = getfile($file,$mod,$version,$path_parts['dirname']);
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
							$str.="<li>{$field}<br>\n";
							$str.="<ul>\n";
							foreach ($items as $item) {
								$str.="<li>{$item}</li>";
							}
							$str.="</ul>\n</li>\n";
*/
	return $group;
}
function DisplayTheaterCreationMenu() {
	global $mods,$snippets,$sections,$theaters,$theatername,$theaterfile,$version,$versions,$theater;
//var_dump($mods);
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
	foreach (array_keys($mods['insurgency']) as $vid) {
		$sel = ($vid == $version) ? ' SELECTED' : '';
		$str.="<option{$sel}>{$vid}</option>\n";
	}
	$str.="</select>\n";
	$str.="<input type='submit' name='go' value='Use This Base'>\n</div>\n";

	foreach ($snippets as $sname => $sdata) {
		//Skip if this is a directory
		if (in_array($sname,$sections)) {
		} else {
			$name = (isset($sdata['name'])) ? $sdata['name'] : $sname;
			$desc = (isset($sdata['desc'])) ? "{$sdata['desc']}<br>" : '';
			$str.="<div class='section toggle-section' id='header-section-{$sname}'>{$name}</div>\n<div id='section-{$sname}'>\n<div class='desc'>{$desc}</div>\n";
			switch ($sname) {
				case 'mutators':
					foreach ($sdata['settings'] as $mutator => $mdata) {
						$name = (isset($mdata['name'])) ? $mdata['name'] : $mutator;
						$desc = (isset($mdata['desc'])) ? "{$mdata['desc']}<br>" : '';
						$str.="<div class='subsection toggle-section' id='header-section-{$sname}-{$mutator}'>\n<input type='checkbox' name='mutator[{$mutator}]'>{$name}</div>\n<div id='section-{$sname}-{$mutator}'>\n<div class='desc'>{$desc}</div>\n";
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
				case 'item_groups':
					foreach ($sdata['settings'] as $groupname => $group) {
						$str.="<div class='subsection'>{$groupname}: ".ShowItemGroupOptions($groupname)."</div>\n<ul>\n";
						$group = ProcessItemGroup($group);
						foreach ($group as $field => $items) {
							if (!isset($theater[$field]))
								continue;
							$str.="<li>{$field}<br>\n";
							$str.="<ul>\n";
							foreach ($items as $item) {
								$str.="<li>{$item}</li>";
							}
							$str.="</ul>\n</li>\n";
						}
						$str.="</ul>";
					}
					break;
				default:
					break;
			}
			$str.="</div>\n</div>\n";
	}
	}
	$str.="<div class='toggle-section section' id='header-theater-snippets'>Theater Snippets</div>\n";
	$str.="<div id='theater-snippets'>\n";
	foreach ($sections as $sname) {
		$str.="<div class='toggle-section subsection' id='header-section-theater-snippets-{$sname}'>{$sname}</div>\n<div id='section-theater-snippets-{$sname}'>\n<select name='section[{$sname}]'>\n<option value=''>--None--</option>\n";
		foreach ($snippets[$sname] as $tname => $tdata) {
			$str.="<option>{$tname}</option>\n";
		}
		$str.="</select>\n</div>\n";
	}
	$str.="</div>\n";
	$str.="<div>\n<input type='submit' name='go' value='Generate Theater'\n></form>\n</div>\n</div>\n";
	return $str;
}

function GenerateTheater() {
	global $theater,$version,$theaterfile,$snippet_path,$snippets,$mod,$mods;
	$data = array();
	$hdr=array("\"#base\" \"{$theaterfile}.theater\"","// Theater generated");
	foreach ($_GET['section'] as $section=>$snippet) {
		if (!strlen($snippet)) {
			continue;
		}
		$hdr[]="// Load {$section}/{$snippet}.theater";
		$data = array_merge_recursive(getfile("{$snippet}.theater",$mod,$version,"{$snippet_path}/{$section}"),$data);
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
