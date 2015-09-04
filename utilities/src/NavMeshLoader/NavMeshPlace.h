#ifndef __war3source_navmeshplace_h__
#define __war3source_navmeshplace_h__

#include "INavMeshPlace.h"

namespace War3Source {
	class NavMeshPlace : public INavMeshPlace {
	public:
		NavMeshPlace(unsigned int id, const char *name);
		~NavMeshPlace();

		const char *GetName();
		unsigned int GetID();

	private:
		unsigned int id;
		char name[256];
	};
}

#endif