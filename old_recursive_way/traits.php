<?php
trait StorableTrait {
	
	protected static $arr = [];
	protected static $count = 0;
	protected $idx = null;
	protected $thres = 10;

	public function idx() { return $this->idx; }
	public static function getStore() { return self::$arr; }

	public function __destruct() {
		if ($this->idx!==null) {
			if (self::$arr[$this->idx]===$this) unset(self::$arr[$this->idx]);
			else unset(self::$arr[array_search($this,self::$arr,true)]);
		}
	}
	public static function resort($thres=null) {
		if ($thres!==null) {self::$thres = abs($thres - 1); $run = true;}
		else {end(self::$arr); $run = end(self::$arr)>(self::$count+self::$thres);}
		if ($run===true) {
			$inst = self::$arr; $self::$arr = []; $i = 0;
			foreach ($inst as $k=>$v) {$self::$arr[$i] = $v; $v->idx = $i; $i++;}
			self::$count = $i;
		}
	}
	public function store() {
		if ($this->idx===null) {
			self::$arr[] = $this; end(self::$arr);
			$this->idx = key(self::$arr); self::$count++;
		}
	}
	public function unstore() {
		if ($this->idx!==null) {
			if (self::$arr[$this->idx]===$this) unset(self::$arr[$this->idx]);
			else unset(self::$arr[array_search($this,self::$arr,true)]);
			$this->idx = null; self::$count--;
			self::resort();
		}
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