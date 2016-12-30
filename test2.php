<?php

class Person {
    public function __construct($leak) { if ($leak===true) $this->me = $this; }
    public function preventLeak() {$this->me = NULL; }
    public function __destruct() {
    	$this->me = NULL;
    	echo "\nA Person has died.\n";
    }
}
function leak($leak) {
    $person = new Person($leak);
    // $person->preventLeak();
    // $person->__destruct();
}
leak(true);

echo "\nEnding script now. All People should already be dead.\n";
