#!/bin/bash
MAPSRC="rsync://ins.jballou.com/fastdl/maps/"
MAPCYCLESRC="rsync://ins.jballou.com/fastdl/mapcycle_files/"
HOMEDIR="/home/insserver"
INSDIR="${HOMEDIR}/serverfiles/insurgency"
MAPDIR="${INSDIR}/maps/"
SMDIR="${INSDIR}/addons/sourcemod"
THEATERDIR="${INSDIR}/scripts/theaters"
DATADIR="${INSDIR}/insurgency-data"

rsync --progress -av $MAPSRC $MAPDIR --exclude=archive
rsync --progress -av $MAPCYCLESRC $INSDIR
cd $SMDIR && git pull origin master
cd $THEATERDIR && git pull origin master
cd $DATADIR && git pull origin master

