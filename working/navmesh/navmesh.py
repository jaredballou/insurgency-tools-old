#!/usr/bin/env python
import struct
from pprint import pprint
mapdir="/home/insserver/serverfiles/insurgency/maps"
navfile="/home/insserver/serverfiles/insurgency/maps/buhriz_coop.nav"

class BinaryReaderEOFException(Exception):
    def __init__(self):
        pass
    def __str__(self):
        return 'Not enough bytes in file to satisfy read request'

class BinaryReader:
    # Map well-known type names into struct format characters.
    typeNames = {
        'int8'   :'b',
        'uint8'  :'B',
        'int16'  :'h',
        'uint16' :'H',
        'int32'  :'i',
        'uint32' :'I',
        'int64'  :'q',
        'uint64' :'Q',
        'float'  :'f',
        'double' :'d',
        'char'   :'s'}

    def __init__(self, fileName):
        self.file = open(fileName, 'rb')
        
    def read(self, typeName):
        typeFormat = BinaryReader.typeNames[typeName.lower()]
        typeSize = struct.calcsize(typeFormat)
        value = self.file.read(typeSize)
        if typeSize != len(value):
            raise BinaryReaderEOFException
        return struct.unpack(typeFormat, value)[0]
    
    def __del__(self):
        self.file.close()

binaryReader = BinaryReader(navfile)
navmesh = dict()
navmesh['places'] = dict()
try:
    navmesh['iNavMagicNumber'] = binaryReader.read('uint32')
    navmesh['iNavVersion'] = binaryReader.read('uint32')
    navmesh['iNavSubVersion'] = binaryReader.read('uint32')
    navmesh['iNavSaveBspSize'] = binaryReader.read('uint32')
    navmesh['cNavMeshAnalyzed'] = binaryReader.read('uint8')
    navmesh['sPlaceCount'] = binaryReader.read('uint16')
    for num in range(1,navmesh['sPlaceCount']):
        navmesh['sPlaceStringSize'] = binaryReader.read('uint16')
        buff = ''
        for num in range(1,navmesh['sPlaceStringSize']):
            buff = buff + binaryReader.read('char')
        navmesh['strPlaceString']  = buff
    navmesh['cNavUnnamedAreas'] = binaryReader.read('uint8')
    navmesh['iAreaCount'] = binaryReader.read('uint32')
    pprint(navmesh)
    navmesh['areas'] = dict()
    for num in range(0,navmesh['iAreaCount']):
        area = dict()
        area['connections'] = dict()
        area['hidingspots'] = dict()
        area['encounterpaths'] = dict()
        area['places'] = dict()
        area['ladderconnections'] = dict()

        area['iAreaID'] = binaryReader.read('uint32')
        area['iAreaFlags'] = binaryReader.read('uint32')
        area['x1'] = binaryReader.read('float')
        area['y1'] = binaryReader.read('float')
        area['z1'] = binaryReader.read('float')
        area['x2'] = binaryReader.read('float')
        area['y2'] = binaryReader.read('float')
        area['z2'] = binaryReader.read('float')
        area['flNECornerZ'] = binaryReader.read('float')
        area['flSWCornerZ'] = binaryReader.read('float')
        for num in range(1,4):
            area['iConnectionCount'] = binaryReader.read('uint32')
            area['iConnectingAreaID'] = binaryReader.read('uint32')
        area['iHidingSpotCount'] = binaryReader.read('uint8')
        pprint(area['iHidingSpotCount'])
        for num in range(0,area['iHidingSpotCount']):
            hidingspot = dict()
            hidingspot['iHidingSpotID'] = binaryReader.read('uint32')
            hidingspot['flHidingSpotX'] = binaryReader.read('float')
            hidingspot['flHidingSpotY'] = binaryReader.read('float')
            hidingspot['flHidingSpotZ'] = binaryReader.read('float')
            hidingspot['iHidingSpotFlags'] = binaryReader.read('char')
#            area['hidingspots'][hidingspot['iHidingSpotID']] = hidingspot
#        pprint(area)
#        pprint("goo")
        area['iEncounterPathCount'] = binaryReader.read('uint32')
        pprint(area['iEncounterPathCount'])
        for num in range(0,area['iEncounterPathCount']):
            encounterpath = dict()
            encounterpath['encounterspots'] = dict()
            encounterpath['iEncounterFromID'] = binaryReader.read('uint32')
            encounterpath['iEncounterFromDirection'] = binaryReader.read('uint8')
            encounterpath['iEncounterToID'] = binaryReader.read('uint32')
            encounterpath['iEncounterToDirection'] = binaryReader.read('uint8')
            encounterpath['iEncounterSpotCount'] = binaryReader.read('uint8')
            for nums in range(1,encounterpath['iEncounterSpotCount']):
#                pprint("goo es")
                encounterspot = dict()
                encounterspot['iEncounterSpotOrderID'] = binaryReader.read('uint32')
                encounterspot['iEncounterSpotT'] = binaryReader.read('uint8')
                encounterpath['encounterspots'][encounterspot['iEncounterSpotOrderID']] = encounterspot
#            area['encounterpaths'][encounterpath['iEncounterFromID']] = encounterpath
        area['sPlaceID'] = binaryReader.read('uint16')
        pprint(area['sPlaceID'])
        for num in range(1,2):
            pprint("goo ladder %d" % num)
            area['iLadderConnectionCount'] = binaryReader.read('uint32')
            pprint("goo lcc %d" % area['iLadderConnectionCount'])
            for nums in range(1,area['iLadderConnectionCount']):
                pprint("goo lcid %d" % nums)
                area['iLadderConnectionID'] = binaryReader.read('uint32')
                pprint("goo lcid %d" % area['iLadderConnectionID'])
        area['flEarliestOccupyTimeFirstTeam'] = binaryReader.read('float')
        pprint("goo")
        area['flEarliestOccupyTimeSecondTeam'] = binaryReader.read('float')
        area['flNavCornerLightuint32ensityNW'] = binaryReader.read('float')
        area['flNavCornerLightuint32ensityNE'] = binaryReader.read('float')
        area['flNavCornerLightuint32ensitySE'] = binaryReader.read('float')
        area['flNavCornerLightuint32ensitySW'] = binaryReader.read('float')
        area['iVisibleAreaCount'] = binaryReader.read('uint32')
        pprint(area)
        for num in range(1,area['iVisibleAreaCount']):
            area['iVisibleAreaID'] = binaryReader.read('uint32')
            area['iVisibleAreaAttributes'] = binaryReader.read('uint8')
        area['iInheritVisibilityFrom'] = binaryReader.read('uint32')
        area['unk01'] = binaryReader.read('uint32')
        pprint(area)
        pprint("area done")
        navmesh['areas'][area['iAreaID']] = area
#    navmesh[''] = binaryReader.read('uint32')
#    navmesh[''] = binaryReader.read('uint32')
#    navmesh[''] = binaryReader.read('uint32')
#    navmesh[''] = binaryReader.read('uint32')
#    navmesh[''] = binaryReader.read('uint32')
except BinaryReaderEOFException:
    # One of our attempts to read a field went beyond the end of the file.
    print "Error: File seems to be corrupted."

pprint(navmesh)
