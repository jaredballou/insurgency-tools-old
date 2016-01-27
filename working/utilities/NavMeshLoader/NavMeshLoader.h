#ifndef __war3source_navmeshloader_h__
#define __war3source_navmeshloader_h__

#include "INavMeshLoader.h"
#include "INavMesh.h"
#include <iostream>

namespace War3Source {
	class NavMeshLoader : public INavMeshLoader {
	public:
		NavMeshLoader(const char *mapName);
		~NavMeshLoader();

		INavMesh *Load(char *error, int errorMaxlen);
	
	private:
		unsigned int ReadData(void *output, unsigned int elementSize, unsigned int elementCount, FILE *fileHandle);

	private:
		char mapName[100];
		unsigned int bytesRead;
	};
}

#endif