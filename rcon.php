<?php
include "include/functions.php";
include_once("include/rcon.class.php");

$r = array();
foreach ($servers as $server => $sdata) {
	var_dump($server,$sdata);
	$r = new rcon($server,'',$sdata['rcon_password']);
	echo "Checking {$server}\n";
	$r->Auth();
	var_dump($r->rconCommand("mapcyclefile"));
}

?>
