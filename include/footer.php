<div class="footbar"><a href="http://validator.w3.org/check?uri=referer"><img src="images/html5.png" alt="Valid HTML 5.0" height="31" width="88" /></a><a href="http://jigsaw.w3.org/css-validator/check/referer"><img style="border:0;width:88px;height:31px" src="http://jigsaw.w3.org/css-validator/images/vcss" alt="Valid CSS!" /></a></div>
</body>
</html>
<?php
$html = ob_get_clean();
if (0==1) {
	$config = array(
//		'clean'			=> TRUE,
//		'indent'		=> TRUE,
//		'output-html'		=> TRUE,
		'wrap'			=> 0,
//		'new-inline-tags'	=> 'li, option',
		'indent-spaces'		=> 4,
//		'show-warnings'		=> TRUE,
//		'indent-cdata'		=> TRUE,
	);
	$tidy = new tidy;
//	$tidy->parseString($html, $config, 'utf8');
//	$tidy->cleanRepair();
//	echo $tidy;
	$clean = $tidy->repairString($html);
	echo $clean;
//print_r($tidy->getConfig());
} else {
	echo $html;
}
if (0==1) {
	require_once "include/htmLawed.php";
	$config = array(
//		'comment'=>0,
//		'cdata'=>1,
		'tidy'=>1,
//		'schemes'=>'*:*',
//		'safe'=>0,
	); 
	$processed = htmLawed($html, $config); 
	echo $processed;
}
?>
