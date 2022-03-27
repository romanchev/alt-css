<?php

function check_tpldir_r($dir) {
    global $statinfo;

    if (($dh = opendir($dir)) === false) {
	E_dir("/$dir");
	return;
    }
    $objs = 0;

    while (($entry = readdir($dh)) !== false) {
	if (check_symlink("$dir/$entry"))
	    ; /* Nothing */
	elseif (!is_dir("$dir/$entry")) {
	    if (basename($entry, ".yml") == $entry)
		U_file("/$dir/$entry");
	    elseif (!filesize("$dir/$entry"))
		I_yaml("/$dir/$entry");
	    else
		$statinfo["templates"] ++;
	}
	elseif (($entry == ".") || ($entry == ".."))
	    continue;
	elseif (!isValidId($entry))
	    U_dir("/$dir/$entry");
	else
	    check_tpldir_r("$dir/$entry");
	$objs ++;
    }

    if (!$objs)
	E_dir("/$dir");
    closedir($dh);
}

function check_templates() {
    global $GITPH, $statinfo;

    if (!isset($statinfo))
	$statinfo = cache2arr("statinfo");
    $statinfo["templates"] = 0;
    $dir = "Templates";
    $objs = 0;

    if (!is_link($dir) && is_dir($dir) && (($dh = opendir($dir)) !== false)) {
	while (($entry = readdir($dh)) !== false) {
	    if (check_symlink("$dir/$entry"))
		; /* Nothing */
	    elseif (!is_dir("$dir/$entry")) {
		if ($entry != $GITPH)
		    U_file("/$dir/$entry");
	    }
	    elseif (($entry == ".") || ($entry == ".."))
		continue;
	    elseif (!isValidId($entry))
		U_dir("/$dir/$entry");
	    else
		check_tpldir_r("$dir/$entry");
	    $objs ++;
	}
	closedir($dh);
    }

    if (!$objs)
	E_dir("/$dir");
    arr2cache("statinfo", $statinfo);

    return $statinfo["templates"];
}

?>