#ifndef __war3source_navmeshencounterpath_h__
#define __war3source_navmeshencounterpath_h__

#include "INavMeshEncounterPath.h"
#include "INavMeshEncounterSpot.h"
#include "NavDirType.h"

namespace War3Source {
	class NavMeshEncounterPath : public INavMeshEncounterPath {
	public:
		NavMeshEncounterPath(unsigned int fromAreaID, NavDirType fromDirection, unsigned int toAreaID, NavDirType toDirection, IList<INavMeshEncounterSpot*> *encounterSpots);
		~NavMeshEncounterPath();

		unsigned int GetFromAreaID();
		NavDirType GetFromDirection();
		unsigned int GetToAreaID();
		NavDirType GetToDirection();
		IList<INavMeshEncounterSpot*> *GetEncounterSpots();

	private:
		unsigned int fromAreaID;
		NavDirType fromDirection;
		unsigned int toAreaID;
		NavDirType toDirection;
		IList<INavMeshEncounterSpot*> *encounterSpots;
	};
}

#endif