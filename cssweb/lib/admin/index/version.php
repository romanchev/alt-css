<?php

function update_version_cache($VendorID, $ProductID, $version, $index=false, $archive=false) {
    global $install, $comp_ext_rules;

    // Auto-detect index
    if ($index === false)
	$index = letter2idx(first_letter($VendorID));
    if (!isset($install))
	check_install();
    $srch = $major = $pdfs = array();
    $stop = array("List", "Tags", "Hidden");

    // Extract primary info and index data for FTS
    $dir = "Vendors/$VendorID/$ProductID/VERS/$version";
    $data = loadYamlFile($yaml = "$dir/version.yml", "version");
    //
    if (isset($data["Platforms"]))
	check_platforms_list($data["Platforms"], $yaml);
    if (isset($data["URI"]))
	$major["URI"] = $data["URI"];
    if (isset($data["Hidden"]))
	$major["Hidden"] = $archive = true;
    if (isset($data["List"]))
	$major["List"] = intval($data["List"]);
    if (isset($data["Install"]))
	$major["Install"] = $data["Install"];
    if (isset($data["Tags"]))
	$major["Tags"] = explode(", ", $data["Tags"]);
    if (isset($data["Footnote"]))
	$major["Footnote"] = $data["Footnote"];
    $tablinks = isset($data["Manuals"]) ? $data["Manuals"]: null;
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
    unset($data, $stop, $key, $value);

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
	if (($tablinks !== null) && count($tablinks))
	    $major["Manuals"] = $tablinks;
	unset($tableId, $docref);
    }

    // Check installation guide PDF's
    if (file_exists("$dir/inst.pdf")) {
	if (isset($install["$dir/inst.pdf"]))
	    $pdfs[] = "B:".$install["$dir/inst.pdf"];
    }
    elseif (file_exists("$dir/inst.ref")) {
	$rpath = ref2pdf($VendorID, $ProductID, "VERS/$version");
	if ($rpath)
	    $pdfs[] = "B:".$install[$rpath];
	unset($rpath);
    }
    foreach ($comp_ext_rules as $tableId => $P) {
	if ($tableId == "S10") {
	    unset($P);
	    continue;
	}
	$P = "$dir/inst.{$tableId}.pdf";
	if (file_exists($P)) {
	    if (isset($install[$P]))
		$pdfs[] = "C:{$tableId}@".$install[$P];
	}
	elseif (file_exists("$dir/inst.{$tableId}.ref")) {
	    $rpath = ref2pdf($VendorID, $ProductID,
				"VERS/$version", ".".$tableId);
	    if ($rpath)
		$pdfs[] = "C:{$tableId}@".$install[$rpath];
	    unset($rpath);
	}
	unset($P);
    }
    if (count($pdfs)) {
	sort($pdfs);
	$major["InstPDF"] = $pdfs;
    }

    // Update data in the cache
    $v_id = "$VendorID:$ProductID:$version";
    $m_idx = cache2arr(($archive ? "M": "m")."$index");
    if (count(array_keys($major)))
	$m_idx[$v_id] =& $major;
    else
	unset($m_idx[$v_id]);
    arr2cache(($archive ? "M": "m")."$index", $m_idx);
    update_fts_cache(($archive ? "S": "s")."$index", $v_id, $srch);
}

?>