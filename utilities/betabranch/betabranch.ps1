
$steamcmd_url="http://media.steampowered.com/installer/steamcmd.zip"
$steamcmd_path="c:\steamcmd"
$steamcmd_file = "${steamcmd_path}/steamcmd.zip"

$steam_user="anonymous"
$steam_pass=""

$game_path="c:\steamapps\common\insurgency-beta"

$steamcmd_script_path = "$steamcmd_path\betabranch.txt"
$steamcmd_script_content = @"
@ShutdownOnFailedCommand 1
@NoPromptForPassword 1
login $steam_user $steam_pass
force_install_dir $game_path
app_update 222880 -beta beta validate
quit
"@

function Expand-ZIPFile($file, $destination)
{
	$shell = new-object -com shell.application
	$zip = $shell.NameSpace($file)
	foreach($item in $zip.items())
	{
		$shell.Namespace($destination).copyhere($item)
	}
}

if(![System.IO.File]::Exists($steamcmd_file)){
	(New-Object Net.WebClient).DownloadFile($steamcmd_url,$steamcmd_file)
	Expand-ZIPFile $steamcmd_file $steamcmd_path
}
#)
#Invoke-WebRequest $steamcmd_url -OutFile "${steamcmd_path}/steamcmd.zip"



//$CurrentContent = Get-Content $steamcmd_script_path
if(![System.IO.File]::Exists($steamcmd_script_file)){
	Set-Content -Path "${steamcmd_script_file}" -Value $steamcmd_script_content
}
echo "${steamcmd_path}/steamcmd.exe" +runscript "${steamcmd_script_file}"
