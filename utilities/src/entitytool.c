/* entitytool.c
 *
 * Copyright (C) 2008  Jakob Westhoff
 * 
 * Half Life BSP Entity Tool is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 3 of the License. 
 * 
 * Half Life BSP Entity Tool is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details. 
 * 
 * You should have received a copy of the GNU General Public License along with
 * arbit; if not, write to the Free Software Foundation, Inc., 51 Franklin St,
 * Fifth Floor, Boston, MA  02110-1301  USA 
 * 
 */

#include <stdlib.h>
#include <stdint.h>
#include <stdio.h>
#include <string.h>

#define TITLE \
        "Half Life BSP Entity Tool\n" \
        "Copyright 2008 Jakob Westhoff <jakob@westhoffswelt.de>\n" \
        "Updated for Source BSP format by Jared Ballou <instools@jballou.com>\n"
//jballou - Updated structures based upon Valve docs https://developer.valvesoftware.com/wiki/Source_BSP_File_Format
//Create byte referenced to unsigned int
typedef uint8_t byte;

//Minimum header version to support
#define MIN_HEADER_VERSION 17

//There can be 64 lumps according to the format
#define HEADER_LUMPS 64
//Highest 'named' lump
#define MAX_LUMP 60

//Begin structures for lump data

//Lump numbers in named array
static const char *s_LumpNames[] = {
	"LUMP_ENTITIES",						// 0
	"LUMP_PLANES",							// 1
	"LUMP_TEXDATA",							// 2
	"LUMP_VERTEXES",						// 3
	"LUMP_VISIBILITY",						// 4
	"LUMP_NODES",							// 5
	"LUMP_TEXINFO",							// 6
	"LUMP_FACES",							// 7
	"LUMP_LIGHTING",						// 8
	"LUMP_OCCLUSION",						// 9
	"LUMP_LEAFS",							// 10
	"LUMP_FACEIDS",							// 11
	"LUMP_EDGES",							// 12
	"LUMP_SURFEDGES",						// 13
	"LUMP_MODELS",							// 14
	"LUMP_WORLDLIGHTS",						// 15
	"LUMP_LEAFFACES",						// 16
	"LUMP_LEAFBRUSHES",						// 17
	"LUMP_BRUSHES",							// 18
	"LUMP_BRUSHSIDES",						// 19
	"LUMP_AREAS",							// 20
	"LUMP_AREAPORTALS",						// 21
	"LUMP_UNUSED0",							// 22
	"LUMP_UNUSED1",							// 23
	"LUMP_UNUSED2",							// 24
	"LUMP_UNUSED3",							// 25
	"LUMP_DISPINFO",						// 26
	"LUMP_ORIGINALFACES",					// 27
	"LUMP_PHYSDISP",						// 28
	"LUMP_PHYSCOLLIDE",						// 29
	"LUMP_VERTNORMALS",						// 30
	"LUMP_VERTNORMALINDICES",				// 31
	"LUMP_DISP_LIGHTMAP_ALPHAS",			// 32
	"LUMP_DISP_VERTS",						// 33
	"LUMP_DISP_LIGHTMAP_SAMPLE_POSITIONS",	// 34
	"LUMP_GAME_LUMP",						// 35
	"LUMP_LEAFWATERDATA",					// 36
	"LUMP_PRIMITIVES",						// 37
	"LUMP_PRIMVERTS",						// 38
	"LUMP_PRIMINDICES",						// 39
	"LUMP_PAKFILE",							// 40
	"LUMP_CLIPPORTALVERTS",					// 41
	"LUMP_CUBEMAPS",						// 42
	"LUMP_TEXDATA_STRING_DATA",				// 43
	"LUMP_TEXDATA_STRING_TABLE",			// 44
	"LUMP_OVERLAYS",						// 45
	"LUMP_LEAFMINDISTTOWATER",				// 46
	"LUMP_FACE_MACRO_TEXTURE_INFO",			// 47
	"LUMP_DISP_TRIS",						// 48
	"LUMP_PHYSCOLLIDESURFACE",				// 49
	"LUMP_WATEROVERLAYS",					// 50
	"LUMP_LEAF_AMBIENT_INDEX_HDR",			// 51
	"LUMP_LEAF_AMBIENT_INDEX",				// 52
	"LUMP_LIGHTING_HDR",					// 53
	"LUMP_WORLDLIGHTS_HDR",					// 54
	"LUMP_LEAF_AMBIENT_LIGHTING_HDR",		// 55
	"LUMP_LEAF_AMBIENT_LIGHTING",			// 56
	"LUMP_XZIPPAKFILE",						// 57
	"LUMP_FACES_HDR",						// 58
	"LUMP_MAP_FLAGS",						// 59
	"LUMP_OVERLAY_FADES",					// 60
};

const char *GetLumpName( unsigned int lumpnum )
{
	if ( lumpnum > MAX_LUMP )
	{
		return "UNKNOWN";
	}
	return s_LumpNames[lumpnum];
}

//Vector type (Common to many Lumps)
typedef struct Vector_t
{
	float x;
	float y;
	float z;
} __attribute__((__packed__)) Vector;
//Plane type (Lump 1)
typedef struct dplane_t
{
	Vector	normal;	// normal vector
	float	dist;	// distance from origin
	int	type;	// plane axis identifier
} __attribute__((__packed__)) dplane;
//Edge type (Lump 12)
typedef struct dedge_t
{
	unsigned short	v[2];	// vertex indices
} __attribute__((__packed__)) dedge;
//Face type (Lump 7)
typedef struct dface_t
{
	unsigned short	planenum;		// the plane number
	byte		side;			// faces opposite to the node's plane direction
	byte		onNode;			// 1 of on node, 0 if in leaf
	int		firstedge;		// index into surfedges
	short		numedges;		// number of surfedges
	short		texinfo;		// texture info
	short		dispinfo;		// displacement info
	short		surfaceFogVolumeID;	// ?
	byte		styles[4];		// switchable lighting info
	int		lightofs;		// offset into lightmap lump
	float		area;			// face area in units^2
	int		LightmapTextureMinsInLuxels[2];	// texture lighting info
	int		LightmapTextureSizeInLuxels[2];	// texture lighting info
	int		origFace;		// original face this was split from
	unsigned short	numPrims;		// primitives
	unsigned short	firstPrimID;
	unsigned int	smoothingGroups;	// lightmap smoothing group
} __attribute__((__packed__)) dface;

//Brush type (Lump 18)
typedef struct dbrush_t
{
	int	firstside;	// first brushside
	int	numsides;	// number of brushsides
	int	contents;	// contents flags
} __attribute__((__packed__)) dbrush;

//Begin BSP data structures
//Lump type (BSP Section)
typedef struct lump_t
{
	int	fileofs;	// offset into file (bytes)
	int	filelen;	// length of lump (bytes)
	int	version;	// lump format version
	char	fourCC[4];	// lump ident code
} __attribute__((__packed__)) bsp_lump;

//BSP Header type
typedef struct dheader_t
{
	int		ident;                // BSP file identifier
	int		version;              // BSP file version
	bsp_lump	lumps[HEADER_LUMPS];  // lump directory array
	int		mapRevision;          // the map's revision (iteration, version) number
} __attribute__((__packed__)) bsp_header;



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
    if ( ( tmp = fopen( filename, mode ) ) == NULL ) 
    {
        fprintf( stderr, "Error: Could not open file \"%s\".\n", filename );
        exit( EXIT_FAILURE );
    }
    return tmp;
}

size_t sfread( void *ptr, size_t size, size_t count, FILE* stream ) 
{
    size_t read;
    if ( ( read = fread( ptr, size, count, stream ) ) != count ) 
    {
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


int main( int argc, char** argv ) 
{
    FILE *bsp_file;
    FILE *entity_file;
    //What to do
    int action = 0;
    //Show help (also means params didn't parse properly)
    int help = 0;
    bsp_header header;
    unsigned int (*endianAwareInt)( unsigned int i );

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
    int m, n,                              /* Loop counters. */
        l,                                 /* String length. */
        x,                                 /* Exit code. */
        ch;                                /* Character buffer. */
    char s[256];                           /* String buffer. */
    char bspfile[256] = "UNSET";
    char outfile[256] = "UNSET";
    char lumps[256] = "LUMP_ENTITIES";

    for( n = 1; n < argc; n++ )            /* Scan through args. */
    {
        switch( (int)argv[n][0] )            /* Check for option character. */
        {
            case '-':
            case '/':
                x = 0;                   /* Bail out if 1. */
                l = strlen( argv[n] );
                for( m = 1; m < l; ++m ) /* Scan through options. */
                {
                    ch = (int)argv[n][m];
                    switch( ch )
                    {
                        case 'e':
                            action = 0;
                            break;
                        case 'h':
                            help = 2;
                            break;
                        case 'b':
                            strcpy( bspfile, &argv[n][m+1] );
                            x = 1;
                            break;
                        case 'l':
                            strcpy( lumps, &argv[n][m+1] );
                            x = 1;
                            break;
                        case 'o':
                            strcpy( outfile, &argv[n][m+1] );
                            x = 1;
                            break;
                        default:  printf( "Illegal option code = %c\n", ch );
                            x = 1;      /* Not legal option. */
                            exit( 1 );
                            break;
                    }
                    if( x == 1 )
                    {
                        break;
                    }
                break;
            }
        }
    }
    int lumpid = -1;
    for( n = 0; n <= MAX_LUMP; n++ )
    {
        if ( !strcasecmp( lumps, GetLumpName(n) ) )
            lumpid = n;
    }
    if (lumpid == -1)
        help = 1;
    if ( !strcasecmp( bspfile, "UNSET" ) ) 
    {
        help = 1;
    } else {
        if ( !strcasecmp( outfile, "UNSET" ) )
        {
            strcpy(outfile,bspfile);
            strcat(outfile,".");
            strcat(outfile,lumps);
            strcat(outfile,".txt");
//            help = 1;
//TODO: Automatically set OUTFILE
//          strcpy( outfile, &bspfile
        }
    }
    if ( help )
    {
        printf( "Usage: %s [-e|-i] -lLUMP -bBSPFILE [-oOUTFILE]\n", argv[0] );
        printf( "Example: %s -e -l LUMP_ENTITIES -b boot_camp.bsp -o entities.txt\n", argv[0] );
        printf( "Actions:\n" );
        printf( "  -e: Extract a lump from a bsp file\n" );
        printf( "  -i: Reintegrate the lump into a bsp file\n" );
        printf( "  -h: Get full help (includes lump name listing)\n" );
        if ( help == 2 )
        {
            printf( "***Lump List***\n" );
            for( n = 0; n <= MAX_LUMP; n++ )
            {
                printf( "%s\n", GetLumpName(n));
            }
        }
        return 1;
    }

    if ( action == 0 ) 
    {
        bsp_file = sfopen( bspfile, "r" );
        entity_file = sfopen( outfile, "w" );
    }
    else 
    {
        bsp_file = sfopen( bspfile, "r+" );
        entity_file = sfopen( outfile, "r" );
    }

    // Read the header
    sfread( &header, sizeof( bsp_header ), 1, bsp_file );

    // Execute the action
    if ( action == 0 ) 
    {
        char* buffer;
        // Check version
        if ( header.version < MIN_HEADER_VERSION )
        {
            fprintf( stderr, "Error the file \"%s\" is not a Half Life bsp file. - version is %d\n", argv[2] ,header.version);
            exit( EXIT_FAILURE );
        }
        printf( "Found %s\n", GetLumpName(lumpid));
        printf( "Found entity table at offset: %u\nExtracting %u bytes\n", endianAwareInt( header.lumps[lumpid].fileofs ), endianAwareInt( header.lumps[lumpid].filelen )-1 );
        buffer = smalloc( endianAwareInt( header.lumps[lumpid].filelen ) );
        fseek( bsp_file, endianAwareInt( header.lumps[lumpid].fileofs ), SEEK_SET );
        sfread( buffer, endianAwareInt( header.lumps[lumpid].filelen )-1, 1, bsp_file );
        sfwrite( buffer, endianAwareInt( header.lumps[lumpid].filelen )-1, 1, entity_file );
        free( buffer );
        printf( "%s written.\n", outfile );
    } else 
    {
        char* newmap;
        char* buffer;
        int entities_size;
        int bsp_size;
        int bsp_new_size;

        fseek( entity_file, 0, SEEK_END );
        entities_size = ftell( entity_file );
        fseek( entity_file, 0, SEEK_SET );

        fseek( bsp_file, 0, SEEK_END );
        bsp_size = ftell( bsp_file );
        fseek( bsp_file, 0, SEEK_SET );

        printf( "Integrating new entity table into map file\n" );

        bsp_new_size = bsp_size + ( entities_size - endianAwareInt( header.lumps[0].filelen ) );
        // Allocate memory for new map data
        newmap = smalloc( bsp_new_size );
        
        // Store new entity table
        sfread( newmap + endianAwareInt( header.lumps[0].fileofs ), entities_size, 1, entity_file );
        printf( "Writing lump %u at offset %u (%u kb in size)\n", 0, endianAwareInt( header.lumps[0].fileofs ), entities_size );

        // Update header according to new size and store new data
        {
            int i;
            for ( i=1; i<15; i++ ) 
            {
                int offset = endianAwareInt( header.lumps[i].fileofs );
                // Update offset if needed
                if ( endianAwareInt( header.lumps[i].fileofs ) > endianAwareInt( header.lumps[0].fileofs ) ) 
                {
                    header.lumps[i].fileofs = endianAwareInt( endianAwareInt( header.lumps[i].fileofs ) + ( entities_size - endianAwareInt( header.lumps[0].filelen ) ) );
                }
                // Copy data to memory
                printf( "Writing lump %u at offset %u (%u kb in size)\n", i, endianAwareInt( header.lumps[i].fileofs ), endianAwareInt(  header.lumps[i].filelen ) );
                fseek( bsp_file, offset, SEEK_SET );
                sfread( newmap + endianAwareInt( header.lumps[i].fileofs ), endianAwareInt( header.lumps[i].filelen ), 1, bsp_file );
            }
        }

        // Update entity length
        header.lumps[0].filelen = endianAwareInt( entities_size );

        // Set header
        memcpy( newmap, &header, sizeof( bsp_header ) );

        // Write data to file
        fseek( bsp_file, 0, SEEK_SET );
        sfwrite( newmap, bsp_new_size, 1, bsp_file );
        free( newmap );
        printf( "%s written.\n", bspfile );
    }

    fclose( bsp_file );
    fclose( entity_file );
}
