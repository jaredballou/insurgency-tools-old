<?php
/*
class.Tree.php
KeyValues Tree Data Structure

This is the base set of classes for processing KeyValues files. This should be
constrained to the standards compliant processing directives and methods as the
Source SDK implements them.
*/
function startsWith($haystack, $needle)
{
	// search backwards starting from haystack length characters from the end
	return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
}

function endsWith($haystack, $needle)
{
	// search forward starting from end minus needle length characters
	return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
}
	// KVNode is the generic object that represents all sections and keyvalue pairs
    class KVNode {
		public $name;
		public $root = NULL;
		public $parent = NULL;
		public $children = array();
        public $value = NULL;
		public $depth = -1;
		public $path = '';
		public $condition = NULL;
		public $escapeCharacters = false;
		public $maxname = 0;
		
		// Instantiate object
        public function __construct($name='root',$value=NULL,&$parent=NULL) {
            $this->name = $name;
            $this->value = $value;
			if ($parent) {
				$this->root = $this->parent =& $parent;
				// If root isn't set on parent, assume parent is the root
				if ($parent->root) {
					$this->root =& $parent->root;
				}
				if (strlen($name) > $this->parent->maxname) {
					$this->parent->maxname = strlen($name);
				}
				$this->depth = $parent->depth+1;
				$this->path = $parent->path;
				$this->condition = $parent->condition;
			}
			$this->path.= "/{$name}";
        }
		// Add child node to this node
		public function addNode($name,$value=NULL) {
			$node = new KVNode($name,$value,$this);
			$this->children[] =& $node;
			return $node;
		}
		// Emit KeyValues data as text
		public function outputKV() {
			$out = '';
			$indent = str_repeat("\t",$this->depth);
            $out.="{$indent}\"{$this->name}\"";
			if ($this->value) {
				// Display KeyValue pair
				// TODO: Unfuck this tab calculation
				// Get difference from maxname and this name
				$diff = ($this->parent->maxname-strlen($this->name));
				// Raw tab count
				$tabs = ceil($diff/4)+1;
				// Add tab for values that divide by 4
				$tabs+=((strlen($this->name) % 4 == 0) || ($this->parent->maxname % 4 == 0) || ($diff % 4 == 0));
				// Subtract a tab for those that will be thrown off by the addition of the quotes
				$tabs-=(($diff % 4 == 1) && ($this->parent->maxname % 4 == 1));
				//echo "{$this->name} tabs {$tabs} diff {$diff} maxname {$this->parent->maxname} strlen ".strlen($this->name)."\n";
				//var_dump($tabs);
				$out.=str_repeat("\t",$tabs);
				$out.="\"{$this->value}\"\n";
			} else {
				// Display section
				$out.="\n{$indent}{\n";
				foreach( $this->children as $child) {
					if ($child) {
					//var_dump($child->maxname);
						$out.=$child->outputKV();
					}
				}
            	$out.="{$indent}}\n";
            }
			return $out;
        }
    }
	// Core KeyValues class that is created for each file to process
	class KeyValues extends KVNode {
		public $bases = array();
		public $includes = array();
		public $filepath = '';
		public $filename = '';
		public $kvString = '';
		public $kvBuffer;
		
		// Pull the next token off the kvBuffer and advance the pointer
		public function getToken() {
			$string =& $this->kvBuffer;
			$token = '';
			$trimmed = false;
			while (!$trimmed) {
				$string = trim($string);
				if (startsWith($string,"//")) {
					$string = trim(substr($string,strpos($string,"\n",2)-1));
				} else {
					$trimmed = true;
				}
			}
			if (startsWith($string, '"'))
			{
				$endFound = false;
				$index = 1;
				while (!$endFound)
				{
					$index = strpos($string,'"',$index);
					if ($index === -1)
					{
						$index = strlen($string);
						$endFound = true;
					} else {
						if (!$index) {
							$endFound = true;
						} else {
							if ($string[$index - 1] !== '\\') {
								$endFound = true;
							}
						}
					}
				}
				$token = trim(substr($string, 1, $index-1));
				$string = trim(substr($string, $index+1));
			}
			else if (startsWith($string, '{') || startsWith($string, '}'))
			{
				$token = $string[0];
				$string = trim(substr($string, 1));
			} else {
				$stop = preg_match('/[ \t\n\v\f\r"{}]/', $string);
				if ($stop) {
					$token = substr($string, 0, $stop);
					$string = substr($string, $stop);
				} else {
					$token = $string;
					$string = '';
				}
			}
			return $token;
		}
		// Load a file into this object
		public function loadFile($filename) {
			$this->filepath = realpath($filename);
			$this->filename = basename($filename);
			$this->load(file_get_contents($filename));
		}
		// Load a string into buffer and parse
		public function load($kvString) {
			$this->kvString = $kvString;
			$this->parse();
		}
		// Parse KeyValues data string
		public function parse() {
			if (!$this->kvString) {
				return;
			}
			$this->kvBuffer = $this->kvString;
			$node =& $this;
			while ($this->kvBuffer) {
				$name = $this->getToken();
				if ($name == "}") { // End section
					if ($node->parent) {
						$ns =& $node->parent;
						$node =& $ns;
					}
				} else {
					$value = $this->getToken();					
					if ($value == "{") { // Begin section
						$ns = $node->addNode($name);
						$node =& $ns;
					} else { // Value
						$node->addNode($name,$value);
					}
				}
			}
		}
		// Output data as formatted KeyValues
		public function outputKV() {
			$out = '';
			foreach( $this->children as $child) {
				if ($child) {
					$out.=$child->outputKV();
                }
            }
			return $out;
        }
		// Dump KeyValues data to output
		public function dump() {
			echo $this->outputKV();
		}
	}
$kv = new KeyValues();
//$kv->loadFile("default_checkpoint.theater");
$kv->loadFile("default_coop_shared.theater");
$kv->dump();
//echo "oh\n";
//var_dump($kv);
//echo "oh\n";

//echo "oh\n";
/*
$kv->addNode("#base","default.theater");
$kv->addNode("#base","default_coop_shared.theater");
$kv->addNode("#base","default_checkpoint.theater");
$theater = $kv->addNode("theater");
$ammo = $theater->addNode("ammo");
$weapons = $theater->addNode("weapons");
$weapon_m14 = $weapons->addNode("weapon_m14");
$weapon_m14->addNode("print_name","#weapon_m14");
$weapon_m14->addNode("cost","2");
$weapon_m14->addNode("weight","32");
$weapon_m14->addNode("class","weapon_rifle");
$core = $theater->addNode("core");
*/
