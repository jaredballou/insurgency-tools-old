<?php
/*
This reads a bunch of in-game tips from a YAML file and dumps an
Advertisements file that SourceMod can use to display in-game.
*/
require_once "../include/class.Spyc.php";
require_once "../include/kvreader.php";
$tips = Spyc::YAMLLoad("data/thirdparty/tips.yaml");
$out = "\"Advertisements\"\n{\n";
$items = array();
foreach ($tips as $section => $lines) {
	foreach ($lines as $index => $line) {
		$out.="\t\"{$section}{$index}\"\n\t{\n\t\t\"type\"\t\t\"S\"\n\t\t\"text\"\t\t\"{$line}\"\n\t}\n";
	}
}
$out.="}\n";
echo $out;
