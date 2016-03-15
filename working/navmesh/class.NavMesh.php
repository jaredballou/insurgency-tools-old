<?php
/*
navmesh.php
This script parses the binary navmesh file and dumps a JSON of all the information it contains.
It is the first step of processing, but I think I will keep the "raw" navmesh data in the
insurgency-data repo for other people to use, and then refine and process it for the web
tools separately.
*/
namespace NavMesh;
function debug_print($msg) {
	echo "{$msg}\n";
}
class Header{
	public 
		$parent,
		$iNavMagicNumber,
		$iNavVersion,
		$iNavSubVersion,
		$iNavSaveBspSize,
		$iNavMeshAnalyzed;

	function __construct(&$parent){
//debug_print(get_class($this));
		//$this->parent				= $parent;
		$this->iNavMagicNumber			= $parent->GetBinary();
		$this->iNavVersion			= $parent->GetBinary();
		if ($this->iNavVersion >= 10) {
			$this->iNavSubVersion		= $parent->GetBinary();
		}
		if ($this->iNavVersion >= 4) {
			$this->iNavSaveBspSize		= $parent->GetBinary();
		}
		if ($this->iNavVersion >= 14) {
			$this->iNavMeshAnalyzed		= $parent->GetBinary('C');
		}
	}
}
class Ladder{
	public
		$parent,
		$iLadderID,
		$flLadderWidth,
		$flLadderTop,
		$flLadderBottom,
		$flLadderLength,
		$iLadderDirection,
		$iLadderTopForwardAreaID,
		$iLadderTopLeftAreaID,
		$iLadderTopRightAreaID,
		$iLadderTopBehindAreaID,
		$iLadderBottomAreaID;
	function __construct(&$parent) {
//debug_print(get_class($this));
		//$this->parent				= $parent;
		$this->iLadderID			= $parent->GetBinary();
		$this->flLadderWidth			= $parent->GetBinary('f');
		$this->flLadderTop			= $parent->GetBinary('VEC');
		$this->flLadderBottom			= $parent->GetBinary('VEC');
		$this->flLadderLength			= $parent->GetBinary('f');
		$this->iLadderDirection			= $parent->GetBinary('f');
		$this->iLadderTopForwardAreaID		= $parent->GetBinary();
		$this->iLadderTopLeftAreaID		= $parent->GetBinary();
		$this->iLadderTopRightAreaID		= $parent->GetBinary();
		$this->iLadderTopBehindAreaID		= $parent->GetBinary();
		$this->iLadderBottomAreaID		= $parent->GetBinary();
	}
}
class HidingSpot {
	public
		$parent,
		$iHidingSpotID,
		$flHidingSpot,
		$iHidingSpotFlags;
	function __construct(&$parent) {
//debug_print(get_class($this));
		//$this->parent				= $parent;
		$this->iHidingSpotID 			= $parent->GetBinary(); // iHidingSpotID
		$this->flHidingSpot 			= $parent->GetBinary('VEC'); // flHidingSpot
		$this->iHidingSpotFlags 		= $parent->GetBinary('C'); // iHidingSpotFlags
var_dump($this);
	}
}
class EncounterSpot {
	public
		$parent,
		$iEncounterSpotOrderID,
		$iEncounterSpotT;
	function __construct(&$parent) {
//debug_print(get_class($this));
		//$this->parent				= $parent;
		$this->iEncounterSpotOrderID		= $parent->GetBinary(); // iEncounterSpotOrderID
		$this->iEncounterSpotT			= $parent->GetBinary('C'); // iEncounterSpotT
var_dump($this);
	}
}
class EncounterPath {
	public
		$parent,
		$iEncounterFromID,
		$iEncounterFromDirection,
		$iEncounterToID,
		$iEncounterToDirection,
		$iEncounterSpotCount,
		$encounterspots;
	function __construct(&$parent) {
//debug_print(get_class($this));
		//$this->parent				= $parent;
		$this->iEncounterFromID			= $parent->GetBinary(); // iEncounterFromID
		$this->iEncounterFromDirection		= $parent->GetBinary('C'); // iEncounterFromDirection
		$this->iEncounterToID			= $parent->GetBinary(); // iEncounterToID
		$this->iEncounterToDirection		= $parent->GetBinary('C'); // iEncounterToDirection
		$this->iEncounterSpotCount		= $parent->GetBinary('C'); // iEncounterSpotCount
		for ($s=0;$s<$this->iEncounterSpotCount;$s++) {
			$this->encounterspots[$s] 	= new EncounterSpot($parent);
		}
var_dump($this);
	}
}
class VisibleArea {
	public
		$parent,
		$iVisibleAreaID,
		$iVisibleAreaAttributes;
	function __construct(&$parent) {
//debug_print(get_class($this));
		//$this->parent				= $parent;
		$this->iVisibleAreaID			= $parent->GetBinary(); // iVisibleAreaID
		$this->iVisibleAreaAttributes		= $parent->GetBinary('C'); // iVisibleAreaAttributes
	}
}
class Area{
	public
		$parent,
		$iAreaID,
		$iAreaFlags,
		$pos1,
		$pos2,
		$flNECornerZ,
		$flSWCornerZ,
		$iConnectionCount,
		$connections,
		$iHidingSpotCount,
		$hidingspots,
		$iApproachAreaCount,
		$approachareas,
		$iEncounterPathCount,
		$encounterpaths,
		$iPlaceID,
		$iLadderConnectionCount,
		$ladderconnections,
		$flEarliestOccupyTimeFirstTeam,
		$flEarliestOccupyTimeSecondTeam,
		$flNavCornerLightIntensityNW,
		$flNavCornerLightIntensityNE,
		$flNavCornerLightIntensitySE,
		$flNavCornerLightIntensitySW,
		$iVisibleAreaCount,
		$visibleareas,
		$iInheritVisibilityFrom,
		$unk01;


	function __construct(&$parent) {
//debug_print(get_class($this));
		//$this->parent				= $parent;
		$this->iAreaID				= $parent->GetBinary();
		$this->iAreaFlags			= $parent->GetBinary();
		$this->pos1				= $parent->GetBinary('VEC');
		$this->pos2				= $parent->GetBinary('VEC');
		$this->flNECornerZ			= $parent->GetBinary('f');
		$this->flSWCornerZ			= $parent->GetBinary('f');
		for ($d=0;$d<4;$d++) {
			echo "direction {$d}\n";
			$this->iConnectionCount[$d]		= $parent->GetBinary();
			echo "iConnectionCount {$this->iConnectionCount[$d]}\n";
			for ($c=0;$c<$this->iConnectionCount[$d];$c++) {
				$this->connections[] = $parent->GetBinary(); // iConnectingAreaID
			}
		}
		$this->iHidingSpotCount			= $parent->GetBinary('C');
debug_print("iHidingSpotCount {$this->iHidingSpotCount}");
		for ($s=0;$s<$this->iHidingSpotCount;$s++) {
			$this->hidingspots[$s] = new HidingSpot($parent);
		}
//		$this->iApproachAreaCount		= $parent->GetBinary('C');
		$this->iEncounterPathCount		= $parent->GetBinary();
debug_print("iEncounterPathCount {$this->iEncounterPathCount}");
		for ($p=0;$p<$this->iEncounterPathCount;$p++) {
			$this->encounterpaths[$p] = new EncounterPath($parent);
		}
		$this->iPlaceID				= $parent->GetBinary('S');
debug_print("iPlaceID {$this->iPlaceID}");
		for ($d=0;$d<2;$d++) {
			$this->iLadderConnectionCount[$d]	= $parent->GetBinary();
			for ($l=0;$l<$this->iLadderConnectionCount[$d];$l++) {
				$this->ladderconnections[] = $parent->GetBinary(); // iLadderConnectionID
			}
		}
		$this->flEarliestOccupyTimeFirstTeam	= $parent->GetBinary('f');
		$this->flEarliestOccupyTimeSecondTeam	= $parent->GetBinary('f');
		if ($parent->navfile_header->iNavVersion >= 11) {
			$this->flNavCornerLightIntensityNW	= $parent->GetBinary('f');
			$this->flNavCornerLightIntensityNE	= $parent->GetBinary('f');
			$this->flNavCornerLightIntensitySE	= $parent->GetBinary('f');
			$this->flNavCornerLightIntensitySW	= $parent->GetBinary('f');
//var_dump($this);
			if ($parent->navfile_header->iNavVersion >= 16) {
				$this->iVisibleAreaCount		= $parent->GetBinary();
debug_print("iVisibleAreaCount {$this->iVisibleAreaCount}");
				for ($a=0;$a<$this->iVisibleAreaCount;$a++) {
					$this->visibleareas[$a] = new VisibleArea($parent);
				}
				$this->iInheritVisibilityFrom		= $parent->GetBinary();
debug_print("iInheritVisibilityFrom: {$this->iInheritVisibilityFrom}");
				$this->unk01				= $parent->GetBinary();
debug_print("unk01: {$this->unk01}");
			}
		}
	}
}

class File{
	public
		$parent,
		$areas,
		$iAreaCount,
		$iLadderCount,
		$iNavUnnamedAreas,
		$iPlaceCount,
		$ladders,
		$navfile_data_offset,
		$navfile_entries = array(),
		$navfile_fd,
		$navfile_fd_size,
		$navfile_fd_count,
		$navfile_header,
		$navfile_path,
		$places = array();
/*
	$sizes = array(
		'iNavMagicNumber'			=> 'int',
		'iNavVersion'				=> 'int',
		'iNavSubVersion'			=> 'int',
		'iNavSaveBspSize'			=> 'int',
		'cNavMeshAnalyzed'			=> 'char',
		'sPlaceCount'				=> 'short',
		'sPlaceStringSize'			=> 'short',
		'strPlaceString'			=> 'string',
		'cNavUnnamedAreas'			=> 'char',
		'iAreaCount'				=> 'int',
		'iAreaID'				=> 'int',
		'iAreaFlags'				=> 'int',
		'vecPos1'				=> 'vector',
		'vecPos2'				=> 'vector',
		'flNECornerZ'				=> 'float',
		'flSWCornerZ'				=> 'float',
		'iConnectionCount'			=> 'int',
		'iConnectingAreaID'			=> 'int',
		'cHidingSpotCount'			=> 'char',
		'iHidingSpotID'				=> 'int',
		'vecHidingSpot'				=> 'vector',
		'cHidingSpotFlags'			=> 'char',
		'cApproachAreaCount'			=> 'char',
		'iApproachHereID'			=> 'int',
		'iApproachPrevID'			=> 'int',
		'cApproachType'				=> 'char',
		'iApproachNextID'			=> 'int',
		'cApproachHow'				=> 'char',
		'iEncounterPathCount'			=> 'int',
		'iEncounterFromID'			=> 'int',
		'cEncounterFromDirection'		=> 'char',
		'iEncounterToID'			=> 'int',
		'cEncounterToDirection'			=> 'char',
		'cEncounterSpotCount'			=> 'char',
		'iEncounterSpotOrderID'			=> 'int',
		'cEncounterSpotT'			=> 'char',
		'sPlaceID'				=> 'short',
		'iLadderConnectionCount'		=> 'int',
		'iLadderConnectionID'			=> 'int',
		'flEarliestOccupyTimeFirstTeam'		=> 'float',
		'flEarliestOccupyTimeSecondTeam'	=> 'float',
		'flNavCornerLightIntensityNW'		=> 'float',
		'flNavCornerLightIntensityNE'		=> 'float',
		'flNavCornerLightIntensitySE'		=> 'float',
		'flNavCornerLightIntensitySW'		=> 'float',
		'iVisibleAreaCount'			=> 'int',
		'iVisibleAreaID'			=> 'int',
		'cVisibleAreaAttributes'		=> 'char',
		'iInheritVisibilityFrom'		=> 'int',
		'unk01'					=> 'int',
		'iLadderCount'				=> 'int',
		'iLadderID'				=> 'int',
		'flLadderWidth'				=> 'float',
		'vecLadderTop'				=> 'vector',
		'vecLadderBottom'			=> 'vector',
		'flLadderLength'			=> 'float',
		'iLadderDirection'			=> 'int',
		'iLadderTopForwardAreaID'		=> 'int',
		'iLadderTopLeftAreaID'			=> 'int',
		'iLadderTopRightAreaID'			=> 'int',
		'iLadderTopBehindAreaID'		=> 'int',
		'iLadderBottomAreaID'			=> 'int',
	);
*/
	function __construct($navfile_path){
//debug_print(get_class($this));
		$this->navfile_path = $navfile_path;
		$this->navfile_fd = fopen($navfile_path, 'rb');
		$this->navfile_fd_size = filesize($navfile_path);
		$this->navfile_header = new Header($this);
		$this->iPlaceCount = $this->GetBinary('S');
		for($p=0;$p<$this->iPlaceCount;$p++) {
			$len = $this->GetBinary('S');
			$name = $this->GetBinary('STR',$len);
			$this->places[$p] = $name;//new Place($this);
		}
		$this->iNavUnnamedAreas = $this->GetBinary('C');
		$this->iAreaCount = $this->GetBinary();
		for($a=0;$a<$this->iAreaCount;$a++) {
			echo "Area {$a}\n";
			$this->areas[$a] = new Area($this);
		}
/*
		$this->iLadderCount			= GetBinary($fd);
		for ($l=0;$l<$this->iLadderCount;$l++) {
			echo "Ladder {$l}\n";
			$this->ladders[$l] = new Ladder($this);
		}
*/
	}
	public function GetBinary($type='L',$len=0) {
		$stats = fstat($this->navfile_fd);
		$pos = ftell($this->navfile_fd);
		if ($len == 0) {
			switch($type) {
				case 'I':
				case 'F':
				case 'f':
				case 'l':
				case 'L':
				case 'N':
				case 'V':
					$len = 4;
					break;
				case 's':
				case 'n':
				case 'v':
				case 'S':
					$len = 2;
					break;
				case 'c':
				case 'C':
					$len = 1;
					break;
			}
		}
		// VEC is just three floats in a row
		if ($type == 'VEC') {
			$data = array($this->GetBinary('f'),$this->GetBinary('f'),$this->GetBinary('f'));
			return $data;
		}
		if ($stats['size'] < ($pos + $len)) {
			echo "ERROR: Seek beyond file size! Size {$stats['size']}, pos {$pos}, type {$type}, len {$len}\n";
			var_dump($this);
			exit;
		}
		if ($type == 'STR') {
			$data = fread($this->navfile_fd, $len);
			return $data;
		} else {
			$data = unpack($type, fread($this->navfile_fd, $len));
			return $data[1];
		}
	}
}
