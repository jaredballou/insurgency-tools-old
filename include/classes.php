<?php
$files = glob("{$includepath}/classes/*");
foreach ($files as $file) {
	require_once($file);
}
