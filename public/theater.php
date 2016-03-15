<?php
/*
This tool takes a number of mutators (settings per section to change) and
snippets (small segments of a theater file) and combines them to generate one
complete theater. This is integrated with another SourceMod plugin which
includes most of this functionality as an in-game menu that admins can use to
generate custom theaters on the fly. It is still very much in-progress and help
would be welcomed on this one.
*/

//Root Path Discovery
do { $rd = (isset($rd)) ? dirname($rd) : realpath(dirname(__FILE__)); $tp="{$rd}/rootpath.php"; if (file_exists($tp)) { require_once($tp); break; }} while ($rd != '/');
require_once "${includepath}/class.Spyc.php";

$title="Theater Creator";

if (isset($_REQUEST['fetch'])) {
	require_once("{$includepath}/functions.php");
} else {
	require_once("{$includepath}/header.php");
}

LoadSnippets($snippets);
$theater = getfile("{$theaterfile}.theater",$mod,$version,$theaterpath);
if (isset($_REQUEST['fetch'])) {
	switch ($_REQUEST['fetch']) {
		case 'snippets':
			$fetch_data = $snippets;
			break;
	}
	if (isset($fetch_data)) {
		header('Content-Type: application/json');
		echo json_encode($fetch_data, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
		exit;
	}
}
/*
<script>
$(document).ready(function(){
    $(".toggle-section").click(function(){
        var target = "#" + $(this).attr('id').replace("header-","");
        $(target).toggle();
    });
});
	$('#tbody').on('click', 'td.details-control', function () {
		var tr = $(this).closest('tr');
		var row = table.row( tr );
 
		if ( row.child.isShown() ) {
			// This row is already open - close it
			row.child.hide();
			tr.removeClass('shown');
		}
		else {
			// Open this row
			row.child( serverDetails(row.data()) ).show();
			tr.addClass('shown');
		}
	});

</script>
*/
//var_dump("{$theaterfile}.theater",$mod,$version,$theaterpath);

startbody();
echo "<div class='beta'>This tool is still new and may be buggy. Please report problems and let me know what theaters you want to see added.</div>\n";

if ($_REQUEST['go'] == "Generate Theater") {
	echo "<textarea rows='20' cols='120'>".GenerateTheater()."</textarea>";
} else {
	DisplayTheaterCreationMenu();
}
include "${includepath}/footer.php";
exit;