<?php

include_once 'traits.php';

class Tree implements Serializable, IteratorAggregate, ArrayAccess, Countable
{
	use DBugTrait, TreeTrait, OldTreeTraits;

	protected $_;

	public function __construct($array=[], $args=[]) {
		$dK = isset($args['.$']) ? $args['.$'] : '';
		$this->_ = array_merge([
			'.$' =>'',        # Key if nested in Parent Tree
			'.#' => 0,        # Depth - distance from Root Tree
			'.@' => true,     # Convert arrays to Tree - true/false/'shallow'
			'.!' => 'Trees',  # Lock reassignment of children - true/false/'Trees'
			'.+' =>[0=>$this],# all Ancestors (Trees) from this up to Root with int keys
			'.-' => null,     # .+ ran through array_reverse
			'.K' =>[0=>$dK],  # all Ancestors (Trees) from this up to Root with string keys
			'.P' => '',       # full path 
			'..' => $this,    # Parent Tree
			'/'  => $this,    # Root Tree
			'T'  => $array,   # contents of this Tree
		], $args);
		if ( !empty($this->_['T']) ) self::_rConfig($this);
	}

	public function serialize(){ return serialize($this->_['T']); }
	public function unserialize($s_ized) { $this->_['T'] = unserialize($s_ized);}
	public function getIterator() { return new ArrayIterator( $this->_['T'] ); }
	public function count() { return count( $this->_['T'] ); }
	public function length(){ return $this->count(); }
	public function offsetExists($key){return array_key_exists($key,$this->_['T']);}

	public function Root() { return $this->_['/']; }
	public function Depth(){ return $this->_['.#']; }

	public function Parent($set=null) # called on pathkeys ending with '.Parent', '../' or '..'
	{
		if ($set===null) return $this->_['..']; # <-GET, SET:
		elseif ($this->_['..']===$set) return self::err('already Parent','n');
		elseif (!is_a($set, get_class())) return self::err('cannot assign as Parent','e');
		else { $this->_['T']['..'] = $set; self::_rConfig($this); }
		return $this;
	}

	# GET only, called on pathkeys ending with '.Ancestors', or '.+'
	# or '-INT' '+INT', where INT is an integer anywhere in pathkey
	public function Ancestors($token=null) 
	{
		if ($token===null) return $this->_['.+']; #GET array, GET elem: 
		else { $i=(int)$token;
			if ($token==$i) {
				if ($i<0) $i=$this->_['.#'] + $i;
				return $this->_['.+'][$i];
			}#ELSE ERROR?
		}
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

	public function Key($set=null) # called on pathkeys ending with '.Key', or '.$'
	{
		if ($set===null) return $this->_['.$']; # <-GET, SET:
		elseif ($this['/']===$this ) { $this['/'] = $set; return $this; }
		elseif ($this->keyState($set)) return new Nil('offsetError', "Tree '$set' is locked", 1);
		else {
			$parent =& $this->_['..']->_['T'];
			if ($parent[$this->_['.$']]===$this) unset($parent[$this->_['.$']]);
			$this->_['.$'] = $set;
			$parent[$set] = $this;
			return $this;
		}
	}

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

	public function offsetUnset($key)
	{
		if (array_key_exists($key, $this->_['T'])) {
				$v = $this->_['T'][$key];
			if (is_a($v, get_class())) {
				$v->_['.#'] = 0;
				$v->_['..']= $v;
				$v->_['/'] = $v;
				if ( !empty($v->_['T']) ) self::_rConfig($v);
			}
			unset( $this->_['T'][$key] ); return $v;
		}
		else return new Nil('offsetUnset', 'Tree element', 1);
	}

	public function path($path, $set='nIl')
	{
		if (is_array($path)) { $paths = $path;
			$path = $path[0]!=='/' ? implode('/',$path) : substr(implode('/',$path), 1);
		} else $paths = null;
		if (array_key_exists($path, $this->_['T'])) {
			if ($set==='nIl') return $this->_['T'][$path];
			elseif ($this->_keySet($path,$set,$old)) return $old;
		}
		if ($paths===null) {
			$paths = preg_split("`/`", $path, -1, PREG_SPLIT_NO_EMPTY); 
			if ($path[0]==='/') array_unshift($paths, '/'); # put back missing / at [0]
		}
		$failchunk=''; $col = $this; $type=2; # 1 array, 2 Tree, 3 ArrayAccess
		end($paths); $lastslash_k = key($paths);

		foreach ($paths as $slash_k=>$slash) { $v = 'nIl';
			$lastslash = $slash_k===$lastslash_k;  $slash_confirmed = false;
			if ($failchunk!=='') {preg_match("`$failchunk/+$slash`", $path, $m); $slash = $m[0];}
			if ($type===1 && array_key_exists($slash, $col)) $slash_confirmed = true;
			elseif ($type===3 && $col->offsetExists($slash)) $slash_confirmed = true;
			elseif ($type===2) {
				if (array_key_exists($slash,$col->_['T'])) $slash_confirmed = true;
				elseif (strrpos($slash,'.')===false) {$failchunk.=$slash; continue;}
				else { 
					$out = self::_dotsplitParse($status,$col,$failchunk,$slash,$lastslash,$set);
					if ($status==='failed') {$failchunk = $out; continue;}
					elseif ($status==='returned') return $out; 
					elseif ($status==='new_Tree') {$failchunk=''; $col = $out; continue;}
				}
			}
			if ($lastslash) {
				if ($slash_confirmed) {
					if ($set==='nIl') return $col[$slash];
					if ($type===2) {
						$result = $col->_keySet($slash,$set,$old);
						if ($result) return $old;
						else return self::err("The Tree element '$slash' is locked",'e');
					} else { $v = $col[$slash]; $col[$slash] = $set; return $v;}
				} else return new Nil('offsetGet', 'Tree path', 1);
			} else {
				if ($slash_confirmed) {
					if ($col[$slash] instanceof ArrayAccess) {
						$type = is_a($col[$slash], get_class()) ? 2 : 3;
						$failchunk=''; $col = $col[$slash]; continue;
					} elseif (is_array($col[$slash])) {
						$type = 1; $failchunk=''; $col =& $col[$slash]; continue;
					}
				} else {
					preg_match("`$failchunk/+$slash`", $path, $m); 
					$failchunk = $m[0]; continue;
				}
			}
		}
	}
	
	private function _keySet ($key, $set, &$old)
	{
		$old = $this->_['T'][$key]; $fail = $this->_['.!'];
		if (is_a($v, get_class())) {
			if ($fail===false) {
				$old->_['.#'] = 0; $old->_['..'] = $old->_['/'] = $old;
				if ( !empty($old->_['T']) ) self::_rConfig($old);
			} else $fail = true;
		}
		if ($fail!==true) { $fail = false;
			unset( $this->_['T'][$key] ); $this->_['T'][$key] = $set;
		}
		return !$fail;
	}

	private static function _dotsplitParse ( // in calling function $tree is $col
		&$status, $tree, $failchunk, $slash, $lastslash, $set='nIl'
	) {
		$RELPEXT = '\.(\.|Parent|[-.]1)$|(\/|\.Root|\.[+R]0)';
		$POSPEXT = '\.[+R](\d+)|\.[-.](\d+)|\.\.\$(.+)';
		$GETTER = '\.([+R]|Ancestors)|\.(\-|Ancestors)|(\.Path)|\.(\$|Keys)|\.(\#|Depth)';
		$GETSET = '\.(\$|Key)|\.(\!|Lock)|\.(\@|Convert)';
		$FLAGS = PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE;
		$status = 'returned'; $new_tree = false;

		$exts = preg_split('/(\.+[^.]+)/', $slash, -1, $FLAGS);
		end($exts); $lastext_k = key($exts); // reset($exts); $firstext_k = key($tree);

		foreach ($exts as $ext_k=>$ext) { // $v = 'nIl'; $firstext = $ext_k===$firstext_k;
			$lastext = $ext_k===$lastext_k;  $verylast = $lastext && $lastslash; 
			if ($failchunk!=='') $ext = $failchunk.$ext;
			
			if ($ext_k===0 && array_key_exists($ext, $tree->_['T'])
			 && is_a($tree->_['T'][$ext], get_class())) { $new_tree = true;
				$tree = $tree->_['T'][$ext]; $failchunk=''; continue;
			} 
			if (!$verylast) preg_match("/^$RELPEXT|$POSPEXT$/",$ext,$capt);
			else preg_match("/^$RELPEXT|$POSPEXT|$GETTER|$GETSET$/",$ext,$capt);
			$n = count($capt)-1;
			if ($n<6) {
				if($n<3){
				    if     ($n===1) $v = $tree->_['..'];
				    elseif ($n===2) $v = $tree->_['/']; 
				} else { if($n===3) $v = $tree->_['.+'][ (int)$capt[1] ];
				    elseif ($n===4) $v = $tree->_['.-'][ (int)$capt[1] ];
				    else  /*$n===5*/$v = $tree->_['.K'][ $capt[5] ];
				}
				if (!is_a($v,get_class())) { $failchunk .= $ext; continue; }
				else {
					if ($verylast) {
						if ($set==='nIl') return $v;
						$k = $v->_['.$'];
						if ($v===$v->_['..']) 
							return new Nil('protected', 'Tree Root', 2);
						$result = $v->_['..']->_keySet($k,$set,$old);
						if ($result) return $old;
						else new Nil('protected', 'Tree Node', 2);
					} else {$failchunk=''; $new_tree=true; $tree = $v; continue;}
				}
			} elseif ($verylast) {
				if ($set==='nIl') {
					if ($n<10/* && $n>5 */) {
						if ($n<8) {
						    if     ($n===6) return $tree->_['.+'];
						    else  /*$n===7*/return $tree->_['.-']; 
						} else { if($n===8) return $tree->_['.P'];
						    else  /*$n===9*/return array_keys($tree->_['.K']); }
					} elseif ($n<14) {
						if ($n<12) {
						    if     ($n===10) return $tree->_['.#'];
						    else  /*$n===11*/return $tree->Key(); 
						} else { if($n===12) return $tree->Lock();
						    else  /*$n===13*/return $tree->Convert(); } }
				} else /*$set!=='nIl'*/ { 
					if    ($n===11) return $tree->Key($set);
					elseif($n===12) return $tree->Lock($set);
					elseif($n===13) return $tree->Convert($set);
				}
			}
			$failchunk .= $ext;
		}
		if ($new_tree && $failchunk==='') {$status = 'new_tree'; return $tree;}
		else { $status = 'failed'; return $failchunk; }
	}

	protected static function _rConfig(&$Parent, $key=null) {
		# If $key is not provided, all contents of $Parent will be configured recursively.
		# $key can specify a single key on $Parent to recursively configure
		$next_=[  '..'=>$Parent,   '/'=>$Parent->_['/'],   '.+'=>$Parent->_['.+'],
		  '.#'=>$Parent->_['.#']+1,  '.!'=>$Parent->_['.!']
		 ];
		foreach ($Parent->_['T'] as $k => &$v) {
			if ($key===null || $k===$key) {
				$next_['.$'] = $k;
				if ( is_array($v) && $Parent->_['.@']) {
					$next_['.@'] = $Parent->_['.@']===true ? true : false;
					$next_['T'] = $v;
					$v = new static();
					$v->_ = array_merge($v->_, $next_);
					$v->_['.+'][ $v->_['.#'] ] = $v;
					$v->_['.-'] = array_reverse ( $v->_['.+'] );
					// $v->_['.K'][ $k ] = $v;
					if (!empty($v->_['T'])) self::_rConfig($v);
				} elseif ( is_a($v, get_class()) ) {
					$next_['.@'] = $v->_['.@'];

					# unset from old Parent if we need to:
					if ($v!==$v->_['..'] && isset($v->_['..']->_['T'][ $v->_['.$'] ])
					 && $v->_['..']->_['T'][ $v->_['.$'] ]===$v)
						unset($v->_['..']->_['T'][ $v->_['.$'] ]);
					$v->_ = array_merge($v->_, $next_);
					$v->_['.+'][ $v->_['.#'] ] = $v;
					$v->_['.-'] = array_reverse ( $v->_['.+'] );
					// $v->_['.K'][ $k ] = $v;
					if (!empty($v->_['T'])) self::_rConfig($v);
				}
			}
		}
	}
}

$inner = new Tree(['inner'=>'innerval'],['.$'=>'inner']);
$tree = new Tree([ 
	'1-leaf' => 'stringvalue',
	'1-tree1' => 
	[
		'2-tree' => 
		[
			'3-leaf' => 'stringvalue',
			'3-tree' =>
			[
				['4-leaf' => 'stringvalue'],
				$inner['.$'] => $inner,
			]
		]
	],
	'1-tree2' => ['2-leaf' => 'stringvalue'],
],['.$'=>'Root']);

// foreach ($inner['.+'] as $k => $v) {
// 	echo "$k => ".$v['.$']."\n";
// }
// echo Tree::_rGet($inner, '/$');
// echo $inner->rGet('/$');
echo $inner['-1/.$'];
// echo $tree['1-tree1']['.$'];
// echo $inner['/']['.$'];

// echo gettype($tree['1-tree1']['2-tree']['3-tree'])."\n";



