<?php
require "include/header.php";
$dirs = glob("data/cvarlist/*");
foreach ($dirs as $dir) {
	if (!is_dir($dir)) {
		continue;
	}
	$ver = basename($dir);
	$files = glob("{$dir}/*.csv");
	foreach ($files as $file) {
		$fn = basename($file,".csv");
		$versions[$ver][$fn] = $fn;
	}
}
//asort($versions);

$version = end(array_keys($versions));
if ($_REQUEST['version']) {
	if (in_array($_REQUEST['version'],array_keys($versions))) {
		$version = $_REQUEST['version'];
	}
}

$listtype = end($versions[$version]);
if ($_REQUEST['listtype']) {
	if (in_array($_REQUEST['listtype'],array_keys($versions[$version]))) {
		$listtype = $_REQUEST['listtype'];
	}
}
if ((!$version) || (!$listtype)) {
	echo "Data not found";
	include "include/footer.php";
	exit;
}
$listfile = "data/cvarlist/{$version}/{$listtype}.csv";
$settingfile = "data/cvarlist/{$version}/{$listtype}.txt";

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
//Collect Headers
$header="";
foreach ($dfields as $idx => $cell) {
	$header.="<th>{$cell}</th>";
}

//Start display
startbody();
echo "<h2>Version {$version} - {$listtype}</h2>\n";
echo "<form><select name='version'>";
foreach ($versions as $ver => $lists) {
	$sel = ($ver == $version) ? ' SELECTED' : '';
	echo "				<option{$sel}>{$ver}</option>\n";
}
echo "</select><select name='listtype'>";
foreach ($versions[$version] as $list) {
	$sel = ($list == $listtype) ? ' SELECTED' : '';
	echo "				<option{$sel}>{$list}</option>\n";
}
echo "</select><input type='submit' value='Load'></form>\n";
echo "CSV lists created by running 'cvarlist log cvartlist.csv' in client console<br>\n";
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
fclose($f);
echo "</tbody></table>\n";
?>

<script type='text/javascript'>
$(document).ready(function() {
		$('#cvarlist').dataTable();
//		$('#cvarlist').column([ 3,4,5,6,7,8,9,10,11,12,13,14,15,16,17 ]).visible(0);
} );
</script>
<?php require "include/footer.php"; exit;?>
