#!/bin/bash
MAPSDIR=/opt/fastdl/maps
cd ${MAPSDIR}

for file in *.bsp *.nav
do
  if [ -L "$file" ]; then continue; fi
  if [ "$file.bz2" -ot "$file" ]
  then
    echo "Creating bzip for $file"
    bzip2 -k -f "$file"
  fi
  if [ "$file.md5" -ot "$file" ]
  then
    echo "Creating MD5 for $file"
    md5sum "${file}" > "${file}.md5"
  fi

  filename=$(basename "$file" | sed -e 's/\.\(bsp\|nav\)$//g')
  if [ "${2}" == "ln" ]
  then
    for mode in $(grep -P '"(ambush|battle|checkpoint|firefight|flashpoint|hunt|infiltrate|occupy|push|skirmish|strike)"' $filename.txt 2>/dev/null | cut -d'"' -f2)
    do
      link="$filename $mode.bsp"
      if [ ! -e "$link" ]
      then
        echo Creating symlink for "$link"
        ln -sf "$file" "$link"
      fi
    done
  fi
done

# Clean up old BZIP and MD5 files
for file in *.md5 *.bz2
do
  filename=$(basename $file | sed -e 's/\.\(md5\|bz2\)$//g')
  if [ ! -e "${filename}" ]
  then
    rm -rvf $file
  fi
done
