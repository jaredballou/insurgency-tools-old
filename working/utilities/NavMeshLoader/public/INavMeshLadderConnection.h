#ifndef __war3source_inavmeshladderconnection_h__
#define __war3source_inavmeshladderconnection_h__

#include "NavLadderDirType.h"

namespace War3Source {
	class INavMeshLadderConnection {
	public:
		virtual unsigned int GetConnectingLadderID() = 0;
		virtual NavLadderDirType GetDirection() = 0;
	};
}

#endif