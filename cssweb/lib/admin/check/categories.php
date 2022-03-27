<?php

function check_category_r($path) {
    global $GITPH, $catids;

    $dir = "Categories".$path;

    if ($path) {
	if (!isValidId(basename($path))) {
	    errx("Invalid CategoryID: $path");
	    return;
	}
	elseif (check_placeholder($dir))
	    return;
	$catids[] = substr($path, 1);
    }

    $dh = opendir($dir);
    while (($entry = readdir($dh)) !== false) {
	if (check_symlink("$dir/$entry"))
	    continue;
	elseif (is_dir("$dir/$entry")) {
	    if (($entry != ".") && ($entry != ".."))
		check_category_r("$path/$entry");
	    continue;
	}
	elseif ($entry == $GITPH)
	    continue;
	elseif ($entry != "category.yml") {
	    U_file("/$dir/$entry");
	    continue;
	}
	check_yaml_file("$dir/$entry");
    }
    closedir($dh);
}

function check_categories() {
    global $statinfo, $catids;

    $catids = array();

    if (!is_link("Categories") && is_dir("Categories")) {
	check_category_r("");
	if (is_link("Categories/ПО") || !is_dir("Categories/ПО"))
	    errx("Software category ('ПО') not found");
	else {
	    $catids = array_flip($catids);
	    unset($catids["ПО"]);
	    ksort($catids);
	    $catids = array_keys($catids);
	}
    }

    if (!isset($statinfo))
	$statinfo = cache2arr("statinfo");
    $statinfo["categories"] = count($catids);
    arr2cache("statinfo", $statinfo);

    return $statinfo["categories"];
}

?>