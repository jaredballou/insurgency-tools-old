#ifndef __war3source_inavmeshplace_h__
#define __war3source_inavmeshplace_h__

namespace War3Source {
	class INavMeshPlace {
	public:
		virtual const char *GetName() = 0;
		virtual unsigned int GetID() = 0;
	};
}

#endif