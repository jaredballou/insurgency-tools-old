#ifndef __war3source_navmeshencounterspot_h__
#define __war3source_navmeshencounterspot_h__

#include "INavMeshEncounterSpot.h"

namespace War3Source {
	class NavMeshEncounterSpot : public INavMeshEncounterSpot {
	public:
		NavMeshEncounterSpot(unsigned int orderID, float parametricDistance);
		~NavMeshEncounterSpot();

		unsigned int GetOrderID();
		float GetParametricDistance();

	private:
		unsigned int orderID;
		float parametricDistance;
	};
}

#endif