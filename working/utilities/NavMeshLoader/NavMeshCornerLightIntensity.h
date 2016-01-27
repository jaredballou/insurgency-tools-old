#ifndef __war3source_navmeshcornerlightintensity_h__
#define __war3source_navmeshcornerlightintensity_h__

#include "INavMeshCornerLightIntensity.h"

namespace War3Source {
	class NavMeshCornerLightIntensity : public INavMeshCornerLightIntensity {
	public:
		NavMeshCornerLightIntensity(NavCornerType cornerType, float lightIntensity);
		~NavMeshCornerLightIntensity();

		NavCornerType GetCornerType();
		float GetLightIntensity();

	private:
		NavCornerType cornerType;
		float lightIntensity;
	};
}

#endif