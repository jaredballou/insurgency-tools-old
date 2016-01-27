#ifndef __war3source_navmeshconnection_h__
#define __war3source_navmeshconnection_h__

#include "INavMeshConnection.h"

namespace War3Source {
	class NavMeshConnection : public INavMeshConnection {
	public:
		NavMeshConnection(unsigned int connectingAreaID, NavDirType direction);
		~NavMeshConnection();

		unsigned int GetConnectingAreaID();
		NavDirType GetDirection();

	private:
		unsigned int connectingAreaID;
		NavDirType direction;
	};
}

#endif