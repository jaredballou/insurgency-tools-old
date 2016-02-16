<?php
class Theater {
	public $sections = array(
		'core',
		'player_settings',
		'ammo',
		'explosives',
		'player_gear',
		'weapons',
		'weapon_upgrades',
		'player_templates',
		'teams',
	);
}
include "vdfparser.php";
$kv = VDFParse("default_coop_shared.theater");
var_dump($kv);

