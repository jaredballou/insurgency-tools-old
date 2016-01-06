bool:NavMeshLoad(const String:sMapName[])
{
	decl String:sNavFilePath[PLATFORM_MAX_PATH];
	Format(sNavFilePath, sizeof(sNavFilePath), "maps\\%s.nav", sMapName);
	
	new Handle:hFile = OpenFile(sNavFilePath, "rb");
	if (hFile == INVALID_HANDLE)
	{
		new EngineVersion:iEngineVersion;
		new bool:bFound = false;
		
		if (GetFeatureStatus(FeatureType_Native, "GetEngineVersion") == FeatureStatus_Available)
		{
			iEngineVersion = GetEngineVersion();
			
			switch (iEngineVersion)
			{
				case Engine_CSGO:
				{
					// Search addon directories.
					new Handle:hDir = OpenDirectory("addons");
					if (hDir != INVALID_HANDLE)
					{
						LogMessage("Couldn't find .nav file in maps folder, checking addon folders...");
						
						decl String:sFolderName[PLATFORM_MAX_PATH];
						decl FileType:iFileType;
						while (ReadDirEntry(hDir, sFolderName, sizeof(sFolderName), iFileType))
						{
							if (iFileType == FileType_Directory)
							{
								Format(sNavFilePath, sizeof(sNavFilePath), "addons\\%s\\maps\\%s.nav", sFolderName, sMapName);
								hFile = OpenFile(sNavFilePath, "rb");
								if (hFile != INVALID_HANDLE)
								{
									bFound = true;
									break;
								}
							}
						}
						
						CloseHandle(hDir);
					}
				}
				case Engine_TF2:
				{
					// Search custom directories.
					new Handle:hDir = OpenDirectory("custom");
					if (hDir != INVALID_HANDLE)
					{
						LogMessage("Couldn't find .nav file in maps folder, checking custom folders...");
					
						decl String:sFolderName[PLATFORM_MAX_PATH];
						decl FileType:iFileType;
						while (ReadDirEntry(hDir, sFolderName, sizeof(sFolderName), iFileType))
						{
							if (iFileType == FileType_Directory)
							{
								Format(sNavFilePath, sizeof(sNavFilePath), "custom\\%s\\maps\\%s.nav", sFolderName, sMapName);
								hFile = OpenFile(sNavFilePath, "rb");
								if (hFile != INVALID_HANDLE)
								{
									bFound = true;
									break;
								}
							}
						}
						
						CloseHandle(hDir);
					}
				}
			}
		}
		
		if (!bFound)
		{
			LogMessage(".NAV file for %s could not be found", sMapName);
			return false;
		}
	}
	
	LogMessage("Found .NAV file in %s", sNavFilePath);
	
	// Get magic number.
	new iNavMagicNumber;
	new iElementsRead = ReadFileCell(hFile, iNavMagicNumber, UNSIGNED_INT_BYTE_SIZE);
	
	if (iElementsRead != 1)
	{
		CloseHandle(hFile);
		LogError("Error reading magic number value from navigation mesh: %s", sNavFilePath);
		return false;
	}
	
	if (iNavMagicNumber != NAV_MAGIC_NUMBER)
	{
		CloseHandle(hFile);
		LogError("Invalid magic number value from navigation mesh: %s [%p]", sNavFilePath, iNavMagicNumber);
		return false;
	}
	
	// Get the version.
	new iNavVersion;
	iElementsRead = ReadFileCell(hFile, iNavVersion, UNSIGNED_INT_BYTE_SIZE);
	
	if (iElementsRead != 1)
	{
		CloseHandle(hFile);
		LogError("Error reading version number from navigation mesh: %s", sNavFilePath);
		return false;
	}
	
	if (iNavVersion < 6 || iNavVersion > 16)
	{
		CloseHandle(hFile);
		LogError("Invalid version number value from navigation mesh: %s [%d]", sNavFilePath, iNavVersion);
		return false;
	}
	
	// Get the sub version, if supported.
	new iNavSubVersion;
	if (iNavVersion >= 10)
	{
		ReadFileCell(hFile, iNavSubVersion, UNSIGNED_INT_BYTE_SIZE);
	}
	
	// Get the save bsp size.
	new iNavSaveBspSize;
	if (iNavVersion >= 4)
	{
		ReadFileCell(hFile, iNavSaveBspSize, UNSIGNED_INT_BYTE_SIZE);
	}
	
	// Check if the nav mesh was analyzed.
	new iNavMeshAnalyzed;
	if (iNavVersion >= 14)
	{
		ReadFileCell(hFile, iNavMeshAnalyzed, UNSIGNED_CHAR_BYTE_SIZE);
		LogMessage("Is mesh analyzed: %d", iNavMeshAnalyzed);
	}
	
	LogMessage("Nav version: %d; SubVersion: %d (v10+); BSPSize: %d; MagicNumber: %d", iNavVersion, iNavSubVersion, iNavSaveBspSize, iNavMagicNumber);
	
	new iPlaceCount;
	ReadFileCell(hFile, iPlaceCount, UNSIGNED_SHORT_BYTE_SIZE);
	LogMessage("Place count: %d", iPlaceCount);
	
	// Parse through places.
	// TF2 doesn't use places, but CS:S does.
	for (new iPlaceIndex = 0; iPlaceIndex < iPlaceCount; iPlaceIndex++) 
	{
		new iPlaceStringSize;
		ReadFileCell(hFile, iPlaceStringSize, UNSIGNED_SHORT_BYTE_SIZE);
		
		new String:sPlaceName[256];
		ReadFileString(hFile, sPlaceName, sizeof(sPlaceName), iPlaceStringSize);
		
		PushArrayString(g_hNavMeshPlaces, sPlaceName);
		
		//LogMessage("Parsed place \"%s\" [index: %d]", sPlaceName, iPlaceIndex);
	}
	
	// Get any unnamed areas.
	new iNavUnnamedAreas;
	if (iNavVersion > 11)
	{
		ReadFileCell(hFile, iNavUnnamedAreas, UNSIGNED_CHAR_BYTE_SIZE);
		LogMessage("Has unnamed areas: %s", iNavUnnamedAreas ? "true" : "false");
	}
	
	// Get area count.
	new iAreaCount;
	ReadFileCell(hFile, iAreaCount, UNSIGNED_INT_BYTE_SIZE);
	
	LogMessage("Area count: %d", iAreaCount);
	
	new Float:flExtentLow[2] = { 99999999.9, 99999999.9 };
	new bool:bExtentLowX = false;
	new bool:bExtentLowY = false;
	new Float:flExtentHigh[2] = { -99999999.9, -99999999.9 };
	new bool:bExtentHighX = false;
	new bool:bExtentHighY = false;
	
	if (iAreaCount > 0)
	{
		// The following are index values that will serve as starting and ending markers for areas
		// to determine what is theirs.
		
		// This is to avoid iteration of the whole area set to reduce lookup time.
		
		new iGlobalConnectionsStartIndex = 0;
		new iGlobalHidingSpotsStartIndex = 0;
		new iGlobalEncounterPathsStartIndex = 0;
		new iGlobalEncounterSpotsStartIndex = 0;
		new iGlobalLadderConnectionsStartIndex = 0;
		new iGlobalVisibleAreasStartIndex = 0;
		
		for (new iAreaIndex = 0; iAreaIndex < iAreaCount; iAreaIndex++)
		{
			new iAreaID;
			new Float:x1, Float:y1, Float:z1, Float:x2, Float:y2, Float:z2;
			new iAreaFlags;
			new iInheritVisibilityFrom;
			new iHidingSpotCount;
			new iVisibleAreaCount;
			new Float:flEarliestOccupyTimeFirstTeam;
			new Float:flEarliestOccupyTimeSecondTeam;
			new Float:flNECornerZ;
			new Float:flSWCornerZ;
			new iPlaceID;
			new unk01;
			
			ReadFileCell(hFile, iAreaID, UNSIGNED_INT_BYTE_SIZE);
			
			//LogMessage("Area ID: %d", iAreaID);
			
			if (iNavVersion <= 8) 
			{
				ReadFileCell(hFile, iAreaFlags, UNSIGNED_CHAR_BYTE_SIZE);
			}
			else if (iNavVersion < 13) 
			{
				ReadFileCell(hFile, iAreaFlags, UNSIGNED_SHORT_BYTE_SIZE);
			}
			else 
			{
				ReadFileCell(hFile, iAreaFlags, UNSIGNED_INT_BYTE_SIZE);
			}
			
			//LogMessage("Area Flags: %d", iAreaFlags);
			
			ReadFileCell(hFile, _:x1, FLOAT_BYTE_SIZE);
			ReadFileCell(hFile, _:y1, FLOAT_BYTE_SIZE);
			ReadFileCell(hFile, _:z1, FLOAT_BYTE_SIZE);
			ReadFileCell(hFile, _:x2, FLOAT_BYTE_SIZE);
			ReadFileCell(hFile, _:y2, FLOAT_BYTE_SIZE);
			ReadFileCell(hFile, _:z2, FLOAT_BYTE_SIZE);
			
			//LogMessage("Area extent: (%f, %f, %f), (%f, %f, %f)", x1, y1, z1, x2, y2, z2);
			
			if (!bExtentLowX || x1 < flExtentLow[0]) 
			{
				bExtentLowX = true;
				flExtentLow[0] = x1;
			}
			
			if (!bExtentLowY || y1 < flExtentLow[1]) 
			{
				bExtentLowY = true;
				flExtentLow[1] = y1;
			}
			
			if (!bExtentHighX || x2 > flExtentHigh[0]) 
			{
				bExtentHighX = true;
				flExtentHigh[0] = x2;
			}
			
			if (!bExtentHighY || y2 > flExtentHigh[1]) 
			{
				bExtentHighY = true;
				flExtentHigh[1] = y2;
			}
			
			// Cache the center position.
			decl Float:flAreaCenter[3];
			flAreaCenter[0] = (x1 + x2) / 2.0;
			flAreaCenter[1] = (y1 + y2) / 2.0;
			flAreaCenter[2] = (z1 + z2) / 2.0;
			
			new Float:flInvDxCorners = 0.0; 
			new Float:flInvDyCorners = 0.0;
			
			if ((x2 - x1) > 0.0 && (y2 - y1) > 0.0)
			{
				flInvDxCorners = 1.0 / (x2 - x1);
				flInvDyCorners = 1.0 / (y2 - y1);
			}
			
			ReadFileCell(hFile, _:flNECornerZ, FLOAT_BYTE_SIZE);
			ReadFileCell(hFile, _:flSWCornerZ, FLOAT_BYTE_SIZE);
			
			//LogMessage("Corners: NW(%f), SW(%f)", flNECornerZ, flSWCornerZ);
			
			new iConnectionsStartIndex = -1;
			new iConnectionsEndIndex = -1;
			
			// Find connections.
			for (new iDirection = 0; iDirection < NAV_DIR_COUNT; iDirection++)
			{
				new iConnectionCount;
				ReadFileCell(hFile, iConnectionCount, UNSIGNED_INT_BYTE_SIZE);
				
				//LogMessage("Connection count: %d", iConnectionCount);
				
				if (iConnectionCount > 0)
				{
					if (iConnectionsStartIndex == -1) iConnectionsStartIndex = iGlobalConnectionsStartIndex;
				
					for (new iConnectionIndex = 0; iConnectionIndex < iConnectionCount; iConnectionIndex++) 
					{
						iConnectionsEndIndex = iGlobalConnectionsStartIndex;
					
						new iConnectingAreaID;
						ReadFileCell(hFile, iConnectingAreaID, UNSIGNED_INT_BYTE_SIZE);
						
						new iIndex = PushArrayCell(g_hNavMeshAreaConnections, iConnectingAreaID);
						SetArrayCell(g_hNavMeshAreaConnections, iIndex, iDirection, NavMeshConnection_Direction);
						
						iGlobalConnectionsStartIndex++;
					}
				}
			}
			
			// Get hiding spots.
			ReadFileCell(hFile, iHidingSpotCount, UNSIGNED_CHAR_BYTE_SIZE);
			
			//LogMessage("Hiding spot count: %d", iHidingSpotCount);
			
			new iHidingSpotsStartIndex = -1;
			new iHidingSpotsEndIndex = -1;
			
			if (iHidingSpotCount > 0)
			{
				iHidingSpotsStartIndex = iGlobalHidingSpotsStartIndex;
				
				for (new iHidingSpotIndex = 0; iHidingSpotIndex < iHidingSpotCount; iHidingSpotIndex++)
				{
					iHidingSpotsEndIndex = iGlobalHidingSpotsStartIndex;
				
					new iHidingSpotID;
					ReadFileCell(hFile, iHidingSpotID, UNSIGNED_INT_BYTE_SIZE);
					
					new Float:flHidingSpotX, Float:flHidingSpotY, Float:flHidingSpotZ;
					ReadFileCell(hFile, _:flHidingSpotX, FLOAT_BYTE_SIZE);
					ReadFileCell(hFile, _:flHidingSpotY, FLOAT_BYTE_SIZE);
					ReadFileCell(hFile, _:flHidingSpotZ, FLOAT_BYTE_SIZE);
					
					new iHidingSpotFlags;
					ReadFileCell(hFile, iHidingSpotFlags, UNSIGNED_CHAR_BYTE_SIZE);
					
					new iIndex = PushArrayCell(g_hNavMeshAreaHidingSpots, iHidingSpotID);
					SetArrayCell(g_hNavMeshAreaHidingSpots, iIndex, flHidingSpotX, NavMeshHidingSpot_X);
					SetArrayCell(g_hNavMeshAreaHidingSpots, iIndex, flHidingSpotY, NavMeshHidingSpot_Y);
					SetArrayCell(g_hNavMeshAreaHidingSpots, iIndex, flHidingSpotZ, NavMeshHidingSpot_Z);
					SetArrayCell(g_hNavMeshAreaHidingSpots, iIndex, iHidingSpotFlags, NavMeshHidingSpot_Flags);
					
					iGlobalHidingSpotsStartIndex++;
					
					//LogMessage("Parsed hiding spot (%f, %f, %f) with ID [%d] and flags [%d]", flHidingSpotX, flHidingSpotY, flHidingSpotZ, iHidingSpotID, iHidingSpotFlags);
				}
			}
			
			// Get approach areas (old version, only used to read data)
			if (iNavVersion < 15)
			{
				new iApproachAreaCount;
				ReadFileCell(hFile, iApproachAreaCount, UNSIGNED_CHAR_BYTE_SIZE);
				
				for (new iApproachAreaIndex = 0; iApproachAreaIndex < iApproachAreaCount; iApproachAreaIndex++)
				{
					new iApproachHereID;
					ReadFileCell(hFile, iApproachHereID, UNSIGNED_INT_BYTE_SIZE);
					
					new iApproachPrevID;
					ReadFileCell(hFile, iApproachPrevID, UNSIGNED_INT_BYTE_SIZE);
					
					new iApproachType;
					ReadFileCell(hFile, iApproachType, UNSIGNED_CHAR_BYTE_SIZE);
					
					new iApproachNextID;
					ReadFileCell(hFile, iApproachNextID, UNSIGNED_INT_BYTE_SIZE);
					
					new iApproachHow;
					ReadFileCell(hFile, iApproachHow, UNSIGNED_CHAR_BYTE_SIZE);
				}
			}
			
			// Get encounter paths.
			new iEncounterPathCount;
			ReadFileCell(hFile, iEncounterPathCount, UNSIGNED_INT_BYTE_SIZE);
			
			//LogMessage("Encounter Path Count: %d", iEncounterPathCount);
			
			new iEncounterPathsStartIndex = -1;
			new iEncounterPathsEndIndex = -1;
			
			if (iEncounterPathCount > 0)
			{
				iEncounterPathsStartIndex = iGlobalEncounterPathsStartIndex;
			
				for (new iEncounterPathIndex = 0; iEncounterPathIndex < iEncounterPathCount; iEncounterPathIndex++)
				{
					iEncounterPathsEndIndex = iGlobalEncounterPathsStartIndex;
				
					new iEncounterFromID;
					ReadFileCell(hFile, iEncounterFromID, UNSIGNED_INT_BYTE_SIZE);
					
					new iEncounterFromDirection;
					ReadFileCell(hFile, iEncounterFromDirection, UNSIGNED_CHAR_BYTE_SIZE);
					
					new iEncounterToID;
					ReadFileCell(hFile, iEncounterToID, UNSIGNED_INT_BYTE_SIZE);
					
					new iEncounterToDirection;
					ReadFileCell(hFile, iEncounterToDirection, UNSIGNED_CHAR_BYTE_SIZE);
					
					new iEncounterSpotCount;
					ReadFileCell(hFile, iEncounterSpotCount, UNSIGNED_CHAR_BYTE_SIZE);
					
					//LogMessage("Encounter [from ID %d] [from dir %d] [to ID %d] [to dir %d] [spot count %d]", iEncounterFromID, iEncounterFromDirection, iEncounterToID, iEncounterToDirection, iEncounterSpotCount);
					
					new iEncounterSpotsStartIndex = -1;
					new iEncounterSpotsEndIndex = -1;
					
					if (iEncounterSpotCount > 0)
					{
						iEncounterSpotsStartIndex = iGlobalEncounterSpotsStartIndex;
					
						for (new iEncounterSpotIndex = 0; iEncounterSpotIndex < iEncounterSpotCount; iEncounterSpotIndex++)
						{
							iEncounterSpotsEndIndex = iGlobalEncounterSpotsStartIndex;
						
							new iEncounterSpotOrderID;
							ReadFileCell(hFile, iEncounterSpotOrderID, UNSIGNED_INT_BYTE_SIZE);
							
							new iEncounterSpotT;
							ReadFileCell(hFile, iEncounterSpotT, UNSIGNED_CHAR_BYTE_SIZE);
							
							new Float:flEncounterSpotParametricDistance = float(iEncounterSpotT) / 255.0;
							
							new iIndex = PushArrayCell(g_hNavMeshAreaEncounterSpots, iEncounterSpotOrderID);
							SetArrayCell(g_hNavMeshAreaEncounterSpots, iIndex, flEncounterSpotParametricDistance, NavMeshEncounterSpot_ParametricDistance);
							
							iGlobalEncounterSpotsStartIndex++;
							
							//LogMessage("Encounter spot [order id %d] and [T %d]", iEncounterSpotOrderID, iEncounterSpotT);
						}
					}
					
					new iIndex = PushArrayCell(g_hNavMeshAreaEncounterPaths, iEncounterFromID);
					SetArrayCell(g_hNavMeshAreaEncounterPaths, iIndex, iEncounterFromDirection, NavMeshEncounterPath_FromDirection);
					SetArrayCell(g_hNavMeshAreaEncounterPaths, iIndex, iEncounterToID, NavMeshEncounterPath_ToID);
					SetArrayCell(g_hNavMeshAreaEncounterPaths, iIndex, iEncounterToDirection, NavMeshEncounterPath_ToDirection);
					SetArrayCell(g_hNavMeshAreaEncounterPaths, iIndex, iEncounterSpotsStartIndex, NavMeshEncounterPath_SpotsStartIndex);
					SetArrayCell(g_hNavMeshAreaEncounterPaths, iIndex, iEncounterSpotsEndIndex, NavMeshEncounterPath_SpotsEndIndex);
					
					iGlobalEncounterPathsStartIndex++;
				}
			}
			
			ReadFileCell(hFile, iPlaceID, UNSIGNED_SHORT_BYTE_SIZE);
			
			//LogMessage("Place ID: %d", iPlaceID);
			
			// Get ladder connections.
			
			new iLadderConnectionsStartIndex = -1;
			new iLadderConnectionsEndIndex = -1;
			
			for (new iLadderDirection = 0; iLadderDirection < NAV_LADDER_DIR_COUNT; iLadderDirection++)
			{
				new iLadderConnectionCount;
				ReadFileCell(hFile, iLadderConnectionCount, UNSIGNED_INT_BYTE_SIZE);
				
				//LogMessage("Ladder Connection Count: %d", iLadderConnectionCount);
				
				if (iLadderConnectionCount > 0)
				{
					iLadderConnectionsStartIndex = iGlobalLadderConnectionsStartIndex;
				
					for (new iLadderConnectionIndex = 0; iLadderConnectionIndex < iLadderConnectionCount; iLadderConnectionIndex++)
					{
						iLadderConnectionsEndIndex = iGlobalLadderConnectionsStartIndex;
					
						new iLadderConnectionID;
						ReadFileCell(hFile, iLadderConnectionID, UNSIGNED_INT_BYTE_SIZE);
						
						new iIndex = PushArrayCell(g_hNavMeshAreaLadderConnections, iLadderConnectionID);
						SetArrayCell(g_hNavMeshAreaLadderConnections, iIndex, iLadderDirection, NavMeshLadderConnection_Direction);
						
						iGlobalLadderConnectionsStartIndex++;
						
						//LogMessage("Parsed ladder connect [ID %d]\n", iLadderConnectionID);
					}
				}
			}
			
			ReadFileCell(hFile, _:flEarliestOccupyTimeFirstTeam, FLOAT_BYTE_SIZE);
			ReadFileCell(hFile, _:flEarliestOccupyTimeSecondTeam, FLOAT_BYTE_SIZE);
			
			new Float:flNavCornerLightIntensityNW;
			new Float:flNavCornerLightIntensityNE;
			new Float:flNavCornerLightIntensitySE;
			new Float:flNavCornerLightIntensitySW;
			
			new iVisibleAreasStartIndex = -1;
			new iVisibleAreasEndIndex = -1;
			
			if (iNavVersion >= 11)
			{
				ReadFileCell(hFile, _:flNavCornerLightIntensityNW, FLOAT_BYTE_SIZE);
				ReadFileCell(hFile, _:flNavCornerLightIntensityNE, FLOAT_BYTE_SIZE);
				ReadFileCell(hFile, _:flNavCornerLightIntensitySE, FLOAT_BYTE_SIZE);
				ReadFileCell(hFile, _:flNavCornerLightIntensitySW, FLOAT_BYTE_SIZE);
				
				if (iNavVersion >= 16)
				{
					ReadFileCell(hFile, iVisibleAreaCount, UNSIGNED_INT_BYTE_SIZE);
					
					//LogMessage("Visible area count: %d", iVisibleAreaCount);
					
					if (iVisibleAreaCount > 0)
					{
						iVisibleAreasStartIndex = iGlobalVisibleAreasStartIndex;
					
						for (new iVisibleAreaIndex = 0; iVisibleAreaIndex < iVisibleAreaCount; iVisibleAreaIndex++)
						{
							iVisibleAreasEndIndex = iGlobalVisibleAreasStartIndex;
						
							new iVisibleAreaID;
							ReadFileCell(hFile, iVisibleAreaID, UNSIGNED_INT_BYTE_SIZE);
							
							new iVisibleAreaAttributes;
							ReadFileCell(hFile, iVisibleAreaAttributes, UNSIGNED_CHAR_BYTE_SIZE);
							
							new iIndex = PushArrayCell(g_hNavMeshAreaVisibleAreas, iVisibleAreaID);
							SetArrayCell(g_hNavMeshAreaVisibleAreas, iIndex, iVisibleAreaAttributes, NavMeshVisibleArea_Attributes);
							
							iGlobalVisibleAreasStartIndex++;
							
							//LogMessage("Parsed visible area [%d] with attr [%d]", iVisibleAreaID, iVisibleAreaAttributes);
						}
					}
					
					ReadFileCell(hFile, iInheritVisibilityFrom, UNSIGNED_INT_BYTE_SIZE);
					
					//LogMessage("Inherit visibilty from: %d", iInheritVisibilityFrom);
					
					ReadFileCell(hFile, unk01, UNSIGNED_INT_BYTE_SIZE);
				}
			}
			
			new iIndex = PushArrayCell(g_hNavMeshAreas, iAreaID);
			SetArrayCell(g_hNavMeshAreas, iIndex, iAreaFlags, NavMeshArea_Flags);
			SetArrayCell(g_hNavMeshAreas, iIndex, iPlaceID, NavMeshArea_PlaceID);
			SetArrayCell(g_hNavMeshAreas, iIndex, x1, NavMeshArea_X1);
			SetArrayCell(g_hNavMeshAreas, iIndex, y1, NavMeshArea_Y1);
			SetArrayCell(g_hNavMeshAreas, iIndex, z1, NavMeshArea_Z1);
			SetArrayCell(g_hNavMeshAreas, iIndex, x2, NavMeshArea_X2);
			SetArrayCell(g_hNavMeshAreas, iIndex, y2, NavMeshArea_Y2);
			SetArrayCell(g_hNavMeshAreas, iIndex, z2, NavMeshArea_Z2);
			SetArrayCell(g_hNavMeshAreas, iIndex, flAreaCenter[0], NavMeshArea_CenterX);
			SetArrayCell(g_hNavMeshAreas, iIndex, flAreaCenter[1], NavMeshArea_CenterY);
			SetArrayCell(g_hNavMeshAreas, iIndex, flAreaCenter[2], NavMeshArea_CenterZ);
			SetArrayCell(g_hNavMeshAreas, iIndex, flInvDxCorners, NavMeshArea_InvDxCorners);
			SetArrayCell(g_hNavMeshAreas, iIndex, flInvDyCorners, NavMeshArea_InvDyCorners);
			SetArrayCell(g_hNavMeshAreas, iIndex, flNECornerZ, NavMeshArea_NECornerZ);
			SetArrayCell(g_hNavMeshAreas, iIndex, flSWCornerZ, NavMeshArea_SWCornerZ);
			SetArrayCell(g_hNavMeshAreas, iIndex, iConnectionsStartIndex, NavMeshArea_ConnectionsStartIndex);
			SetArrayCell(g_hNavMeshAreas, iIndex, iConnectionsEndIndex, NavMeshArea_ConnectionsEndIndex);
			SetArrayCell(g_hNavMeshAreas, iIndex, iHidingSpotsStartIndex, NavMeshArea_HidingSpotsStartIndex);
			SetArrayCell(g_hNavMeshAreas, iIndex, iHidingSpotsEndIndex, NavMeshArea_HidingSpotsEndIndex);
			SetArrayCell(g_hNavMeshAreas, iIndex, iEncounterPathsStartIndex, NavMeshArea_EncounterPathsStartIndex);
			SetArrayCell(g_hNavMeshAreas, iIndex, iEncounterPathsEndIndex, NavMeshArea_EncounterPathsEndIndex);
			SetArrayCell(g_hNavMeshAreas, iIndex, iLadderConnectionsStartIndex, NavMeshArea_LadderConnectionsStartIndex);
			SetArrayCell(g_hNavMeshAreas, iIndex, iLadderConnectionsEndIndex, NavMeshArea_LadderConnectionsEndIndex);
			SetArrayCell(g_hNavMeshAreas, iIndex, flNavCornerLightIntensityNW, NavMeshArea_CornerLightIntensityNW);
			SetArrayCell(g_hNavMeshAreas, iIndex, flNavCornerLightIntensityNE, NavMeshArea_CornerLightIntensityNE);
			SetArrayCell(g_hNavMeshAreas, iIndex, flNavCornerLightIntensitySE, NavMeshArea_CornerLightIntensitySE);
			SetArrayCell(g_hNavMeshAreas, iIndex, flNavCornerLightIntensitySW, NavMeshArea_CornerLightIntensitySW);
			SetArrayCell(g_hNavMeshAreas, iIndex, iVisibleAreasStartIndex, NavMeshArea_VisibleAreasStartIndex);
			SetArrayCell(g_hNavMeshAreas, iIndex, iVisibleAreasEndIndex, NavMeshArea_VisibleAreasEndIndex);
			SetArrayCell(g_hNavMeshAreas, iIndex, iInheritVisibilityFrom, NavMeshArea_InheritVisibilityFrom);
			SetArrayCell(g_hNavMeshAreas, iIndex, flEarliestOccupyTimeFirstTeam, NavMeshArea_EarliestOccupyTimeFirstTeam);
			SetArrayCell(g_hNavMeshAreas, iIndex, flEarliestOccupyTimeSecondTeam, NavMeshArea_EarliestOccupyTimeSecondTeam);
			SetArrayCell(g_hNavMeshAreas, iIndex, unk01, NavMeshArea_unk01);
			SetArrayCell(g_hNavMeshAreas, iIndex, -1, NavMeshArea_Parent);
			SetArrayCell(g_hNavMeshAreas, iIndex, NUM_TRAVERSE_TYPES, NavMeshArea_ParentHow);
			SetArrayCell(g_hNavMeshAreas, iIndex, 0, NavMeshArea_TotalCost);
			SetArrayCell(g_hNavMeshAreas, iIndex, 0, NavMeshArea_CostSoFar);
			SetArrayCell(g_hNavMeshAreas, iIndex, -1, NavMeshArea_Marker);
			SetArrayCell(g_hNavMeshAreas, iIndex, -1, NavMeshArea_OpenMarker);
			SetArrayCell(g_hNavMeshAreas, iIndex, -1, NavMeshArea_PrevOpenIndex);
			SetArrayCell(g_hNavMeshAreas, iIndex, -1, NavMeshArea_NextOpenIndex);
			SetArrayCell(g_hNavMeshAreas, iIndex, 0.0, NavMeshArea_PathLengthSoFar);
			SetArrayCell(g_hNavMeshAreas, iIndex, false, NavMeshArea_Blocked);
			SetArrayCell(g_hNavMeshAreas, iIndex, -1, NavMeshArea_NearSearchMarker);
		}
	}
	
	// Set up the grid.
	NavMeshGridAllocate(flExtentLow[0], flExtentHigh[0], flExtentLow[1], flExtentHigh[1]);
	
	for (new i = 0; i < iAreaCount; i++)
	{
		NavMeshAddAreaToGrid(i);
	}
	
	NavMeshGridFinalize();
	LogMessage("Loading ladders....");
	
	new iLadderCount;
	ReadFileCell(hFile, iLadderCount, UNSIGNED_INT_BYTE_SIZE);
	
	if (iLadderCount > 0)
	{
		for (new iLadderIndex; iLadderIndex < iLadderCount; iLadderIndex++)
		{
			new iLadderID;
			ReadFileCell(hFile, iLadderID, UNSIGNED_INT_BYTE_SIZE);
			
			new Float:flLadderWidth;
			ReadFileCell(hFile, _:flLadderWidth, FLOAT_BYTE_SIZE);
			
			new Float:flLadderTopX, Float:flLadderTopY, Float:flLadderTopZ, Float:flLadderBottomX, Float:flLadderBottomY, Float:flLadderBottomZ;
			ReadFileCell(hFile, _:flLadderTopX, FLOAT_BYTE_SIZE);
			ReadFileCell(hFile, _:flLadderTopY, FLOAT_BYTE_SIZE);
			ReadFileCell(hFile, _:flLadderTopZ, FLOAT_BYTE_SIZE);
			ReadFileCell(hFile, _:flLadderBottomX, FLOAT_BYTE_SIZE);
			ReadFileCell(hFile, _:flLadderBottomY, FLOAT_BYTE_SIZE);
			ReadFileCell(hFile, _:flLadderBottomZ, FLOAT_BYTE_SIZE);
			
			new Float:flLadderLength;
			ReadFileCell(hFile, _:flLadderLength, FLOAT_BYTE_SIZE);
			
			new iLadderDirection;
			ReadFileCell(hFile, iLadderDirection, UNSIGNED_INT_BYTE_SIZE);
			
			new iLadderTopForwardAreaID;
			ReadFileCell(hFile, iLadderTopForwardAreaID, UNSIGNED_INT_BYTE_SIZE);
			
			new iLadderTopLeftAreaID;
			ReadFileCell(hFile, iLadderTopLeftAreaID, UNSIGNED_INT_BYTE_SIZE);
			
			new iLadderTopRightAreaID;
			ReadFileCell(hFile, iLadderTopRightAreaID, UNSIGNED_INT_BYTE_SIZE);
			
			new iLadderTopBehindAreaID;
			ReadFileCell(hFile, iLadderTopBehindAreaID, UNSIGNED_INT_BYTE_SIZE);
			
			new iLadderBottomAreaID;
			ReadFileCell(hFile, iLadderBottomAreaID, UNSIGNED_INT_BYTE_SIZE);
			
			new iIndex = PushArrayCell(g_hNavMeshLadders, iLadderID);
			SetArrayCell(g_hNavMeshLadders, iIndex, flLadderWidth, NavMeshLadder_Width);
			SetArrayCell(g_hNavMeshLadders, iIndex, flLadderLength, NavMeshLadder_Length);
			SetArrayCell(g_hNavMeshLadders, iIndex, flLadderTopX, NavMeshLadder_TopX);
			SetArrayCell(g_hNavMeshLadders, iIndex, flLadderTopY, NavMeshLadder_TopY);
			SetArrayCell(g_hNavMeshLadders, iIndex, flLadderTopZ, NavMeshLadder_TopZ);
			SetArrayCell(g_hNavMeshLadders, iIndex, flLadderBottomX, NavMeshLadder_BottomX);
			SetArrayCell(g_hNavMeshLadders, iIndex, flLadderBottomY, NavMeshLadder_BottomY);
			SetArrayCell(g_hNavMeshLadders, iIndex, flLadderBottomZ, NavMeshLadder_BottomZ);
			SetArrayCell(g_hNavMeshLadders, iIndex, iLadderDirection, NavMeshLadder_Direction);
			SetArrayCell(g_hNavMeshLadders, iIndex, iLadderTopForwardAreaID, NavMeshLadder_TopForwardAreaIndex);
			SetArrayCell(g_hNavMeshLadders, iIndex, iLadderTopLeftAreaID, NavMeshLadder_TopLeftAreaIndex);
			SetArrayCell(g_hNavMeshLadders, iIndex, iLadderTopRightAreaID, NavMeshLadder_TopRightAreaIndex);
			SetArrayCell(g_hNavMeshLadders, iIndex, iLadderTopBehindAreaID, NavMeshLadder_TopBehindAreaIndex);
			SetArrayCell(g_hNavMeshLadders, iIndex, iLadderBottomAreaID, NavMeshLadder_BottomAreaIndex);
		}
	}
	
	g_iNavMeshMagicNumber = iNavMagicNumber;
	g_iNavMeshVersion = iNavVersion;
	g_iNavMeshSubVersion = iNavSubVersion;
	g_iNavMeshSaveBSPSize = iNavSaveBspSize;
	g_bNavMeshAnalyzed = bool:iNavMeshAnalyzed;
	
	LogMessage("Done loading ladders.");
	CloseHandle(hFile);
	LogMessage("Navmesh file closed");
	
	// File parsing is all done. Convert IDs to array indexes for faster performance and 
	// lesser lookup time.
	LogMessage("Index cleanup starting...");
	if (GetArraySize(g_hNavMeshAreaConnections) > 0)
	{
		for (new iIndex = 0, iSize = GetArraySize(g_hNavMeshAreaConnections); iIndex < iSize; iIndex++)
		{
			new iConnectedAreaID = GetArrayCell(g_hNavMeshAreaConnections, iIndex, NavMeshConnection_AreaIndex);
			SetArrayCell(g_hNavMeshAreaConnections, iIndex, FindValueInArray(g_hNavMeshAreas, iConnectedAreaID), NavMeshConnection_AreaIndex);
		}
	}
	LogMessage("g_hNavMeshAreaConnections Done!");
	/*
	if (GetArraySize(g_hNavMeshAreaVisibleAreas) > 0)
	{
		for (new iIndex = 0, iSize = GetArraySize(g_hNavMeshAreaVisibleAreas); iIndex < iSize; iIndex++)
		{
			new iVisibleAreaID = GetArrayCell(g_hNavMeshAreaVisibleAreas, iIndex, NavMeshVisibleArea_Index);
			SetArrayCell(g_hNavMeshAreaVisibleAreas, iIndex, FindValueInArray(g_hNavMeshAreas, iVisibleAreaID), NavMeshVisibleArea_Index);
		}
	}
	LogMessage("g_hNavMeshAreaVisibleAreas Done!");
	*/
	if (GetArraySize(g_hNavMeshAreaLadderConnections) > 0)
	{
		for (new iIndex = 0, iSize = GetArraySize(g_hNavMeshAreaLadderConnections); iIndex < iSize; iIndex++)
		{
			new iLadderID = GetArrayCell(g_hNavMeshAreaLadderConnections, iIndex, NavMeshLadderConnection_LadderIndex);
			SetArrayCell(g_hNavMeshAreaLadderConnections, iIndex, FindValueInArray(g_hNavMeshLadders, iLadderID), NavMeshLadderConnection_LadderIndex);
		}
	}
	LogMessage("g_hNavMeshAreaLadderConnections Done!");
	
	if (GetArraySize(g_hNavMeshLadders) > 0)
	{
		for (new iLadderIndex = 0; iLadderIndex < iLadderCount; iLadderIndex++)
		{
			new iTopForwardAreaID = GetArrayCell(g_hNavMeshLadders, iLadderIndex, NavMeshLadder_TopForwardAreaIndex);
			SetArrayCell(g_hNavMeshLadders, iLadderIndex, FindValueInArray(g_hNavMeshAreas, iTopForwardAreaID), NavMeshLadder_TopForwardAreaIndex);
			
			new iTopLeftAreaID = GetArrayCell(g_hNavMeshLadders, iLadderIndex, NavMeshLadder_TopLeftAreaIndex);
			SetArrayCell(g_hNavMeshLadders, iLadderIndex, FindValueInArray(g_hNavMeshAreas, iTopLeftAreaID), NavMeshLadder_TopLeftAreaIndex);
			
			new iTopRightAreaID = GetArrayCell(g_hNavMeshLadders, iLadderIndex, NavMeshLadder_TopRightAreaIndex);
			SetArrayCell(g_hNavMeshLadders, iLadderIndex, FindValueInArray(g_hNavMeshAreas, iTopRightAreaID), NavMeshLadder_TopRightAreaIndex);
			
			new iTopBehindAreaID = GetArrayCell(g_hNavMeshLadders, iLadderIndex, NavMeshLadder_TopBehindAreaIndex);
			SetArrayCell(g_hNavMeshLadders, iLadderIndex, FindValueInArray(g_hNavMeshAreas, iTopBehindAreaID), NavMeshLadder_TopBehindAreaIndex);
			
			new iBottomAreaID = GetArrayCell(g_hNavMeshLadders, iLadderIndex, NavMeshLadder_BottomAreaIndex);
			SetArrayCell(g_hNavMeshLadders, iLadderIndex, FindValueInArray(g_hNavMeshAreas, iBottomAreaID), NavMeshLadder_BottomAreaIndex);
		}
	}
	LogMessage("g_hNavMeshLadders Done!");
	LogMessage("Index cleanup complete.");
	return true;
}
