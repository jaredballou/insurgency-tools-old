#include <stdlib.h>
#include <stdint.h>
#include <stdio.h>
#include <string.h>

#define TITLE \
	"Half Life Nav Mesh Tool\n" \
	"Copyright 2014 by Jared Ballou <instools@jballou.com>\n"

typedef uint8_t byte;

//Vector type
typedef struct Vector_t
{
	float x;
	float y;
	float z;
} __attribute__((__packed__)) Vector;

#define HalfHumanWidth			16
#define HalfHumanHeight			35.5
#define HumanHeight				71
#define HumanEyeHeight			62
#define HumanCrouchHeight		55
#define HumanCrouchEyeHeight	37

#define UNSIGNED_INT_BYTE_SIZE 4
#define UNSIGNED_CHAR_BYTE_SIZE 1
#define UNSIGNED_SHORT_BYTE_SIZE 2
#define FLOAT_BYTE_SIZE 4

#define NAV_MAGIC_NUMBER 0xFEEDFACE				// to help identify nav files

#define MAX(x, y) (((x) > (y)) ? (x) : (y))
#define MIN(x, y) (((x) < (y)) ? (x) : (y))

/**
 * A place is a named group of navigation areas
 */
typedef unsigned int Place;
#define UNDEFINED_PLACE 0				// ie: "no place"
#define ANY_PLACE 0xFFFF


//Enumerated types
enum { MAX_NAV_TEAMS = 2 };
enum NavErrorType
{
	NAV_OK,
	NAV_CANT_ACCESS_FILE,
	NAV_INVALID_FILE,
	NAV_BAD_FILE_VERSION,
	NAV_FILE_OUT_OF_DATE,
	NAV_CORRUPT_DATA,
	NAV_OUT_OF_MEMORY,
};

enum NavAttributeType
{
	NAV_MESH_INVALID		= 0,
	NAV_MESH_CROUCH			= 0x00000001,				// must crouch to use this node/area
	NAV_MESH_JUMP			= 0x00000002,				// must jump to traverse this area (only used during generation)
	NAV_MESH_PRECISE		= 0x00000004,				// do not adjust for obstacles, just move along area
	NAV_MESH_NO_JUMP		= 0x00000008,				// inhibit discontinuity jumping
	NAV_MESH_STOP			= 0x00000010,				// must stop when entering this area
	NAV_MESH_RUN			= 0x00000020,				// must run to traverse this area
	NAV_MESH_WALK			= 0x00000040,				// must walk to traverse this area
	NAV_MESH_AVOID			= 0x00000080,				// avoid this area unless alternatives are too dangerous
	NAV_MESH_TRANSIENT		= 0x00000100,				// area may become blocked, and should be periodically checked
	NAV_MESH_DONT_HIDE		= 0x00000200,				// area should not be considered for hiding spot generation
	NAV_MESH_STAND			= 0x00000400,				// bots hiding in this area should stand
	NAV_MESH_NO_HOSTAGES	= 0x00000800,				// hostages shouldn't use this area
	NAV_MESH_STAIRS			= 0x00001000,				// this area represents stairs, do not attempt to climb or jump them - just walk up
	NAV_MESH_NO_MERGE		= 0x00002000,				// don't merge this area with adjacent areas
	NAV_MESH_OBSTACLE_TOP	= 0x00004000,				// this nav area is the climb point on the tip of an obstacle
	NAV_MESH_CLIFF			= 0x00008000,				// this nav area is adjacent to a drop of at least CliffHeight

	NAV_MESH_FIRST_CUSTOM	= 0x00010000,				// apps may define custom app-specific bits starting with this value
	NAV_MESH_LAST_CUSTOM	= 0x04000000,				// apps must not define custom app-specific bits higher than with this value

	NAV_MESH_FUNC_COST		= 0x20000000,				// area has designer specified cost controlled by func_nav_cost entities
	NAV_MESH_HAS_ELEVATOR	= 0x40000000,				// area is in an elevator's path
	NAV_MESH_NAV_BLOCKER	= 0x80000000				// area is blocked by nav blocker ( Alas, needed to hijack a bit in the attributes to get within a cache line [7/24/2008 tom])
};

enum NavTraverseType
{
	// NOTE: First 4 directions MUST match NavDirType
	GO_NORTH = 0,
	GO_EAST,
	GO_SOUTH,
	GO_WEST,

	GO_LADDER_UP,
	GO_LADDER_DOWN,
	GO_JUMP,
	GO_ELEVATOR_UP,
	GO_ELEVATOR_DOWN,

	NUM_TRAVERSE_TYPES
};

enum NavCornerType
{
	NORTH_WEST = 0,
	NORTH_EAST = 1,
	SOUTH_EAST = 2,
	SOUTH_WEST = 3,

	NUM_CORNERS
};

enum NavRelativeDirType
{
	FORWARD = 0,
	RIGHT,
	BACKWARD,
	LEFT,
	UP,
	DOWN,

	NUM_RELATIVE_DIRECTIONS
};

enum NavDirType
{
	NORTH = 0,
	EAST = 1,
	SOUTH = 2,
	WEST = 3,

	NUM_DIRECTIONS
};
enum LadderDirectionType
{
	LADDER_UP = 0,
	LADDER_DOWN,

	NUM_LADDER_DIRECTIONS
};

//Data structures
typedef struct navfile_area_connection_t
{
	unsigned int ident;
} __attribute__((__packed__)) navfile_area_connection;

typedef struct navfile_area_hidingspot_t
{
	unsigned int	ident;
	Vector		pos;
	unsigned char	flags;
} __attribute__((__packed__)) navfile_area_hidingspot;

typedef struct navfile_area_encounterspot_t
{
	unsigned int	id;
	unsigned char	t;
} __attribute__((__packed__)) navfile_area_encounterspot;
typedef struct navfile_area_encounterpath_t
{
	unsigned int	fromid;
	unsigned char	fromdir;
	unsigned int	toid;
	unsigned char	todir;
	unsigned char	spotcount;
} __attribute__((__packed__)) navfile_area_encounterpath;


typedef struct Extent_t
{
	Vector lo;
	Vector hi;
} __attribute__((__packed__)) Extent;


//nav Header type
typedef struct navfile_header_t
{
	unsigned int 	magic;
	unsigned int 	version;
	unsigned int 	subversion;
	unsigned int 	bspSize;
	unsigned char	isAnalyzed;
} __attribute__((__packed__)) navfile_header;

typedef struct navfile_area_t
{
	unsigned int	ident;
	unsigned int	attributeFlags;
	Vector		nwCorner;
	Vector		seCorner;
	float		neZ;
	float		swZ;
} __attribute__((__packed__)) navfile_area;

unsigned int swapBytes( unsigned int i ) 
{
	unsigned char b1, b2, b3, b4;

	b1 = i & 255;
	b2 = ( i >> 8 ) & 255;
	b3 = ( i>>16 ) & 255;
	b4 = ( i>>24 ) & 255;

	return ((int)b1 << 24) + ((int)b2 << 16) + ((int)b3 << 8) + b4;
}

unsigned int copyBytes( unsigned int i ) 
{
	return i;
}

FILE* sfopen( const char* filename, const char* mode ) 
{
	FILE *tmp;
	if ( ( tmp = fopen( filename, mode ) ) == NULL ) {
		fprintf( stderr, "Error: Could not open file \"%s\".\n", filename );
		exit( EXIT_FAILURE );
	}
	return tmp;
}

size_t sfread( void *ptr, size_t size, size_t count, FILE* stream ) 
{
	size_t read;
	if ( ( read = fread( ptr, size, count, stream ) ) != count ) {
		fprintf( stderr, "Error: Could not read from file.\n" );
		exit( EXIT_FAILURE );
	}
	return read;
}

size_t sfwrite( void *ptr, size_t size, size_t count, FILE* stream ) 
{
	size_t written;
	if ( ( written = fwrite( ptr, size, count, stream ) ) != count ) 
	{
		fprintf( stderr, "Error: Could not write to file.\n" );
		exit( EXIT_FAILURE );
	}
	return written;
}

void* smalloc( size_t size ) 
{
	void* tmp;
	if ( ( tmp = malloc( size ) ) == NULL ) 
	{
		fprintf( stderr, "Error: Could not allocate %i bytes.\n", size );
		exit( EXIT_FAILURE );
	}
	return tmp;
}


int main( int argc, char *argv[] ) 
{
	FILE *nav_file;
	navfile_header header;
	navfile_area area;
	navfile_area_connection area_connection;
	navfile_area_hidingspot area_hidingspot;
	navfile_area_encounterpath area_encounterpath;
	navfile_area_encounterspot area_encounterspot;
	unsigned int (*endianAwareInt)( unsigned int i );
	char* buffer;
	char placeName[256];
	unsigned int a,p,d,c,h,e,s,v,iAreaCount,iDirCount,iEpCount,iVaCount,iPlaceCount,iSpotCount,iNavUnnamedAreas;
	unsigned short sLen,sPlaceIndex;
	unsigned char cHidingSpotCount;
	float fEarliestOccupyTime[MAX_NAV_TEAMS],fLightIntensity[NUM_CORNERS];

	// Check for endian support
	char EndianTest[2] = { 0, 1 };
	if ( *( short* )EndianTest == 1 ) 
	{
		// big endian machine
		endianAwareInt = swapBytes;
	}
	else 
	{
		// little endian machine
		endianAwareInt = copyBytes;
	}

	printf( TITLE );
	nav_file = sfopen( argv[1], "r" );

	// Read the header
	printf("current pos is %d\n",ftell(nav_file));
	sfread( &header, sizeof( navfile_header ), 1, nav_file );
	printf( "magic 0x%08x\n", header.magic);
	printf( "version %d\n", header.version);
	printf( "subversion %d\n", header.subversion);
	printf( "bspSize %d\n", header.bspSize);
	printf( "isAnalyzed %d\n", header.isAnalyzed);
	//fseek( nav_file, 1, SEEK_CUR );

	//Places
	sfread( &iPlaceCount, UNSIGNED_SHORT_BYTE_SIZE, 1, nav_file );
	printf( "iPlaceCount %d\n", iPlaceCount);
	for ( p=0; p<iPlaceCount; p++ ) {
		sfread( &sLen, UNSIGNED_SHORT_BYTE_SIZE, 1, nav_file );
		sfread( &placeName, MIN(sizeof(placeName),sLen) , 1, nav_file );
		printf( "placeName %s\n", placeName);
	}
	//Areas
	if (header.version > 11) {
		sfread( &iNavUnnamedAreas,UNSIGNED_CHAR_BYTE_SIZE, 1, nav_file );
		printf( "iNavUnnamedAreas %d\n", iNavUnnamedAreas);
	}
	sfread( &iAreaCount, UNSIGNED_INT_BYTE_SIZE, 1, nav_file );
	printf( "iAreaCount %d\n", iAreaCount);
	for ( a=0; a<iAreaCount; a++ ) {

		sfread( &area.ident, UNSIGNED_INT_BYTE_SIZE,1,nav_file);
		sfread( &area.attributeFlags, UNSIGNED_INT_BYTE_SIZE,1,nav_file);
		sfread( &area.nwCorner.x, FLOAT_BYTE_SIZE,1,nav_file);
		sfread( &area.nwCorner.y, FLOAT_BYTE_SIZE,1,nav_file);
		sfread( &area.nwCorner.z, FLOAT_BYTE_SIZE,1,nav_file);
		sfread( &area.seCorner.x, FLOAT_BYTE_SIZE,1,nav_file);
		sfread( &area.seCorner.y, FLOAT_BYTE_SIZE,1,nav_file);
		sfread( &area.seCorner.z, FLOAT_BYTE_SIZE,1,nav_file);
		sfread( &area.neZ, FLOAT_BYTE_SIZE,1,nav_file);
		sfread( &area.swZ, FLOAT_BYTE_SIZE,1,nav_file);



		printf( "ident %d\n", area.ident);
		printf( "  attributeFlags %d\n", area.attributeFlags);
		printf( "  nwCorner %f %f %f\n", area.nwCorner.x,area.nwCorner.y,area.nwCorner.z);
		printf( "  seCorner %f %f %f\n", area.seCorner.x,area.seCorner.y,area.seCorner.z);
		printf( "  neZ %f\n", area.neZ);
		printf( "  swZ %f\n", area.swZ);

		for( d=0; d<NUM_DIRECTIONS; d++ ) {
			printf("  direction %d\n",d);
			sfread( &iDirCount, UNSIGNED_INT_BYTE_SIZE, 1, nav_file );
			printf("    iDirCount %d\n",iDirCount);
			for( c=0; c<iDirCount; c++ ) {
				sfread( &area_connection.ident, UNSIGNED_INT_BYTE_SIZE,1,nav_file);
				printf("      c %d dir ident %d\n",c,area_connection.ident);
			}
		}
		sfread( &cHidingSpotCount, UNSIGNED_CHAR_BYTE_SIZE, 1, nav_file );
		printf( "  cHidingSpotCount %u\n", cHidingSpotCount);
		for( h=0; h<(int)cHidingSpotCount; h++ ) {
			printf( "    area_hidingspot %d\n", h);
			sfread( &area_hidingspot.ident, UNSIGNED_INT_BYTE_SIZE ,1,nav_file);
			sfread( &area_hidingspot.pos.x, FLOAT_BYTE_SIZE,1,nav_file);
			sfread( &area_hidingspot.pos.y, FLOAT_BYTE_SIZE,1,nav_file);
			sfread( &area_hidingspot.pos.z, FLOAT_BYTE_SIZE,1,nav_file);
			sfread( &area_hidingspot.flags,UNSIGNED_CHAR_BYTE_SIZE,1,nav_file); 
			printf( "      id %d\n", area_hidingspot.ident);
			printf( "      pos %f %f %f\n", area_hidingspot.pos.x,area_hidingspot.pos.y,area_hidingspot.pos.z);
			printf( "      flags %d\n",area_hidingspot.flags);
		}
		sfread( &iEpCount, UNSIGNED_INT_BYTE_SIZE, 1, nav_file );
		printf( "  iEpCount %d\n", iEpCount);
		for( e=0; e<iEpCount; e++ ) {
			printf( "    ep %d\n",e);
			sfread( &area_encounterpath.fromid, UNSIGNED_INT_BYTE_SIZE,1,nav_file);
			sfread( &area_encounterpath.fromdir, UNSIGNED_CHAR_BYTE_SIZE,1,nav_file);
			sfread( &area_encounterpath.toid, UNSIGNED_INT_BYTE_SIZE,1,nav_file);
			sfread( &area_encounterpath.todir, UNSIGNED_CHAR_BYTE_SIZE,1,nav_file);
			sfread( &area_encounterpath.spotcount, UNSIGNED_CHAR_BYTE_SIZE,1,nav_file);
//			printf( "      fromid %d\n", area_encounterpath.fromid);
//			printf( "      fromdir %u\n", area_encounterpath.fromdir);
//			printf( "      toid %d\n", area_encounterpath.toid);
//			printf( "      todir %u\n", area_encounterpath.todir);
//			printf( "      spotcount %u\n", area_encounterpath.spotcount);
			for( s=0; s<(int)area_encounterpath.spotcount; s++ ) {
//				printf( "        es %d\n",s);
				sfread( &area_encounterspot.id, UNSIGNED_INT_BYTE_SIZE,1,nav_file);
				sfread( &area_encounterspot.t, UNSIGNED_CHAR_BYTE_SIZE,1,nav_file);
//				printf( "          id %d\n", area_encounterspot.id);
//				printf( "          t %d\n", area_encounterspot.t);
//				if (!area_encounterspot.id) {
//					s = area_encounterpath.spotcount;
//				}
			}
		}
		sfread( &sPlaceIndex, UNSIGNED_SHORT_BYTE_SIZE,1,nav_file);
		printf( "  sPlaceIndex %d\n", sPlaceIndex);
		for( d=0; d<NUM_LADDER_DIRECTIONS; d++ ) {
			sfread( &iDirCount, UNSIGNED_INT_BYTE_SIZE, 1, nav_file );
			for( c=0; c<iDirCount; c++ ) {
				sfread( &area_connection.ident, UNSIGNED_INT_BYTE_SIZE,1,nav_file);
			}
		}
		for( d=0; d<MAX_NAV_TEAMS; d++) {
			sfread( &fEarliestOccupyTime[d], FLOAT_BYTE_SIZE,1,nav_file);
		}
		if (header.version > 11) {
			for( d=0; d<NUM_CORNERS; d++) {
				sfread( &fLightIntensity[d], FLOAT_BYTE_SIZE,1,nav_file);
			}
			if (header.version >= 16) {
				sfread( &iVaCount, UNSIGNED_INT_BYTE_SIZE,1,nav_file);
				for( v=0; v<iVaCount; v++) {
					sfread( &buffer, UNSIGNED_CHAR_BYTE_SIZE,1,nav_file); //id
					sfread( &buffer, UNSIGNED_INT_BYTE_SIZE,1,nav_file); //attributes
				}
				sfread( &buffer, UNSIGNED_INT_BYTE_SIZE,1,nav_file); //m_inheritVisibilityFrom
				sfread( &buffer, UNSIGNED_INT_BYTE_SIZE,1,nav_file); //unk01
			}
		}
	}
	//ladders
	fclose( nav_file );
}
