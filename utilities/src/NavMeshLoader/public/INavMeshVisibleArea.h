#ifndef __war3source_inavmeshvisiblearea_h__
#define __war3source_inavmeshvisiblearea_h__

namespace War3Source {
	class INavMeshVisibleArea {
	public:
		virtual unsigned int GetVisibleAreaID() = 0;
		virtual unsigned char GetAttributes() = 0;
	};
}

#endif