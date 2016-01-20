<?php
/*
This reads the workshop and dumps all the Workshop files to local disk.
The original intent of this tool was to create an index of files and
changes that each mod encompassed, and at each version bump be able to
see with a fair degree of accuracy what needed to be updated in the mods
to support the new version. It is currently not being worked on and only
included as a reference or jumping off point for other tools.
*/
include "../include/functions.php";
//Connect to database
mysql_connect($mysql_server,$mysql_username,$mysql_password);
mysql_select_db($mysql_database);

//Global variables
$tb['prefix'] = 'workshop_';
$tb['pubfiles'] = 'pubfiles';

//Create arrays
$dbfields = array();
$pubfiles = array();
$json_pubfiles = array();

//For now, just update the database
UpdateWorkshopDatabase();
GetWorkshopFiles();
exit;

function GetWorkshopPages($page=0,$numperpage=100) {
	global $json_pubfiles,$pubfiles,$apikey,$cache_dir;
	echo "Fetching page {$page}\n";
	$url='https://api.steampowered.com/IPublishedFileService/QueryFiles/v1/';
	$args = array(
		'key' => $apikey,
		'format' => 'json',
		'query_type' => 1,
		'page' => $page,
		'numperpage' => $numperpage,
		'creator_appid' => 222880,
		'appid' => 222880,
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

	//Get data
	//$data = file_get_contents("out-workshop.json");
	$data = file_get_contents($url);

	//Store local copy for failback
	file_put_contents("${cache_dir}/workshop-${page}.json",$data);

	//Decode JSON to array
	$json = json_decode($data,true);

	//Get total records
	$total = $json['response']['total'];

	//Get number of pages
	$pages = ceil($total/$args['numperpage']);

	if (is_array($json['response']['publishedfiledetails'])) {
		$json_pubfiles = array_merge($json_pubfiles,$json['response']['publishedfiledetails']);
	} else {
		var_dump($json);
	}
	if ($pages > $page) {
		GetWorkshopPages($page+1);
	}
}
function UpdateWorkshopDatabase()
{
	global $dbfields,$pubfiles,$json_pubfiles,$tb;

	$result = do_mysql_query("SHOW COLUMNS FROM {$tb['prefix']}{$tb['pubfiles']}");
	//var_dump($result);
	if ($result === false) {
		$result = do_mysql_query("CREATE TABLE IF NOT EXISTS `{$tb['prefix']}{$tb['pubfiles']}` (`id` int(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;");
		$result = do_mysql_query("SHOW COLUMNS FROM {$tb['prefix']}{$tb['pubfiles']}");
	}
	while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
			$dbfields[$row['Field']] = strtolower($row['Type']);
	}
	$result = do_mysql_query("SELECT * FROM `{$tb['prefix']}{$tb['pubfiles']}`");
	while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
		$pubfiles[$row['publishedfileid']] = $row;
	}

	GetWorkshopPages();
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
	global $dbfields,$pubfiles,$tb,$cache_dir;
	$mydir = dirname(__FILE__);
	$doextract = array();
	$result = mysql_query("SELECT publishedfileid,filename,file_size,file_url,time_updated FROM `{$tb['prefix']}{$tb['pubfiles']}`");
//	foreach ($pubfiles as $publishedfileid => $pubfile) {
	while ($pubfile = mysql_fetch_array($result)) {
		$dir = "{$cache_dir}/workshop/{$pubfile['publishedfileid']}";
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
function DisplayWorkshopItems()
{
	//'result','publishedfileid','creator','creator_appid','consumer_appid','consumer_shortcutid','filename','file_size','preview_file_size','file_url','preview_url','url','hcontent_file','hcontent_preview','title','short_description','time_created','time_updated','visibility','flags','workshop_file','workshop_accepted','show_subscribe_all','num_comments_developer','num_comments_public','banned','ban_reason','banner','can_be_deleted','incompatible','app_name','file_type','can_subscribe','subscriptions','favorited','followers','lifetime_subscriptions','lifetime_favorited','lifetime_followers','views','spoiler_tag','num_children','num_reports','tags',

	$fields = array(
		'incompatible',
		'title',
		'creator',
		'preview_url',
		'short_description',
		'time_created',
		'time_updated',

		'filename',
		'file_size',
		'preview_file_size',
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
	);

	echo "<table border='1' bordercolor='#000000' cellspacing='0' cellpadding='1'><tr>";
	foreach($fields as $key) {
		echo "<th>{$key}</th>";
	}
	echo "</tr>\n";
	foreach ($result['response']['publishedfiledetails'] as $pubfile) {
		echo "<tr>";
		foreach($fields as $key) {
			$val = $pubfile[$key];
			switch ($key) {
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
