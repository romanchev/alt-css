<?php

function reindex_categories() {
    global $SUITES, $hw_platforms, $statinfo, $catids, $catfull, $comp_ext_rules;

    $dir = "Categories";
    if (!isset($catids))
	check_categories();
    if (!count($catids))
	fatal("Category definitions not found, nothing to do");
    $model = loadDataModel("category");
    $lastid = $lastabbr = false;
    $soft = $hard = array();

    foreach ($catids as $id) {
	if (!file_exists($yaml = "$dir/$id/category.yml"))
	    $record = array();
	else
	    $record = loadYamlFile($yaml, "category", $model);
	if (isset($record["Disabled"]))
	    if ($record["Disabled"] !== true) {
		errx("Bad value: Disabled='".$record["Disabled"]."' in /$yaml");
		unset($record["Disabled"]);
	    }
	if (isset($record["Suitable"]))
	    if (!isset($SUITES[ $record["Suitable"] ])) {
		errx("Bad value: Suitable='".$record["Suitable"]."' in /$yaml");
		unset($record["Suitable"]);
	    }

	if (isset($record["ArchDefs"])) {
	    if (!is_array($record["ArchDefs"])) {
		errx("Invalid 'ArchDefs' field format in /$yaml");
		unset($record["ArchDefs"]);
	    }
	    else {
		$flag = false;
		foreach ($record["ArchDefs"] as $arch => &$data) {
		    if (!isset($hw_platforms[$arch])) {
			errx("Unknown platform name: '$arch' in /$yaml");
			$flag = true;
		    }
		    foreach ($data as $key => $val) {
			if ($key == "Manuals") {
			    if (!is_array($val)) {
				errx("Invalid 'Manuals' field format in /$yaml");
				$flag = true;
				break;
			    }
			    foreach ($val as $tabID => $docref) {
				if (!isset($comp_ext_rules[$tabID])) {
				    errx("Unknown table ID: '$tabID' in /$yaml");
				    $flag = true;
				}
				if (!isUrl($docref)) {
				    errx("Bad value: $tabID='$docref' in /$yaml");
				    $flag = true;
				}
			    }
			    unset($tabID, $docref);
			}
			elseif (($key != "URI") && ($key != "Install")) {
			    errx("Unexpected field: '$key' in /$yaml");
			    $flag = true;
			}
			elseif (!isUrl($val)) {
			    errx("Bad value: $key='$val' in /$yaml");
			    $flag = true;
			}
			unset($key, $val);
		    }
		}
		if ($flag) {
		    warnx("Invalid 'ArchDefs' field will be ignored in /$yaml");
		    unset($record["ArchDefs"]);
		}
		unset($data, $arch, $flag);
	    }
	}

	if (!isset($record["ABBR"]))
	    $abbr = basename($id);
	else {
	    $abbr = $record["ABBR"];
	    if (strstr($abbr, " (") !== false)
		$abbr = preg_replace("/[[:space:]]+\\(.*$/", "", $abbr);
	}

	$softflag = (mb_substr($id, 0, 3) == "ПО/");
	$short = $softflag ? mb_substr($id, 3): $id;
	if (strstr($short, "/") === false)
	    $lastabbr = $abbr;
	elseif (dirname($id) !== $lastid)
	    $lastabbr = dirname($lastabbr)."/$abbr";
	else {
	    $lastabbr .= "/$abbr";
	    $lastid .= "/";
	}
	if ($softflag) {
	    $soft[] = array(0, str_replace("_", " ", $lastabbr), $id, $record);
	    $lastid =& $soft[ count($soft)-1 ][2];
	}
	else {
	    $hard[] = array(1, str_replace("_", " ", $lastabbr), $id, $record);
	    $lastid =& $hard[ count($hard)-1 ][2];
	}
	unset($yaml, $record, $softflag);
    }

    usort($soft, "compare_groups");
    usort($hard, "compare_groups");

    if (!isset($statinfo))
	$statinfo = cache2arr("statinfo");
    $statinfo["softgroups"] = count($soft);
    $statinfo["hardgroups"] = count($hard);
    $categories = array_merge($soft, $hard);
    unset($soft, $hard, $lastabbr, $lastid, $model);

    $catids = $catfull = array();

    foreach ($categories as &$cat) {
	$fullpath = str_replace("/", " :: ",
		    ($cat[0] ? "Железо/": "Софт/").$cat[1]);
	$catids[] = $short_id = $cat[2];
	$disabled = isset($cat[3]["Disabled"]);
	$suitable = category2suitable($short_id);
	$catfull[]= array (
	    $short_id,
	    $fullpath,
	    $suitable,
	    $disabled
	);
	if (isset($cat[3]["ArchDefs"]))
	    $catfull[ count($catfull)-1 ][] = $cat[3]["ArchDefs"];
	unset($short_id, $fullpath, $disabled, $suitable);
    }

    $statinfo["categories"] = count($catids);
    $catids = array_flip($catids);
    arr2cache("statinfo", $statinfo);
    arr2cache("catfull", $catfull);
    arr2cache("catids", $catids);
    unset($categories);

    return $statinfo["categories"];
}

function compare_groups($a, $b) {
    return mb_strcasecmp($a[FCAT_FullIDX], $b[FCAT_FullIDX]);
}

?>