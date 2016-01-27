#ifndef __war3source_navmeshvisiblearea_h__
#define __war3source_navmeshvisiblearea_h__

#include "INavMeshVisibleArea.h"

namespace War3Source {
	class NavMeshVisibleArea : public INavMeshVisibleArea {
	public:
		NavMeshVisibleArea(unsigned int visibleAreaID, unsigned char attributes);
		~NavMeshVisibleArea();

		unsigned int GetVisibleAreaID();
		unsigned char GetAttributes();

	private:
		unsigned int visibleAreaID;
		unsigned char attributes;
	};
}

#endif