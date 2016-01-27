#include "NavMeshPlace.h"
#include <iostream>

namespace War3Source {
	NavMeshPlace::NavMeshPlace(unsigned int id, const char *name) {
		this->id = id;
		strcpy_s(this->name, sizeof(this->name), name);
	}

	NavMeshPlace::~NavMeshPlace() {
	}

	const char *NavMeshPlace::GetName() {
		return this->name;
	}

	unsigned int NavMeshPlace::GetID() {
		return this->id;
	}
}