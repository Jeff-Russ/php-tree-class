<?php

trait UnusedTreeTrait
{
	# THE FOLLOWING WHERE NEVER USED: 

	# set to Root and GET
	public function Eject() {
		return $this->offsetUnset($key); # $key ? should be on $this ?
	}
	public function canAssign($key) { # Returns truthy if assignable, falsy if not.
		# non-assignable returns: 'Tree'
		# null means unset, a boolean means it's a Tree object. A string means 
		# it's some other type. Empty strings, false and null are all assignable.
		if ( array_key_exists($key, $this->_['T']) ) {
			if ($this->_['.@']!==false) {
				if (is_a($this->_['T'][$key], get_class())) return true;
				elseif ($this->_['.@']==='trees') return false;
				elseif ($this->_['.@']===true) return gettype($this->_['T'][$key]);
			}
			elseif (is_a($this->_['T'][$key], get_class())) return false;
			else return '';
		} else return true;
	}

	public static function arrayType($var, $gettype=false) {
		if (is_array($var)) return 'array';
		elseif (is_a($var, get_class())) return 'Node';
		elseif($var instanceof ArrayAccess) { $ret = 'ArrayAccess';
			if ($var instanceof Traversable)  $ret = $ret.' Traversable';
			if ($var instanceof Countable)    $ret = $ret.' Countable';
			if ($var instanceof Serializable) $ret = $ret.' Serializable';
		}
		elseif (!$getttype) return false;
		else return gettype($var);
	}
	public function isTree($key) { return is_a($this->_['T'][$key], get_class()); }
	
	public function childType($key)
	{
		if ( array_key_exists($key, $this->_['T']) ) {
			if (is_a($this->_['T'][$key], get_class())) return "Node";
			else return gettype($this->_['T'][$key]);
		} else return null;
	}

}