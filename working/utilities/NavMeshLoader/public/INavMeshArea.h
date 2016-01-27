#ifndef __war3source_inavmesharea_h__
#define __war3source_inavmesharea_h__

#include "IList.h"
#include "INavMeshConnection.h"
#include "INavMeshHidingSpot.h"
#include "INavMeshEncounterPath.h"
#include "INavMeshLadderConnection.h"
#include "INavMeshCornerLightIntensity.h"
#include "INavMeshVisibleArea.h"

namespace War3Source {
	class INavMeshArea {
	public:
		virtual unsigned int GetID() = 0;
		virtual unsigned int GetFlags() = 0;
		virtual unsigned int GetPlaceID() = 0;

		virtual float GetNWExtentX() = 0;
		virtual float GetNWExtentY() = 0;
		virtual float GetNWExtentZ() = 0;

		virtual float GetSEExtentX() = 0;
		virtual float GetSEExtentY() = 0;
		virtual float GetSEExtentZ() = 0;

		virtual float GetEarliestOccupyTimeFirstTeam() = 0;
		virtual float GetEarliestOccupyTimeSecondTeam() = 0;

		virtual float GetNECornerZ() = 0;
		virtual float GetSWCornerZ() = 0;

		virtual IList<INavMeshConnection*> *GetConnections() = 0;
		virtual IList<INavMeshHidingSpot*> *GetHidingSpots() = 0;
		virtual IList<INavMeshEncounterPath*> *GetEncounterPaths() = 0;
		virtual IList<INavMeshLadderConnection*> *GetLadderConnections() = 0;
		virtual IList<INavMeshCornerLightIntensity*> *GetCornerLightIntensities() = 0;
		virtual IList<INavMeshVisibleArea*> *GetVisibleAreas() = 0;

		virtual unsigned int GetInheritVisibilityFromAreaID() = 0;

		virtual unsigned char GetUnk01() = 0;
	};
}

#endif