#ifndef __war3source_inavmash_h__
#define __war3source_inavmash_h__

#include "IList.h"
#include "INavMeshPlace.h"
#include "INavMeshArea.h"
#include "INavMeshLadder.h"

namespace War3Source {
	class INavMesh {
	public:
		virtual unsigned int GetMagicNumber() = 0;
		virtual unsigned int GetVersion() = 0;
		virtual unsigned int GetSubVersion() = 0;
		virtual unsigned int GetSaveBSPSize() = 0;
		virtual bool IsMeshAnalyzed() = 0;
		virtual IList<INavMeshPlace*> *GetPlaces() = 0;
		virtual IList<INavMeshArea*> *GetAreas() = 0;
		virtual IList<INavMeshLadder*> *GetLadders() = 0;
	};
}

#endif