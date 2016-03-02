<?php
// New KeyValues class, create tree of objects for parsing
$data = array(
	'theater' => array(
		'ammo' => array(
			'ammo_9mm' => array(
				'IsBase' => 0,
				'print_name' => '9mm',
			),
		),
		'weapons' => array(
			'weapon_m9' => array(
				'IsBase' => 0,
				'print_name' => 'M9',
			),
			'?nightmap' => array(
				'weapon_m14' => array(
					'IsBase' => 0,
					'print_name' => 'M14',
				),
			),
		),
	),
);

class KeyValues {
	static $instances = 0;
	public $id;
	protected static $used;
	protected $name;
	protected $value;
//	protected $type;
	protected $parent;
	protected $path;
	protected $children;
	protected $conditional;
	protected $subkey;
	protected $peer;
//	protected $
/*
	protected $m_iKeyName = INVALID_KEY_SYMBOL;
	protected $m_iDataType = TYPE_NONE;

	protected $m_pSub = NULL;
	protected $m_pPeer = NULL;
	protected $m_pChain = NULL;

	protected $m_sValue = NULL;
	protected $m_wsValue = NULL;
	protected $m_pValue = NULL;
	
	protected $m_bHasEscapeSequences = false;
	protected $m_bEvaluateConditionals = true;

*/
	public function __clone() {
		$this->id = ++self::$instances;
	}

	public function __construct($name,$parent='',$conditional='') {
		$this->id = ++self::$instances;
		$this->name = $name;
		$this->conditional = $conditional;
		if ($parent != '')
		{
			$this->parent = $parent->id;
//			$this->conditional = $parent->conditional;
			$this->path = "{$parent->path}/{$name}";
		} else {
			$this->path = $name;
		}
	}

//	public function () {
	public function AddChild($kv) {
		$this->children[] = $kv;
	}

	public function __get($property) {
		if (property_exists($this, $property)) {
			return $this->$property;
		}
	}

	public function __set($property, $value) {
		if (property_exists($this, $property)) {
			$this->$property = $value;
		}
		return $this;
	}
}
function ArrayToKV(&$array,&$parent='',$conditional='') {
	if ($parent == '') {
		if (count($array) == 1) {
			$key = array_keys($array)[0];
			$val = array_values($array)[0];
			return ArrayToKV($val,new KeyValues($key),'');
		} else {
			$parent = new KeyValues('root');
		}
	}
	foreach ($array as $key=>$val) {
		if ($key[0] == '?') {
//			$conditional = $key;
			$kv = ArrayToKV($val,$parent,$key);
		} else {
			$kv = new KeyValues($key,$parent,$conditional);
			if (is_array($val)) {
				//$kv->AddChild(
				ArrayToKV($val,$kv,$conditional);
			} else {
				$kv->value = $val;
			}
		}
		$parent->AddChild($kv);
	}
	return $parent;
}
$kv = ArrayToKV($data);
var_dump($kv);
