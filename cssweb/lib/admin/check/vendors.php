<?php

function check_drafts_dir($dir) {
    if (($dh = opendir($dir)) === false) {
	E_dir("/$dir");
	return 0;
    }
    $objs = $drafts = 0;

    while (($entry = readdir($dh)) !== false) {
	if (check_symlink("$dir/$entry"))
	    ; /* Nothing */
	elseif (is_dir("$dir/$entry")) {
	    if (($entry != ".") && ($entry != ".."))
		U_dir("/$dir/$entry");
	}
	elseif (!preg_match("/^cert[1-9]?\.odt$/", $entry))
	    U_file("/$dir/$entry");
	elseif (!filesize("$dir/$entry"))
	    S_file("/$dir/$entry");
	else
	    $drafts ++;
	$objs ++;
    }

    if (!$objs)
	E_dir("/$dir");
    closedir($dh);

    return $drafts;
}

function check_single_vendor($vID) {
    global $statinfo, $prodids;

    if (($dh = opendir($dir = "Vendors/$vID")) === false) {
	E_dir("/$dir");
	return -1;
    }
    $ids = array();
    $main = $cont = false;
    $rc = $drafts = $pdfs = $logo = 0;

    while (($entry = readdir($dh)) !== false) {
	if (check_symlink("$dir/$entry"))
	    continue;
	elseif (!is_dir("$dir/$entry")) {
	    if ($entry == "events.yml") {
		if (check_yaml_file("$dir/$entry"))
		    $rc = -1;
	    }
	    elseif ($entry == "vendor.yml") {
		if (check_yaml_file("$dir/$entry"))
		    $rc = -1;
		$main = true;
	    }
	    elseif ($entry == "contacts.yml") {
		if (check_yaml_file("$dir/$entry"))
		    $rc = -1;
		$cont = true;
	    }
	    elseif (($entry != "vendor.jpg") && ($entry != "vendor.png"))
		U_file("/$dir/$entry");
	    else {
		if (!filesize("$dir/$entry"))
		    S_img("/$dir/$entry");
		$logo ++;
	    }
	    continue;
	}
	elseif (($entry == ".") || ($entry == ".."))
	    continue;
	elseif ($entry == ".DRAFTS") {
	    $drafts = check_drafts_dir("$dir/$entry");
	    continue;
	}
	elseif ($entry == ".INSTALL") {
	    $pdfs = check_common_inst_dir("$dir/$entry");
	    continue;
	}
	elseif (!isValidId($entry) || !file_exists("$dir/$entry/product.yml")) {
	    U_dir("/$dir/$entry");
	    continue;
	}
	$ids[] = "$vID:$entry";
    }

    if (!$main) {
	E_yaml("/$dir/vendor.yml");
	$rc = -1;
    }
    if (!$cont)
	E_yaml("/$dir/contacts.yml");
    if ($logo > 1)
	errx("One more logo files found in /$dir");
    if (!$rc) {
	$statinfo["manuals"] += $pdfs;
	$statinfo["drafts"] += $drafts;
	$prodids = array_merge($prodids, $ids);
    }
    closedir($dh);

    return $rc;
}

function check_vendors() {
    global $GITPH, $statinfo, $vendids, $prodids;

    $dir = "Vendors";
    if (!isset($statinfo))
	$statinfo = cache2arr("statinfo");
    $statinfo["vendors"] = $statinfo["products"] = 0;
    $statinfo["manuals"] = $statinfo["drafts"] = 0;
    $vendids = $prodids = array();

    if (!is_link($dir) && is_dir($dir) && (($dh = opendir($dir)) !== false)) {
	while (($entry = readdir($dh)) !== false) {
	    if (check_symlink("$dir/$entry"))
		continue;
	    elseif (!is_dir("$dir/$entry")) {
		if ($entry != $GITPH)
		    U_file("/$dir/$entry");
		continue;
	    }
	    elseif (($entry == ".") || ($entry == ".."))
		continue;
	    elseif (!isValidId($entry) || !file_exists("$dir/$entry/vendor.yml")) {
		U_dir("/$dir/$entry");
		continue;
	    }
	    elseif (check_single_vendor($entry))
		continue;
	    $vendids[] = $entry;
	}
	closedir($dh);
    }

    $statinfo["vendors"] = count($vendids);
    $statinfo["products"] = count($prodids);
    arr2cache("statinfo", $statinfo);

    return $statinfo["vendors"];
}

?>