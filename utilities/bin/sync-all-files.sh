#!/bin/bash
MAPSRC="rsync://ins.jballou.com/fastdl/maps/"
MAPCYCLESRC="rsync://ins.jballou.com/fastdl/mapcycle_files/"
USER="insserver"
HOMEDIR="/home/${USER}"
INSDIR="${HOMEDIR}/serverfiles/insurgency"
MAPDIR="${INSDIR}/maps/"
BLACKLIST="${DATADIR}/thirdparty/maps-blacklist.txt"
REPOS="addons:https://github.com/jaredballou/insurgency-addons.git addons/sourcemod:https://github.com/jaredballou/insurgency-sourcemod.git insurgency-data:https://github.com/jaredballou/insurgency-data.git scripts/theaters:https://github.com/jaredballou/insurgency-theaters.git"

echo "Sync Git repos"
for REPO in $REPOS
do
	REPO_PATH="${INSDIR}/$(echo "${REPO}" | cut -d':' -f1)"
	REPO_URL="$(echo "${REPO}" | cut -d':' -f2-100)"
	PARENT="$(dirname "${REPO_PATH}")"
	if [ ! -e "${REPO_PATH}/.git" ]
	then
		mkdir -p "${REPO_PATH}"
		cd "${REPO_PATH}"
		git init
		git add remote origin "${REPO_URL}"
	fi
	cd "${REPO_PATH}"
	git pull origin master
	git submodule init
	git submodule update
done

echo "Sync Maps"
for EXT in bsp nav txt
do
        rsync -z --progress --ignore-existing --exclude='archive/' --exclude-from "${BLACKLIST}" "${MAPSRC}/*.${EXT}" "${MAPDIR}"
done

echo "Sync Map Cycle Files"
rsync --progress -av "${MAPCYCLESRC}" "${INSDIR}"

echo "Removing blacklisted maps"
for MAP in $(cat "${BLACKLIST}")
do
        rm -vf "${MAPDIR}${MAP}"
done

