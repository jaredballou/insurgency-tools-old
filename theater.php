<?php
require_once "include/Spyc.php";
$title="Theater Creator";
//$css_content = "div { margin: 5px; }\n";
$snippet_path = "/opt/fastdl/scripts/theaters/snippets";
$sections = array();
include "include/header.php";
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
	global $sections,$snippet_path;
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
						$snippets[$path_parts['filename']] = getfile($file,'1.9.0.0',$path_parts['dirname']);
						break;
				}
			}
		}
	}
}
function DisplayTheaterCreationMenu() {
	global $snippets,$sections,$theaters,$theatername,$theaterfile;
	$str = "<div><form action='theater.php' method='GET'>\n";
	$str.="<b>Base Theater:</b> <select name='theater'>";
	foreach ($theaters as $theatername) {
		$sel = (($theatername == $theaterfile) || ($theatername == $_REQUEST['theater'])) ? ' SELECTED' : '';
		$str.="					<option{$sel}>{$theatername}</option>\n";
	}
	$str.="</select><br>\n";

	foreach ($snippets as $sname => $sdata) {
		if (in_array($sname,$sections)) {
		} else {
			$str.="<div><h2>{$sname}</h2>";
			switch ($sname) {
				case 'mutators':
					foreach ($sdata as $mutator => $mdata) {
//var_dump($sdata,$mutator,$settings);
						$str.="<div><h3><input type='checkbox' name='mutator[{$mutator}]'>{$mutator}</h3>";
						foreach ($mdata as $section => $sdata) {
//							$str.="<div>{$section}<br>";
							foreach ($sdata as $setting => $default) {
								$str.="<div>{$section}.{$setting}: <input type='text' name='setting[{$mutator}][{$section}][{$setting}]' value='{$default}'><br></div>";
							}
//							$str.="</div>";
						}
						$str.="</div>";
					}
					break;
				case 'weapon_groups':
					foreach ($sdata as $wpntype => $weapons) {
						$str.="<div>{$wpntype}: ".ShowWeaponOptions($wpntype)."<br>";
						foreach ($weapons as $weapon) {
							$str.="{$weapon}<br>";
						}
						$str.="</div>";
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
