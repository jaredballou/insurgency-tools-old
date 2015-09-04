#include "NavMeshArea.h"

namespace War3Source {
	NavMeshArea::NavMeshArea(unsigned int id, unsigned int flags, unsigned int placeID,
			float nwExtentX, float nwExtentY, float nwExtentZ,
			float seExtentX, float seExtentY, float seExtentZ,
			float neCornerZ, float swCornerZ,
			IList<INavMeshConnection*> *connections, IList<INavMeshHidingSpot*> *hidingSpots, IList<INavMeshEncounterPath*> *encounterPaths,
			IList<INavMeshLadderConnection*> *ladderConnections, IList<INavMeshCornerLightIntensity*> *cornerLightIntensities,
			IList<INavMeshVisibleArea*> *visibleAreas, unsigned int inheritVisibilityFromAreaID,
			float earliestOccupyTimeFirstTeam, float earliestOccupyTimeSecondTeam, unsigned char unk01) {
				this->id = id;
				this->flags = flags;
				this->placeID = placeID;
				this->nwExtentX = nwExtentX;
				this->nwExtentY = nwExtentY;
				this->nwExtentZ = nwExtentZ;
				this->seExtentX = seExtentX;
				this->seExtentY = seExtentY;
				this->seExtentZ = seExtentZ;
				this->neCornerZ = neCornerZ;
				this->swCornerZ = swCornerZ;
				this->connections = connections;
				this->hidingSpots = hidingSpots;
				this->encounterPaths = encounterPaths;
				this->ladderConnections = ladderConnections;
				this->cornerLightIntensities = cornerLightIntensities;
				this->visibleAreas = visibleAreas;
				this->inheritVisibilityFromAreaID = inheritVisibilityFromAreaID;
				this->earliestOccupyTimeFirstTeam = earliestOccupyTimeFirstTeam;
				this->earliestOccupyTimeSecondTeam = earliestOccupyTimeSecondTeam;
				this->unk01 = unk01;
	}

	NavMeshArea::~NavMeshArea() {
	}

	unsigned int NavMeshArea::GetID() {
		return this->id;
	}

	unsigned int NavMeshArea::GetFlags() {
		return this->flags;
	}

	unsigned int NavMeshArea::GetPlaceID() {
		return this->placeID;
	}

	float NavMeshArea::GetNWExtentX() {
		return this->nwExtentX;
	}

	float NavMeshArea::GetNWExtentY() {
		return this->nwExtentY;
	}
	
	float NavMeshArea::GetNWExtentZ() {
		return this->nwExtentZ;
	}

	float NavMeshArea::GetSEExtentX() {
		return this->seExtentX;
	}

	float NavMeshArea::GetSEExtentY() {
		return this->seExtentY;
	}

	float NavMeshArea::GetSEExtentZ() {
		return this->seExtentZ;
	}

	float NavMeshArea::GetEarliestOccupyTimeFirstTeam() {
		return this->earliestOccupyTimeFirstTeam;
	}

	float NavMeshArea::GetEarliestOccupyTimeSecondTeam() {
		return this->earliestOccupyTimeSecondTeam;
	}

	float NavMeshArea::GetNECornerZ() {
		return this->neCornerZ;
	}

	float NavMeshArea::GetSWCornerZ() {
		return this->swCornerZ;
	}

	IList<INavMeshConnection*> *NavMeshArea::GetConnections() {
		return this->connections;
	}

	IList<INavMeshHidingSpot*> *NavMeshArea::GetHidingSpots() {
		return this->hidingSpots;
	}

	IList<INavMeshEncounterPath*> *NavMeshArea::GetEncounterPaths() {
		return this->encounterPaths;
	}

	IList<INavMeshLadderConnection*> *NavMeshArea::GetLadderConnections() {
		return this->ladderConnections;
	}

	IList<INavMeshCornerLightIntensity*> *NavMeshArea::GetCornerLightIntensities() {
		return this->cornerLightIntensities;
	}

	IList<INavMeshVisibleArea*> *NavMeshArea::GetVisibleAreas() {
		return this->visibleAreas;
	}

	unsigned int NavMeshArea::GetInheritVisibilityFromAreaID() {
		return this->inheritVisibilityFromAreaID;
	}

	unsigned char NavMeshArea::GetUnk01() {
		return this->unk01;
	}
}