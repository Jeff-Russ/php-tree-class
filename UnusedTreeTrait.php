<?php

trait UnusedTreeTrait
{
	# THE FOLLOWING ARE NOT NOT USED:

	public function keyState($key) { # Returns falsy if assignable, truthy if not.
		# null means unset, a boolean means it's a Tree object. A string means else
		if ( array_key_exists($key, $this->_['T']) ) {
			if ($this->_['.@']!==false) {
				# Trees are never assignable if there is ANY Lock:
				if (is_a($this->_['T'][$key], get_class())) return true;
				# now we know it's not a Tree:
				elseif ($this->_['.@']==='trees') return ''; #only locking Trees
				else return gettype($this->_['T'][$key]);   #locking all
			}# no lock and it's a Tree:
			elseif (is_a($this->_['T'][$key], get_class())) return false;
			else return '';  # not a Tree and no lock
		} else return null; # not assigned at all
	}
	
	public function Convert($set=null) # called on pathkeys ending with '.Convert', or '.@'
	{
		if ($set=null) return $this->_['.@']; # <-GET, SET:
		elseif ($set===$this->_['.@']) return $this;
		else $this->_['.@'] = $set;
		if (!empty($this->_['T'])) self::_rConfig($this);
		return $this;
	}

	public function Lock($set=null) { # called on pathkeys ending with '.Lock', or '.!'
		if ($set=null) return $this->_['.!']; # <-GET, SET:
		else $this->_['.!'] = $set;
	}

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