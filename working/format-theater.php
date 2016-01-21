<?php
/*
This tool is in-flight, the original idea was to have it neaten up theaters.
New thinking is I merge it into the classes and have the writing done there.
*/
if (count($argv) > 1) {
	for($i=1;$i<count($argv);$i++) {
		if (file_exists($argv[$i])) {
			FixTheater($argv[$i]);
		}

	}
} else {
	$theaterpath = '../../data/theaters/1.8.4.3';
	$files = glob("{$theaterpath}/*.theater");
	foreach ($files as $file) {
		FixTheater("{$theaterpath}/default_checkpoint.theater");
	}
}
function FixTheater($filename, $coltabs = 5) {
	echo "Formatting {$filename}\n";
	$lines = file($filename);
	$indent = 0;
	$output = array();
	$strlen = array();
	foreach ($lines as $line_num => $line) {
		$comment="";
		$out="";
		$line = trim($line);
		if ($line == "") {
			$output[] = $line;
			continue;
		}
		$bits = explode("//",$line,2);
		if (count($bits) > 1) {
			$line = trim($bits[0]);
			if (strlen($line))
				$comment.="\t";
			$comment.="// ".trim($bits[1]);
		}
		$line = preg_replace('/"\s+"/','||',$line);
		if ($line == "}") {
			$indent--;
		}
		for($i=0;$i<$indent;$i++) {
			$out.="\t";
		}
		$bits = explode('||',$line);
		if (count($bits) > 1) {
			$out.=trim($bits[0])."\"";
			if (trim($bits[0]) != "\"#base") {
				$len = strlen($bits[0]);
				$numtabs = ($coltabs-floor(($len+1)/8));
				for($i=0;$i<$numtabs;$i++) {
					$out.="\t";
				}
			} else {
			$out.=" ";
			}
			$out.="\"{$bits[1]}";
		} else {
			$out.=$line;
		}
		if ($line == "{") {
			$indent++;
		}
		$output[] = $out.$comment;
	}
	file_put_contents($filename,implode("\r\n",$output));
}
