<?php
function DisplayModSelection($compare=0) {
	
	$suffix = ($compare) ? '_compare' : '';
	$fields = array('mod','version','theater' => 'theaterfile');
	$vars = $data = array();
	foreach ($fields as $field => $varname) {
		if (is_numeric($field))
			$field = $varname;
//var_dump($field,$varname);
		$data[$field] = 
			($suffix) ?
				(($GLOBALS["{$varname}{$suffix}"] == $GLOBALS[$varname]) ? '-' : $GLOBALS["{$varname}{$suffix}"]) :
				$GLOBALS[$varname];
		echo "{$field}: <select name='{$field}{$suffix}' id='{$field}{$suffix}'></select>\n";
		$vars[$field] = $data[$field];
	}

	// If showing comparison options, put in blank as first entry to denote no comparison
	if ($compare)
		$vars['data']['-']['-']['-'] = '-';
	foreach ($GLOBALS['mods'] as $mname => $mdata) {
		foreach ($mdata as $vname => $vdata) {
			if (isset($vdata['scripts']['theaters'])) {
				foreach ($vdata['scripts']['theaters'] as $tname => $tpath) {
					$bn = basename($tname,".theater");
					$vars['data'][$mname][$vname][$bn] = $bn;
				}
			}
		}
	}

/*
foreach ($data as $field => $val) {
	var select_{$field}{$suffix} = \$('#{$field}{$suffix}');
	var cur_{$field}{$suffix} = '{$vars[$field]}';"
*/
?>
<script type="text/javascript">
jQuery(function($) {
	var data = <?php echo json_encode($vars['data'], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT); ?>;
	var suffix = '<?php echo $suffix; ?>';
	var compare = '<?php echo $compare; ?>';
	var mods = $('#mod' + suffix);
	var versions = $('#version' + suffix);
	var theaters = $('#theater' + suffix);

	$('#mod' + suffix).change(function () {
		var mod = $(this).val(), vers = data[mod] || [];
		var html = $.map(Object.keys(vers), function(ver){
			return '<option value="' + ver + '">' + ver + '</option>'
		}).join('');
		versions.html(html);
		versions.change();
	});

	$('#version' + suffix).change(function () {
		var version = $(this).val(), mod = $('#mod' + suffix).val(), items = data[mod][version] || [];

		var html = $.map(items, function(theater){
			return '<option value="' + theater + '">' + theater + '</option>'
		}).join('');
		theaters.html(html);
		theaters.change();
	});
	var html = $.map(Object.keys(data), function(mod){
		return '<option value="' + mod + '">' + mod + '</option>'
	}).join('');
	var cur_mod = '<?php echo $vars['mod']; ?>';
	var cur_version = '<?php echo $vars['version']; ?>';
	var cur_theater = '<?php echo $vars['theater']; ?>';
	mods.html(html);
	mods.val(cur_mod);
	mods.change();
	versions.val(cur_version);
	versions.change();
	theaters.val(cur_theater);
	theaters.change();
});
</script>
<?php
}

