#!/bin/bash
# betabranch.sh
# (c_ 2016, Jared Ballou <insurgency@jballou.com>
#
# Install and amintain a Beta Branch installation on OSX.
# TODO:
#  * Linux!

originalpwd=$(pwd)

if [ "$(uname -s)" == "Darwin" ]
then
	echo "Mac OS X Detected"
	os=osx
else
	echo "Linux Detected"
	os=linux
fi

declare -a varlist
config=""
# Function to allow one-line addition of variables
add_var()
{
	varname=$1
	desc=$2
	default=$3
	varlist=("${varlist[@]}" $varname)
	varprompt="${desc} [${default}]"
	# Set default if var is not yet set
	if [ -z ${!varname} ]
	then
		val=$default
		if [ "$defaults_loaded" != "1" ]
		then
			read -p "${varprompt}: " val
			if [ "$val" == "" ]
			then
				val=$default
			fi
		fi
		eval ${varname}=$val
	fi
	config="${config}# ${varprompt}\n$varname=\"${!varname}\"\n"
}
echo $varlist
# Load defaults
defaults_file=~/steamcmd/betabranch.conf
if [ -e $defaults_file ]
then
	defaults_loaded=1
	source $defaults_file
fi

# Add variables to structure
add_var "steamcmd_path" "Directory to use for installing SteamCMD" "~/steamcmd"
add_var "steamcmd_url" "URL for SteamCMD Installer Package" "https://steamcdn-a.akamaihd.net/client/installer/steamcmd_${os}.tar.gz"
add_var "steamcmd_file" "SteamCMD Installer Download Target" "${steamcmd_path}/steamcmd.zip"
add_var "steamcmd_log" "SteamCMD Log File" "${steamcmd_path}/steamcmd.log"
add_var "steam_user" "Steam Username" "anonymous"
add_var "steam_pass" "Steam Password" ""
add_var "game_path" "Path for game installation" "${steamcmd_path}/steamapps/common/insurgency-beta"
add_var "steamcmd_script_file" "SteamCMD Game Update Script File" "${steamcmd_path}/betabranch.txt"

# Create SteamCMD directory
if [ ! -e $steamcmd_path ]
then
	mkdir -p $steamcmd_path
fi

# Download and Extract SteamCMD
if [ ! -e ${steamcmd_file} ]
then
	curl -s -o ${steamcmd_file} ${steamcmd_url}
	tar -xzvpf ${steamcmd_file} -C ${steamcmd_path}
fi

echo -ne $config > $defaults_file

# Create SteamCMD updater script
echo -n "@ShutdownOnFailedCommand 1
@NoPromptForPassword 1
login ${steam_user} ${steam_pass}
force_install_dir ${game_path}
app_update 222880 -beta beta validate
quit
" > "${steamcmd_script_file}"

cd ${steamcmd_path} && ./steamcmd.sh +runscript ${steamcmd_script_file}
