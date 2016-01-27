#include "NavMeshVisibleArea.h"

namespace War3Source {
	NavMeshVisibleArea::NavMeshVisibleArea(unsigned int visibleAreaID, unsigned char attributes) {
		this->visibleAreaID = visibleAreaID;
		this->attributes = attributes;
	}

	NavMeshVisibleArea::~NavMeshVisibleArea() {
	}

	unsigned int NavMeshVisibleArea::GetVisibleAreaID() {
		return this->visibleAreaID;
	}

	unsigned char NavMeshVisibleArea::GetAttributes() {
		return this->attributes;
	}
}