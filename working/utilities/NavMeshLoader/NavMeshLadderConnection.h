#ifndef __war3source_navmeshladderconnection_h__
#define __war3source_navmeshladderconnection_h__

#include "INavMeshLadderConnection.h"

namespace War3Source {
	class NavMeshLadderConnection : public INavMeshLadderConnection {
	public:
		NavMeshLadderConnection(unsigned int connectingLadderID, NavLadderDirType direction);
		~NavMeshLadderConnection();

		unsigned int GetConnectingLadderID();
		NavLadderDirType GetDirection();
	
	private:
		unsigned int connectingLadderID;
		NavLadderDirType direction;
	};
}

#endif