<?php
$curpage = basename($_SERVER['SCRIPT_FILENAME']);
$pages = array(
'stats.php' => 'Stats (Theater Parser)',
'maps.php' => 'Maps (Overlay Viewer)',
'cvarlist.php' => 'CVAR List',
'https://github.com/jaredballou' => 'My Github');
?>
<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="index.php">Insurgency Tools</a>
        </div>
        <div id="navbar" class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
<?php
	foreach ($pages as $url => $page) {
		$act = ($url == $curpage) ? ' class="active"' : '';
		echo "            <li{$act}><a href='{$url}'>{$page}</a></li>\n";
	}
?>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </nav>
