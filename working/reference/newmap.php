<?php
include "include/functions.php";
include "vdf.php";
include "vdfparser.php";
$file = "data/maps/src/buhriz_coop_d.vmf";
$data = file_get_contents($file);
//$data = preg_replace('/^(\s*)([a-zA-Z]+)/m','${1}"${2}"',$data);
$out = parseKeyValues($data);
//VDFParse($file);
//vdf_decode($data);
var_dump($out);
