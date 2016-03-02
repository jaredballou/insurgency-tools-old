<?php
/*
This takes the CVAR list CSV files from data and displays them in a simple
tabular format.
*/
//Root Path Discovery
$use_ob=1;
do { $rd = (isset($rd)) ? dirname($rd) : realpath(dirname(__FILE__)); $tp="{$rd}/rootpath.php"; if (file_exists($tp)) { require_once($tp); break; }} while ($rd != '/');
if (isset($_REQUEST['fetch'])) {
	require_once("{$includepath}/functions.php");
} else {
	require_once("{$includepath}/header.php");
}
$dirs = glob("${datapath}/cvarlist/*");
foreach ($dirs as $dir) {
	if (!is_dir($dir)) {
		continue;
	}
	$ver = basename($dir);
	$files = glob("{$dir}/*.csv");
	foreach ($files as $file) {
		$fn = basename($file,".csv");
		$lists[$ver][$fn] = $fn;
	}
}
//asort($lists);
$version = end(array_keys($lists));
if ($_REQUEST['version']) {
	if (in_array($_REQUEST['version'],array_keys($lists))) {
		$version = $_REQUEST['version'];
	}
}
$listtype = end($lists[$version]);
if ($_REQUEST['listtype']) {
	if (in_array($_REQUEST['listtype'],array_keys($lists[$version]))) {
		$listtype = $_REQUEST['listtype'];
	}
}
//var_dump($lists,$version,$listtype);
if ((!$version) || (!$listtype)) {
	echo "Data not found";
	include "{$includepath}/footer.php";
	exit;
}
$listfile = "${datapath}/cvarlist/{$version}/{$listtype}.csv";
$settingfile = "${datapath}/cvarlist/{$version}/{$listtype}.txt";

$fields = array();
$data = array();
$dfields = array();
$settings = array();
//Load settings if available
if (file_exists($settingfile)) {
	$lines = file($settingfile);
	foreach ($lines as $line) {
		$bits = explode(" ",trim($line),2);
		$settings[$bits[0]] = $bits[1];
	}
}

//Load CVAR list
$f = fopen($listfile, "r");
//First line contains field names
$line = fgetcsv($f);
foreach ($line as $cell) {
	$fields[] = $cell;
}
//var_dump($fields);
//Load remainder of the file into fields
while (($line = fgetcsv($f)) !== false) {
	$row = array();
	$idx=0;
	foreach ($line as $cell) {
		if (($idx < 2) || ($idx == 5) || ($idx == 21)) {
			if ($cell)
				$dfields[$idx] = $fields[$idx];
			$row[$fields[$idx]] = trim($cell);
		}
		$idx++;
	}
	if (isset($settings[$row['Name']])) {
		if ($row['Value'] != $settings[$row['Name']]) {
			$row['Value'] = $settings[$row['Name']];
			//$row['Value'].=" ({$settings[$row['Name']]})";
			//var_dump($row['Name'].": ".$row['Value']);
		}
	}
	$data[] = $row;
}
if ($_REQUEST['fetch'] == 'list') {
	header('Content-Type: application/json');
	echo json_encode($data, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
	exit;
}
//Collect Headers
$header="";
foreach ($dfields as $idx => $cell) {
	$header.="<th>{$cell}</th>";
}

//Start display
startbody();
echo "<h2>Version {$version} - {$listtype}</h2>\n";
echo "<form><select name='version'>";
foreach (array_keys($lists) as $ver) {
	$sel = ($ver == $version) ? ' SELECTED' : '';
	echo "				<option{$sel}>{$ver}</option>\n";
}
echo "</select><select name='listtype'>";
foreach ($lists[$version] as $list) {
	$sel = ($list == $listtype) ? ' SELECTED' : '';
	echo "				<option{$sel}>{$list}</option>\n";
}
echo "</select><input type='submit' name='command' value='Load'><br><input type='submit' name='command' value='Dump Config'></form>\n";
echo "CSV lists created by running 'cvarlist log cvartlist.csv' in client console<br>\n";
if ($_REQUEST['command'] == 'Dump Config') {
	echo "<textarea cols='80' rows='40'>\n";
	foreach ($data as $row) {
		$prefix = '';
		if ($row['Value'] == 'cmd')
			continue;
		if ($row['Help Text'] || $row['CHEAT']) {
			$help = " //{$row['Help Text']}";
			if ($row['CHEAT']) {
				$prefix = 'sm_cvar ';
				$help.=" CHEAT";
			}
		}
		echo "{$prefix}{$row['Name']} \"{$row['Value']}\"{$help}\n";
	}
	echo "</textarea>\n";

} else {
echo "<table class='display' id='cvarlist'>\n";
echo "<thead><tr>{$header}</tr>\n</thead>\n<tbody>\n";
foreach ($data as $row) {
	echo "<tr>";
	foreach ($dfields as $idx => $field) {
		if ($field == 'Name')
			$anchor = "<a name='".htmlspecialchars($row[$field])."'>";
		else
			$anchor = "";
		echo "<td>{$anchor}".htmlspecialchars($row[$field])."</td>";
	}
	echo "</tr>\n";
}
echo "</tbody></table>\n";
?>

<script type='text/javascript'>
$(document).ready(function() {
		$('#cvarlist').dataTable();
//		$('#cvarlist').column([ 3,4,5,6,7,8,9,10,11,12,13,14,15,16,17 ]).visible(0);
} );
</script>
<?php
}
fclose($f);
require_once("{$includepath}/footer.php");
exit;
?>
