<?php

// include_once 'traits.php';

class Tree implements Serializable, IteratorAggregate, ArrayAccess, Countable
{
	use ArrayTrait; # DBugTrait, TreeTrait, OldTreeTraits;
}

trait ArrayTrait #implements Serializable, IteratorAggregate, ArrayAccess, Countable
{
	protected $array = [];
	protected static $globlock = false;
	protected $lock = false;

	public function __construct() {
		$argc = func_num_args(); $argv = func_get_args();
		if ($argc===1 && is_array($argv[0])) $this->array = $argv[0]; 
		elseif ($argc>1) $this->array= $argv;
	}
	public function serialize(){ return serialize($this->array); }
	public function unserialize($serialized) { $this->array = unserialize($serialized);}
	public function getIterator() { return new ArrayIterator( $this->array ); }
	public function count() { return count($this->array); }
	public function length(){ return count($this->array); }

	public function offsetSet($key, $value) {
		$isset = isset($key);
		if (!$isset || )
		if ($key===null) $this->array[] = $value;
		else $this->array[$key] = $value;
	}
	public function offsetGet($key) {
		return array_key_exists($key,$this->array) ? $this->array[$key] : null;
	}
	public function offsetExists($key) {
		return array_key_exists($key, $this->array);
	}
	public function offsetUnset($key) {
		unset($this->array[$key]);
	}
	public function __callStatic($method, $argv)
	{
		if (substr($method,0,3)==='get') {

		}
	}
	public function __call($method, $argv)
	{
		if (substr($method,0,3)==='get') {
			switch ($method) {
				case'getLock': return $this->lock;
				case'getArray': case'getArrayCopy': return $this->array;
				case'getJson': return json_encode($this->array);
			}
		}
		$trace = debug_backtrace()[0];
		"Undefined method ".get_called_class()."'$method'";
		self::_notice("Undefined property on object: '$method'"
	}
	public function toArray() {return $this->array;}
	public function toJson() {return json_encode($this->array);}

	public function __toString() {
		return json_encode($this->array, JSON_PRETTY_PRINT);
	}
	protected static function _notice($message, $trace_0=false) {
		$end = $trace ? "\nFile: ".$trace['file']." Line: "$trace['line']} :'';
		trigger_error("$message ".get_called_class()..$end, E_USER_NOTICE);

	}
	protected static trace
}


$arr = new Tree([
	'key'=>'someval',
	'otherkey'=>'otherval',
	[ 'inner' => "innerval"],
]);

var_dump($arr->toJson());