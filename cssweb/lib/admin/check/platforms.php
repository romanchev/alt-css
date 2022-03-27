<?php

function check_platforms() {
    global $GITPH, $statinfo, $hw_platforms, $platforms;

    $dir = "Platforms";
    $platforms = array();

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
	    elseif (!isset($hw_platforms[$arch])) {
		// Change lib/config.php:$hw_platforms[] to resolve this problem
		errx("Invalid or unregistered (non-hardcoded) platform: /$dir/$entry");
		continue;
	    }
	    elseif (check_yaml_file("$dir/$entry", true))
		continue;
	    $platforms[$arch] = true;
	}
	closedir($dh);
    }

    foreach ($hw_platforms as $arch => $desc) {
	if (!isset($platforms[$arch])) {
	    // Add /Platforms/<ARCH>.yml to resolve this problem
	    warnx("Hardcoded platform has no description: $arch");
	}
    }

    if (!isset($statinfo))
	$statinfo = cache2arr("statinfo");
    $statinfo["platforms"] = count(array_keys($platforms));
    arr2cache("statinfo", $statinfo);

    return $statinfo["platforms"];
}

?>