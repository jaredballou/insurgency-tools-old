#ifndef __war3source_inavmeshcornerlightintensity_h__
#define __war3source_inavmeshcornerlightintensity_h__

#include "NavCornerType.h"

namespace War3Source {
	class INavMeshCornerLightIntensity {
	public:
		virtual NavCornerType GetCornerType() = 0;
		virtual float GetLightIntensity() = 0;
	};
}

#endif