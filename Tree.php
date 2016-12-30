<?php

// include_once 'traits.php';

class Tree implements Serializable, IteratorAggregate, ArrayAccess, Countable {
	// use DBugTrait, TreeTrait, OldTreeTraits;

	protected $array = [];

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
	public function __call($method, $argv)
	{
		switch ($method) {
		 case 'getArray':
		 case 'getArrayCopy':
			return $this->array; break;
		 case 'getJson':
		 case 'json_encode':
			return json_encode($this->array); break;
		}
		$trace = debug_backtrace();
		trigger_error("Undefined property on Fail object: '".$method."' \nin "
			.$trace[0]['file']
		 .' on line ' .$trace[0]['line'],E_USER_NOTICE);
	}
	public function toArray() {return $this->array;}
	public function toJson() {return json_encode($this->array);}

}


$arr = new Tree([]);