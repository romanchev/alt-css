<?php

function check_certs() {
    global $GITPH, $FORCE_MODE, $statinfo;

    if (!isset($statinfo))
	$statinfo = cache2arr("statinfo");
    $changes = array();
    $objs = $certs = 0;
    $dir = "Certs";

    if (!is_link($dir) && is_dir($dir) && (($dh = opendir($dir)) !== false)) {
	while (($entry = readdir($dh)) !== false) {
	    if (check_symlink("$dir/$entry"))
		; /* Nothing */
	    elseif (is_dir("$dir/$entry")) {
		if (($entry == ".") || ($entry == ".."))
		    continue;
		U_dir("/$dir/$entry");
	    }
	    elseif ($entry == $GITPH)
		; /* Nothing */
	    elseif (!preg_match("/^20\d{6}\-\d\d\.jpg$/", $entry))
		U_file("/$dir/$entry");
	    else {
		$full = "Legal/".
			substr($entry, 0, 4)."/".
			substr($entry, 4, 2)."/".
			substr($entry, 6, 5)."/cert";
		if (file_exists("$full.pdf") || file_exists("$full.jpg"))
		    $certs ++;
		elseif (!$FORCE_MODE || $statinfo["errors"])
		    errx("Deprecated thumbnail found: /$dir/$entry");
		else {
		    $changes[] = $entry;
		    unset($full);
		    continue;
		}
		unset($full);
	    }
	    $objs ++;
	}

	if (!$objs)
	    E_dir("/$dir");
	closedir($dh);
    }

    if (!$statinfo["errors"] && count($changes)) {
	foreach ($changes as $entry) {
	    warnx("Deprecated thumbnail removed: /$dir/$entry");
	    $entry = `git rm -f "$dir/$entry" 2>&1`;
	}
    }
    unset($changes);

    return $certs;
}

?>