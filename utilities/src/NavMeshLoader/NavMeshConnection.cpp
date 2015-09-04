#include "NavMeshConnection.h"

namespace War3Source {
	NavMeshConnection::NavMeshConnection(unsigned int connectingAreaID, NavDirType direction) {
		this->connectingAreaID = connectingAreaID;
		this->direction = direction;
	}

	NavMeshConnection::~NavMeshConnection() {
	}

	unsigned int NavMeshConnection::GetConnectingAreaID() {
		return this->connectingAreaID;
	}

	NavDirType NavMeshConnection::GetDirection() {
		return this->direction;
	}
}