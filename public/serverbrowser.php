<?php
//================================================================================
// Server Browser
// (c) 2016 Jared Ballou <insurgency@jballou.com>
// 
// This is a web-based server browser for Insurgency that (hopefully) doesn't 
// suck as much as the in-game one does.
//================================================================================


//error_reporting(E_ALL);
//error_reporting(E_ERROR);
//error_reporting(E_ALL & ~(E_STRICT|E_NOTICE));

//Root Path Discovery
do { $rd = (isset($rd)) ? dirname($rd) : realpath(dirname(__FILE__)); $tp="{$rd}/rootpath.php"; if (file_exists($tp)) { require_once($tp); break; }} while ($rd != '/');

$title = "Insurgency Server Browser";
$tableclasses = "table table-striped table-bordered table-condensed table-responsive";


$css_content = '
	table.floatThead-table {
		background-color: #FFFFFF;
	}
';

if (isset($_REQUEST['fetch'])) {
	include "${rootpath}/include/functions.php";
} else {
	include "${rootpath}/include/header.php";
}

include "${rootpath}/thirdparty/steam-condenser-php/vendor/autoload.php";
include "${rootpath}/thirdparty/geoip2/vendor/autoload.php";
use GeoIp2\Database\Reader;
$reader = new Reader("{$rootpath}/thirdparty/geoip2/GeoLite2-City.mmdb");

$masters = array(
	'GOLD_SRC'	=> 'hl1master.steampowered.com:27010',
	'SOURCE'	=> 'hl2master.steampowered.com:27011',
);

$regions = array(
	'0x00'	=> 'US East Coast',
	'0x01'	=> 'US West Coast',
	'0x02'	=> 'South America',
	'0x03'	=> 'Europe',
	'0x04'	=> 'Asia',
	'0x05'	=> 'Austrailia',
	'0x06'	=> 'Middle East',
	'0x07'	=> 'Africa',
	'0xFF'	=> 'Other',
);

// Paths for where to store the data
$paths = array();
$paths['appdata'] = "${cachepath}/steam/{$appid}";
$paths['servers'] = "{$paths['appdata']}/servers";
$paths['versions'] = "{$paths['appdata']}/versions";

$region = '0x00';
$filters = '\gamedir\insurgency';
$list_file_maxage = 60;
$server_file_maxage = 600;

$tags_file = "{$paths['servers']}/tags.json";
$list_file = "{$paths['servers']}/list.json";

foreach ($paths as $name => $path) {
	//Check if this app is up to date
	if (!file_exists($path)) {
		mkdir($path,0755,true);
	}
}

if (isset($_REQUEST['fetch'])) {
	switch ($_REQUEST['fetch']) {
		case 'regions':
			$data = $regions;
			break;
		case 'masters':
			$data = $masters;
			break;
		case 'list':
			$data = GetServerList();
			break;
		case 'server':
			$region = filter_var($_REQUEST['region'], FILTER_SANITIZE_URL);
			$ip = filter_var($_REQUEST['ip'], FILTER_SANITIZE_URL);
			$port = filter_var($_REQUEST['port'], FILTER_SANITIZE_URL);
			$data = GetServer($region,$ip,$port,0,1);
			break;
	}
	if (isset($data)) {
		header('Content-Type: application/json');
		echo json_encode($data, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
	}
	exit;
}


// Get required version
$url = "http://api.steampowered.com/ISteamApps/UpToDateCheck/v0001?appid={$appid}&version=0";
$raw = json_decode(file_get_contents($url),true);
if (isset($raw['response']['required_version'])) {
	$required_version = $raw['response']['required_version'];
	$version_schema = "{$paths['versions']}/{$required_version}/schema.json";
	// Save schema if not present
	if (!file_exists($version_schema)) {
		$schema = file_get_contents("http://api.steampowered.com/ISteamUserStats/GetSchemaForGame/v2/?key={$apikey}&appid={$appid}");
		file_put_contents($version_schema,$schema);
	}
}
// Get server list
$list = GetServerList();

// Get server data
//$servers = GetAllServers();
if (!isset($maps))
	$maps = array();
// Display fields for filtering
$fields = array(
	'region'	=> $regions,
	'gametype'	=> array_keys($tag_values['g']),
	'theater'	=> array_keys($tag_values['t']),
	'map'		=> $maps,
	'playlist'	=> array_keys($tag_values['p']),
	'pure'		=> array_keys($tag_values['pure']),
	'deathmsgs'	=> '__',
	'coop'		=> '__',
	'no3dvoip'	=> '__',
	'sourcemod'	=> '__',
	'respawn'	=> '__',
	'pvp'		=> '__',
);

//var_dump($servers);

?>
<script type="text/javascript" class="init">
$(document).ready(function() {
		$('table.display').dataTable({ saveState: true });
		$('table.display').floatThead({scrollingTop: 50});
} );
</script>
<?php
startbody();
echo "<h1>This is still very new, and not much is wired up yet. Let me know what features you'd like</h1>\n";
DisplayServerList($servers);

//exit;


// BEGIN FUNCTIONS

function DisplayServerList() {
	global
		$tag_values,
		$required_version,
		$list_age,
		$list_mtime,
		$fields,
		$servers,
		$list,
		$region,
		$regions,
		$reader;
	echo "Server List Refreshed {$list_age} seconds ago<br>\n";
	foreach ($fields as $field => $values) {
		if (is_array($values)) {
			asort($values);
			echo "{$field}: <select name='field-{$field}'>\n<option value=''>--ANY--</option>\n";
			foreach ($values as $key=>$val) {
				$label = (is_numeric($key)) ? $val : $key;
				echo "<option value='{$val}'>{$label}</option>\n";
			}
			echo "</select>\n";
		} else {
			echo "{$field}: <input type='checkbox' name='field-{$field}'>\n";
		}
	}
	echo "<table class='display row-border' id='table-servers' width='100%'>\n";
	echo "<thead>\n";
	echo "<tr>
<th>Reg
</th><th>Name
</th><th>Map
</th><th>Pl
</th><th>Max
</th><th>Game
</th><th>Playlist
</th><th>Theater
</th><th>Pure
</th><th>Tags
</th></tr>\n";
	echo "</thead>\n";
	foreach ($list as $region => $items) {
		foreach ($items as $item) {
			$tags = array();
			$address = "{$item[0]}:{$item[1]}";
			$record = $reader->city($item[0]);
			$flag = "<img src='/images/locations/{$record->country->isoCode}.png' alt='{$record->country->name}' height='16' width='16'>";
//{$record->mostSpecificSubdivision->name}
//{$record->mostSpecificSubdivision->isoCode}
//{$record->city->name}
//{$record->location->latitude}
//{$record->location->longitude}

//json_decode(json_encode(
//), true);

//var_dump($record);
//exit;
			$server = GetServer($region,$item[0],$item[1]);
			if (isset($server['info'])) {
				// Parse version information into different formats for different tools
				$version = $server['info']['gameVersion'];
				$version_num = preg_replace("/[^0-9]/", "",$version);

				// Check for version number
				$version_check = ($version_num == $required_version) ? "OK" : "FAIL - Should be {$required_version}";
				$name = $server['info']['serverName'];
			} else {
				$name = $address;
			}
			if (isset($server['tags'])) {
				$rmtags = array('v','p','g','pure','t');
				foreach ($server['tags'] as $tag => $val) {
					if (in_array($tag,array('v','p','g','pure','t')))
						continue;
					$tags[] = ($val == '__') ? $tag : "{$tag}:{$val}";
				}
			}
			if (isset($server['error'])) {
				$icon = 'no_response';
			} else {
				if (isset($server['info'])) {
					if ($server['info']['passwordProtected']) {
						$icon = 'online_password';
					} else {
						$icon = 'online';
					}
				} else {
					$icon = 'unknown';
				}
			}
//"operatingSystem"
//"secureServer"
//{$regions[$server['region']]}
			$tags = implode(",",$tags);
			echo "<tr id='{$address}' class='row-server'>
<td>{$flag}<img src='/images/servers/icon_{$icon}.gif' height='16' width='16' alt='{$icon}'>
</td><td><a href='steam://connect/{$address}'>{$name}</a>
</td><td>{$server['info']['mapName']}
</td><td>{$server['info']['numberOfPlayers']}
</td><td>{$server['info']['maxPlayers']}
</td><td>{$server['tags']['g']}
</td><td>{$server['tags']['p']}
</td><td>{$server['tags']['t']}
</td><td>{$server['tags']['pure']}
</td><td>{$tags}
</td></tr>\n";
		}
	}
	echo "</table>\n";
}

function GetServerList() {
	global
		$paths,
		$tag_values,
		$list_file,
		$tags_file,
		$server_file_maxage,
		$list_file_maxage,
		$region,
		$regions,
		$filters,
		$list_mtime,
		$list_age;
	if (file_exists($list_file) && (filemtime($list_file) > (time() - $list_file_maxage))) {
		$list = json_decode(file_get_contents($list_file),TRUE);
		$list_mtime = filemtime($list_file);
		$list_age = time() - $list_mtime;
	} else {
		$list = array();
		foreach ($regions as $code => $name) {
			$path = "{$paths['servers']}/{$code}";
			if (!file_exists($path)) {
				mkdir($path,0755,true);
			}
			// Get server list
			$master = new \SteamCondenser\Servers\MasterServer('hl2master.steampowered.com',27011);
			$list[$code] = $master->getServers($code,$filters);
		}
		file_put_contents($list_file,json_encode($list, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT), LOCK_EX);
		$list_mtime = time();
		$list_age = 0;
	}
	return $list;
}
function GetAllServers() {
	global
		$paths,
		$tag_values,
		$list_file,
		$tags_file,
		$server_file_maxage,
		$list_file_maxage,
		$region,
		$regions,
		$filters,
		$list,
		$list_mtime,
		$list_age;

	$servers = array();
	// Get server tags
	if (file_exists($tags_file)) {
		$tag_values = json_decode($tags_file,TRUE);
	} else {
		$tag_values = array();
	}

	// Process servers
	foreach ($list as $list_region => $items) {
		foreach ($items as $item) {
			$servers["{$item[0]}:{$item[1]}"] = GetServer($list_region,$item[0],$item[1]);
		}
	}
	// Update tags list
	file_put_contents($tags_file,json_encode($tag_values, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT), LOCK_EX);
	return $servers;
}

function GetServer($region,$host,$port,$cacheonly=1,$forcerefresh=0) {
	global $paths;
	$ip = gethostbyname($host);
	$server = array('ipAddress' => $ip, 'port' => $port, 'region' => $region);
	$cache_file = "{$paths['servers']}/{$region}/{$ip}_{$port}.json";

	// Should we use the cache file?
	$get_cache = (file_exists($cache_file) && (filemtime($cache_file) > (time() - $server_file_maxage)) && (!$forcerefresh));
	if ($get_cache || $cacheonly) {
		$server = json_decode(file_get_contents($cache_file),TRUE);
		$server['refreshed'] = filemtime($list_file);
		$server['region'] = $region;
		// If server has an error, attempt new fetch
		//$get_cache = (!isset($server['error']));
	}
	// If the cached data is not available or has been invalidated, refresh
	if ((!$cacheonly) && ((!$get_cache) || ($forcerefresh))) {
		try {
			$connection = new \SteamCondenser\Servers\SourceServer($ip,$port);
			$connection->initialize();
			// Collect data from Steam Condenser
			$server['ping'] = $connection->getPing();
			$server['players'] = $connection->getPlayers();
			$server['rules'] = $connection->getRules();
			$server['info'] = $connection->getServerInfo();
			$tags = array_filter(explode(',',$server['info']['serverTags']));
			foreach ($tags as $tag) {
				$bits = explode(':',$tag,2);
				if (count($bits) == 2) {
					$server['tags'][$bits[0]] = $tag_values[$bits[0]][$bits[1]] = $bits[1];
				} else {
					$server['tags'][$tag] = $tag_values[$tag] = "__";
				}
			}
			$server['refreshed'] = time();
			file_put_contents($cache_file,json_encode($server, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT), LOCK_EX);
		}
		catch(Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
			$server['error'] = $e->getMessage();
			file_put_contents($cache_file,json_encode($server, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT), LOCK_EX);
		}
	}
	// Skip processing for servers with errors
	$server['region'] = $region;
	return $server;
}

//exit;
