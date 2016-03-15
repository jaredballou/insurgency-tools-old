<?php
if (isset($use_ob)) {
	ob_start();
}
require_once "functions.php";
if (!isset($title)) {
	$title = 'Insurgency Tools';
}
?><!DOCTYPE html>
<html lang="en">
  <head>
    <title><?php echo $title; ?></title>
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
		//padding-top: 50px;
	}
	body, html, .container {
		height: 100%;
		min-height:100%;
	}
	table.floatThead-table {
		background-color: #FFFFFF;
	}
	td.details-control {
		background: url("images/details_open.png") no-repeat center center;
		cursor: pointer;
	}
	tr.shown td.details-control {
		background: url("images/details_close.png") no-repeat center center;
	}
	.beta {
		background: #cc0000;
		border: 1px solid black;
		margin: 3px;
		display: inline-block;
	}
	.bodygraph {
		position: relative;
		height: 340px;
		width: 157px;
		background-image: url("images/stats/body.png");
	}
	.vgui {
		background-size: 256px 128px;
		background-repeat: no-repeat;
		background-position: top 16px center;
		min-height: 144px;
		height: 144px;
		width: 256px;
		text-align: center;
		display: inline-block;
		white-space: normal;
		font-weight: bold;
		font-size: 1.2em;
		text-shadow: #000 0px 0px 1px, #000 -1px 0px 1px, #000 -1px 0px 1px, #000 0px 0px 1px;\
		-webkit-font-smoothing: antialiased;
	}
	.title {
		margin: 5px;
		align: center;
		font-size: 200%;
		font-weight: bold;
	}
	.help {
		margin: 5px;
		margin: 15px;
	}
	.section {
		margin: 5px;
		font-size: 175%;
		font-weight: bold;
	}
	.subsection {
		margin: 5px;
		font-size: 150%;
		font-weight: bold;
	}
	.desc {
		margin: 5px;
		font-style: italic;
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
	global $curpage,$pages,$includepath;
	echo "  </head>\n  <body>\n";
	include "{$includepath}/menu.php";
}
?>
