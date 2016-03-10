<?php
/*
navmesh.php
This script parses the binary navmesh file and dumps a JSON of all the information it contains.
It is the first step of processing, but I think I will keep the "raw" navmesh data in the
insurgency-data repo for other people to use, and then refine and process it for the web
tools separately.
*/
namespace NavMesh;

class Header{
	public 
		$magicNumber,
		$version,
		$subVersion,
		$saveBspSize,
		$isAnalyzed;

	function __construct(&$parent){
		$this->magicNumber	= $parent->GetBinary();
		$this->version		= $parent->GetBinary();
		if ($this->version >= 10) {
			$this->subVersion	= $parent->GetBinary();
		}
		if ($this->version >= 4) {
			$this->saveBspSize	= $parent->GetBinary();
		}
		if ($this->version >= 14) {
			$this->isAnalyzed	= $parent->GetBinary('C');
		}
	}
}
class Ladder{
	public
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
		$this->iLadderID		= $parent->GetBinary();
		$this->flLadderWidth		= $parent->GetBinary('f');
		$this->flLadderTop		= $parent->GetBinary('VEC');
		$this->flLadderBottom		= $parent->GetBinary('VEC');
		$this->flLadderLength		= $parent->GetBinary('f');
		$this->iLadderDirection		= $parent->GetBinary('f');
		$this->iLadderTopForwardAreaID	= $parent->GetBinary();
		$this->iLadderTopLeftAreaID	= $parent->GetBinary();
		$this->iLadderTopRightAreaID	= $parent->GetBinary();
		$this->iLadderTopBehindAreaID	= $parent->GetBinary();
		$this->iLadderBottomAreaID	= $parent->GetBinary();
	}
}
class Area{
	public 
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
				$parent->GetBinary(); // iConnectingAreaID
			}
		}
		$this->iHidingSpotCount			= $parent->GetBinary('C');
		echo "iHidingSpotCount {$this->iHidingSpotCount}\n";
		for ($s=0;$s<$this->iHidingSpotCount;$s++) {
			$iHidingSpotID = $parent->GetBinary(); // iHidingSpotID
			echo "hidingspots {$s} id {$iHidingSpotID}\n";
			$this->hidingspots[$iHidingSpotID]->flHidingSpot = $parent->GetBinary('VEC'); // flHidingSpot
			$this->hidingspots[$iHidingSpotID]->iHidingSpotFlags = $parent->GetBinary('L'); // iHidingSpotFlags
			var_dump("spot",$iHidingSpotID,$this->hidingspots[$iHidingSpotID]);
		}
//		$this->iApproachAreaCount		= $parent->GetBinary('C');
		$this->iEncounterPathCount		= $parent->GetBinary();
		echo "iEncounterPathCount {$this->iEncounterPathCount}\n";
		for ($p=0;$p<$this->iEncounterPathCount;$p++) {
			$iEncounterFromID = $parent->GetBinary(); // iEncounterFromID
			$this->encounterpaths[$iEncounterFromID]->iEncounterFromDirection = $parent->GetBinary('C'); // iEncounterFromDirection
			$this->encounterpaths[$iEncounterFromID]->iEncounterToID =$parent->GetBinary(); // iEncounterToID
			$this->encounterpaths[$iEncounterFromID]->iEncounterToDirection = $parent->GetBinary('C'); // iEncounterToDirection
			$this->encounterpaths[$iEncounterFromID]->iEncounterSpotCount = $parent->GetBinary('C'); // iEncounterSpotCount
			for ($s=0;$s<$this->encounterpaths[$iEncounterFromID]->iEncounterSpotCount;$s++) {
				$iEncounterSpotOrderID = $parent->GetBinary(); // iEncounterSpotOrderID
				$this->encounterpaths[$iEncounterFromID]->encounterspots[$iEncounterSpotOrderID]->iEncounterSpotT = $parent->GetBinary('C'); // iEncounterSpotT
			}
		}			
		$this->iPlaceID				= $parent->GetBinary('S');
		echo "iPlaceID {$this->iPlaceID}\n";
		for ($d=0;$d<2;$d++) {
			$this->iLadderConnectionCount[$d]	= $parent->GetBinary();
			echo "iLadderConnectionCount[{$d}] {$this->iLadderConnectionCount[$d]}\n";
			for ($l=0;$l<$this->iLadderConnectionCount[$d];$l++) {
				echo "ladder {$l}\n";
				$this->ladderconnections[] = $parent->GetBinary(); // iLadderConnectionID
			}
		}
		$this->flEarliestOccupyTimeFirstTeam	= $parent->GetBinary('f');
		$this->flEarliestOccupyTimeSecondTeam	= $parent->GetBinary('f');
		if ($parent->navfile_header->version >= 11) {
			$this->flNavCornerLightIntensityNW	= $parent->GetBinary('f');
			$this->flNavCornerLightIntensityNE	= $parent->GetBinary('f');
			$this->flNavCornerLightIntensitySE	= $parent->GetBinary('f');
			$this->flNavCornerLightIntensitySW	= $parent->GetBinary('f');
var_dump($this);
			if ($parent->navfile_header->version >= 16) {
				$this->iVisibleAreaCount		= $parent->GetBinary();
				echo "iVisibleAreaCount {$this->iVisibleAreaCount}\n";
				for ($a=0;$a<$this->iVisibleAreaCount;$a++) {
					$iVisibleAreaID = $parent->GetBinary(); // iVisibleAreaID
					$this->visibleareas->$iVisibleAreaID->iVisibleAreaAttributes = $parent->GetBinary('C'); // iVisibleAreaAttributes
				}
				$this->iInheritVisibilityFrom		= $parent->GetBinary();
				$this->unk01				= $parent->GetBinary();
			}
		}
	}
}

class File{
	public
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


	function __construct($navfile_path){
		$this->navfile_path = $navfile_path;
		$this->navfile_fd = fopen($navfile_path, 'rb');
		$this->navfile_fd_size = filesize($navfile_path);
		$this->navfile_header = new Header($this);
		$this->iPlaceCount = $this->GetBinary('S');
		for($p=0;$p<$this->iPlaceCount;$p++) {
			echo "Place {$p}\n";
			$len = $this->GetBinary('S');
			$name = $this->GetBinary('STR',$len);
			$this->places[$p] = $name;
		}
		$this->iNavUnnamedAreas = $this->GetBinary('C');
		$this->iAreaCount = $this->GetBinary();
		for($a=0;$a<$this->iAreaCount;$a++) {
			echo "Area {$a}\n";
			$this->areas[$a] = new Area($this);
			var_dump($this->areas[$a]);
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
