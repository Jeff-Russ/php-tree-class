# PHP Tree Class: Smart Array Inspired by Node/Tree Data Structure

Tree is a smart array object inspired by the Node/Tree data structure and the UNIX file system's 'inodes'.  

# WARNING

__Tree class currently has memory leak issues__  

## General Info

Just a UNIX directory has a hidden file called `..` in every directory (which is a reference to the parent directory), a Tree array object has the `['..']` key which is a reference to the parent Tree and `['/']` which would also be reference to itself if Root. This means that each flat array `Tree` object is held together in a chain of references with other `Tree` objects, making them independent yet able to access each other even if out of scope.  

This means that a root Tree and all of it's descendants effectively become a multi-dimensional namespace. This is useful for passing data to callbacks; you can just send any one object and the rest will still be accessible.  

### Universal Key Access

The problem with tradition multi-dimensional arrays is that need multiple keys to access a nested element and sometimes you may not know now many layers deep you will have to go. This is not the case when dealing with directories in a system shell where you can use a single string to access an arbitrary depth. For example you can do this `cd dir/other-dir` which in an array would be like `$arr['key']['other-key']`. Trees can have access like this (more on this later):  

    $other = $root[ ['key','other-key'] ];
    // OR even better:
    $other = $root['key/other-key'];

## Under the Hood

The layer of code between you and the array ensures that any arrays/Tree added and removed to the Tree are automatically configured to reflect the changing relationships. For example, if you take a Tree that sits in inside a Tree and add it the key of another Tree, the old Tree will be told to unset it and the new Tree will set it's Parent and Root properties to reflect it's new home. Arrays can be set to be converted to full Tree objects automatically. Nested Tree's can be set not be directly re-assignable, only their contents and their keys can be locked.  

