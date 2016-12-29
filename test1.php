<?php

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

echo $tree;
// echo $tree['1-tree1']['.$'];
// echo $inner['/']['.$'];

// echo gettype($tree['1-tree1']['2-tree']['3-tree'])."\n";
