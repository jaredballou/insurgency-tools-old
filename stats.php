<?php
$title = "Insurgency Theater Parser";
$tableclasses = "table table-striped table-bordered table-condensed table-responsive";
require_once "include/header.php";

//require_once "include/functions.php";
//getspreadgraph('0.2 0.2 0.2');
//exit;

//Include kv reader
//require_once "kvreader2.php";

$langfiles = glob("data/resource/insurgency_*.txt");
$langfiles = glob("data/resource/insurgency_english.txt");
$theaterpath='';
$custom_theater_paths = array('Custom' => '/opt/fastdl/scripts/theaters');
//$reader = new KVReader();
//Load languages
$lang = array();
$data = trim(preg_replace('/[\x00-\x08\x0E-\x1F\x80-\xFF]/s', '', file_get_contents('data/sourcemod/configs/languages.cfg')));
$data = parseKeyValues($data);//$reader->read($data);
$langcode = array();
foreach ($data['Languages'] as $code => $name) {
	$name = strtolower($name);
	$langcode[$name] = $code;
}

foreach ($langfiles as $langfile) {

	$data = trim(preg_replace('/[\x00-\x08\x0E-\x1F\x80-\xFF]/s', '', file_get_contents($langfile)));
//var_dump($data);
	$data = parseKeyValues($data);//$reader->read($data);
	foreach ($data["lang"]["Tokens"] as $key => $val) {
		if ($_REQUEST['command'] != 'smtrans') {
			$key = "#".strtolower($key);
		}
		$key = trim($key);
		if ($key) {
//var_dump($data["lang"]["Language"],$key,$val);
			//Sometimes NWI declares a strint twice!
			if (is_array($val))
				$val = $val[0];
			$lang[$data["lang"]["Language"]][$key] = $val;
		}
	}
}
//var_dump($lang);
//exit;
$language = "English";
if ($_REQUEST['language']) {
	if (in_array($_REQUEST['language'],$lang)) {
		$language = $_REQUEST['language'];
	}
}

//var_dump($lang);
//Load versions
$versions = array();
$dirs = glob("data/theaters/*");
foreach ($dirs as $dir) {
	$versions[] = basename($dir);
}
asort($versions);
$newest_version = $version = end($versions);
/*
foreach ($custom_theater_paths as $name => $path) {
	if (file_exists($path)) {
		$versions[] = $name;
	}
}
*/
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
//Always set local range to units to match game
//var_dump($range,$range_unit);
//if (array_key_exists($version,$custom_theater_paths)) {
//	$files = glob("{$custom_theater_paths[$version]}/*.theater");
//} else {
	$files = glob("data/theaters/{$version}/*.theater");
//}
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
//var_dump($theater);
if ($version != $version_compare) {
	$theater_compare = getfile("{$theaterfile}.theater",$version_compare,$theaterpath);
	$changes = multi_diff($version,$theater,$version_compare,$theater_compare);
echo "<table border='1' cellpadding='2' cellspacing='0'><tr><th>Value</th><th>{$version}</th><th>{$version_compare}</th></tr>\n";
$sections = array();
DisplayCompare($changes,$sections,$version,$version_compare);
//var_dump($changes,version,$theater,$version_compare,$theater_compare);

exit;
}


if ($_REQUEST['command'] == 'weaponlog') {
	DisplayLoggerConfig();
	exit;
}
if ($_REQUEST['command'] == 'wiki') {
	DisplayWikiView();
	exit;
}
if ($_REQUEST['command'] == 'hlstats') {
	DisplayHLStatsX();
	exit;
}
if ($_REQUEST['command'] == 'smtrans') {
	DisplaySMTranslation();
	exit;
}

//Load weapon items
$weapons = array();
foreach($theater["weapons"] as $wpnname => $data) {
	if (isset($data["IsBase"])) {
		continue;
	}
	$object = getobject("weapons",$wpnname,1);
	$weapons[$wpnname] = $object;
}
ksort($weapons);
$weapon = current($weapons);
if ($_REQUEST['weapon']) {
//var_dump($_REQUEST['weapon']);
	if (array_key_exists($_REQUEST['weapon'],$weapons)) {
		$weapon = $_REQUEST['weapon'];
	}
}
//var_dump($weapon);
//Load weapon_upgrade items
$weapon_upgrades = array();
$weapon_upgrade_slots = array();
foreach($theater["weapon_upgrades"] as $wpnname => $data) {
	if (isset($data["IsBase"])) {
		continue;
	}
	$object = getobject("weapon_upgrades",$wpnname,1);
	$weapon_upgrades[$wpnname] = $object;
	if ($object['upgrade_slot'])
		$weapon_upgrade_slots[$object['upgrade_slot']] = $object['upgrade_slot'];
}
//var_dump($weapon_upgrade_slots);
ksort($weapon_upgrades);
ksort($weapon_upgrade_slots);
//var_dump($weapon_upgrades);
$weapon_upgrade = current($weapon_upgrades);
if ($_REQUEST['weapon_upgrade']) {
	if (array_key_exists($_REQUEST['weapon_upgrade'],$weapon_upgrades)) {
		$weapon_upgrade = $_REQUEST['weapon_upgrade'];
	}
}

//Begin main program
//Process weapon upgrades first so we can connect them to the weapons
foreach ($theater["weapon_upgrades"] as $upname => $data) {
	if ((substr($upname,0,5) == "base_") || (substr($upname,-5,5) == "_base")) {
		continue;
	}
	$item = getobject("weapon_upgrades",$upname,1);
	if (isset($item["allowed_weapons"])) {
		$arr = (is_array(current($item["allowed_weapons"]))) ? current($item["allowed_weapons"]) : $item["allowed_weapons"];
		foreach ($arr as $wpn) {
			$upgrades[$wpn][$upname] = $item;
		}
	}
}
?>

<style type="text/css">
	.bodygraph {
		position: relative;
		height: 340px;
		width: 157px;
		background-image: url("images/body.png");
	}
	.vgui {
		background-size: 256px 128px;
		background-repeat: no-repeat;
		background-position: top 16px center;
		min-height: 144px;
		height: 144px;
		width: 256px;
		text-align: center;
		display: inline-block;
		white-space: normal;
		font-weight: bold;
		font-size: 1.2em;
		text-shadow: #000 0px 0px 1px, #000 -1px 0px 1px, #000 -1px 0px 1px, #000 0px 0px 1px;\
		-webkit-font-smoothing: antialiased;
	}
	table.floatThead-table {
		background-color: #FFFFFF;
	}
}
</style>
<script type="text/javascript" class="init">
$(document).ready(function() {
		$('table.display').dataTable({ saveState: true });
		$('table.display').floatThead({scrollingTop: 50});
} );
</script>
<?php
startbody();
echo "
		<form action='{$_SERVER['PHP_SELF']}' method='get'>
		<div style='margin: 5px;'>
			<h1>Insurgency Theater Parser</h1>
			<h2>Parsing {$theaterfile} from Version {$version}</h2>\n";
echo "				Version: <select name='version'>\n";
foreach ($versions as $vid) {
	$sel = ($vid == $version) ? ' SELECTED' : '';
	echo "					<option{$sel}>{$vid}</option>\n";
}
echo "				</select><br>
				Compare To Version (BETA raw data!): <select name='version_compare'>\n";
if ($version == $version_compare) {
	echo "<option SELECTED>-None-</option>\n";
}
foreach ($versions as $vid) {
	$sel = (($vid == $version_compare) && (!($version == $version_compare))) ? ' SELECTED' : '';
	echo "					<option{$sel}>{$vid}</option>\n";
}
echo "				</select><br>
				Theater: <select name='theater'>\n";
foreach ($theaters as $theatername) {
	$sel = (($theatername == $theaterfile) || ($theatername == $_REQUEST['theater'])) ? ' SELECTED' : '';
	echo "					<option{$sel}>{$theatername}</option>\n";
}
echo "				</select><br>\n";
//<input type='text' id='range' name='range' readonly style='border:0; color:#f6931f; font-weight:bold;'><div id='slider-range'></div>";
echo "				Range: <input type='text' value='".dist($range,'IN',null,0)."' name='range'> 
<select name='range_unit'>\n";
foreach ($range_units as $ru => $runame) {
	$sel = ($range_unit == $ru) ? ' SELECTED' : '';
	echo "					<option{$sel} value='{$ru}'>{$runame}</option>\n";
}
echo "				</select><br>\n";
echo "				<input type='submit' value='Parse'>
			</div>\n";
$stats = array(
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
//echo "go\n";
GenerateStatTable();
//var_dump($stats);
//echo "what\n";
DisplayStatTable();
//echo "done\n";
echo "		</form>";
require "include/footer.php";
exit;



function DisplayStatTable() {
	global $stats,$tableclasses;

	foreach (array_keys($stats) as $sectionname) {
		echo "<a href='#{$sectionname}'>{$sectionname}</a><br>\n";
	}
//class='{$tableclasses}' data-sort-name='{$sectionname}' id='{$sectionname}' data-sort-order='asc'>
	foreach ($stats as $sectionname => $sectiondata) {
		echo "		<a id='{$sectionname}'><h2>{$sectionname}</h2></a>
		<table class='display row-border' id='table-{$sectionname}' width='100%'>
			<thead>
				<tr>\n";
		if ($sectionname != 'Teams')
			echo "					<th>Cmp</th>\n";
		foreach ($sectiondata['fields'] as $fieldname => $show) {
			if (!$show)
				continue;
			echo "					<th>{$fieldname}</th>\n";
		}
		echo "			</tr>\n			 </thead>\n			 <tbody>\n";
		foreach ($sectiondata['items'] as $itemname => $itemdata) {
			if ($sectionname != 'Teams')
			{
				$sel = '';
				if (isset($_REQUEST["compare-{$sectionname}"]))
					$_REQUEST["compare_{$sectionname}"] = $_REQUEST["compare-{$sectionname}"];
				if (isset($_REQUEST["compare_{$sectionname}"]))
				{
					if (!in_array($itemname,$_REQUEST["compare_{$sectionname}"]))
						continue;
					else
						$sel = ' CHECKED';
				}
				echo "			<tr><td><input type='checkbox' name='compare_{$sectionname}[]' value='{$itemname}'{$sel}></td>\n";
			}
			foreach ($sectiondata['fields'] as $fieldname => $show) {
				if (!$show)
					continue;
				echo "				<td";
				$fd = $itemdata[$fieldname];
				if ($fieldname == 'Name') {
					if (isset($itemdata['Img'])) {
						echo $itemdata['Img'];
					} else {
						echo " class='vgui'";
					}
					$fd = "<a href='stats.php?showitem={$itemname}&amp;showtype={$sectionname}' id='{$itemname}' target='_blank'>{$itemname}<br>{$fd}</a>";
				}
				echo ">{$fd}</td>\n";
			}
			echo "			</tr>\n";
		}
		echo "</tbody></table>\n";
	}
}

function GenerateStatTable() {
	global $stats,$theater,$upgrades,$armors,$range;
	$armors = array('No Armor' => ($theater['player_settings']['damage']['DamageHitgroups']));
//var_dump($theater);
	foreach ($theater["player_gear"] as $gearname => $data) {
		$gear = getobject("player_gear",$gearname);
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
			$thisitem['DamageHitgroups'] = getbodygraph($gear,$gear["DamageHitgroups"],'',0,2);
		}
		$stats['Gear']['items'][$gearname] = $thisitem;
		if ($data["gear_slot"] == 'armor') {
			$armors[$thisitem['Name']] = $gear["DamageHitgroups"];
		}
	}
	foreach ($theater["weapons"] as $wpnname => $data) {
		if (isset($data["IsBase"])) {
			continue;
		}
		$thisitem = array();
		$item = getobject("weapons",$wpnname);
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
			$expammo = getobject("ammo",$item["ammo_clip"]["ammo_type"]);
			$thisitem['Carry'] = printval($expammo,"carry");
			$thisitem['Carry Max'] = printval($expammo,"carry");
		} elseif (isset($item["ammo_clip"])) {
			$ammo = getobject("ammo",$item["ammo_clip"]["ammo_type"]);
			$dmg = damageatrange($ammo['Damage'],$range);
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
		$stats['Weapons']['items'][$wpnname] = $thisitem;
	}
	foreach ($theater["weapon_upgrades"] as $upname => $data) {
		if ((substr($upname,0,5) == "base_") || (substr($upname,-5,5) == "_base")) {
			continue;
		}
		$upgrade = getobject("weapon_upgrades",$upname,1);
		$img = getvgui($upname,'css');
		$thisitem = array();
		if (isset($upgrade['attach_weapon'])) {
			if (isset($stats['Weapons']['items'][$upgrade['attach_weapon']])) {
				$stats['Weapons']['items'][$upgrade['attach_weapon']]['Img'] = $img;
			}
		}
		if (substr($upname,0,5) == "ammo_") {
			$link = "<br><a href='#{$upgrade['ammo_type_override']}'>{$upgrade['ammo_type_override']} [{$upgrade['upgrade_cost']}]</a>";
			$fn = 'Ammo';
		} else {
			$link = "<a href='#{$upname}'>".getlookup($upgrade['print_name'])." [{$upgrade['upgrade_cost']}]</a><br>";
			$fn = 'Upgrades';
		}
		//Add ammo and upgrade links to weapon items
		$weapons = $upgrade['allowed_weapons']['weapon'];
		if (!is_array($weapons))
			$weapons = array($weapons);
		foreach ($weapons as $wpnname) {
			$stats['Weapons']['items'][$wpnname][$fn].=$link;
		}
		$thisitem['Img'] = $img;
		$thisitem['Name'] = getlookup($upgrade['print_name']);
		$thisitem['Slot'] = printval($upgrade,"upgrade_slot");
		$thisitem['CR'] = printval($upgrade,"class_restricted");
		$thisitem['Cost'] = printval($upgrade,"upgrade_cost");
		$thisitem['Ammo Type'] = printval($upgrade,"ammo_type_override",1);
		$thisitem['Abilities'] = printval($upgrade,"weapon_abilities");
		$thisitem['Weapons'] = printval($upgrade,"allowed_weapons",1);
		$stats['Upgrades']['items'][$upname] = $thisitem;
	}
	foreach ($theater["ammo"] as $ammoname => $data) {
		//Hide rockets and grenades (so we can link to them in #explosives), and other items we don't want to see at all
		if ((substr($ammoname,0,7) == "rocket_") || (substr($ammoname,0,8) == "grenade_") || ($ammoname == 'default') || ($ammoname == 'no_carry')) {
			continue;
		}
		$ammo = getobject("ammo",$ammoname);
		if (!isset($ammo['carry']))
			continue;
		$thisitem = array();
		$thisitem['Name'] = "<a id='{$ammoname}'>{$ammoname}</a>";
		$thisitem['Carry'] = printval($ammo,"carry");
		$thisitem['Mag'] = printval($ammo,"magsize");
		$dmg = damageatrange($ammo['Damage'],$range);
		if ($ammo['bulletcount'] > 1) {
			$dmg=($dmg*$ammo['bulletcount'])." max ({$ammo['bulletcount']} pellets)";
		}
		$thisitem['Damage'] = $dmg;
		$thisitem['DamageGraph'] = printval($ammo,"Damage");
		$thisitem['PenetrationPower'] = damageatrange($ammo["PenetrationPower"],$range);
		$thisitem['PenetrationGraph'] = printval($ammo,"PenetrationPower");
		$thisitem['Tracer'] = "Type: {$ammo["tracer_type"]}<br>Frequency: {$ammo["tracer_frequency"]}<br>Low Ammo: {$ammo["tracer_lowammo"]}";
		$thisitem['Suppression'] = printval($ammo,"SuppressionIncrement");
		$thisitem['DamageHitgroups'] = getbodygraph($ammo,$ammo["DamageHitgroups"],array_keys($armors));
		$stats['Ammo']['items'][$ammoname] = $thisitem;
	}
	foreach ($theater["explosives"] as $explosivename => $data) {
		if (isset($data["IsBase"])) {
			continue;
		}
		$explosive = getobject("explosives",$explosivename);
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
		$stats['Explosives']['items'][$explosivename] = $thisitem;
	}
	foreach ($theater['player_templates'] as $classname => $classdata) {
		$thisitem = array();
		$thisitem['Name'] = getlookup($classdata['print_name']);
		$thisitem['Team'] = printval($classdata,"team");
		$thisitem['Models'] = printval($classdata,"models");
		foreach ($classdata["buy_order"] as $type => $items) {
			foreach ($items as $item) {
				$thisitem['Buy order'].= "<a href='#{$item}'>{$item}</a><br>";
			}
		}
		foreach ($classdata["allowed_items"] as $type => $items) {
			foreach ($items as $item) {
				$thisitem['Allowed Items'].= "<a href='#{$item}'>{$item}</a><br>";
			}
		}
		$stats['Classes']['items'][$classname] = $thisitem;
	}
	//Teams are goofy because of display method
//var_dump($theater['teams']);
	foreach ($theater['teams'] as $teamname=>$teamdata) {
		$thisitem = '<table>';
		$tn = getlookup($teamdata['name']);
		$stats['Teams']['fields'][$tn] = 1;
		if (isset($teamdata['logo'])) {
			$thisitem.="<div style='text-align: center;'><img src='data/materials/vgui/{$teamdata['logo']}.png' style='width: 64px; height: 64px;'></div>\n";
		}
		foreach ($teamdata['squads'] as $squad => $squaddata) {
			$sn = getlookup($squad);
			$thisitem.="<tr><td><h3>{$sn}</h3></td></tr><tr><td>\n";
			foreach ($squaddata as $position => $class) {
				if (!is_array($class)) {
					$class = array($class);
				}
				foreach ($class as $cn) {
					$classname = getlookup($position);
					$thisitem.="<a href='#{$cn}'>{$classname}<br>\n";
				}
			}
			$thisitem.="</td></tr>\n";
		}
		$thisitem.="</table>\n";
		$stats['Teams']['items'][0][$tn] = $thisitem;
	}
	//Do cleanup and make links between items here
	foreach (array_keys($stats) as $sectionname) {
		ksort($stats[$sectionname]['items']);
	}
}
//FUNCTIONS
function DisplayLoggerConfig() {
	echo "<pre>";
	echo "\"weapons\"\n{\n";
	foreach ($weapons as $weapon => $item) {
		printf("\t%-20s\t%s\n", "\"{$weapon}\"",'"'.getlookup($item["print_name"]).'"');
	}
	echo "}\n";
}
function DisplaySMTranslation() {
	echo "<pre>";
	global $lang,$langcode;
	$phrases = array();
//var_dump($langcode,$lang);
	foreach ($lang as $language => $tokens) {
//var_dump($language);
		$lc = strtolower($language);
		$lc = $langcode[$lc];
		if ($lc) {
			foreach ($tokens as $key => $val) {
//				$key = str_replace('#','',$key);
				$val = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $val);

				$phrases[$key][$lc] = $val;
			}
		}
	}
//jballou - commented out until I decide if we even need this
/*
	$reader = new KVReader();
	ksort($phrases);
	echo $reader->write(array('Phrases' => $phrases));
*/
//var_dump($phrases);
}
function DisplayWikiView() {
	$itemtype = $_REQUEST['itemtype'];
	$itemname = $_REQUEST['itemname'];
	$item = getobject($itemtype,$itemname);
	
}
function DisplayHLStatsX() {
	global $theater;
	echo "<pre>";
	$values = array();
	$tables = array(
		'Awards' => array(
			'allfields' => array('game', 'code', 'name', 'verb'),
			'fields'	=> array('name', 'verb')
		),
		'Ribbons' => array(
			'allfields' => array('game', 'awardCode', 'awardCount', 'special', 'image', 'ribbonName'),
			'fields'	=> array('image', 'ribbonName')
		),
		'Weapons' => array(
			'allfields' => array('game', 'code', 'name', 'modifier'),
			'fields'	=> array('name')
		),
		'Teams' => array(
			'allfields' => array('game', 'code', 'name'),
			//, 'hidden', 'playerlist_bgcolor', 'playerlist_color', 'playerlist_index'),
			'fields'	=> array('name')
		),
		'Roles' => array(
			'allfields' => array('game', 'code', 'name'),
			'fields'	=> array('name')
		)
	);
	foreach ($theater['weapons'] as $weapon => $item) {
		if (isset($item["IsBase"])) {
			continue;
		}
		$wpnname = getlookup($item["print_name"]);
		$wn = str_replace('weapon_','',$weapon);
		$values['Awards'][] = "('insurgency', '{$weapon}', '{$wpnname}', 'Kills with {$wpnname}')";
		$values['Ribbons'][] = "('insurgency', '{$weapon}', '10', '0', 'rg_{$wn}.png', 'Gold {$wpnname}')";
		$values['Ribbons'][] = "('insurgency', '{$weapon}', '5', '0', 'rs_{$wn}.png', 'Silver {$wpnname}')";
		$values['Weapons'][] = "('insurgency', '{$weapon}', '{$wpnname}', '1.00')";
	}

	foreach ($theater['player_templates'] as $class => $classdata) {
		if (isset($classdata["IsBase"])) {
			continue;
		}
		$classname = getlookup($classdata['print_name']);
		//$values['Roles'][] = "('insurgency','{$classdata['print_name']}','{$classname}')";
		$shortclass = str_replace(array('template_','_insurgent','_security','_training','_elimination'),'',$class);
		$values['Roles'][$shortclass] = "('insurgency','{$shortclass}','{$classname}')";
	}
	foreach ($theater['teams'] as $team=>$teamdata) {
		$teamname = getlookup($teamdata['name']);
		$values['Teams'][] = "('insurgency','{$teamdata['name']}','{$teamname}')";
		//$values['Teams'][] = "('insurgency','{$team}','{$teamname}')";
	}
	$dbprefix = $_REQUEST['dbprefix'] ? $_REQUEST['dbprefix'] : 'hlstats';
	foreach ($tables as $table => $tdata) {
		echo "--\n-- Update {$dbprefix}_{$table}\n--\n\n";
		$fields = array();
		foreach ($tdata['fields'] as $field) {
			$fields[] = "{$field} = VALUES({$field})";
		}
		asort($values[$table]);
		echo "INSERT INTO `{$dbprefix}_{$table}`\n	(`".implode('`, `',$tdata['allfields'])."`)\n	 VALUES\n		 ".implode(",\n		 ",$values[$table])."\n	 ON DUPLICATE KEY UPDATE ".implode(', ',$fields).";\n";
	}
}
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
function DisplayCompare($changes,$sections,$version,$version_compare) {
	foreach ($changes as $name => $data) {
		$sections[] = $name;
		if (isset($data[$version]) || isset($data[$version_compare])) {
			echo "<tr><td>".implode("/",$sections)."</td>";
			echo "<td>".printval($data,$version,0,'-')."</td>\n";;
			echo "<td>".printval($data,$version_compare,0,'-')."</td>\n";;
			echo "</tr>\n";
		} else {
			DisplayCompare($data,$sections,$version,$version_compare);
		}
		array_pop($sections);
	}
}
function damageatrange($dmg,$range,$dec=2) {
	//0.0254
	ksort($dmg);
//var_dump($dmg);
	foreach ($dmg as $dist => $dmg) {
		if ($dist >= $range) {
			if (!isset($mindist)) {
				return $dmg;
			}
			$diffdist = ($dist - $mindist);
			$diffdmg = ($mindmg - $dmg);
			$diffrange = ($range - $mindist);
			$dmgpct = $diffrange/$diffdist;
//var_dump($diffdist,$diffdmg,$diffrange,$dmgpct);
			return round(($mindmg - ($dmgpct * $diffdmg)),$dec);
		}
		else {
			$mindmg = $dmg;
			$mindist = $dist;
		}
	}
}
//echo damageatrange(array("800" => "36", "2000" => "18", "7000" => "5"),1400);
//exit;
/*
getgraph
*/
function getgraph($points,$xname='Damage',$yname='Distance',$type='line',$height=100,$width=200) {
	ksort($points);
	if (count($points) > 2) {
		$midpoints = array_slice($points,1,-1,true);
	}
	$maxdmg = max(array_values($points));
	$maxdist = max(array_keys($points));
	$mindmg = min(array_values($points));
	$mindist = min(array_keys($points));

	if ($xname == 'Damage') {
		$gmaxdmg = 300;
		$gmaxdist = 20000;
		$gmindmg = 0;
		$gmindist = 10;
	} elseif ($xname == 'PenetrationPower') {
		$gmaxdmg = 1500;
		$gmaxdist = 20000;
		$gmindmg = 0;
		$gmindist = 10;
	} else {
		$gmaxdmg = $maxdmg;
		$gmaxdist = $maxdist;
		$gmindmg = $mindmg;
		$gmindist = $mindist;
	}
	$margin = 64;
	$half = ($margin / 2);
	$hm = ($height / ($gmaxdmg - $gmindmg));
	$wm = ($width / ($gmaxdist - $gmindist));
	$pts = array();
	if ($maxdist < $gmaxdist)
		$points[$gmaxdist] = $points[$maxdist];
	foreach ($points as $dist => $dmg) {
		$pts[] = round((($dist - $gmindist) * $wm)+$margin).",".round($height - (($dmg - $gmindmg) * $hm));
	}
	$dispdistmin = ($xname == 'Speed') ? $gmindist : dist($gmindist,'IN');
	$dispdistmax = ($xname == 'Speed') ? $gmaxdist : dist($gmaxdist,'IN');
	$dispdmgmin = ($xname == 'Speed') ? dist($gmindmg,'IN')."/s" : $gmindmg;
	$dispdmgmax = ($xname == 'Speed') ? dist($gmaxdmg,'IN')."/s" : $gmaxdmg;
	$pts = "M".implode(' ',$pts);
	$svg = "							<svg xmlns='http://www.w3.org/2000/svg' height='".($height+$half)."' width='".($width+$margin+2)."' style='fill: red;'>
								<defs>
									<marker id='circle' markerWidth='7' markerHeight='7' refx='4' refy='4'><rect x='1' y='1' width='5' height='5' style='stroke: none; fill:#000000;' /></marker>
								</defs>
								<rect x='{$margin}' height='{$height}' width='{$width}' y='2' style='fill:rgb(255,255,200);stroke-width:2;stroke:rgb(0,0,0)' />
								<path d='{$pts}' style='fill:none; stroke:blue; stroke-width:1; marker-start: url(#circle); marker-mid: url(#circle); marker-end: url(#circle);'/>
								<g style='font-size: 12; font-face: sans-serif; fill: #000000; stroke: none;'>
									<text font-size='16' x='16' y='50%' text-anchor='middle' style='font-weight: bold; writing-mode: tb; glyph-orientation-vertical: 90;'>{$xname}</text>
									<text font-size='16' x='50%' y='{$height}' dy='{$half}' dx='{$half}' text-anchor='middle' style='font-weight: bold;'>{$yname}</text>
									<text x='{$margin}' dx='-2' y='12' text-anchor='end'>{$dispdmgmax}</text>
									<text x='{$margin}' dx='-2' y='{$height}' dy='-2' text-anchor='end'>{$dispdmgmin}</text>
									<text x='{$margin}' y='{$height}' dy='16' text-anchor='start'>{$dispdistmin}</text>
									<text x='".($width+$margin)."' y='116' text-anchor='end'>{$dispdistmax}</text>
								</g>
								<g font-size='12' font='sans-serif' fill='black' stroke='none'	text-anchor='left'>\n";
/*
	foreach ($midpoints as $dist => $dmg) {
		$x=round((($dist - $gmindist) * $wm)+$margin) + 4;
		$y=round($height - (($dmg - $gmindmg) * $hm)) - 4;
//var_dump($x,$y);
		$ta = ($x < $width) ? 'start' : 'end';
		if ($xname == 'Speed') {
			$cap = "{$dist}:".dist($dmg,'IN')."/s";
		} else {
			$cap = dist($dist,'IN',null,0).":{$dmg}";
		}
		$svg.= "									<text x='{$x}' y='{$y}' text-anchor='{$ta}'>{$cap}</text>\n";
	}
*/
	$svg.= "								</g>
							</svg>\n";
	return $svg;
}
/* getcirclegraph
Show a graph for DamageRadius stuff
*/
function getcirclegraph($object,$size=100,$maxdamage=1200,$margin = 0) {
	$half = $margin / 2;
	$dm = ($maxdamage / $size);
//var_dump($dm);
	$c = ($size+$margin)/2;
	$step = ($object["DetonateDamage"] / $object["DetonateDamageRadius"]);
	$dmgradr = (($object["DetonateDamageRadius"] / $maxdamage) * $size)/2;
	$penradr = (($object["DetonatePenetrationRadius"] / $maxdamage) * $size)/2;
	$svg = "							<svg xmlns='http://www.w3.org/2000/svg' height='".($size+$margin)."' width='".($size+$margin)."'>
								<rect x='{$half}' y='{$half}' height='{$size}' width='{$size}' style='fill:rgb(255,255,200);stroke-width:3;stroke:rgb(0,0,0)' />
								<circle cx='{$c}' cy='{$c}' r='{$dmgradr}' stroke='black' stroke-width='3' fill='yellow' />
								<circle cx='{$c}' cy='{$c}' r='{$penradr}' stroke='black' stroke-width='3' fill='red' />
							</svg>\n";
	return $svg;
}
/* getspreadgraph
Show a graph for spread
*/
function getspreadgraph($vector,$size=100,$maxspread=2,$margin = 0) {
	$coords = explode(' ',$vector);
	$half = $margin / 2;
	$dm = ($maxspread / $size);
	$c = ($size+$margin)/2;
	$radr = (($coords[0] / $maxspread) * $size)/2;
//var_dump($dm,$c,$maxspread,$coords,$radr);
	$svg = "							<svg xmlns='http://www.w3.org/2000/svg' height='".($size+$margin)."' width='".($size+$margin)."'>
								<defs>
									<pattern id='grid' width='9' height='9' patternUnits='userSpaceOnUse'>
										<path d='M 9 0 L 0 0 0 9' fill='none' stroke='gray' stroke-width='1'/>
									</pattern>
								</defs>
								<rect x='{$half}' y='{$half}' height='{$size}' width='{$size}' fill='url(#grid)' />
								<circle cx='{$c}' cy='{$c}' r='{$radr}' stroke='black' stroke-width='3' fill='red' />
								<circle cx='{$c}' cy='{$c}' r='1' stroke='black' stroke-width='1' fill='black' />
							</svg>\n";
	return $svg;
}
/* getrecoilgraph
Show a graph for recoil
*/
function getrecoilgraph($recoil,$size=100,$maxspread=10,$margin = 0) {
//"recoil_lateral_range"													"-1.0 1.25"
//"recoil_vertical_range"													"3.65 3.75"
//"recoil_aim_punch"//			"0.7 0.75"
//"recoil_rest_rate"																			"9"
//"recoil_rest_delay"//			"0.12"
//"recoil_roll_range"//			"-1.35 -1.35"
//"recoil_roll_rest_rate"													"150"
//"recoil_shot_reset_time"												"0.75"	// Time delay for resetting the shots fired counter for the above (default = 0.3)
//"recoil_punch_additive_factor"					"0.9"		// How much of the view punch from the previous shot(s) get added to any additional shot fired (default 1.0)
//"recoil_additional_rest_per_shot"				"5"
//"recoil_freeaim_frac"														"0.5"
//"recoil_ironsight_frac"													"0.8"

	$lateral = explode(' ',$recoil['recoil_lateral_range']);
	$vertical = explode(' ',$recoil['recoil_vertical_range']);
	asort($lateral);
	asort($vertical);
	return("<table>
<tr>
<td>&nbsp;</td>
<td>{$vertical[1]}</td>
<td>&nbsp;</td>
</tr>
<tr>
<td>{$lateral[0]}</td>
<td>&nbsp;</td>
<td>{$lateral[1]}</td>
</tr>
<tr>
<td>&nbsp;</td>
<td>{$vertical[0]}</td>
<td>&nbsp;</td>
</tr></table>\n");
	$c = ($size+$margin)/2;
	$step = ($size / $maxspread);
	$half = $maxspread / 2;

	$rwidth = (abs($lateral[1] - $lateral[0]) * $step);
	$rheight = (abs($vertical[1] - $vertical[0]) * $step);
	$rx = ($half - $lateral[0]) * $step;
	$ry = ($half - $vertical[0]) * $step;
//var_dump($c,$maxspread,$lateral,$vertical,$step,$half,$rwidth,$rheight,$rx,$ry);
//exit;
//								<rect x='{$half}' y='{$half}' height='{$size}' width='{$size}' style='fill:rgb(255,255,200);stroke-width:3;stroke:rgb(0,0,0)' />

	$svg = "							<svg xmlns='http://www.w3.org/2000/svg' height='".($size+$margin)."' width='".($size+$margin)."'>
<defs>
		<pattern id='grid' width='9' height='9' patternUnits='userSpaceOnUse'>
			<path d='M 9 0 L 0 0 0 9' fill='none' stroke='gray' stroke-width='1'/>
		</pattern>

	</defs>
	<rect width='100%' height='100%' fill='url(#grid)' />
								<rect x='{$rx}' y='{$ry}' height='{$rheight}' width='{$rwidth}' style='fill:rgb(255,0,0);stroke-width:1;stroke:rgb(0,0,0)' />
								<rect x='{$px}' y='{$py}' height='{$pheight}' width='{$pwidth}' style='fill:rgb(255,0,255);stroke-width:1;stroke:rgb(0,0,0)' />\n";
	$svg.="								 <line </svg>\n";
	$svg.="							 </svg>\n";
	return $svg;
}
function getbodygraph($object,$hitgroups,$disparmor='',$headers=1,$dec=0) {

	$positions = array('HITGROUP_HEAD' => '78,22','HITGROUP_CHEST' => '78,86','HITGROUP_STOMACH' => '78,136','HITGROUP_LEFTARM' => '122,122','HITGROUP_RIGHTARM' => '32,122','HITGROUP_LEFTLEG' => '56,222','HITGROUP_RIGHTLEG' => '94,222');
//var_dump($object);
	global $armors,$range;
	$graph = '';
	$header = '';
//var_dump($hitgroups);
	if (!is_array($disparmor)) {
		$disparmor = array($disparmor);
	}
	foreach ($disparmor as $armor) {
		$graph.="<td><div class='bodygraph'>";
		$armordata = array();
		foreach ($positions as $key => $val) {
			$armordata[$key] = isset($armors[$armor][$key]) ? round($armors[$armor][$key],2) : 1;
		}
		$dist = dist($GLOBALS['range'],'IN');
		$header.="<th>{$armor} @ {$dist}</th>";
		foreach ($armordata as $key => $val) {
			$eq='';
			if (isset($object["Damage"])) {
				$basedmg = damageatrange($object['Damage'],$range);
				$result = ($basedmg * $armors['No Armor'][$key]);
				$eq.= "base damage: {$basedmg}\nbase hitgroup: * {$armors['No Armor'][$key]}\n";
				if ($hitgroups[$key]) {
					$result*=$hitgroups[$key];
					$eq.= "ammo hitgroup: * {$hitgroups[$key]}\n";
				}
				if ($armor != 'No Armor') {
					$eq.= "armor hitgroup: * {$val}\n";
					$result*=$val;
				}
				if ($object['bulletcount'] > 1) {
					$eq.= "bullet count: * {$object['bulletcount']}\n";
					$result*=$object['bulletcount'];
				}
				$eq.="total: {$result}\n";
			} else {
				$result = ($val * $armordata[$key] * $hitgroups[$key]);
			}
			$result = round($result,$dec);
			$coords = explode(',',$positions[$key]);
			$graph.="<div title='{$eq}' style='position: absolute; left: {$coords[0]}px; top: {$coords[1]}px; width: 100px; text-align: center; transform: translate(-50%, -50%);'>{$result}</div>";
		}
		$graph.="</div></td>";
	}
	if ($headers) {
		$retval = "<table><tr>{$header}</tr><tr>{$graph}</tr></table>";
	} else {
		$retval = "<table><tr>{$graph}</tr></table>";
	}
	return $retval;
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
/* getobject
Take a type (weapon, ammo, explosive, etc), key (name of item), and boolean for recursing
*/
function getobject($type,$key,$recurse=0) {
	global $theater;
	//Get object from theater
	$object = $theater[$type][$key];
	if (isset($object['IsBase'])) {
		if ($object['IsBase'] > 0) {
			$isbase = 1;
		}
	}
	//Merge in imports
	if (isset($object["import"])) {
		//Merge using replacement of like items, which will not merge sub-array values like damagehitgroups or ammo_clip if the object also defines the section. This appears to be the way the game processes these sections.
		$object = array_replace(getobject($type,$object["import"],$recurse),$object);
		//If the main object was not IsBase, then remove the entry if it is in the output. Isbase must be set explicitly per-item in theater.
		if ((!isset($isbase)) && (isset($object['IsBase']))) {
			unset($object['IsBase']);
		}
	}
	return $object;
}
/* printarray
Display the damage of bullets, this is used to show damage at distances
*/
function printarray($object,$index,$prefix='') {
	$data = '';
	if ($prefix != '') {
		$prefix.='->';
	}
	global $range;
	$graph = (in_array($index,array('Damage','PenetrationPower','PhysicsImpulse','PenetrationDamage'))) ? 1 : 0;
	if ($graph) {
		if (!isset($object[$index][$range])) {
			$object[$index][$range] = damageatrange($object[$index],$range);
		}
		ksort($object[$index]);
	}
	$arr = array();
	$dmg = array();
	foreach ($object[$index] as $rangedist => $rangedmg) {
		if (is_array($rangedmg)) {
			$arr[] = printarray($object[$index],$rangedist,$prefix.$rangedist);
		} else {
			$disprange = ($graph) ? dist($rangedist,'IN') : $rangedist;
			$dmg[$rangedist] = $rangedmg;
			if ($index == 'Damage' && ($object['bulletcount'] > 1)) {
				$totaldmg = $rangedmg * $object['bulletcount'];
				$dmg[$rangedist] = $totaldmg;
				$arr[] = "{$prefix}{$disprange}: {$totaldmg}";
			} else {
				if (($index == 'DamageHitgroups') && ($object['Damage'])) {
					$basedmg = array_shift(array_values($object['Damage']));
					if ($object['bulletcount'] > 1) {
						$basedmg = $basedmg * $object['bulletcount'];
					}
					$arr[] = "{$prefix}{$disprange}: {$rangedmg} (".($rangedmg * $basedmg)." dmg)";
				} else {
					$arr[] = "{$prefix}{$disprange}: {$rangedmg}";
				}
			}
		}
	}
	if ($graph) {
		$data = getgraph($dmg,$index)."<br>\n";
	}
	return $data.implode('<br>',$arr);
}
/*
getlookup
Returns a string localized
*/
function getlookup($key) {
	global $language, $lang;
	if (is_array($key))
		$key = end($key);
	if (substr($key,0,1) == "#") {
		$key = strtolower($key);
		return $lang[$language][$key];
	}
	return $key;
}
/*
printval
Displays a <td> item of one cell of information
$object - (array of theater section, like $ammo, $weapons),
$index - Name of the item
$open - Open the td
$close - Close the td
$link - Create a link to an anchor of the name $index
*/
function printval($object,$index,$link=0,$nulldisp='&nbsp;') {
	$data = '';
	if (isset($object[$index])) {
		if (is_array($object[$index])) {
			$data.=printarray($object,$index);
		} else {
			$data.=getlookup($object[$index]);
		}
	} else {
		$data.=$nulldisp;
	}
	if ($link) {
		$data = "<a href='#{$object[$index]}'>{$data}</a>";
	}
	return $data;
}
/*
dist
Returns the value sent in the correct units
$dist - The distance
$fromunits - Units the distance provided is in, defaults to IN (Game Units)
$tounits - Units to convert to, defaults to global $range_units
$suffix - Append space and tounits at the end
*/
function dist($dist,$fromunits='U',$tounits=null,$suffix=1) {
	if ((!array_key_exists($tounits,$GLOBALS['range_units'])) || (is_null($tounits))) {
		$tounits = $GLOBALS['range_unit'];
	}
	//If no conversion just return
	//var_dump($dist,$fromunits,$tounits);
	if ($fromunits != $tounits) {
		$conv = array(
			'U' => 1,
			'IN' => 0.75,
			'M' => 0.01905, //12 0.0254,
			'FT' => (1/16),
			'YD' => (1/48)
		);
		//Convert to units first
		if ($fromunits != 'U') {
			$dist = round($dist/$conv[$fromunits]);
		}
		$dist = round($dist*$conv[$tounits],2);
	}
	if ($suffix) {
		$dist.=strtolower($tounits);
	}
	return $dist;
}
