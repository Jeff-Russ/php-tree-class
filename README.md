# PHP Tree Class: Smart Array Inspired by Node/Tree Data Structure

Tree is a smart array object inspired by the Node/Tree data structure and the UNIX file system's 'inodes'.  

# NOTIE

__Tree is currently being rewritten to be non-recursive__  

### Universal Key Access

The problem with tradition multi-dimensional arrays is that need multiple keys to access a nested element and sometimes you may not know now many layers deep you will have to go. This is not the case when dealing with directories in a system shell where you can use a single string to access an arbitrary depth. For example you can do this `cd dir/other-dir` which in an array would be like `$arr['key']['other-key']`. Trees can have access like this (more on this later):  

    $var = $tree[ ['key','other-key'] ];
    // OR even better:
    $var = $tree['key/other-key'];

