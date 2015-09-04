#!/bin/sh
################################################################################
# Insurgency Data Extractor
# (C) 2014, Jared Ballou <instools@jballou.com>
# Extracts all game file information to the data repo
################################################################################
GAMEDIR=../../..
REPODIR=../..
DATADIR=../../data
MAPSRCURL=rsync://ins.jballou.com/fastdl/maps/
VERSION=$(grep -i '^patchversion=' $GAMEDIR/steam.inf | cut -d'=' -f2)
RSYNC="/c/cygwin64/bin/rsync.exe -av"
BSPSRC="java -cp ../thirdparty/bspsrc/bspsrc.jar info.ata4.bspsrc.cli.BspSourceCli -no_areaportals -no_cubemaps -no_details -no_occluders -no_overlays -no_rotfix -no_sprp -no_brushes -no_cams -no_lumpfiles -no_prot -no_visgroups"
PAKRAT="java -jar ../thirdparty/pakrat/pakrat.jar"
MAPSRCDIRS="materials/vgui/ materials/overviews/ resource/"

TD=$DATADIR/theaters/$VERSION
PD=$DATADIR/playlists/$VERSION

#If theater files for this Steam version don't exist, unpack desired VPK files and copy theaters to data
#This is not the "best" way to track versions, but it works for now
if [ ! -d $TD ]; then
	echo Extracting VPK files
	$GAMEDIR/../bin/vpk "$GAMEDIR/insurgency_misc_dir.vpk"
	$GAMEDIR/../bin/vpk "$GAMEDIR/insurgency_materials_dir.vpk"
	echo Creating $TD
	mkdir $TD
	mkdir $PD
	$RSYNC $GAMEDIR/insurgency_misc_dir/scripts/theaters/ $TD/
	$RSYNC $GAMEDIR/insurgency_misc_dir/scripts/playlists/ $PD/
	$RSYNC $GAMEDIR/insurgency_misc_dir/resource/ $DATADIR/resource/
	$RSYNC $GAMEDIR/insurgency_materials_dir/materials/vgui/ $DATADIR/materials/vgui/
	$RSYNC $GAMEDIR/insurgency_materials_dir/materials/overviews/ $DATADIR/materials/overviews/
fi
#Copy map source files
echo "Updating maps from repo"
$RSYNC -z --progress --ignore-existing --exclude='*.bz2' --exclude='archive/' $MAPSRCURL $GAMEDIR/maps/
echo Updating decompiled maps as needed
for MAP in $GAMEDIR/maps/*.bsp
do
	SRCFILE=$DATADIR/maps/src/$(basename $MAP .bsp)_d.vmf
	ZIPFILE=$MAP.zip
	if [ ! -e $SRCFILE ] || [ $MAP -nt $SRCFILE ]
	then
		echo "Decompile $MAP to $SRCFILE"
		$BSPSRC "$MAP" -o "$SRCFILE"
	fi
	if [ ! -e $ZIPFILE ] || [ $MAP -nt $ZIPFILE ]
	then
		echo "Extract files from $MAP to $ZIPFILE"
		$PAKRAT -dump "$MAP"
		echo Extracting map files from ZIP
		unzip -o "$ZIPFILE" -x '*.vhv' 'models/*' 'scripts/*' 'sound/*' 'materials/maps/*' -d "$GAMEDIR/maps/out"
	fi
done
echo "Synchronizing extracted map files with data tree"
for SRCDIR in $MAPSRCDIRS
do
	if [ -e $GAMEDIR/maps/out/$SRCDIR ]
	then
		$RSYNC -c $GAMEDIR/maps/out/$SRCDIR $DATADIR/$SRCDIR
	fi
done
$RSYNC -c $GAMEDIR/maps/out/maps/ $GAMEDIR/maps/
echo Copying map text files
$RSYNC $GAMEDIR/maps/*.txt $DATADIR/maps/

echo Create PNG files for VTF files
VTFS=$(find $DATADIR/materials/ -type f | grep '\.vtf$')
echo "Begin loop"
for VTF in $VTFS
do
	DIR=$(dirname $VTF)
	PNG="$DIR/$(basename $VTF .vtf).png"

	if [ ! -e $PNG ] || [ $VTF -nt $PNG ]
	then
		WINFILE=$(echo $VTF | sed -e 's/\//\\/g' -e 's/\\$//')
		WINPATH=$(echo $DIR | sed -e 's/\//\\/g' -e 's/\\$//')
		echo Processing $VTF to $PNG
		if [ -e $PNG ]
		then
			rm -v $PNG
		fi
		vtfcmd -file "$WINFILE" -output "$WINPATH" -exportformat "png"
	fi
done
echo "Adding everything to Git and committing"
git -C "$DATADIR" pull origin master
git -C "$DATADIR" add "*"
git -C "$DATADIR" commit -m "Updated game data files from script"
git -C "$DATADIR" push origin master
