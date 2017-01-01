<?php
trait ReportTrait {
	public $trace;
	public $previous = null;
	protected $code; # protected on parent
	protected $message = '';
	protected $variable = null;
	protected $report = null;
	protected $value = null;
	protected $depth = 0;
	protected $class = null;
	protected $function = null;

	public function getMsgPart()  { return $this->variable;}
	public function getVariable() { return $this->variable;}
	public function getValue()    { return $this->value; }
	public function getDepth()    { return $this->depth; }

	protected $exception = [
		'code' => '',
		'variable' => null,
		'report' => '',
		'value' => null,
		'depth' => 0,
	];

	public function tracedMsg() {

		$argc = func_num_args();

		if ($argc!==0) {
			$argv = func_get_args();
			foreach ($argv as $k=>$v) { if (is_a($v,'Exception')) $this->prevous = $v; }
		}
		parent::__construct('', 0, $this->prevous);
		$this->trace = $this->getTrace();

		# set class from trace here

		$this->trace = array_slice(debug_backtrace(),1);

		$consts = [E_USER_NOTICE=>1,E_USER_ERROR=>1,E_USER_WARNING=>1];

		foreach ($argv as $k=>$v) {
			if (is_int($v)) {
				$this->code = $v;
			} elseif (is_string($v)) {
				if     ( $v==='' )   $this->report = '';
				elseif ($v[0]==='!') $this->report = substr($v,1);
				elseif (strpos($v,' ')!==false) $this->message = $v;

				elseif (ctype_punct($v[0])) { # no spaces and starting punct:
					if (preg_match('/^[-+]{0,1}\d+$/',$v)!==0) $this->depth=abs($v);
					elseif ($v[0]==='$') $this->variable = substr($v,1);
					elseif ($v[0]==='=') $this->value    = substr($v,1);
					elseif ($v[0]==='[' && $v[-1]===']')$this->store = substr($v,1,-1);
					else $this->message = $v;
				
				} else { # no spaces and NOT starting punct:
					if ($this->class!==null && method_exists(
					 $this->class,$v))        $this->function= $v;
					elseif (class_exists($v)) $this->class   = $v;
					else                      $this->message = $v;
				}
			}
		}
		$argv = array_merge(['depth'=>1,'message'=>''], $argv);
		extract(array_merge(debug_backtrace()[ $argv['depth'] ], $argv));

		if (isset($class)) $message =  "$message: $class::$function $file($line)\n";
		elseif (isset($function)) $message =  "$message: $function() $file($line)\n";
		else $message =  "$message in $file($line)\n";

		if (isset($level)) trigger_error($message, $level);
		elseif (isset($trig)) {
			$label = $trig==='!' ? '' : strtolower($trig[1]);
			if     ($label==='n') $label = "Notice: ";
			elseif ($label==='i') $label = "Info: ";
			elseif ($label==='w') $label = "Warning: ";
			elseif ($label==='e') $label = "Error: ";
			echo $label.$message;
		}
		return $message;
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
	public function offsetGet($key, $variable=null, $value=null) {
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
	public function offsetSet($key, $set) {
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


