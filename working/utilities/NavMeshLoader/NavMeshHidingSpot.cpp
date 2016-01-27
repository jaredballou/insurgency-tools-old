#include "NavMeshHidingSpot.h"

namespace War3Source {
	NavMeshHidingSpot::NavMeshHidingSpot(unsigned int id, float x, float y, float z, unsigned char flags) {
		this->id = id;
		this->x = x;
		this->y = y;
		this->z = z;
		this->flags = flags;
	}

	NavMeshHidingSpot::~NavMeshHidingSpot() {
	}

	unsigned int NavMeshHidingSpot::GetID() {
		return this->id;
	}

	float NavMeshHidingSpot::GetX() {
		return this->x;
	}

	float NavMeshHidingSpot::GetY() {
		return this->y;
	}

	float NavMeshHidingSpot::GetZ() {
		return this->z;
	}

	unsigned char NavMeshHidingSpot::GetFlags() {
		return this->flags;
	}
}