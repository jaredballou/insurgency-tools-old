#ifndef __war3source_navmesharea_h__
#define __war3source_navmesharea_h__

#include "INavMeshArea.h"
#include "IList.h"
#include "INavMeshConnection.h"
#include "INavMeshHidingSpot.h"
#include "INavMeshEncounterPath.h"
#include "INavMeshLadderConnection.h"
#include "INavMeshCornerLightIntensity.h"
#include "INavMeshVisibleArea.h"

namespace War3Source {
	class NavMeshArea : public INavMeshArea {
	public:
		NavMeshArea(unsigned int id, unsigned int flags, unsigned int placeID,
			float nwExtentX, float nwExtentY, float nwExtentZ,
			float seExtentX, float seExtentY, float seExtentZ,
			float neCornerZ, float swCornerZ,
			IList<INavMeshConnection*> *connections, IList<INavMeshHidingSpot*> *hidingSpots, IList<INavMeshEncounterPath*> *encounterPaths,
			IList<INavMeshLadderConnection*> *ladderConnections, IList<INavMeshCornerLightIntensity*> *cornerLightIntensities,
			IList<INavMeshVisibleArea*> *visibleAreas, unsigned int inheritVisibilityFromAreaID,
			float earliestOccupyTimeFirstTeam, float earliestOccupyTimeSecondTeam, unsigned char unk01);

		~NavMeshArea();

		unsigned int GetID();
		unsigned int GetFlags();
		unsigned int GetPlaceID();

		float GetNWExtentX();
		float GetNWExtentY();
		float GetNWExtentZ();

		float GetSEExtentX();
		float GetSEExtentY();
		float GetSEExtentZ();

		float GetEarliestOccupyTimeFirstTeam();
		float GetEarliestOccupyTimeSecondTeam();

		float GetNECornerZ();
		float GetSWCornerZ();

		IList<INavMeshConnection*> *GetConnections();
		IList<INavMeshHidingSpot*> *GetHidingSpots();
		IList<INavMeshEncounterPath*> *GetEncounterPaths();
		IList<INavMeshLadderConnection*> *GetLadderConnections();
		IList<INavMeshCornerLightIntensity*> *GetCornerLightIntensities();
		IList<INavMeshVisibleArea*> *GetVisibleAreas();

		unsigned int GetInheritVisibilityFromAreaID();

		unsigned char GetUnk01();

	private:
		unsigned int id;
		unsigned int flags;
		unsigned int placeID;

		float nwExtentX;
		float nwExtentY;
		float nwExtentZ;

		float seExtentX;
		float seExtentY;
		float seExtentZ;

		float neCornerZ;
		float swCornerZ;

		IList<INavMeshConnection*> *connections;
		IList<INavMeshHidingSpot*> *hidingSpots;
		IList<INavMeshEncounterPath*> *encounterPaths;
		IList<INavMeshLadderConnection*> *ladderConnections;
		IList<INavMeshCornerLightIntensity*> *cornerLightIntensities;
		IList<INavMeshVisibleArea*> *visibleAreas;

		float earliestOccupyTimeFirstTeam;
		float earliestOccupyTimeSecondTeam;

		unsigned int inheritVisibilityFromAreaID;

		unsigned char unk01;
	};
}

#endif