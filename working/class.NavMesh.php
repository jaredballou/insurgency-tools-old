<?php
/*
navmesh.php
This script parses the binary navmesh file and dumps a JSON of all the information it contains.
It is the first step of processing, but I think I will keep the "raw" navmesh data in the
insurgency-data repo for other people to use, and then refine and process it for the web
tools separately.
*/
namespace NavMesh;
function GetBinary($fd,$type='L',$len=0) {
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
		$data = array(GetBinary($fd,'f'),GetBinary($fd,'f'),GetBinary($fd,'f'));
		return $data;
	} elseif ($type == 'STR') {
		$data = fread($fd, $len);
		return $data;
	} else {
		$data = unpack($type, fread($fd, $len));
		return $data[1];
	}
}

class Header{
	public 
		$magicNumber,
		$version,
		$subVersion,
		$saveBspSize,
		$isAnalyzed;

	function __construct($fd){
		$this->magicNumber	= GetBinary($fd);
		$this->version		= GetBinary($fd);
		if ($this->version >= 10) {
			$this->subVersion	= GetBinary($fd);
		}
		if ($this->version >= 4) {
			$this->saveBspSize	= GetBinary($fd);
		}
		if ($this->version >= 14) {
			$this->isAnalyzed	= GetBinary($fd,'C');
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
	function __construct($header,$fd){
		$this->iLadderID		= GetBinary($fd);
		$this->flLadderWidth		= GetBinary($fd,'f');
		$this->flLadderTop		= GetBinary($fd,'VEC');
		$this->flLadderBottom		= GetBinary($fd,'VEC');
		$this->flLadderLength		= GetBinary($fd,'f');
		$this->iLadderDirection		= GetBinary($fd,'f');
		$this->iLadderTopForwardAreaID	= GetBinary($fd);
		$this->iLadderTopLeftAreaID	= GetBinary($fd);
		$this->iLadderTopRightAreaID	= GetBinary($fd);
		$this->iLadderTopBehindAreaID	= GetBinary($fd);
		$this->iLadderBottomAreaID	= GetBinary($fd);
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


	function __construct($header,$fd){
		$this->iAreaID				= GetBinary($fd);
		$this->iAreaFlags			= GetBinary($fd);
		$this->pos1				= GetBinary($fd,'VEC');
		$this->pos2				= GetBinary($fd,'VEC');
		$this->flNECornerZ			= GetBinary($fd,'f');
		$this->flSWCornerZ			= GetBinary($fd,'f');
//var_dump($this);
		for ($d=0;$d<4;$d++) {
			echo "direction {$d}\n";
			$this->iConnectionCount[$d]		= GetBinary($fd);
			echo "iConnectionCount {$this->iConnectionCount[$d]}\n";
			for ($c=0;$c<$this->iConnectionCount[$d];$c++) {
//				echo "Connection {$c}\n";
				GetBinary($fd); // iConnectingAreaID
			}
		}
		$this->iHidingSpotCount			= GetBinary($fd,'C');
		echo "iHidingSpotCount {$this->iHidingSpotCount}\n";
		for ($s=0;$s<$this->iHidingSpotCount;$s++) {
			echo "hidingspots {$s}\n";
			$iHidingSpotID = GetBinary($fd); // iHidingSpotID
			$this->hidingspots[$iHidingSpotID]->flHidingSpot = GetBinary($fd,'VEC'); // flHidingSpot
			$this->hidingspots[$iHidingSpotID]->iHidingSpotFlags = GetBinary($fd); // iHidingSpotFlags
			var_dump($iHidingSpotID,$this->hidingspots[$iHidingSpotID]);
		}
//		$this->iApproachAreaCount		= GetBinary($fd,'C');
		$this->iEncounterPathCount		= GetBinary($fd);
		echo "iEncounterPathCount {$this->iEncounterPathCount}\n";
		for ($p=0;$p<$this->iEncounterPathCount;$p++) {
//			echo "ep {$p}\n";
			$iEncounterFromID = GetBinary($fd); // iEncounterFromID
			$this->encounterpaths[$iEncounterFromID]->iEncounterFromDirection = GetBinary($fd,'C'); // iEncounterFromDirection
			$this->encounterpaths[$iEncounterFromID]->iEncounterToID =GetBinary($fd); // iEncounterToID
			$this->encounterpaths[$iEncounterFromID]->iEncounterToDirection = GetBinary($fd,'C'); // iEncounterToDirection
			$this->encounterpaths[$iEncounterFromID]->iEncounterSpotCount = GetBinary($fd,'C'); // iEncounterSpotCount
			for ($s=0;$s<$this->encounterpaths[$iEncounterFromID]->iEncounterSpotCount;$s++) {
//				"es {$s}\n";
				$iEncounterSpotOrderID = GetBinary($fd); // iEncounterSpotOrderID
				$this->encounterpaths[$iEncounterFromID]->encounterspots[$iEncounterSpotOrderID]->iEncounterSpotT = GetBinary($fd,'C'); // iEncounterSpotT
			}
		}			
		$this->iPlaceID				= GetBinary($fd,'S');
		echo "iPlaceID {$this->iPlaceID}\n";
		for ($d=0;$d<2;$d++) {
			$this->iLadderConnectionCount[$d]	= GetBinary($fd);
			echo "iLadderConnectionCount[{$d}] {$this->iLadderConnectionCount[$d]}\n";
			for ($l=0;$l<$this->iLadderConnectionCount[$d];$l++) {
				echo "ladder {$l}\n";
				$this->ladderconnections[] = GetBinary($fd); // iLadderConnectionID
			}
		}
		$this->flEarliestOccupyTimeFirstTeam	= GetBinary($fd,'f');
		$this->flEarliestOccupyTimeSecondTeam	= GetBinary($fd,'f');
		$this->flNavCornerLightIntensityNW	= GetBinary($fd,'f');
		$this->flNavCornerLightIntensityNE	= GetBinary($fd,'f');
		$this->flNavCornerLightIntensitySE	= GetBinary($fd,'f');
		$this->flNavCornerLightIntensitySW	= GetBinary($fd,'f');
		$this->iVisibleAreaCount		= GetBinary($fd);
		echo "iVisibleAreaCount {$this->iVisibleAreaCount}\n";
		for ($a=0;$a<$this->iVisibleAreaCount;$a++) {
			$iVisibleAreaID = GetBinary($fd); // iVisibleAreaID
			//$this->visibleareas[$iVisibleAreaID]->iVisibleAreaAttributes = 
			GetBinary($fd,'C'); // iVisibleAreaAttributes
		}
		$this->iInheritVisibilityFrom		= GetBinary($fd);
		$this->unk01				= GetBinary($fd);
	}
}

class File{
	public 
	$nav_path,
	$nav_fd,
	$nav_data_offset,
	$nav_header,
	$iPlaceCount,
	$places = array(),
	$iNavUnnamedAreas,
	$iAreaCount,
	$areas,
	$iLadderCount,
	$ladders,
	$nav_fd_count,
	$nav_entries = array();


	function __construct($nav_path){
		$this->nav_path = $nav_path;
		$this->nav_fd = fopen($nav_path, 'rb');
		$this->nav_header = new Header($this->nav_fd);
		$this->iPlaceCount = GetBinary($this->nav_fd,'S');
		for($p=0;$p<$this->iPlaceCount;$p++) {
			echo "Place {$p}\n";
			$len = GetBinary($this->nav_fd,'S');
			$name = GetBinary($this->nav_fd,'STR',$len);
			$this->places[$p] = $name;
		}
		$this->iNavUnnamedAreas = GetBinary($this->nav_fd,'C');
		$this->iAreaCount = GetBinary($this->nav_fd);
		for($a=0;$a<$this->iAreaCount;$a++) {
			echo "Area {$a}\n";
			$this->areas[$a] = new Area($this->nav_header,$this->nav_fd);
			//var_dump($this->areas[$a]);
		}
		$this->iLadderCount			= GetBinary($fd);
		for ($l=0;$l<$this->iLadderCount;$l++) {
			echo "Ladder {$l}\n";
			$this->ladders[$l] = new Ladder($this->nav_fd);
		}
	}
}
