#!/bin/bash
#
# Not working yet, weill eventually symlink VMTs
#
MATDIR=../../materials
VMTS=$(find $MATDIR | grep '\.vmt$')
for VMT in $VMTS
do
	BN=$(basename $VMT .vmt)
	BT=$(grep basetexture $VMT | grep -o '[^\\/]*$' | sed -e 's/"//g' -e 's/^[ \t\r\n]*//' -e 's/[ \t\r\n]*$//')
	if [ "$BN" != "$BT" ]
	then
		DIRNAME=$(dirname $VMT)
		TARG=$(readlink $DIRNAME/$BT.png)
		echo VMT is \"$VMT\"
		if [ "$TARG" != "$BT.png" ]
		then
			echo TARG is \"$TARG\"
			echo BN is \"$BN\"
			echo BT is \"$BT\"
		fi
	fi
done
