#ifndef __war3source_navmeshhidingspot_h__
#define __war3source_navmeshhidingspot_h__

#include "INavMeshHidingSpot.h"

namespace War3Source {
	class NavMeshHidingSpot : public INavMeshHidingSpot {
	public:
		NavMeshHidingSpot(unsigned int id, float x, float y, float z, unsigned char flags);
		~NavMeshHidingSpot();

		unsigned int GetID();

		float GetX();
		float GetY();
		float GetZ();

		unsigned char GetFlags();

	private:
		unsigned int id;
		float x;
		float y;
		float z;
		unsigned char flags;
	};
}

#endif