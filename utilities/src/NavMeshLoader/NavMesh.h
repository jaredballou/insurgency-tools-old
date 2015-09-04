#ifndef __war3source_navmesh_h__
#define __war3source_navmesh_h__

#include "INavMesh.h"
#include "IList.h"
#include "INavMeshPlace.h"
#include "INavMeshLadder.h"
#include "INavMeshArea.h"

namespace War3Source {
	class NavMesh : public INavMesh {
	public:
		NavMesh(unsigned int magicNumber, unsigned int version, unsigned int subVersion, unsigned int saveBSPSize, bool isMeshAnalyzed,
			IList<INavMeshPlace*> *places, IList<INavMeshArea*> *areas, IList<INavMeshLadder*> *ladders);

		~NavMesh();

		unsigned int GetMagicNumber();
		unsigned int GetVersion();
		unsigned int GetSubVersion();
		unsigned int GetSaveBSPSize();
		bool IsMeshAnalyzed();
		IList<INavMeshPlace*> *GetPlaces();
		IList<INavMeshArea*> *GetAreas();
		IList<INavMeshLadder*> *GetLadders();

	private:
		unsigned int magicNumber;
		unsigned int version;
		unsigned int subVersion;
		unsigned int saveBSPSize;
		bool isMeshAnalyzed;
		IList<INavMeshPlace*> *places;
		IList<INavMeshArea*> *areas;
		IList<INavMeshLadder*> *ladders;
	};
}

#endif