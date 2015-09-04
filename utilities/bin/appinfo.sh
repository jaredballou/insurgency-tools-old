#!/bin/bash
APPID=$1
APPINFODIR=$(dirname $0)../../data/appdata
if [ ! -e $APPINFODIR ]
then
    	mkdir $APPINFODIR
fi
./steamcmd/steamcmd.sh +app_info_print $APPID +quit | sed -n -e "/\"$APPID\"/,\$p" > $APPINFODIR/$APPID.txt
