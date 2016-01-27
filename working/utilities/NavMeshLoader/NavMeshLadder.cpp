#include "NavMeshLadder.h"

namespace War3Source {
	NavMeshLadder::NavMeshLadder(unsigned int id, float width, float length, float topX, float topY, float topZ,
		float bottomX, float bottomY, float bottomZ, NavDirType direction,
		unsigned int topForwardAreaID, unsigned int topLeftAreaID, unsigned int topRightAreaID, unsigned int topBehindAreaID, unsigned int bottomAreaID) {
			this->id = id;
			this->width = width;
			this->length = length;
			this->topX = topX;
			this->topY = topY;
			this->topZ = topZ;
			this->bottomX = bottomX;
			this->bottomY = bottomY;
			this->bottomZ = bottomZ;
			this->direction = direction;
			this->topForwardAreaID = topForwardAreaID;
			this->topLeftAreaID = topLeftAreaID;
			this->topRightAreaID = topRightAreaID;
			this->topBehindAreaID = topBehindAreaID;
			this->bottomAreaID = bottomAreaID;
	}

	NavMeshLadder::~NavMeshLadder() {
	}

	unsigned int NavMeshLadder::GetID() {
		return this->id;
	}

	float NavMeshLadder::GetWidth() {
		return this->width;
	}

	float NavMeshLadder::GetLength() {
		return this->length;
	}

	float NavMeshLadder::GetTopX() {
		return this->topX;
	}

	float NavMeshLadder::GetTopY() {
		return this->topY;
	}

	float NavMeshLadder::GetTopZ() {
		return this->topZ;
	}

	float NavMeshLadder::GetBottomX() {
		return this->bottomX;
	}

	float NavMeshLadder::GetBottomY() {
		return this->bottomY;
	}

	float NavMeshLadder::GetBottomZ() {
		return this->bottomZ;
	}

	NavDirType NavMeshLadder::GetDirection() {
		return this->direction;
	}
	
	unsigned int NavMeshLadder::GetTopForwardAreaID() {
		return this->topForwardAreaID;
	}

	unsigned int NavMeshLadder::GetTopLeftAreaID() {
		return this->topLeftAreaID;
	}

	unsigned int NavMeshLadder::GetTopRightAreaID() {
		return this->topRightAreaID;
	}

	unsigned int NavMeshLadder::GetTopBehindAreaID() {
		return this->topBehindAreaID;
	}

	unsigned int NavMeshLadder::GetBottomAreaID() {
		return this->bottomAreaID;
	}
}