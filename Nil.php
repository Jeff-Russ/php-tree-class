<?php
include_once 'traits.php';

if (!defined('NIL')) define('NIL', '');

if (!trait_exists ('NilTrait')) {
	trait NilTrait {
		/*objects with NilTrait should be checkable with: if ($var==NIL) ...
		so that Nil object can be check in the same way as NIL constants.
		NOTE that ($var==NIL) works the same if $var is anything falsy
		*/
		final public function __toString() { return NIL; }
	}
}

class Nil extends Exception { use NilTrait, StorableTrait;

	protected $code; # protected on parent
	protected $msg_part = '';
	protected $variable = null;
	protected $report = '';
	protected $value = null;
	protected $depth = 0;

	public function getMsgPart()  { return $this->variable;}
	public function getVariable() { return $this->variable;}
	public function getValue()    { return $this->value; }
	public function getDepth()    { return $this->depth; }

	public function setVariable($variable) {
		$this->variable = $variable; $this->setMessage($this->msg_part);
	}
	public function setValue($value) {
		$this->value = $value; $this->setMessage($this->msg_part);
	}
	public function setDepth($depth=null) {
		$trace = $this->getTrace();
		if ($depth===null) {
			if (empty($trace)) $this->depth = 0;
			$recalc = $this->depth!==0;
		} else {$recalc = $depth!==$this->depth; $this->depth = $depth;}

		if ($recalc) { # $trace[0] is what we want for $depth = 1
			$count = count($trace);
			if ($this->depth > $count - 1) $this->depth = $count;
			$target = $this->depth-1;
			$this->file = $trace[$this->depth-1]['file'];
			$this->line = $trace[$this->depth-1]['line'];
		}
		if ($recalc || $depth===null) $this->setMessage($this->msg_part);
	}

	public function setMessage($msg_part='') {
		$this->message = "Failed [{$this->code}]";
		if ($msg_part!=='') $this->message.=" $msg_part";
		if ($this->variable!==null) {   #USE SOME OTHER TEST TO SEE IF STRINGABLE
			$this->message .= ": {$this->variable}";
			if ($this->value!=='') $this->message.=", value: {$this->value}";
		}
		$this->message.="\nLine {$this->line} {$this->file}\n".$this->getTraceAsString();
	}

	public function __construct() {
		$argc = func_num_args();
		if ($argc===0) {parent::__construct(); return;}
		$argv = func_get_args();  $code = $previous = null; $store = false;
		if ($argc===1 && is_array($argv[0])) $argv = $argv[0];

		foreach ($argv as $i=>$v) {
			if (is_string($v)) {
				if (strpos($v,' ')!==false) $this->msg_part = $v;
				else { # ^ has spaces, no spaces:
					if ($v==='') $this->report = '';
					elseif ( !ctype_punct($v[0]) ) {
						if ($v==='store') {$store=true; if($code===null)$code=$v;}
						else $code = $v; 
					} else {
						if     ($v[0]==='!') $this->report   = substr($v,1);
						elseif ($v[0]==='@') $code           = substr($v,1);
						elseif (preg_match('/^[-+]{0,1}\d+$/',$v)!==0) $this->depth=abs($v);
						elseif ($v[0]==='$') $this->variable = substr($v,1);
						elseif ($v[0]==='=') $this->value    = substr($v,1);
						else $code = $v;
					}
				}
			} elseif (is_integer($v)) {$code===null ? $code=$v : $this->depth=$v;}
			elseif ($v instanceof Exception) $previous = $v;
			elseif (is_array($v)) { foreach ($v as $opt=>$val) $this->opts[$opt] = $val;} 
		}
		if ($previous===null) parent::__construct();
		else parent::__construct('',0, $previous);
		$this->code = $code;
		if ($this->depth!==0) $this->setDepth(); #also sets message!
		else $this->setMessage();
		if ($store===true) $this->store();
	}
}
