#ifndef __war3source_inavmeshloader_h__
#define __war3source_inavmeshloader_h__

#include "INavMesh.h"

namespace War3Source {
	class INavMeshLoader {
	public:
		virtual INavMesh *Load(char *error, int errorMaxlen) = 0;
	};
}

#endif