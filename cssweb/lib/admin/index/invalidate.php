<?php

function invalidate_cache($index) {
    global $CACHEDIR;

    $caches = array("C", "S", "v", "p", "c", "m", "s");

    foreach ($caches as $symbol)
	@unlink("$CACHEDIR/{$symbol}{$index}.php");
    unset($caches, $symbol);
}

function invalidate_rootidx() {
    global $CACHEDIR, $q_index;

    $q_index = array();

    if (file_exists("$CACHEDIR/abc.php")) {
	$abc = cache2arr("abc");
	arr2cache("abc", $q_index);
	foreach ($abc as $index)
	    invalidate_cache($index);
	unset($abc, $index);
    }
}

?>