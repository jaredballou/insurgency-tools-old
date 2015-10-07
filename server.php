<?php
//$appid = 237410;
$datadir = "cache/steam";

//error_reporting(E_ALL);
error_reporting(E_ERROR);
//ALL & ~(E_STRICT|E_NOTICE));
include "include/functions.php";
include "utilities/thirdparty/steam-condenser-php/vendor/autoload.php";

$version_max = 0;
foreach ($servers as $address => $data) {
	$port = (isset($data['port'])) ? $data['port'] : '';
	$rcon_password = (isset($data['rcon_password'])) ? $data['rcon_password'] : '';
	$server = new \SteamCondenser\Servers\SourceServer($address,$port);
	$server->initialize();
	$getPing = $server->getPing();
	$getPlayers = $server->getPlayers($rcon_password);
	$getRules = $server->getRules();
	$getServerInfo = $server->getServerInfo();
	$version = $getServerInfo["gameVersion"];
	$version_num = preg_replace("/[^0-9]/", "",$version);
	$version_path = "{$datadir}/{$version_num}";
	$version_schema = "{$version_path}/schema.json";
	$playercount = $getServerInfo["numberOfPlayers"];
	$maxplayers = $getServerInfo["maxPlayers"];
	if ($version_num == $version_max) {
		$version_check = $version_max_check;
	} else if ($version_num > $version_max) {
		$version_max = $version_num;
		$url = "http://api.steampowered.com/ISteamApps/UpToDateCheck/v0001?appid={$appid}&version={$version_num}";
		$raw = json_decode(file_get_contents($url),true);
		$version_max_check = $version_check = $raw['response']['up_to_date'] ? 'OK' : 'FAIL';
//var_dump($url,$version_max_check,$version_check);
//exit;
		//Check if this app is up to date
		if (!file_exists($version_path)) {
			mkdir($version_path);
		}
		if ($version_check == 'OK') {
			if (!file_exists($version_schema)) {
				$schema = file_get_contents("http://api.steampowered.com/ISteamUserStats/GetSchemaForGame/v2/?key={$apikey}&appid={$appid}");
				file_put_contents($version_schema,$schema);
			}
		}
	} else {
		$version_check = 'FAIL';
	}
	echo "{$address}: \"{$getServerInfo["serverName"]}\": ({$playercount}/{$maxplayers}): {$version}: {$version_check}\n";
//var_dump($getServerInfo);

//$getRules,$getPing,$getPlayers);
}

exit;
