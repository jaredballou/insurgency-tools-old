#!/bin/bash
################################################################################
# Insurgency Data Extractor
# (C) 2014, Jared Ballou <instools@jballou.com>
# Extracts all game file information to the data repo
################################################################################

SCRIPTNAME=$(basename $(readlink -f "${BASH_SOURCE[0]}"))
SCRIPTDIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"


REPODIR="${SCRIPTDIR}/../.."
GAMEDIR="/home/insserver/serverfiles/insurgency"
DATADIR="${REPODIR}/data"
MAPSDIR="${GAMEDIR}/maps"

MAPSRCURL="rsync://ins.jballou.com/fastdl/maps/"
VERSION="$(grep -oP -i 'PatchVersion=([0-9\.]+)' "${GAMEDIR}/steam.inf" | cut -d'=' -f2)"

RSYNC="rsync -av"
BSPSRC="java -cp ../thirdparty/bspsrc/bspsrc.jar info.ata4.bspsrc.cli.BspSourceCli -no_areaportals -no_cubemaps -no_details -no_occluders -no_overlays -no_rotfix -no_sprp -no_brushes -no_cams -no_lumpfiles -no_prot -no_visgroups"
PAKRAT="java -jar ../thirdparty/pakrat/pakrat.jar"
VPK="${SCRIPTDIR}/vpk.php"
VTF2TGA="${SCRIPTDIR}/vtf2tga"

TD="${DATADIR}/theaters/${VERSION}"
PD="${DATADIR}/playlists/${VERSION}"
BLACKLIST="${DATADIR}/thirdparty/maps-blacklist.txt"
MAPSRCDIRS="materials/vgui/ materials/overviews/ resource/"
declare -A VPKPATHS
VPKPATHS["insurgency_misc_dir"]="scripts/theaters:${TD} scripts/playlists:${PD} resource:${DATADIR}/resource maps:${DATADIR}/maps"
VPKPATHS["insurgency_materials_dir"]="materials/vgui:${DATADIR}/materials/vgui materials/overviews:${DATADIR}/materials/overviews"
if [ ! -d "${TD}" ]
then
	EXTRACTFILES=1
else
	EXTRACTFILES=1
fi
GETMAPS=1
REMOVEBLACKLISTMAPS=0
DECOMPILEMAPS=1
SYNC_MAPS_TO_DATA=1
COPY_MAP_FILES_TO_DATA=1
CONVERT_VTF=1
MAPDATA=0
GITUPDATE=0

#If theater files for this Steam version don't exist, unpack desired VPK files and copy theaters to data
#This is not the "best" way to track versions, but it works for now
function extractfiles()
{
	echo Extracting VPK files
	for k in "${!VPKPATHS[@]}"
	do
		for PAIR in ${VPKPATHS[$k]}
		do
			IFS=':' read -r -a PATHS <<< "${PAIR}"
			$VPK "${GAMEDIR}/${k}.vpk" "${PATHS[0]}" "${PATHS[1]}"
		done
	done
}

function getmaps()
{
	#Copy map source files
	echo "Updating maps from repo"
	for EXT in bsp nav txt
	do
		$RSYNC -z --progress --ignore-existing --exclude='archive/' --exclude-from "${BLACKLIST}" "${MAPSRCURL}/*.${EXT}" "${GAMEDIR}/maps/"
	done
}

function removeblacklistmaps()
{
	echo "Removing blacklisted map assets from data directory"
	for MAP in $(cat $BLACKLIST | cut -d'.' -f1)
	do
		rm "${DATADIR}/maps/src/${MAP}_d.vmf" -vf
		rm ${DATADIR}/maps/{parsed,navmesh,.}/${MAP}.* -vf
		rm "${GAMEDIR}/maps/${MAP}.bsp.zip" -vf
	done
}

function decompilemaps()
{
	echo Updating decompiled maps as needed
	for MAP in ${MAPSDIR}/*.bsp
	do
		if [ "$(echo "${MAP}" | sed -e 's/ //g')" != "${MAP}" ]
		then
			#echo "SPACE"
			continue
		fi
		#echo "Updating ${MAP}"
		BASENAME=$(basename "${MAP}" .bsp)
		#echo "BASENAME is ${BASENAME}"
		if [ $(grep -c "^${BASENAME}.*\$" "${BLACKLIST}") -eq 0 ]
		then
			SRCFILE="${DATADIR}/maps/src/${BASENAME}_d.vmf"
			ZIPFILE="${MAP}.zip"
			if [ ! -e "${SRCFILE}" ] || [ "${MAP}" -nt "${SRCFILE}" ]
			then
				echo "Decompile ${MAP} to ${SRCFILE}"
				$BSPSRC "${MAP}" -o "${SRCFILE}"
			fi
			if [ ! -e "$ZIPFILE" ] || [ "${MAP}" -nt "${ZIPFILE}" ]
			then
				echo "Extract files from ${MAP} to ${ZIPFILE}"
				$PAKRAT -dump "${MAP}"
				echo Extracting map files from ZIP
				unzip -o "${ZIPFILE}" -x '*.vhv' 'models/*' 'scripts/*' 'sound/*' 'materials/maps/*' -d "${GAMEDIR}/maps/out"
			fi
		fi
	done
}

function sync_maps_to_data()
{
	echo "Synchronizing extracted map files with data tree"
	for SRCDIR in $MAPSRCDIRS
	do
		if [ -e "${GAMEDIR}/maps/out/${SRCDIR}" ]
		then
			$RSYNC -c "${GAMEDIR}/maps/out/${SRCDIR}" "${DATADIR}/${SRCDIR}"
		fi
	done
}

function copy_map_files_to_data()
{
	echo Copying map text files
	for TXT in ${GAMEDIR}/maps/*.txt ${GAMEDIR}/maps/out/maps/*.txt
	do
		BASENAME=$(basename "${TXT}" .txt)
		if [ $(grep -c "^${BASENAME}.*\$" "${BLACKLIST}") -eq 0 ]
		then
			cp "${TXT}" "${DATADIR}/maps/"
		fi
	done
}

function convert_vtf()
{
	echo Create PNG files for VTF files
	for DIR in $(find "${DATADIR}/materials/" -type d)
	do
		for VTF in ${DIR}/*.vtf
		do
			DIR=$(dirname "${VTF}")
			PNG="${DIR}/$(basename "${VTF}" .vtf).png"
			if [ ! -e "${PNG}" ] || [ "${VTF}" -nt "${PNG}" ]
			then
				echo Processing "${VTF}" to "${PNG}"
#				if [ -e "${PNG}" ]
#				then
#					rm -v "${PNG}"
#				fi
				$VTF2TGA "${VTF}" "${PNG}"
			fi
		done
	done
}

function gitupdate()
{
	echo "Adding everything to Git and committing"
	git -C "${DATADIR}" pull origin master
	git -C "${DATADIR}" add "*"
	git -C "${DATADIR}" commit -m "Updated game data files from script"
	git -C "${DATADIR}" push origin master
}

if [ $EXTRACTFILES == 1 ]
then
	extractfiles
fi

if [ $GETMAPS == 1 ]
then
	getmaps
fi

if [ $REMOVEBLACKLISTMAPS == 1 ]
then
	removeblacklistmaps
fi

if [ $DECOMPILEMAPS == 1 ]
then
	decompilemaps
fi

if [ $SYNC_MAPS_TO_DATA == 1 ]
then
	sync_maps_to_data
fi

if [ $COPY_MAP_FILES_TO_DATA == 1 ]
then
	copy_map_files_to_data
fi

if [ $CONVERT_VTF == 1 ]
then
	convert_vtf
fi

if [ $MAPDATA == 1 ]
then
	"${SCRIPTDIR}/mapdata.php"
fi

if [ $GITUPDATE == 1 ]
then
	gitupdate
fi
