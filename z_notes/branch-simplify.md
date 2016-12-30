# Notes for 'simplify' Branch

## Objective A: Removing Convert and Lock

To simplify code by removing options which also has the benefit of increasing performance. There is also the capacity for memory leaks with so many references to object, which is even worse when there are recursive references!  
__1. remove $this->_['.@'] and all related to array->tree conversion__  

All arrays will be converted to Tree objects so to convert or not to convert is no longer an option. Previously it was thought that having the capability to contain a normal array might be necessary in some circumstances but there will always be ways to get an array copy. Not having a distinction removes the need for many conditional checks in the Tree class and increases performance. 

__2. remove $this->_['.!'] and all related to Lock functionality__  

Having reset-able options for protecting keys was seemingly a good way to prevent accidental reassignment of elements but since memory leaks are always a risk nested Tree objects it makes sense to have a uniform way of removing them that must be followed.   

Therefore, now __all non-Root Tree objects must be explicitly removed from the parent before the key they occupy is available for assignment__. Be aware the unsetting doesn't mean the object is headed for the garbage collector since a reference to it might exist elsewhere. Either of the following might happen:  

1. Tree is unset from parent and it's parent is set to another Tree
2. Tree is unset from parent and it's parent is set to itself

If the second happens, you might or might have have a variable storing the Tree object directly. But either way we might have Tree's within it that store references to it and the Tree itself has references to itself so we have a real potential for blocking it's destructor from being called.  

## Objective B: Removing Unneeded References

The next step involves changing the behavior of root Trees by setting to null instead of $this. Nothing should store a reference to $this. Even if you put something in your __destruct() to remove these references it won't work since __destruct() will never be called in the first place!  

We will will need an explicitly defined __destruct where we can destroy all child Trees or make them or at least make all the top level ones roots for their descendants, thus removing the reference to the original Tree which was destroyed!  










