<?php

trait StorableTrait
{
	protected static $array = [];
	protected static $count = 0;
	protected $index = null;
	protected $thresh = 10;

	public function index() { return $this->index; }
	public static function getStore() { return self::$array; }

	public function __destruct() {
		if ($this->index!==null) {
			if (self::$array[$this->index]===$this)
				unset(self::$array[$this->index]);
			else
				unset(self::$array[array_search($this,self::$array,true)]);
		}
	}
	public static function resort($thresh=null) {
		if ($thresh!==null) {self::$thresh = abs($thresh - 1); $run = true;}
		else {
			end(self::$array);
			$run = end(self::$array) > (self::$count + self::$thresh);
		}
		if ($run===true) {
			$inst = self::$array; $self::$array = []; $i = 0;
			foreach ($inst as $k=>$v)
				{$self::$array[$i] = $v; $v->index = $i; $i++;}
			self::$count = $i;
		}
	}
	public function store() {
		if ($this->index===null) {
			self::$array[] = $this; end(self::$array);
			$this->index = key(self::$array);
			self::$count++;
		}
	}
	public function unstore() {
		if ($this->index!==null) {
			if (self::$array[$this->index]===$this)
				unset(self::$array[$this->index]);
			else
				unset(self::$array[array_search($this,self::$array,true)]);
			$this->index = null; self::$count--;
			self::resort();
		}
	}
}

trait DBugTrait {
	
	public static function offsetError ($key=null) {
		extract(self::offsetError);
		if ($key!==null) $message = $message.": '$key'";
		if ($reporting==='e'): trigger_error($message, E_USER_ERROR);
		elseif ($reporting==='w'): trigger_error($message, E_USER_WARNING);
		elseif ($reporting==='n'): trigger_error($message, E_USER_NOTICE);
		endif;
		return $value;
	}

	public static function err($msg, $opts=[]) {
		$return = false;
		if (is_string($opts)) {
			if ($opts[0]==='e') $level=E_USER_ERROR;
			elseif ($opts[0]==='w') $level=E_USER_WARNING;
			elseif ($opts[0]==='n') $level=E_USER_NOTICE;
		} elseif(is_array($opts)) {
			foreach ($opts as $k => $v) {
				if (!is_string($v)) $return = $v;
				elseif ($v[0]==='e') $level=E_USER_ERROR;
				elseif ($v[0]==='w') $level=E_USER_WARNING;
				elseif ($v[0]==='n') $level=E_USER_NOTICE;
			}
		} else $return = $opts;
		if (!isset($level)) $level = E_USER_NOTICE;
		trigger_error($msg, $level);
		return $return;
	}

	public static function warn($msg)  { trigger_error($msg, E_USER_WARNING); return NIL; }
	public static function notice($msg){ trigger_error($msg, E_USER_NOTICE);  return NIL; }
	public static function error($msg) { trigger_error($msg, E_USER_ERROR);   return NIL; }
}

trait OldTreeTraits {
	
	public function offsetGet($key, $variable=null, $value=null)
	{
		# recursive getter, subsequent calls
		if ($variable!==null) {
			$next = array_shift($key);
			if ($next===null) return $value; # DONE
			elseif (is_a($variable, get_class()) || is_array($variable)) {
				$value = $variable[$next];
				if (empty($key) || $value===null) return $value;
				else $value = $this->offsetGet($key, $value, $value);
			} else {
				$trail = $next . implode($key);
				self::err("recrusive key contains trailing garbage: '".$trail."'",'w');
			}
			return $value;
		}
		# normal key getter:
		elseif ( array_key_exists( $key,$this->_['T'] ) ) return $this->_['T'][$key];

		elseif (ctype_punct($key[0])) {
			if (array_key_exists($key,$this->_)) return $this->_[$key];

			# get tree at numeric Depth:
			if (is_numeric($key)) { $i=(int)$key;
				if ($key==$i) {
					if ($i<0) $i=$this->_['.#'] + $i;
					return $this->_['.+'][$i];
				}
			}
			# recursive key getter first call:
			if (strpos($key,'/')!==false) {
				$keys = explode ('/', $key);
				if ($keys[0]==='') $keys[0] = "/";
				if (end($keys)==='') array_pop($keys);
				return $this->offsetGet($keys, $this);
			}
			elseif ($key==='?') return $this->_['/']===$this; # is Root?
			elseif ($key==='[n]')return count($this->_['T']);  # count
		}
		else return self::err("Tree key out of bounds",'w');
	}
	public function offsetSet($key, $set)
	{
		# modify tree properties
		if (ctype_punct($key[0])) {

			# set tree at numeric Depth:
			if (is_numeric($key)) { $i=(int)$key;
				if ($key==$i) {
					if ($i<0) $i=$this->_['.#'] + $i;
					$tree = $this->_['.+'][$i];
					# error if tree is locked:
					if ($tree->_['.#']===0) return self::err('cannot reassign Root Tree','e');
					elseif ($tree->_['..']->_['.!']) self::err('cannot reassign locked Tree','e');
					# tree is not locked:
					else {
						$p = $tree->_['..']; # Parent of tree being reassigned
						$k = $tree->_['.$'];  # key on Parent of tree being reassigned
						unset($p[ $k ]);     # unset should make the old note be Root
						$p[ $k ] = $set;     # add the new value
						return $this;   # maybe we could return the unset instead? for when method mode
					}
				}
			}
			// # recursive key getter first call:
			// if (strpos($key,'/')!==false) {
			// 	$keys = explode ('/', $key);
			// 	if ($keys[0]==='') $keys[0] = "/";
			// 	if (end($keys)==='') array_pop($keys);
			// 	return $this->offsetGet($keys, $this);
			// }
			elseif ($key==='.$') return $this->Key($set); # change Key
			elseif ($key==='..') return $this->Parent($set); # set Parent
			elseif ($key==='.!') $this->_['.!'] = $set; # set Lock level
			elseif ($key==='.@') return $this->Convert($set); # set Convert, apply
		}
		if ( array_key_exists($key, $this->_['T']) ) { 
			$prev_val = $this->_['T'][$key];
			$is_tree = is_a($prev_val, get_class());
			if ( $this->_['.!']!==false ) /* there is at least some lock */ {

				# anything but Lock being false blocks trees. true blocks all:
				if ($this->_['.!']===true || $is_tree)
					return self::err('the key '.$key.' is a locked Tree','e');

				# we can overwrite an array but only if Lock is set to 'trees'
				elseif (is_array($prev_val) && $this->_['.!']!=='trees')
					return self::err('the key '.$key.' is locked','e');

				# If it was a tree we should formally remove it (make it Root):
				if ($is_tree) {
					$prev_val->_['.#'] = 0;
					$prev_val->_['..']= $prev_val;
					$prev_val->_['/'] = $prev_val;
					if ( !empty($this->_['T'][$key]->_['T']) )
						self::_rConfig( $this->_['T'][$key] );
				}
			}
			unset($this->_['T'][$key]);
		}
		$this->_['T'][$key] = $set;
		self::_rConfig($this, $key);
		return $prev_val ? $prev_val : $this;
	}
}

trait TreeTrait {

	public function echoTree($echo=true) {
		ini_set('xdebug.var_display_max_depth', 100);
		ini_set('xdebug.var_display_max_children', 256);
		ini_set('xdebug.var_display_max_data', 1024);
		$tree_ob = clone $this;
		$recurse = function( &$tree ) use (&$recurse)
		{
			if ( !empty($tree->_['T']) )
				foreach ($tree->_['T'] as $k => $v)
					if ( is_a($v, get_class()) ) $recurse($v);
			$tree->_['..'] = $tree->_['..']->_['.$'];
			$tree->_['/'] = $tree->_['/']->_['.$'];
			return $tree;
		};
		$result = $recurse($tree_ob);
		ob_start(); var_dump($result);
		$str = preg_replace('/\s+(=>)\s+/s','=>',ob_get_clean());
		$str = str_replace([ "class "],'',$str);
		$str = preg_replace(['/locked/', '/#[0-9]+ \([0-9]+\)/', '/\n\s*}/',
			'/string\([0-9]+\)\s/','/array\([0-9]+\)\s/', '/object\((Tree)\) {/',
			'/bool\(([a-zA-Z]+)\)/', '/int\(([0-9]+)\)/','/\n\s*(\s.\..=>)/',],'$1',$str);
		$str = preg_replace(['/\n\s*\$_=>\s*/s','/\n\s*(.#.=>)/s','/\n\s*(.\.\..=>)/s',
		'/\s+(.\!.=>)/s','/\s+(.\~.=>)/s','/\n\s*(.\/.=>)/s',
		'/{/', '/\s*(.\$.=>)/'],' $1',$str); // cut \n
		if ($echo) echo $str;
		return $str;
	}
}
