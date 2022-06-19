<?php

function update_product_cache($VendorID, $ProductID, $index=false) {
    global $statinfo, $catids, $catfull, $install, $SUITES, $comp_ext_rules;

    // Auto-detect index
    if ($index === false)
	$index = letter2idx(first_letter($VendorID));
    if (!isset($catids))
	reindex_categories();
    if (!isset($catfull))
	$catfull = cache2arr("catfull");
    if (!isset($install))
	check_install();
    if (!isset($statinfo))
	$statinfo = cache2arr("statinfo");
    $srch = $pdfs = array();

    // Load Vendor data for extract default List#
    $yaml = "Vendors/$VendorID/vendor.yml";
    $data = loadYamlFile($yaml, "vendor");
    $dflt_list = intval($data["List"]);
    unset($data);

    // Extract primary info and index data for FTS
    $yaml = "Vendors/$VendorID/$ProductID/product.yml";
    $data = loadYamlFile($yaml, "product");
    $name = str_replace("_", " ", $ProductID);
    $link = isset($data["URI"]) ? $data["URI"]: "";
    $note = isset($data["Footnote"]) ? $data["Footnote"]: null;
    $list = isset($data["List"]) ? intval($data["List"]): $dflt_list;
    $tags = isset($data["Tags"]) ? explode(", ", $data["Tags"]): null;
    $stop = array("Name", "List", "Suitable", "Category", "Hidden", "Tags");
    $versions = rebuildVersions($data, "$VendorID/$ProductID/VERS");
    $tablinks = isset($data["Manuals"]) ? $data["Manuals"]: null;
    $instlink = isset($data["Install"]) ? $data["Install"]: "";
    $hidden   = isset($data["Hidden"]);
    $category = $data["Category"];
    if (isset($catids[$category]))
	$cat_idx = $catids[$category];
    elseif (isset($catids["$category/"]))
	$cat_idx = $catids["$category/"];
    else {
	errx("Unexpected category name: '$category' in /$yaml");
	$category = $catfull[$cat_idx = 0][FCAT_NameIDX];
    }
    if ($catfull[$cat_idx][FCAT_DisbIDX]) {
	errx("Link to disabled category '$category' found in /$yaml");
	$category = $catfull[$cat_idx = 0][FCAT_NameIDX];
    }
    if (!isset($data["Suitable"]))
	$suitable = $catfull[$cat_idx][FCAT_SuitIDX];
    elseif (isset($SUITES[ $data["Suitable"] ]))
	$suitable = $data["Suitable"];
    else {
	errx("Bad value: Suitable='".$data["Suitable"]."' in /$yaml");
	$data["Suitable"] = $suitable = SUITES_NOEXPAND;
    }
    if (isset($data["Platforms"]))
	check_platforms_list($data["Platforms"], $yaml);
    $srch[] = fts_string($name);
    if (isset($data["Name"])) {
	if ($data["Name"] != $name)
	    $srch[] = fts_string($data["Name"]);
	$name = $data["Name"];
    }
    $srch[] = fts_string(str_replace(array("/", "_"), " ", $category));
    //
    foreach ($data as $key => &$value) {
	if (in_array($key, $stop, true))
	    continue;
	if (!is_string($value) || ($value == "."))
	    continue;
	$s = fts_string($value);
	if ($s)
	    $srch[] = $s;
	unset($s);
    }
    unset($yaml, $data, $key, $value, $stop, $cat_idx);

    // Count versions
    $majvers = array();
    for ($i=0; $i < 2; $i++)
	foreach ($versions[$i] as $rel => $ver) {
	    if ($rel != $ver)
		$statinfo["releases"] ++;
	    if ($ver && !in_array($ver, $majvers, true)) {
		$statinfo["majorver"] ++;
		$majvers[] = $ver;
	    }
	}
    unset($majvers, $i, $rel, $ver);

    // Check table's links
    if (($tablinks !== null) && !is_array($tablinks)) {
	errx("Invalid 'Manuals' field format in /$yaml");
	$tablinks = null;
    }
    elseif (is_array($tablinks)) {
	foreach ($tablinks as $tableId => $docref) {
	    if (!isset($comp_ext_rules[$tableId])) {
		errx("Unknown table ID: '$tableId' in /$yaml");
		$tablinks = null;
		break;
	    }
	    if (!isUrl($docref)) {
		errx("Bad value: $tableId='$docref' in /$yaml");
		$tablinks = null;
		break;
	    }
	}
	unset($tableId, $docref);
    }

    // Build installation guides array
    $dir = "Vendors/$VendorID/$ProductID/ARCH";
    if (!is_link($dir) && is_dir($dir) && (($dh = opendir($dir)) !== false)) {
	while (($entry = readdir($dh)) !== false) {
	    if (is_link("$dir/$entry"))
		continue;
	    elseif (!is_dir("$dir/$entry"))
		continue;
	    elseif (($entry == ".") || ($entry == ".."))
		continue;

	    foreach ($comp_ext_rules as $tableId => $P) {
		$P = "$dir/$entry/inst.$tableId";
		if (file_exists("$P.pdf")) {
		    if (isset($install["$P.pdf"])) {
			if ($entry == "ALL")
			    $pdfs[] = "6:{$tabelId}@".$install["$P.pdf"];
			else
			    $pdfs[] = "8:{$tabelId}@$entry=".$install["$P.pdf"];
		    }
		}
		elseif (file_exists("$P.ref")) {
		    $rpath = ref2pdf($VendorID, $ProductID,
					"ARCH/$entry", ".".$tableId);
		    if ($rpath) {
			if ($entry == "ALL")
			    $pdfs[] = "6:{$tabelId}@".$install[$rpath];
			else
			    $pdfs[] = "8:{$tabelId}@$entry=".$install[$rpath];
		    }
		    unset($rpath);
		}
		unset($tableId);
	    }

	    $P = "$dir/$entry/inst";
	    if (file_exists("$P.pdf")) {
		if (isset($install["$P.pdf"])) {
		    if ($entry == "ALL")
			$pdfs[] = "5:".$install["$P.pdf"];
		    else
			$pdfs[] = "7:$entry=".$install["$P.pdf"];
		}
	    }
	    elseif (file_exists("$P.ref")) {
		$rpath = ref2pdf($VendorID, $ProductID, "ARCH/$entry");
		if ($rpath) {
		    if ($entry == "ALL")
			$pdfs[] = "5:".$install[$rpath];
		    else
			$pdfs[] = "7:$entry=".$install[$rpath];
		}
		unset($rpath);
	    }
	    unset($P);
	}
	closedir($dh);
	unset($dh, $entry);
    }

    // Update data in the cache
    $p_idx = cache2arr("p{$index}");
    $tID = "$VendorID:$ProductID";
    $p_idx[$tID] = array (
	"Name" => $name,
	"URI"  => $link,
	"List" => $list,
	"Tags" => $tags,
	"Category" => $category,
	"Suitable" => $suitable,
	"Install"  => $instlink,
	"Versions" => $versions
    );
    if ($tablinks !== null)
	$p_idx[$tID]["Manuals"] = $tablinks;
    if ($note)
	$p_idx[$tID]["Footnote"] = $note;
    if ($hidden)
	$p_idx[$tID]["Hidden"] = true;
    if (count($pdfs)) {
	sort($pdfs);
	$p_idx[$tID]["InstPDF"] = $pdfs;
    }
    arr2cache("p{$index}", $p_idx);
    arr2cache("statinfo", $statinfo);
    update_fts_cache(($hidden ? "S": "s")."$index", $tID, $srch);
}

?>