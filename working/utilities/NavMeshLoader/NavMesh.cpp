#include "NavMesh.h"

namespace War3Source {
	NavMesh::NavMesh(unsigned int magicNumber, unsigned int version, unsigned int subVersion, unsigned int saveBSPSize, bool isMeshAnalyzed,
		IList<INavMeshPlace*> *places, IList<INavMeshArea*> *areas, IList<INavMeshLadder*> *ladders) {
			this->magicNumber = magicNumber;
			this->version = version;
			this->subVersion = subVersion;
			this->saveBSPSize = saveBSPSize;
			this->isMeshAnalyzed = isMeshAnalyzed;
			this->places = places;
			this->areas = areas;
			this->ladders = ladders;
	}

	NavMesh::~NavMesh() {
	}

	unsigned int NavMesh::GetMagicNumber() {
		return this->magicNumber;
	}

	unsigned int NavMesh::GetVersion() {
		return this->version;
	}

	unsigned int NavMesh::GetSubVersion() {
		return this->subVersion;
	}

	unsigned int NavMesh::GetSaveBSPSize() {
		return this->saveBSPSize;
	}

	bool NavMesh::IsMeshAnalyzed() {
		return this->isMeshAnalyzed;
	}

	IList<INavMeshPlace*> *NavMesh::GetPlaces() {
		return this->places;
	}

	IList<INavMeshArea*> *NavMesh::GetAreas() {
		return this->areas;
	}

	IList<INavMeshLadder*> *NavMesh::GetLadders() {
		return this->ladders;
	}
}