<?php
/*
VPK reader
*/
include 'utilities/thirdparty/php-vpk-reader/VPKReader.php';
$vpk_file = '/home/insserver/serverfiles/insurgency/insurgency_misc_dir.vpk';
$vpk = new \VPKReader\VPK($vpk_file);

$ent_tree = $vpk->vpk_entries;

function print_tree($node, $pwd='') {
	if(!is_null($node) && count($node) > 0) {
		if(is_array($node)){
			echo '<ul>';
			foreach($node as $name=>$subn) {
				$fp = "$pwd/$name";
				echo "<li>$fp";
				print_tree($subn, $fp);
				echo '</li>';
			}
			echo '</ul>';
		}else{ // Node
			echo " | size: $node->size bytes";
		}
	}
}
print_tree($ent_tree);
/*
$data = $vpk->read_file('/path/to/file.txt', 10000);
echo $data;
*/
