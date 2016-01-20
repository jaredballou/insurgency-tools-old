<?php
define('QUOTE', '"');
define('CURLY_BRACE_BEGIN', '{');
define('CURLY_BRACE_END', '}');
define('SLASH', '/');
define('NEW_LINE', "\n");
define('ASTERIX', '*');
define('ESCAPE', '\\');

/**
 * 
 * A Valve KV/Sourcemod SMC parser for PHP
 * Version 2.0, completely redesigned reading.
 * 
 * @author Nikki
 *
 */
class KVReader {
	
	/**
	 * Write an array to a string
	 * @param array $arr
	 * 			The array to convert
	 */
	public function write($arr) {
		$str = "";
		$this->writeSegment($str, $arr);
		return $str;
	}
	
	/**
	 * Write an array to a file
	 * @param string $file
	 * 			The file name to write to
	 * @param array $arr
	 * 			The array to convert and write
	 */
	public function writeFile($file, $arr) {
		$contents = $this->write($arr);
		$fh = fopen($file, 'w');
		fwrite($fh, $contents);
		fclose($fh);
	}
	
	/**
	 * Write a segment of the array to a file.
	 * @param string $str
	 * 			The output string
	 * @param array $arr
	 * 			The array to read off
	 * @param int $tier
	 * 			The tier number, default 0
	 */
	private function writeSegment(&$str, $arr, $tier = 0) {
		$indent = str_repeat(chr(9), $tier);
		// TODO check for a certain key to keep it in the same tier instead of going into the next?
		foreach ($arr as $key => $value) {
			if (is_array($value)) {
				$key = '"' . $key . '"';
				$str .= $indent . $key  . "\n" . $indent. "{\n";
				
				$this->writeSegment($str, $value, $tier+1);
				
				$str .= $indent . "}\n";
			} else {
				$str .= $indent . '"' . $key . '"' . chr(9) . '"' . $value . "\"\n";
			}
		}
		return $str;
	}
	
	/**
	 * 
	 * Read a VDF/SMC file and convert it to an array
	 * @param string $file
	 * 			The file name
	 */
	public function readFile($file) {
		return $this->read(file_get_contents($file));
	}
	
	/**
	 * Read from a string and convert it to an array
	 * @param string $contents
	 * 			The VDF/SMC contents to convert to an array
	 */
	public function read($contents) {
		$out = array();
		$idx = 0;
		$this->readLevel($contents, strlen($contents), $idx, $out);
		return $out;
	}
	
	public function readLevel(&$contents, $length, &$index, &$output) {
		$firstPosition = -1;
		
		$prev = false;
		
		$key = false;
		
		while($index < $length) {
			$c = $contents[$index];
			switch($c) {
				case QUOTE:
					if($prev != ESCAPE) { // Ignore escaped quotes
						if($firstPosition == -1) {
							$firstPosition = $index + 1;
						} else {
							$len = $index - $firstPosition;
							$str = substr($contents, $firstPosition, $len);
//echo "<pre>\n";
//var_dump($str);
//var_dump($key);
//var_dump($output);
							if($key === false) {
								$key = $str;
							} else {
								// TODO verification of keys to make sure they are numeric before changing it into an array
								if(!empty($output[$key])) {
									if(is_array($output[$key])) {
										$output[$key][] = $str;
									} else {
										$arr = array(0 => $output[$key]);
										$arr[] = $str;
										$output[$key] = $arr;
									}
								} else {
									$output[$key] = $str;
								}
								$key = false;
							}
							$firstPosition = -1;
						}
					}
					break;
				case CURLY_BRACE_BEGIN:
					// Start section
					if($key !== false) {
//echo "start section\n";
//var_dump($key);
//var_dump($output);
//var_dump($output[$key]);
//echo "go section\n";
						$getback = array();
						$this->readLevel($contents, $length, $index, $getback);
						if (isset($output[$key])) {
							$od = $output[$key];
							if (is_array($output[$key])) {
								if (!isset($output[$key]['is_multiple_array'])) {
									$output[$key] = array('is_multiple_array' => 1, 0 => $od);
								}
								$output[$key][] = $getback;
							} else {
								$output[$key] = $getback;
							}
						} else {
							$output[$key] = $getback;
						}
						$key = false;
					}
					break;
				case CURLY_BRACE_END:
					// End section, return since this is called on each sub section
					return;
				case SLASH:
					if($firstPosition == -1 && $prev == SLASH) {
						// Increase index until NEW_LINE
						while($index < $length && $contents[$index] != NEW_LINE)
							$index++;
					}
					break;
				case ASTERIX:
					if($firstPosition == -1 && $prev == SLASH) {
						// Increase index until NEW_LINE
						while($index < $length) {
							if($contents[$index] == SLASH && $contents[$index - 1] == ASTERIX) {
								$index++;
								break;
							}
							$index++;
						}
					}
					break;
			}
			$prev = $c;
			
			$index++;
		}
	}
}
