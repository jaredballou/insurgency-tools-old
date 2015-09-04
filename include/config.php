<?php
//MySQL Server connection settings
$mysql_server   = 'localhost';
$mysql_username = 'username';
$mysql_password = 'password';
$mysql_database = 'database';

$dbprefix = isset($_REQUEST['dbprefix']) ? $_REQUEST['dbprefix'] : 'hlstats';
$gamecode = isset($_REQUEST['gamecode']) ? $_REQUEST['gamecode'] : 'insurgency';
$hlstatsx_root='/opt/hlstatsx-community-edition';
$hlstatsx_heatmaps="{$hlstatsx_root}/web/hlstatsimg/games/{$gamecode}/heatmaps";

if (isset($use_hlstatsx_db)) {
	require "{$hlstatsx_root}/heatmaps/config.inc.php";
	mysql_connect(DB_HOST,DB_USER,DB_PASS);
	mysql_select_db(DB_NAME);
}

$cache_dir = 'cache';
//Create cache dir if needed
if (!file_exists($cache_dir)) {
        mkdir($cache_dir);
}

//Old versions and maps that I just don't want in the list
$excludemaps = array(
	'amber_spirits_coop_beta3',
	'amber_spirits_coop_beta4',
	'amber_spirits_coop_beta5',
	'amber_spirits_coop_beta6',
	'angle_iron_coop_beta2',
	'angle_iron_coop_beta3',
	'angle_iron_coop_beta4',
	'battle_sdk_example',
	'block_party_coop_beta4',
	'bridge_coop_b3',
	'bunker_busting_coopv1_2',
	'bunker_busting_coopv1_3',
	'caves_coop1',
	'clean_sweep_coop_beta2',
	'contact_coop_oldv1',
	'district_coop_oldv1',
	'fortress_coop_beta1',
	'fortress_coop_beta2',
	'fortress_coop_beta3',
	'fortress_coop_beta4',
	'game_day_coopv1_2',
	'goldeneye_facility_coop',
	'heights_coop_oldv1',
	'hijacked_b2',
	'ins_prison_b3',
	'jail_break_coopv1_1',
	'kandagal_b3',
	'kandagal_coop_b3',
	'launch_control_coopv1_4',
	'launch_control_coopv1_5',
	'launch_control_coopv1_6',
	'market_coop_oldv1',
	'ministry_coop_oldv1',
	'mout',
	'sdk_coop',
	'siege_coop_oldv1',
	'tell_coop_v2',
	'tell_v1',
	'the_burbs_coop_beta5',
	'training',
	'tunnel_rats_coopv1_4',
	'warehouse_coop_Alpha5_2B',
	'warehouse_coop_beta_1'
);

$tables = array(
        'Games_Defaults' => array(
                'allfields'     => array('code', 'parameter', 'value'),
                'fields'        => array('parameter')
        ),
        'Heatmap_Config' => array(
                'allfields'     => array('game','map','xoffset','yoffset','flipx','flipy','rotate','days','brush','scale','font','thumbw','thumbh','cropx1','cropy1','cropx2','cropy2'),
                'fields'        => array('xoffset','yoffset','flipx','flipy','rotate','days','brush','scale','font','thumbw','thumbh','cropx1','cropy1','cropx2','cropy2')
        ),
        'Actions' => array(
                'allfields'     => array('game', 'code', 'reward_player', 'reward_team', 'team', 'description', 'for_PlayerActions', 'for_PlayerPlayerActions', 'for_TeamActions', 'for_WorldActions'),
                'fields'        => array('code')
        ),
        'Ranks' => array(
                'allfields'     => array('game','image','minKills','maxKills','rankName'),
                'fields'        => array('rankName')
        ),
        'Awards' => array(
                'allfields'     => array('game', 'code', 'name', 'verb'),
                'fields'        => array('name', 'verb')
        ),
        'Ribbons' => array(
                'allfields'     => array('game', 'awardCode', 'awardCount', 'special', 'image', 'ribbonName'),
                'fields'        => array('image', 'ribbonName')
        ),
        'Weapons' => array(
                'allfields'     => array('game', 'code', 'name', 'modifier'),
                'fields'        => array('name')
        ),
        'Teams' => array(
                'allfields'     => array('game', 'code', 'name', 'hidden', 'playerlist_bgcolor', 'playerlist_color', 'playerlist_index'),
                'fields'        => array('name')
        ),
        'Roles' => array(
                'allfields'     => array('game', 'code', 'name'),
                'fields'        => array('name')
        )
);
require_once("config.private.php");
?>
