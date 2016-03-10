<?php
/**
* Server
* 
* This class contains the data for a game server.
* There are methods to pull in data from GameQ and SteamCondenser.
*
* @version 0.1
* @author Jared Ballou <insurgency@jballou.com>
* @project server
*/
class Server {
	var $dedicated;
	var $game_descr;
	var $game_dir;
	var $id;
	var $ipAddress;
	var $map;
	var $mod;
	var $max_players;
	var $name;
	var $num_bots;
	var $num_players;
	var $online;
	var $os;
	var $password;
	var $ping;
	var $players;
	var $port;
	var $protocol;
	var $refreshed;
	var $rules;
	var $secure;
	var $steamappid;
	var $tags;
	var $version;
	public function __set($property, $value)
	{
		switch ($property) {
			case 'gameDesc':
				$property = 'game_descr';
				break;
			case 'gameDir':
				$property = 'game_dir';
				break;
			case 'serverId':
				$property = 'id';
				break;
			case 'gq_address':
				$property = 'ipAddress';
				break;
			case 'gq_mapname':
			case 'mapName':
				$property = 'map';
				break;
			case 'gq_mod':
				$property = 'mod';
				break;
			case 'gq_maxplayers':
			case 'maxPlayers':
				$property = 'max_players';
				break;
			case 'hostname':
			case 'serverName':
				$property = 'name';
				break;
			case 'botNumber':
				$property = 'num_bots';
				break;
			case 'numberOfPlayers':
				$property = 'num_players';
				break;
			case 'gq_password':
				$property = 'online';
				break;
			case 'operatingSystem':
				$property = 'os';
				break;
			case 'gq_password':
			case 'passwordProtected':
				$property = 'password';
				break;
			case 'serverPort':
			case 'gq_port':
				$property = 'port';
				break;
			case 'networkVersion':
				$property = 'protocol';
				break;
			case 'secureServer':
				$property = 'secure';
				break;
			case 'appId':
			case 'gameId':
				$property = 'steamappid';
				break;
			case 'gameVersion':
				$property = 'version';
				break;
			case 'serverTags':
				$property = 'tags';
			case 'tags':
				$tags = explode(",",$value);
				$value = array();
				foreach ($tags as $tag) {
					$bits = explode(':',$tag,2);
					if (!$tag)
						continue;
					if (count($bits) == 2) {
						$value[$bits[0]] = $bits[1];
					} else {
						$value[$tag] = "__";
					}
				}
				break;
		}
		if (property_exists($this, $property)) {
			$this->$property = $value;
		}
	}
	public function ingest($data) {
		foreach ($data as $key=>$val) {
			$this->$key = $val;
		}
	}
}
class Player {
	var $name;
	var $ping;
	var $score;
	var $time;
}
class Rules {
	var $mp_timelimit;
	var $mp_winlimit;
	var $nextlevel;
}
class Tags {
	var $g;
	var $p;
	var $pure;
	var $t;
	var $v;
}
