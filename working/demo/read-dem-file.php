<?php

// Read demo file and parse out header and frames/packets/commands.
// Use this as a guide:
// https://github.com/mikeemoo/jsgo/blob/master/lib/jsgo.js

// Main loop
if (is_file($argv[1])) {
//	echo $argv[1];
	$demo = new DemoFile($argv[1]);
	var_dump($demo);
}

class DemoHeader {
	// Header fields
	var $filestamp;		// Header format
	var $dem_prot;   // Demo protocol version 
	var $net_prot;   // Network protocol versio
	var $host_name;  // HOSTNAME in case of TV, and IP:PORT or localhost:PORT in case of RIE (Record In eyes).
	var $client_name;// Client name or TV name.
	var $map_name;   // Map name
	var $gamedir;// Root game directory
	var $time;   // Playback time (s)
	var $ticks;  // Number of ticks
	var $frames; // Number of frames
	var $tickrate;   // Tickrate
	var $type;   // TV or RIE ? (0 = RIE, 1 = TV)
	var $status_present; // true if a status command is available in the demo.
	var $signOnLength;	// Sign-on length (?)
	var $parent;		// Parent DemoFile object
	function __construct($parent) {
//	var $parent;		// Parent DemoFile object
		$this->parent = &$parent;
		// Only process HL2DEMO or INSDEMO
		$filestamp = $parent->ReadString(8);
		switch($filestamp) {
			case "HL2DEMO":
			case "INSDEMO":
				break;
			default:
				echo "Bad file format.";
				return false;
		}

		// Parse demo header
		$this->filestamp = $filestamp;
		$this->dem_prot = $parent->ReadInt();
		$this->net_prot = $parent->ReadInt();
		$this->host_name = $parent->ReadString();
		$this->client_name = $parent->ReadString();
		$this->map_name = $parent->ReadString();
		$this->gamedir = $parent->ReadString();
		$this->time = $parent->ReadFloat();
		$this->ticks = $parent->ReadInt();
		$this->frames = $parent->ReadInt();
		$this->tickrate = intval($this->ticks / $this->time);
		$this->signOnLength = $parent->ReadInt();

		$this->type = (!$parent->IsGoodIPPORTFormat($this->host_name));
/*
		$this->status_present = false;
		if(!$this->fast && !($this->type == 1)) {
			while(!(($l = fgets($this->handle)) === false)) {
				if(stripos($l, "\x00status\x00") !== false) {
					$this->status_present = true;
					break;
				}
			}
		}
*/
	// TODO: Find out what this extra data is!
//		var_dump($parent->ReadByte());
//		var_dump($parent->ReadByte());
//		var_dump($parent->ReadByte());
//		var_dump($parent->ReadByte());
		var_dump($parent->ReadByte());
		var_dump($parent->ReadByte());
		var_dump($parent->ReadByte());
		//return true;
	}
}
/*
class DemoFrame {
	var ServerFrame;
	var ClientFrame; //ServerFrame and ClientFrame delta probably correspond to client ping.
	var SubPacketSize;
	//*buffer = new char[SubPacketSize]; // State update message?
	//Packet pkt = (rest of frame as data exists) // All demo commands are strung together in this area, structure below
	//JunkData data = (unknown) // ex: 0x8f 5a b5 04 94 e6 7c 24 00 00 00 00 00 ... (40 bytes of 0x00 after the 0x24)
	// This could either be the end of the frame or the start of the next frame.
}
class DemoPacket {
	var CmdType;
	var Unknown;
	var TickCount; //This only sporadically appears.
	var SizeOfPacket;
	//*buffer = new char[SizeOfPacket];
}
*/
class DemoCommand {
	const CMD_SIGNON = 1;
	const CMD_PACKET = 2;
	const CMD_SYNC_TICK = 3;
	const CMD_CONSOLE_CMD = 4;
	const CMD_USER_CMD = 5;
	const CMD_DATA_TABLES = 6;
	const CMD_STOP = 7;
	const CMD_CUSTOM = 8;
	const CMD_STRING_TABLES = 9;

	const MSG_SERVER_INFO = 8;
	const MSG_DATA_TABLE = 9;
	const MSG_CREATE_STRING_TABLE = 12;
	const MSG_UPDATE_STRING_TABLE = 13;
	const MSG_USER_MESSAGE = 23;
	const MSG_GAME_EVENT = 25;
	const MSG_PACKET_ENTITIES = 26;
	const MSG_GAME_EVENTS_LIST = 30;

	const PVS_ENTER = 0;
	const PVS_LEAVE = 1;
	const DELTA_ENT = 2;
	const PRESERVE_ENT = 3;
	var $parent;		// Parent DemoFile object
	function __construct($parent) {
		$this->parent = &$parent;
		$command = $parent->ReadByte();
		$tick = $parent->ReadInt();
		switch ($command) {
			case self::CMD_SIGNON:
			case self::CMD_PACKET:
				$this->parsePacket();
				break;
			case self::CMD_DATA_TABLES:
				$this->parseDataTables();
				break;
			case self::CMD_USER_CMD:
				$parent->ReadSkip(4);
				$parent->ReadSkip($parent->ReadInt());
				break;
			case self::CMD_STRING_TABLES:
				$this->parseStringTables();
				break;
			case self::CMD_STOP:
				$parent->running = false;
				break;
			case self::CMD_CUSTOM:
			case self::CMD_CONSOLE_CMD:
				$parent->ReadSkip($parent->ReadInt());
				break;
			default:
				break;
		}
	}
	function parsePacket() {
		var_dump("parsePacket");
	}
	function parseDataTables() {
		var_dump("parseDataTables");
	}
	function parseStringTables() {
		var_dump("parseStringTables");
	}

}
class DemoFile {
	// Demo file header
	var $header;		// Header onbect

	// Command data
	var $commands = array();	// Frame data

	// Internal variables
	var $fast;		// Fast processing
	var $handle;		// File handle
	var $running;		// Is the file load running?

	function __construct($pathtofile, $fast = false) {
		$this->fast = $fast;
		$this->LoadFile($pathtofile);
	}

	function LoadFile($pathtofile) {
		$this->running = true;
		if($this->ExtOfFile($pathtofile) === "dem") {
			$this->handle = fopen($pathtofile, "r");
			if($this->handle) {
				$this->header = new DemoHeader($this);
				if ($this->header) {
					$this->parseCommands();
				} else {
					echo "Header did not process properly";
					return false;
				}
				fclose($this->handle);
			} else {
				echo "File not found or unable to read.";
				return false;
			}
		} else {
			echo "Bad file extension.";
			return false;
		}
		return true;
	}

	function parseCommands() {
		while ($this->running) {
			$this->commands[] = new DemoCommand($this);
		}
		return true;
	}

	function ExtOfFile($pathtofile) {
		return end(explode('.',$pathtofile));
	}
 
	function ReadString($n = 260) {
		$buffer = "";
		for($d = 1; ((($char = fgetc($this->handle)) !== false) && ($d < $n)); $d++) $buffer = $buffer.$char;
		return trim($buffer);
	}
 
	function ReadSkip($n = 0) {
		$buf = fread($this->handle, $n);
	}
	function ReadInt($n = 4) {
		$buf = fread($this->handle, $n);
		$number = unpack("i", $buf);
		return $number[1];
	}
	function ReadByte($n = 1) {
		$buf = fread($this->handle, $n);
		$number = unpack("c", $buf);
		//var_dump($number[1]);
		return $number[1];
	}
	function ReadFloat($n = 4) {
		$buf = fread($this->handle, $n);
		$number = unpack("f", $buf); 
		return $number[1];
	}
 
	function IsGoodIPPORTFormat($string) {
		if(preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\:[0-9]{1,5}/', $string))	return true;
		else return false;
	}

}
 
 
?>
