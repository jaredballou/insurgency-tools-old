<?php
/*

More mucking about with KeyValues parsing

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
function getNextToken($string, $options)
{
	$token = '';
	$trimmed = false;
	while (!$trimmed) {
		$string = trim($string);
		if (startsWith($string,'//')) {
			$string = trim(substr($string,strpos($string,'\n')+1));
		} else {
			$trimmed = true;
		}
	}
//var_dump($string);
	if (startsWith($string, '"'))
	{
		if ($options['escapeCharacters'])
		{
			$endFound = false;
			$index = 1;
			while (!$endFound)
			{
				$index = strpos($string,'"',1);
				if ($index === -1)
				{
					$index = count($string);
					$endFound = true;
				} else {
					if (!$index) {
						$endFound = true;
					} else {
						if ($string[$index - 1] !== '\\')
						{
							$endFound = true;
						}
					}
				}
			}
			$token = substr($string, 0, $index + 1);
			$string = substr($string, $index + 1);
		}
		else
		{
			$index = strpos($string,'"',1);
			$token = substr($string, 0, $index + 1);
			$string = substr($string, $index + 1);
		}
	}
	else if (startsWith($string, '{') || startsWith($string, '}'))
	{
		$token = $string[0];
		$string = substr($string, 1);
	}
	else
	{
		$stop = preg_match('/[ \t\n\v\f\r"{}]/', $string);
		if ($stop)
		{
			$token = substr($string, 0, $stop);
			$string = substr($string, $stop);
		}
		else
		{
			$token = $string;
			$string = '';
		}
	}
	$result = array(
		"token" => $token,
		"remainder" => $string
	);
//	var_dump($token);
	return $result;
}
function escapeString($value, $options)
{
	if ($options['escapeCharacters'])
	{
		$value = preg_replace('/\\/', '\\\\', $value);
	}
	$value = preg_replace('/"/', '\\"', $value);
	return $value;
}
function parseQuotedValue($value, $options)
{
	$value = substr($value,1, -1);
	/*
	if ($options->escapeCharacters) {
	$value = preg_replace('/\n/', '\n', $value);
	$value = preg_replace('/\t/', '\t', $value);
	$value = preg_replace('/\v/', '\v', $value);
	$value = preg_replace('/\b/', '\b', $value);
	$value = preg_replace('/\r/', '\r', $value);
	$value = preg_replace('/\f/', '\f', $value);
	$value = preg_replace('/\a/', '\a', $value);
	$value = preg_replace('/\\\\/', '\\', $value);
	$value = preg_replace('/\\\?/', '?', $value);
	$value = preg_replace('/\'/', '\'', $value);
	$value = preg_replace('/\\"/', '"', $value);
	}
	 */
//var_dump($value);
	return $value;
}
function checkConditional($conditional, $conditions)
{
	if (startsWith($conditional, '[') && endsWith($conditional, ']'))
	{
		$conditional = $conditional->slice(1, -1);
		$negate = false;
		if (startsWith($conditional, '!'))
		{
			$negate = true;
			$conditional = $conditional->slice(1);
		}
		return array_search($conditional, $conditions) !== -1^$negate;
	}
}

class KeyValues
{
	public $escapeCharacters = true;
	public $evaluateConditionals = false;
	public $conditions = array();
	public $key;
	public $value;
	public $retrieveKeyValueFile = null;
	function __constructor($key = "", $value = "", $options = array())
	{
		if ($key)
			$this->key = $key;
		if ($value)
			$this->value = $value;
/*
		$this->escapeCharacters = isset($options['escapeCharacters']) ? $options['escapeCharacters'] : true;
		$this->evaluateConditionals = isset($options['evaluateConditionals']) ? $options['evaluateConditionals'] : false;
		$this->conditions = isset($options['conditions']) ? $options['conditions'] : [];
		$this->retrieveKeyValueFile = isset($options['retrieveKeyValueFile']) ? $options['retrieveKeyValueFile'] : null;
*/
	}
	function addSubKey($key, $value)
	{
		if (!is_array($this->value))
		{}

		array_push($this->value, new KeyValues($key, $value));
	}
	function findKey($key)
	{
		$result = null;
		if (is_array($this->value))
		{
			for ($i = 0;
				$i < count($this->value); $i++)
			{
				$current = $this->value[$i];
				if ($current instanceof $KeyValues && $current->key === $key)
				{
					$result = $current;
					break;
				}
			}
		}
		return $result;
	}
	function mergeKeys($kv)
	{
		if (is_array($kv->value))
		{
			$kv->value->forEach(function ($source)
			{
				if ($source instanceof KeyValues)
				{
					$destination = $this->findKey($source->key);
					if ($destination)
					{
						$destination->mergeKeys($source);
					}
					else
					{
						$this->addSubKey($source->key, $source->value);
					}
				}
			}
			);
		}
	}
	function recursiveSave($depth, $options)
	{
		$kvString = '';
		if (is_array($this->value))
		{
			$subKeys = $this->value->slice();
			if ($options->sortKeys)
			{
				$subKeys->sort(function ($a, $b)
				{
					if (!$a instanceof $KeyValues && !$b instanceof $KeyValues)
					{
						return 0;
					}
					if (!$a instanceof $KeyValues)
					{
						return -1;
					}
					if (!$b instanceof $KeyValues)
					{
						return 1;
					}
					$firstKey = strtolower($a->key);
					$secondKey = strtolower($b->key);
					return ($firstKey < $secondKey) ? -1 : ($firstKey > $secondKey) ? 1 : 0;
				}
				);
			}
			for ($i = 0;
				$i < $depth; $i++)
			{
				$kvString += '\t';
			}
			$kvString += '"' + escapeString($this->key, $options) + '"' + '\n';
			for ($i = 0;
				$i < $depth; $i++)
			{
				$kvString += '\t';
			}
			$kvString += '{' + '\n';
			$subKeys->forEach(function ($subKey)use( & $depth,  & $options)
			{
				if ($subKey instanceof $KeyValues)
				{
					$kvString += $subKey->recursiveSave($depth + 1, $options);
				}
			}
			);
			for ($i = 0;
				$i < $depth; $i++)
			{
				$kvString += '\t';
			}
			$kvString += '}' + '\n';
		}
		else if ($options['allowEmptyStrings'] === true || $this->value !== '')
		{
			for ($i = 0;
				$i < $depth; $i++)
			{
				$kvString += '\t';
			}
			$kvString += '"' + escapeString($this->key, $options) + '"' + '\t\t' + '"' + escapeString($this->value, $options) + '"' + '\n';
		}
		return $kvString;
	}
	function recursiveLoad($kvString, $options)
	{
		$finished = false;
		$extracted = array();
		$opening = getNextToken($kvString, $options);
		$kvString = $opening['remainder'];
//var_dump($opening,$kvString);
		while (!$finished && $kvString)
		{
			$results = getNextToken($kvString, $options);
			if ($results['token'] === '')
			{}

			if ($results['token'] === '}')
			{
				$finished = true;
				break;
			}
			$key = $results['token'];
			if (startsWith($key,'"') && endsWith($key, '"'))
			{
				$key = parseQuotedValue($key, $options);
			}
			$kvString = $results['remainder'];
			$accepted = true;
			$results = getNextToken($kvString, $options);
			if (startsWith($results['token'],'[') && endsWith($results['token'], ']'))
			{
				if ($options['evaluateConditionals'])
				{
					$accepted = checkConditional($results['token'], $options['conditions']);
				}
				$kvString = $results['remainder'];
				$results = getNextToken($kvString, $options);
			}
			$value = $results['token'];
			if ($value === null)
			{}

			if ($value === '}')
			{}

			if (startsWith($value,'[') && endsWith($value,']'))
			{}

			if (startsWith($value,'"') && endsWith($value,'"'))
			{
				$value = parseQuotedValue($value, $options);
			}
			if ($value === '{')
			{
				$results = $this->recursiveLoad($kvString, $options);
				$value = $results['extracted'];
				$kvString = $results['remainder'];
			}
			else
			{
				$kvString = $results['remainder'];
				$results = getNextToken($kvString, $options);
				if (startsWith($results['token'],'[') && endsWith($results['token'],']'))
				{
					if ($options->evaluateConditionals)
					{
						$accepted = checkConditional($results['token'], $options['conditions']);
					}
					$kvString = $results['remainder'];
					$results = getNextToken($kvString, $options);
				}
			}
			if ($accepted)
			{
var_dump("recurse",$key,$value);
				array_push($extracted, new KeyValues($key, $value));
			}
		}
		$closing = getNextToken($kvString, $options);
		$kvString = $closing['remainder'];
		$result = array(
			"extracted" => $extracted,
			"remainder" => $kvString
		);
		return $result;
	}
	function load($kvString, $options = array())
	{
		$options['escapeCharacters'] = isset($options['escapeCharacters']) ? $options['escapeCharacters'] : true;
		$options['evaluateConditionals'] = isset($options['evaluateConditionals']) ? $options['evaluateConditionals'] : false;
		$options['conditions'] = isset($options['conditions']) ? $options['conditions'] : [];
		$options['retrieveKeyValueFile'] = isset($options['retrieveKeyValueFile']) ? $options['retrieveKeyValueFile'] : null;

		$finished = false;
		$includes = array();
		$bases = array();
		$extracted = array();
		while (!$finished)
		{
			$results = getNextToken($kvString, $options);
			if ($results['token'] === '')
			{
				$finished = true;
				break;
			}
			if ($results['token'] === '#include')
			{
				$results = getNextToken($results['remainder']);
				if (!$results['token'])
				{}

				array_push($includes, $results['token']);
				$kvString = $results['remainder'];
			}
			else if ($results['token'] === '#base')
			{
				$results = getNextToken($results['remainder']);
				if (!$results['token'])
				{}

				array_push($bases, $results['token']);
				$kvString = $results['remainder'];
			}
			else
			{
				$key = $results['token'];
				if (startsWith($key,'"') && endsWith($key,'"'))
				{
					$key = parseQuotedValue($key, $options);
				}
				$kvString = $results['remainder'];
				$accepted = true;
				$results = getNextToken($kvString, $options);
				if (startsWith($results['token'],'[') && endsWith($results['token'],']'))
				{
					if ($options['evaluateConditionals'])
					{
						$accepted = checkConditional($results['token'], $options['conditions']);
					}
					$kvString = $results['remainder'];
					$results = getNextToken($kvString, $options);
				}
				if ($results['token'] !== '{')
				{}

				$results = $this->recursiveLoad($kvString, $options);
				$value = $results['extracted'];
				$kvString = $results['remainder'];
				if ($accepted)
				{
var_dump("load",$key,$value);
					array_push($extracted, new KeyValues($key, $value));
				}
			}
		}
		// TODO: Fix include and base handling
		foreach ($includes as $include) {
			$extracted[0] = array_merge_recursive($include,$extracted[0]);
		}
		foreach ($bases as $base) {
			$extracted[0]->mergeKeys($base);
		}
//var_dump($extracted);
		if (count($extracted) === 1)
		{
			$this->key = $extracted[0]->key;
			$this->value = $extracted[0]->value;
		}
		else
		{
			$this->value = $extracted;
		}
	}

	function save($options = array())
	{
		$options['escapeCharacters'] = isset($options['escapeCharacters']) ? $options['escapeCharacters'] : true;
		$options['sortKeys'] = isset($options['sortKeys']) ? $options['sortKeys'] : false;
		$options['allowEmptyStrings'] = isset($options['allowEmptyStrings']) ? $options['allowEmptyStrings'] : false;

		return $this->recursiveSave(0, $options);
	}
}
