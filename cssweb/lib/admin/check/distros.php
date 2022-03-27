<?php

function check_distros() {
    global $GITPH, $statinfo, $hw_platforms, $distids;

    $dir = "Distros";
    $distids = array();

    if (!is_link($dir) && is_dir($dir) && (($dh = opendir($dir)) !== false)) {
	while (($entry = readdir($dh)) !== false) {
	    if (check_symlink("$dir/$entry"))
		continue;
	    elseif (is_dir("$dir/$entry")) {
		if (($entry != ".") && ($entry != ".."))
		    U_dir("/$dir/$entry");
		continue;
	    }
	    elseif ($entry == $GITPH)
		continue;
	    elseif (($arch = basename($entry, ".yml")) == $entry) {
		U_file("/$dir/$entry");
		continue;
	    }
	    elseif (!strstr($arch, ".") || !isValidId($arch)) {
		errx("Invalid DisroID: /$dir/$entry");
		continue;
	    }
	    list($entry, $arch) = explode(".", $arch, 2);
	    if (!isset($hw_platforms[$arch])) {
		errx("Unexpected platform ($arch) in ".
		     "DisroID: /$dir/$entry.$arch.yml");
		continue;
	    }
	    elseif (check_yaml_file("$dir/$entry.$arch.yml"))
		continue;
	    $distids[] = "$entry/$arch";
	}
	closedir($dh);
    }

    if (!isset($statinfo))
	$statinfo = cache2arr("statinfo");
    $statinfo["distros"] = count($distids);
    arr2cache("statinfo", $statinfo);

    return $statinfo["distros"];
}

?>