#!/bin/bash
for map in *.bsp
do
  if [ -L "$map" ]; then continue; fi
  if [ "$map.bz2" -ot "$map" ]
  then
    echo "Creating bzip for $map"
    bzip2 -k -f "$map"
  fi
  mapname=$(basename "$map" .bsp)
  for mode in $(grep -P '"(ambush|battle|checkpoint|firefight|flashpoint|hunt|infiltrate|occupy|push|skirmish|strike)"' $mapname.txt 2>/dev/null | cut -d'"' -f2)
  do
    link="$mapname $mode.bsp"
    if [ ! -e "$link" ]
    then
      echo Creating symlink for "$link"
      ln -sf "$map" "$link"
    fi
  done
done
for map in *.nav
do
  if [ -L "$map" ]; then continue; fi
  if [ "$map.bz2" -ot "$map" ]
  then
    echo "Creating bzip for $map"
    bzip2 -k -f "$map"
  fi
done

