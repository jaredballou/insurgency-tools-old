<?php 
ob_start();
include "functions.php"; ?><!DOCTYPE html>
<html lang="en">
  <head>
<?php
	if (!$title) { $title = 'Insurgency Tools'; }
	echo "    <title>{$title}</title>\n";
?>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <script src='https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js'></script>
    <script src="http://code.jquery.com/ui/1.11.2/jquery-ui.js"></script>
<!-- Latest compiled and minified CSS -->
<link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.0/css/bootstrap.min.css'>
<link rel='stylesheet' href='https://cdn.datatables.net/1.10.4/css/jquery.dataTables.min.css'>

<!-- Optional theme -->
<link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.0/css/bootstrap-theme.min.css'>

<!-- Latest compiled and minified JavaScript -->
<script src='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.0/js/bootstrap.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/floatthead/1.2.10/jquery.floatThead.min.js'></script>
    <script src='//cdn.datatables.net/1.10.4/js/jquery.dataTables.min.js'></script>
<!-- D3 -->
<script src="http://d3js.org/d3.v3.min.js" charset="utf-8"></script>
<style>
body {
	padding-top: 50px;
}
body, html, .container {
	height: 100%;
	min-height:100%;
}
<?php if (isset($css_content)) { echo $css_content; } ?>
</style>
<script type="text/javascript" class="init">
	$('a.toggle-vis').on( 'click', function (e) {
		e.preventDefault();

		// Get the column API object
		var column = table.column( $(this).attr('data-column') );

		// Toggle the visibility
		column.visible( ! column.visible() );
	} );
<?php if (isset($js_content)) { echo $js_content; } ?>
</script>

<?php
function startbody() {
echo "  </head>\n  <body>\n";
include "menu.php";
}
?>
