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
	td.details-control {
		background: url("images/details_open.png") no-repeat center center;
		cursor: pointer;
	}
	tr.shown td.details-control {
		background: url("images/details_close.png") no-repeat center center;
	}
';


if (isset($_REQUEST['fetch'])) {
	include "${rootpath}/include/functions.php";
} else {
	include "${rootpath}/include/header.php";
}

include "class.Server.php";
include "${rootpath}/thirdparty/steam-condenser-php/vendor/autoload.php";

require_once 'gameq/GameQ.php';

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

$tags_file = "{$paths['appdata']}/tags.json";
$maps_file = "{$paths['appdata']}/maps.json";
$list_file = "{$paths['appdata']}/list.json";

foreach ($paths as $name => $path) {
	//Check if this app is up to date
	if (!file_exists($path)) {
		mkdir($path,0755,true);
	}
}

// FETCH

if (isset($_REQUEST['fetch'])) {
	switch ($_REQUEST['fetch']) {
		case 'regions':
			$data = $regions;
			break;
		case 'masters':
			$data = $masters;
			break;
		case 'list':
			$data = GetList();
			break;
		case 'server':
			$ip = filter_var($_REQUEST['ip'], FILTER_SANITIZE_URL);
			$port = filter_var($_REQUEST['port'], FILTER_SANITIZE_URL);
			$data = GetServer($ip,$port,'',0,1);
			break;
	}
	if (isset($data)) {
		header('Content-Type: application/json');
		echo json_encode($data, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
	}
	exit;
}


// VERSION
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

// SERVER LIST

$list = GetList();

// MAPS

function AddMap(&$maps,$map) {
	$map = str_replace('\\','/',$map);
	$bn = strtolower(basename($map));
	if (!isset($maps[$bn])) {
		$maps[$bn] = $map;
		ksort($maps);
	}
}
if (file_exists($maps_file)) {
	$maps = json_decode(file_get_contents($maps_file),TRUE);
} else {
	$maps = array();
	$files = glob("${datapath}/maps/parsed/*.json");

	// Open all files and add gametypes and other map info to array
	foreach ($files as $file) {
		$mapname = basename($file,".json");
		if (in_array($mapname,$excludemaps)) {
			continue;
		}
		AddMap($maps,$mapname);
	}
}

// TAGS

if (file_exists($tags_file)) {
	$tag_values = json_decode(file_get_contents($tags_file),TRUE);
	ksort($tag_values);
} else {
	$tag_values = array();
}


foreach ($list as $server) {
	if ($server['info']['mapName']) {
		AddMap($maps,$server['info']['mapName']);
	}
	$tags = array_filter(explode(',',$server['info']['serverTags']));
	foreach ($tags as $tag) {
		$bits = explode(':',$tag,2);
//if ($bits[0] == 'p') {
//var_dump($tag,$bits);
//}
		if (count($bits) == 2) {
			if (!is_array($tag_values[$bits[0]])) {
				$tag_values[$bits[0]] = ($tag_values[$bits[0]] == '__') ? array() : array($tag_values[$bits[0]]);
			}
			$tag_values[$bits[0]][$bits[1]] = $bits[1];
		} else {
			$tag_values[$tag] = "__";
		}
	}
}

ksort($maps);
ksort($tag_values);
file_put_contents($tags_file,json_encode($tag_values, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT), LOCK_EX);
file_put_contents($maps_file,json_encode($maps, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT), LOCK_EX);



// Get server data
//$servers = GetAllServers();


// Display fields for filtering
$fields = array(
	'region'	=> $regions,
	'gametype'	=> array_keys($tag_values['g']),
	'theater'	=> array_keys($tag_values['t']),
	'map'		=> array_keys($maps),
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

// jQuery code is handled outside PHP
?>
<script type="text/javascript" class="init">

function serverDetails ( d ) {
	var data = '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">'+
		'<tr>'+
			'<td colspan="2">'+d.info.serverName+'<br>'+
			d.ipAddress+':'+d.port+'</td>'+
		'</tr>'+
		'<tr>'+
			'<td>Players:</td>'+
			'<td>'+d.info.numberOfPlayers+'/'+d.info.maxPlayers+'</td>'+
		'</tr>'+
		'<tr>'+
			'<td colspan="2">';
//	data = data + (d.players.forEach(
//		function(key) {
//			return key+'<br>';
//		}
//	));
	data = data+
			'</td>'+
		'</tr>'+

		'<tr>'+
			'<td>Map:</td>'+
			'<td>'+d.info.mapName+'</td>'+
		'</tr>'+
	'</table>';
	return data;
}

$(document).ready(function() {
	//$('table.display').dataTable({ saveState: true });
	//$('table.display').floatThead({scrollingTop: 50});
/*
	$('#servers tfoot th').each( function () {
		var title = $(this).text();
		$(this).html( '<input type="text" placeholder="Search '+title+'" />' );
	} );
*/
	var table = $('#servers').DataTable( {
		"ajax": {
			"url": "serverbrowser.php?fetch=list",
			"dataSrc": "",
		},
//		"deferRender": true,
		"columns": [
			{
				"className":  'details-control',
				"orderable":  false,
				"data":   null,
				"defaultContent": ''
			},
			{ "data": "region" },
			{ "data": "info.serverName" },
			{ "data": "info.mapName" },
			{ "data": "info.numberOfPlayers" },
			{ "data": "info.maxPlayers" },
			{ "data": "tags.g" },
			{ "data": "tags.p" },
			{ "data": "tags.t" },
			{ "data": "tags.pure" },
			{ "data": "info.serverTags" },
		],
		"order": [[1, 'asc']],
        initComplete: function () {
            this.api().columns().every( function () {
                var column = this;
/*
                var select = $('<select><option value=""></option></select>')
                    .appendTo( $(column.footer()).empty() )
                    .on( 'change', function () {
                        var val = $.fn.dataTable.util.escapeRegex(
                            $(this).val()
                        );
 
                        column
                            .search( val ? '^'+val+'$' : '', true, false )
                            .draw();
                    } );
*/ 
                column.data().unique().sort().each( function ( d, j ) {
                    $(column.footer()).html("AAAAAA")
//find('select').append( '<option value="'+d+'">'+d+'</option>' )
                } );
            } );
        }
	} );
 
/*
	// Apply the search
	table.columns().every( function () {
		var that = this;
 
		$( 'input', this.footer() ).on( 'keyup change', function () {
			if ( that.search() !== this.value ) {
				that
					.search( this.value )
					.draw();
			}
		} );
	} );
*/
	// Add event listener for opening and closing details
	$('#servers tbody').on('click', 'td.details-control', function () {
		var tr = $(this).closest('tr');
		var row = table.row( tr );
 
		if ( row.child.isShown() ) {
			// This row is already open - close it
			row.child.hide();
			tr.removeClass('shown');
		}
		else {
			// Open this row
			row.child( serverDetails(row.data()) ).show();
			tr.addClass('shown');
		}
	} );
} );

</script>
<?php

// Display beginning of body with menu
startbody();

// Disclaimer
echo "<h1>This is still very new, and not much is wired up yet. Let me know what features you'd like</h1>\n";

// Display the list
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
	echo "<table class='display row-border' id='servers' width='100%'>\n";
	$columns = array('','Reg','Name','Map','Pl','Max','Game','Playlist','Theater','Pure','Tags');
	echo "<thead>\n";
	echo "<tr><td>".implode("</td><td>",$columns)."</td></tr>\n";
	echo "</thead>\n";
	echo "<tfoot>\n";
	echo "<tr>";
	foreach ($columns as $column) {
		echo "<td><select id='search-{$column}'><option value=''></option></select></td>";
	}
	echo "</tfoot>\n";
/*
	foreach ($list as $address => $data) {
		$tags = array();
		$record = $reader->city($data['ipAddress']);
		$flag = "<img src='{$urlbase}images/locations/{$record->country->isoCode}.png' alt='{$record->country->name}' height='16' width='16'>";
//{$record->mostSpecificSubdivision->name}
//{$record->mostSpecificSubdivision->isoCode}
//{$record->city->name}
//{$record->location->latitude}
//{$record->location->longitude}

//json_decode(json_encode(
//), true);

//var_dump($record);
//exit;
			$server = GetServer($data['ipAddress'],$data['port']);
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
<td>{$flag}<img src='{$urlbase}images/servers/icon_{$icon}.gif' height='16' width='16' alt='{$icon}'>
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
*/

	echo "</table>\n";
}

function GetList() {
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
			$result = $master->getServers($code,$filters);
			foreach ($result as $item) {
				$list[] = GetServer($item[0],$item[1],$code);
			}
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
	// Process servers
	foreach ($list as $item) {
		$servers[] = GetServer($item['ipAddress'],$item['port']);
	}
	return $servers;
}

function GetServer($host,$port,$region='',$cacheonly=1,$forcerefresh=0) {
	global $paths,$columns,$server_file_maxage,$tag_values;
	$ip = gethostbyname($host);
	$server = new Server();
	$cache_file = "{$paths['servers']}/{$ip}_{$port}.json";

	// Should we use the cache file?
	if (file_exists($cache_file)) {
		$get_cache = ((filemtime($cache_file) > (time() - $server_file_maxage)) && (!$forcerefresh));
		if ($get_cache || $cacheonly) {
			$cache = json_decode(file_get_contents($cache_file),TRUE);
			$server->ingest($cache);
		}
	}
	// If the cached data is not available or has been invalidated, refresh
	if ((!$cacheonly) && ((!$get_cache) || ($forcerefresh))) {
		try {
			$address = "{$ip}:{$port}";
			$gq = new GameQ();
			$gq->addServers(array($address => array('insurgency',$ip,$port)));
			$gq->setOption('timeout', 200);
			$gq->setFilter('normalise');
			$gq->setFilter('sortplayers', 'gq_ping');
			$results = $gq->requestData();
			$server->ingest($results[$address]);

/*
			$connection = new \SteamCondenser\Servers\SourceServer($ip,$port);
			$connection->initialize();
			// Collect data from Steam Condenser
			$server->ingest(array(
				'region'	=> $region,
				'ipAddress'	=> $ip,
				'port'		=> $port,
				'ping'		=> $connection->getPing(),
				'players'	=> $connection->getPlayers(),
				'rules'		=> $connection->getRules(),
				'refreshed'	=> time(),
			));
			$server->ingest($connection->getServerInfo());
*/
			file_put_contents($cache_file,json_encode($server, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT), LOCK_EX);
		}
		catch(Exception $e) {
//			echo 'Caught exception: ',  $e->getMessage(), "\n";
			$server['error'] = $e->getMessage();
			file_put_contents($cache_file,json_encode($server, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT), LOCK_EX);
		}
	}
	// Skip processing for servers with errors
	if ($region) {
		$server->ingest(array('region' => $region));
	}
	return $server;
}

//exit;
