<?php
/*
This reads the workshop and dumps all the Workshop files to local disk.
The original intent of this tool was to create an index of files and
changes that each mod encompassed, and at each version bump be able to
see with a fair degree of accuracy what needed to be updated in the mods
to support the new version. It is currently not being worked on and only
included as a reference or jumping off point for other tools.
*/

//Root Path Discovery
do { $rd = (isset($rd)) ? dirname($rd) : realpath(dirname(__FILE__)); $tp="{$rd}/rootpath.php"; if (file_exists($tp)) { require_once($tp); break; }} while ($rd != '/');

include "${includepath}/functions.php";

//Connect to database
mysql_connect($mysql_server,$mysql_username,$mysql_password);
mysql_select_db($mysql_database);

//Global variables
$tb['prefix'] = 'workshop_';
$tb['pubfiles'] = 'pubfiles';

//Create arrays
$dbfields = array();
$pubfiles = array();
$steamusers = array();

if ($_REQUEST['command'] == 'update') {
	UpdateWorkshopDatabase();
} else {
	DisplayWorkshopItems($_REQUEST['page'],$_REQUEST['perpage']);
}

//GetWorkshopFiles();
exit;


function GetSteamUsernameFromUID($uid) {
	global $steamusers;
	$fields = array(
		'steamID',
		'customURL',
		'realname',
		'steamID64',
		'avatarFull',
		'headline',
		'location',
	);
	if (!isset($steamusers[$uid])) {
		$url = "http://www.steamcommunity.com/profiles/${uid}";
/*
		$username = '';
		for ($i=0;$i<=5;$i++) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Linux x86_64; rv:21.0) Gecko/20100101 Firefox/21.0"); // Necessary. The server checks for a valid User-Agent.
			curl_exec($ch);
			$response = curl_exec($ch);
			curl_close($ch);

			preg_match_all('/^Location:(.*)$/mi', $response, $matches);
			if (!empty($matches[1])) {
				$url = trim($matches[1][0]);
				$steamusers[$uid]['url'] = $url;
				preg_match_all('|http://steamcommunity.com/id/([^/\?]*)|mi', $url, $matches);
				if (!empty($matches[1])) {
					$steamusers[$uid]['customURL'] = trim($matches[1][0]);
				}
			}
		}
*/
		$ch = curl_init("{$url}?xml=1");
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Linux x86_64; rv:21.0) Gecko/20100101 Firefox/21.0"); // Necessary. The server checks for a valid User-Agent.
		curl_exec($ch);
		$response = curl_exec($ch);
		curl_close($ch);
		$steamusers[$uid]['url'] = $url;
		$steamusers[$uid]['steamID64'] = $uid;
		foreach ($fields as $field) {
			preg_match_all("/<{$field}>[\s]*<!\[CDATA\[(.*?)\]\]>[\s]*<\/{$field}>/", $response, $matches);
			if (!empty($matches[1])) {
				$steamusers[$uid][$field] = trim($matches[1][0]);
			}
		}
		$query="INSERT INTO steam_users (".implode(",",array_keys($steamusers[$uid])).") VALUES('".implode("','",array_values($steamusers[$uid]))."');\n";
//		echo $query."<br>\n";
		do_mysql_query($query);

	}
	foreach ($fields as $field) {
		if ($steamusers[$uid][$field] != '') {
			return $steamusers[$uid][$field];
		}
	}
	return $uid;
}


function GetWorkshopPages($page=1,$numperpage=100) {
	global
		$pubfiles,
		$apikey,
		$appid,
		$cachepath;
	$json_pubfiles = array();
	echo "Fetching page {$page}\n";
	$url='https://api.steampowered.com/IPublishedFileService/QueryFiles/v1/';
	$args = array(
		'key' => $apikey,
		'format' => 'json',
		'query_type' => 1,
		'page' => $page,
		'numperpage' => $numperpage,
		'creator_appid' => $appid,
		'appid' => $appid,
		'match_all_tags' => 0,
		'include_recent_votes_only' => 0,
		'totalonly' => 0,
		'return_vote_data' => 1,
		'return_tags' => 1,
		'return_kv_tags' => 1,
		'return_previews' => 1,
		'return_children' => 1,
		'return_short_description' => 1,
		'return_for_sale_data' => 1
	);
	//Build URL
	$url = "{$url}?".http_build_query($args);
	echo $url."\n";
	//Get data
	//$data = file_get_contents("out-workshop.json");
	$data = file_get_contents($url);

	//Store local copy for failback
//	file_put_contents("${cachepath}/workshop-${page}.json",$data);

	//Decode JSON to array
	$json = json_decode($data,true);

	//Get total records
	$total = $json['response']['total'];

	//Get number of pages
	$pages = ceil($total/$args['numperpage']);

	if (is_array($json['response']['publishedfiledetails'])) {
		$json_pubfiles = $json['response']['publishedfiledetails'];
	} else {
		var_dump($json);
	}
	if ($pages > $page) {
		$json_pubfiles = array_merge($json_pubfiles,GetWorkshopPages($page+1));
	}
	return $json_pubfiles;
}
function GetTableColumns($table) {
	$fields = array();
	$result = do_mysql_query("SHOW COLUMNS FROM {$table}");
	while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
		$fields[$row['Field']] = strtolower($row['Type']);
	}
	return $fields;
}
function CreateTables($tables) {
	foreach ($tables as $name=>$table) {
		$query = "CREATE TABLE IF NOT EXISTS `{$name}` ";
		if (count($table['fields'])) {
			$query.="(\n";
			foreach ($table['fields'] as $field=>$parms) {
				$query.="`{$field}` ${parms},\n";
			}
			$query.=")\n";
		}
		$query.=implode(" ",$table['settings']);
		do_mysql_query($query);
	}
}

// Update Workshop MySQL database
function UpdateWorkshopDatabase()
{
	global $dbfields,$pubfiles,$tb;

	// Get current column list if we haven't loaded it yet
	if (!count($dbfields)) {
		// Create table if it's not existing
		$tables = array(
			"{$tb['prefix']}{$tb['pubfiles']}" => array(
				"settings" => array(
					"ENGINE=InnoDB",
					"DEFAULT CHARSET=latin1",
					"AUTO_INCREMENT=1",
				),
				"fields" => array(
					'db_updated' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
				),
			),
		);
		CreateTables($tables);
		$dbfields = GetTableColumns("{$tb['prefix']}{$tb['pubfiles']}");
	}
	$result = do_mysql_query("SELECT * FROM `{$tb['prefix']}{$tb['pubfiles']}`");
	while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
		$pubfiles[$row['publishedfileid']] = $row;
	}

	$json_pubfiles = GetWorkshopPages();
	file_put_contents("${cachepath}/workshop.json",$json_pubfiles);
	foreach ($json_pubfiles as $pubfile) {
		foreach ($pubfile as $key=>$val) {
			CheckDatabaseField($key,$val);
			if (isset($pubfiles[$pubfile['publishedfileid']])) {
				UpdateDatabaseRow($pubfile['publishedfileid'],$key,$val);
			}
		}
		if (!isset($pubfiles[$pubfile['publishedfileid']])) {
			AddDatabaseRow($pubfile);
		}
	}
}

function GetWorkshopFiles() {
	global $dbfields,$pubfiles,$tb,$cachepath;
	$mydir = dirname(__FILE__);
	$doextract = array();
	$result = mysql_query("SELECT publishedfileid,filename,file_size,file_url,time_updated FROM `{$tb['prefix']}{$tb['pubfiles']}`");
//	foreach ($pubfiles as $publishedfileid => $pubfile) {
	while ($pubfile = mysql_fetch_array($result)) {
		$dir = "{$cachepath}/workshop/{$pubfile['publishedfileid']}";
		$file = $dir."/".basename($pubfile['filename']);
		$filemtime = file_exists($file) ? @filemtime($file) : 0;
		$filesize = file_exists($file) ? filesize($file) : 0;
		if (($pubfile['time_updated'] > $filemtime) || ($pubfile['file_size'] != $filesize)) {
			echo "Fetching {$file} - {$pubfile['file_size']} vs {$filesize}\n";
			if (file_exists($dir)) {
				delTree($dir);
			}
			mkdir($dir,0755,true);
			file_put_contents($file,file_get_contents($pubfile['file_url']));
			$doextract[$dir] = escapeshellarg("./".basename($pubfile['filename']));
		}
	}
	foreach ($doextract as $dir => $file) {
		chdir($mydir."/".$dir);
		exec("vpk {$file}",$output);
		var_dump($output);
	}
}

function AddDatabaseRow($row) {
	global $dbfields,$pubfiles,$tb;
	$vals = array();
	foreach ($row as $key=>$val) {
		$val = str_replace("'","\'",$val);
		$vals[] = is_array($val) ? json_encode($val) : $val;
	}
	$query = "INSERT INTO `{$tb['prefix']}{$tb['pubfiles']}` (`".implode('`,`',array_keys($row))."`) VALUES ('".implode("','",$vals)."')";
	do_mysql_query($query);
	$pubfiles[$row['publishedfileid']] = $row;
}

function UpdateDatabaseRow($publishedfileid,$key,$val) {
	global $dbfields,$pubfiles,$tb;
	$oval = isset($pubfiles[$publishedfileid][$key]) ? str_replace("'","\\'",$pubfiles[$publishedfileid][$key]) : '';
	$val = str_replace("'","\\'",$val);
	$vals = is_array($val) ? json_encode($val) : $val;
	if (str_replace("\\","",$oval) != str_replace("\\","",$vals)) {
		//echo "Update {$publishedfileid} key {$key} from {$oval} to {$vals}\n";
		do_mysql_query("UPDATE `{$tb['prefix']}{$tb['pubfiles']}` SET `{$key}`='{$vals}' WHERE `publishedfileid`='{$publishedfileid}'");
		$pubfiles['publishedfileid'][$key] = $vals;
	}
}

function UsesLength($type) {
	switch ($type) {
		case 'text':
		case 'float':
		case 'double':
			return false;
		default:
			return true;
	}
}

function UpdateDatabaseField($key,$type,$length) {
	global $dbfields,$pubfiles,$tb;
	$dbtype = UsesLength($type) ? $type."({$length})" : $type;
	//If dbfield array value for this field, check if we need to update it
	if (isset($dbfields[$key])) {
		$otype = $dbfields[$key];
		if ($otype == $dbtype) {
			return;
		}
		$bits = explode("(",$otype);
		$olen = (count($bits) == 2) ? str_replace(array("(",")"),"",$bits[1]) : 0;
		if ($bits[0] != $type) {
			//Change in type from database to now. Only change varchar to text, or int to string
			if ((($bits[0] == 'varchar') && ($type == 'text')) || ($bits[0] == 'int')) {
				//echo "Changing {$key} TYPE from {$otype} to {$dbtype}\n";
				do_mysql_query("ALTER TABLE `{$tb['prefix']}{$tb['pubfiles']}` CHANGE `{$key}` `{$key}` {$dbtype}");
				$dbfields[$key] = $dbtype;
			}
		} else {
			if (UsesLength($type)) {
				if ($olen < $length) {
					//echo "Changing {$key} LENGTH from {$olen} to {$length} - {$otype} to {$dbtype}\n";
					do_mysql_query("ALTER TABLE `{$tb['prefix']}{$tb['pubfiles']}` CHANGE `{$key}` `{$key}` {$dbtype}");
					$dbfields[$key] = $dbtype;
				}
			}
		}
	} else {
		//Add new field if not existant
		do_mysql_query("ALTER TABLE `{$tb['prefix']}{$tb['pubfiles']}` ADD `{$key}` {$dbtype}");
		$dbfields[$key] = $dbtype;
	}

}

function CheckDatabaseField($key,$val) {
	global $dbfields,$pubfiles;
	if (is_array($val)) {
		if (!isset($dbfields[$key])) {
			UpdateDatabaseField($key,'text',0);
		}
	} else {
		$type = gettype($val);
		$len = strlen($val);
		switch ($type) {
			case 'integer':
				$dbtype = 'int';
				break;
			case 'boolean':
				$dbtype = 'int';
				$len = 1;
				break;
			case 'double':
				$dbtype = $type;
				break;
			default:
				$dbtype = ($len > 255) ? 'text' : 'varchar';
				break;
		}
		UpdateDatabaseField($key,$dbtype,$len);
	}
}

function do_mysql_query($query) {
	//echo $query."\n";
	return mysql_query($query);
}

function DisplayWorkshopItems($page=0,$perpage=25)
{
	global $steamusers,$pubfiles;
	global $tb;
	if ((!is_numeric($perpage)) || ($perpage < 1)) {
		$perpage = 25;
	}
	if ($perpage > 100) {
		$perpage = 100;
	}
	if ((!is_numeric($page)) || ($page < 0)) {
		$page=0;
	}
	$result = do_mysql_query("SELECT count(result) as total FROM `{$tb['prefix']}{$tb['pubfiles']}`");
	$row = mysql_fetch_array($result,MYSQL_ASSOC);
	$total = $row['total'];
	echo "({$total}) ";
	$last = floor($total/$perpage);
	if ($page > $last) {
		$page = $last;
		$start = $total - $perpage;
	} else {
		$start = $page*$perpage;
	}
	echo "({$start}) ";
	
	$result = do_mysql_query("SELECT * FROM `steam_users`");
	while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
		$steamusers[$row['steamID64']] = $row;
	}

	if ($_REQUEST['command'] == 'steamid') {
		$result = do_mysql_query("SELECT creator FROM `{$tb['prefix']}{$tb['pubfiles']}`");
		while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
			GetSteamUsernameFromUID($row['creator']);
		}
	}

	$result = do_mysql_query("SELECT * FROM `{$tb['prefix']}{$tb['pubfiles']}` LIMIT {$start}, {$perpage}");
	while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
		$pubfiles[$row['publishedfileid']] = $row;
	}

	//'result','publishedfileid','creator','creator_appid','consumer_appid','consumer_shortcutid','filename','file_size','preview_file_size','file_url','preview_url','url','hcontent_file','hcontent_preview','title','short_description','time_created','time_updated','visibility','flags','workshop_file','workshop_accepted','show_subscribe_all','num_comments_developer','num_comments_public','banned','ban_reason','banner','can_be_deleted','incompatible','app_name','file_type','can_subscribe','subscriptions','favorited','followers','lifetime_subscriptions','lifetime_favorited','lifetime_followers','views','spoiler_tag','num_children','num_reports','tags',

	$fields = array(
//		'incompatible',
		'title',
		'creator',
		'preview_url',
		'short_description',
		'time_created',
		'time_updated',
		'filename',
		'file_size',
		'preview_file_size',
/*
		'visibility',
		'flags',
		'num_comments_developer',
		'num_comments_public',
		'banned',
		'ban_reason',
		'banner',
		'file_type',
		'can_subscribe',
		'subscriptions',
		'favorited',
		'followers',
		'lifetime_subscriptions',
		'lifetime_favorited',
		'lifetime_followers',
		'views',
		'spoiler_tag',
		'num_children',
		'num_reports',
		'tags'
*/
	);
	if ($page) {
		if ($page > 1) {
			echo "<a href='workshop.php?perpage={$perpage}&page=0'>[0]</a> ";
		}
		$prev = ($page-1);
		echo "<a href='workshop.php?perpage={$perpage}&page={$prev}'> [{$prev}]</a> ";
	}
	echo "<b>[{$page}]</b>";
	if ($page < $last) {
		$next = ($page+1);
		echo " <a href='workshop.php?perpage={$perpage}&page={$next}'>[{$next}]</a> ";
		echo " <a href='workshop.php?perpage={$perpage}&page={$last}'>[{$last}]</a> ({$total})";
	}
	
	echo "<table border='1' bordercolor='#000000' cellspacing='0' cellpadding='1'><tr>";
	foreach($fields as $key) {
		echo "<th>{$key}</th>";
	}
	echo "</tr>\n";
	foreach ($pubfiles as $pubfile) {
		echo "<tr>";
		foreach($fields as $key) {
			$val = $pubfile[$key];
			switch ($key) {
				case 'creator':
					$name = GetSteamUsernameFromUID($val);
//					var_dump($name,$steamusers);
					
					$val = "<a href='{$steamusers[$val]['url']}' target='_blank'>{$name}</a>";
					break;
				case 'time_created':
				case 'time_updated':
					$val = date("Y-m-d h:i",$val);
					break;
				case 'filename':
					$val = "<a href='{$pubfile['file_url']}' target='_blank'>{$val}</a>";
					break;
				case 'preview_url':
					$val = "<img src='{$val}' height='100' width='100'>";
					break;
				case 'file_size':
				case 'preview_file_size':
					$val = formatBytes($val);
					break;
			}
			echo "<td>{$val}</td>";
		}
		echo "</tr>\n";
	}
	echo "</table>";

	//http://steamcommunity.com/sharedfiles/downloadfile/?id=
}
