<?php
require_once "include/Spyc.php";
$title="Theater Creator";
$css_content = "div { margin: 5px; }\n";
$snippet_path = "/opt/fastdl/scripts/theaters/snippets";
$sections = array();
include "include/header.php";

startbody();
function ShowWeaponOptions($wpntype) {
	$str = "<select name='weapon_groups.{$wpntype}'>\n";
	foreach (array('--Ignore--','Disable Weapons','Allow Weapons for All Classes','Only Allow Weapons from this Group') as $option) {
		$checked = ''; // TODO: Keep correct value through reloads
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
	global $snippets,$sections;
	$str ="<form action='theater.php' method='GET'>";
	foreach ($snippets as $sname => $sdata) {
		if (in_array($sname,$sections)) {
		} else {
			$str.="<div><input type='checkbox' name='{$sname}'>{$sname}<br>";
			switch ($sname) {
				case 'mutators':
					foreach ($sdata as $mutator => $mdata) {
//var_dump($sdata,$mutator,$settings);
						$str.="<div><input type='checkbox' name='{$mutator}'>{$mutator}<br>";
						foreach ($mdata as $section => $sdata) {
//							$str.="<div>{$section}<br>";
							foreach ($sdata as $setting => $default) {
								$str.="<div>{$section}.{$setting}: <input type='text' name='{$mutator}.{$section}.{$setting}' value='{$default}'><br></div>";
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
	foreach ($sections as $sname) {
		$str.="<div>{$sname}<select name='{$sname}'><option value=''>--None--</option>";
		foreach ($snippets[$sname] as $tname => $tdata) {
			$str.="<option>{$tname}</option>";
		}
		$str.="</select></div>";
	}
	$str.="<div><input type='submit' name='go' value='Generate Theater'></form></div>";
	return $str;
}
$snippets = array();
LoadSnippets($snippets);
//var_dump($snippets);
//$tidy = new tidy;
//echo $tidy->repairString('<div class="container">'.DisplayTheaterCreationMenu().'</div>');
echo DisplayTheaterCreationMenu();

include "include/footer.php";
exit;
?>
