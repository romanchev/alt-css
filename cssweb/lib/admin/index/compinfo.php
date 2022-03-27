<?php

function update_cmpinfo_cache($VendorID, $ProductID, $index=false) {
    global $CACHEDIR, $hw_platforms, $dateFmtRegex;
    global $distids, $vendids, $prodids;

    // Auto-detect index
    if ($index === false)
	$index = letter2idx(first_letter($VendorID));
    if (!isset($distids))
	$distids = cache2arr("distids");
    elseif (isset($distids[0]))
	$distids = array_flip($distids);
    if (!isset($vendids))
	$vendids = cache2arr("vendids");
    elseif (isset($vendids[0]))
	$vendids = array_flip($vendids);
    if (!isset($prodids))
	$prodids = cache2arr("prodids");
    elseif (isset($prodids[0]))
	$prodids = array_flip($prodids);
    $lstkeys = array("Versions", "Issues", "Distros", "NewsLinks");
    $objkeys = array("Products", "Depends", "Features", "Restricts");

    // Load product fields
    $tID  = "$VendorID:$ProductID";
    $prod = cache2arr("p{$index}");
    $pver = $prod[$tID]["Versions"];
    $pgrp = $prod[$tID]["Category"];
    $suit = $prod[$tID]["Suitable"];
    $plst = $prod[$tID]["List"];
    $ids  = array();
    unset($prod);

    // Load CI ID's
    $dir = "Vendors/$VendorID/$ProductID/CI";
    if (!is_link($dir) && is_dir($dir) && (($dh = opendir($dir)) !== false)) {
	while (($entry = readdir($dh)) !== false) {
	    if (is_link("$dir/$entry"))
		continue;
	    elseif (is_dir("$dir/$entry"))
		continue;
	    elseif (preg_match("/^\d{8}\.yml$/", $entry))
		$ids[] = basename($entry, ".yml");
	}
	closedir($dh);
	unset($dh, $entry);
    }
    sort($ids);

    // Prepare other common data
    $act_idx = cache2arr("c{$index}");
    $old_idx = cache2arr("C{$index}");
    $model   = loadDataModel("compinfo");

    // Iterate all CI ID's
    foreach ($ids as $cID) {
	$data = loadYamlFile($yaml = "$dir/$cID.yml", "compinfo", $model);

	// Check fields format
	if (isset($data["Tested"]))
	    if (!preg_match("/^{$dateFmtRegex}$/", $data["Tested"])) {
		errx("Bad value: Tested='".$data["Tested"]."' in /$yaml");
		unset($data["Tested"]);
	    }
	if (isset($data["CertLink"]))
	    if (!preg_match("/^{$dateFmtRegex}\/\d\d$/", $data["CertLink"])) {
		errx("Bad value: CertLink='".$data["CertLink"]."' in /$yaml");
		unset($data["CertLink"]);
	    }
	foreach ($objkeys as $key)
	    if (isset($data[$key])) {
		if (check_object_field($key, $data[$key], $yaml))
		    unset($data[$key]);
	    }
	foreach ($lstkeys as $key)
	    if (isset($data[$key]))
		if (check_strlst_field($key, $data[$key], $yaml)) {
		    if ($key != "Distros")
			unset($data[$key]);
		    else {
			unset($data);
			continue;
		    }
		}
	if (!isset($data["Checked"])) {
	    errx("Required field 'Checked' not defined in /$yaml");
	    $data["Checked"] = "We";
	}
	else {
	    $key = $data["Checked"];
	    if (($key !== "We") && ($key !== "They") && ($key !== "All")) {
		errx("Bad value: Checked='$key' in /$yaml");
		$data["Checked"] = "We";
	    }
	}
	if (!isset($data["Suitable"]))
	    $data["Suitable"] = $suit;
	elseif ($data["Suitable"] === "NoExpand")
	    $data["Suitable"] = "NoExpand";
	else {
	    errx("Bad value: Suitable='$key' in /$yaml");
	    $data["Suitable"] = $suit;
	}
	if (isset($data["IsCompat"])) {
	    $key = $data["IsCompat"];
	    if ($key === true)
		$data["IsCompat"] = $key = "Yes";
	    elseif ($key === false)
		$data["IsCompat"] = $key = "No";
	    if (($key !== "Yes") && ($key !== "No")) {
		errx("Bad value: IsCompat='$key' in /$yaml");
		unset($data["IsCompat"]);
	    }
	}
	if (isset($data["Hidden"]))
	    if ($data["Hidden"] !== "Yes") {
		if ($data["Hidden"] === true)
		    $data["Hidden"] = "Yes";
		else {
		    errx("Bad value: Hidden='$key' in /$yaml");
		    unset($data["Hidden"]);
		}
	    }
	if (!isset($data["Distros"])) {
	    errx("Required field 'Distros' not defined in /$yaml");
	    continue;
	}

	// Check products field
	if (isset($data["Products"])) {
	    $key = false;

	    foreach ($data["Products"] as $v_id => &$v_data) {
		if (!isset($vendids[$v_id])) {
		    errx("Broken vendor ID: '$v_id' in /$yaml");
		    $key = true;
		    continue;
		}
		foreach ($v_data as $p_id)
		    if (!isset($prodids["$v_id:$p_id"])) {
			errx("Broken product ID: '$v_id:$p_id' in /$yaml");
			$key = true;
		    }
		unset($p_id);
	    }

	    if ($key) {
		warnx("Invalid 'Products' field will be ignored in /$yaml");
		unset($data["Products"]);
	    }
	    unset($v_id, $v_data);
	}

	// Start record
	$record = array (
	    "vID" => $VendorID,
	    "pID" => $ProductID,
	    "cID" => "$cID"
	);

	// Save date's and record number
	$year = intval(substr($cID, 0, 2));
	$mon  = intval(substr($cID, 2, 2));
	$day  = intval(substr($cID, 4, 2));
	$record["start"] = mktime(0, 0, 0, $mon, $day, $year);
	$record["finish"] = null;
	if (isset($data["CertLink"])) {
	    $year = intval(substr($data["CertLink"], 6, 4));
	    $mon  = intval(substr($data["CertLink"], 3, 2));
	    $day  = intval(substr($data["CertLink"], 0, 2));
	    $record["finish"] = mktime(0, 0, 0, $mon, $day, $year);
	    $record["certID"] = sprintf("%04d%02d%02d-%s", $year, $mon, $day,
					    substr($data["CertLink"], 11, 2));
	    if (!file_exists("Certs/".$record["certID"].".jpg")) {
		errx("Broken CertLink: '".$record["certID"]."' in /$yaml");
		$record["finish"] = null;
		unset($record["certID"]);
		unset($data["CertLink"]);
	    }
	}
	if (isset($data["Tested"])) {
	    $year = intval(substr($data["Tested"], 6, 4));
	    $mon  = intval(substr($data["Tested"], 3, 2));
	    $day  = intval(substr($data["Tested"], 0, 2));
	    $record["finish"] = mktime(0, 0, 0, $mon, $day, $year);
	}
	unset($year, $mon, $day);

	// Check compatibility type
	if (!isset($data["IsCompat"]) &&
	    !isset($record["certID"]))
	{
	    $record["type"] = "INFO";
	}
	elseif (isset($data["IsCompat"]) &&
		!isset($record["certID"]) &&
		($data["IsCompat"] === "No"))
	{
	    $record["type"] = "NO";
	}
	elseif (isset($data["IsCompat"]) &&
		!isset($record["certID"]) &&
		($data["IsCompat"] === "Yes"))
	{
	    $record["type"] = "YES";
	}
	elseif (isset($data["IsCompat"]) &&
		isset($record["certID"]) &&
		($data["IsCompat"] === "Yes"))
	{
	    $record["type"] = "CERT";
	}
	else {
	    errx("Bad IsCompat/CertLink combination in /$yaml");
	    unset($data, $record);
	    continue;
	}
	if (($record["type"] == "INFO") || ($record["type"] == "NO")) {
	    if (isset($data["Hidden"]))
		warnx("Not needed to hide the record in /$yaml");
	    if (isset($data["Notes"]))
		warnx("Notes will be hidden in /$yaml");
	}

	// Versions
	$nver = array(array(), array());
	if (isset($data["Versions"])) {
	    foreach ($data["Versions"] as &$ver) {
		if (isset($pver[0][$ver]))
		    $nver[0][$ver] = $pver[0][$ver];
		elseif (isset($pver[1][$ver]))
		    $nver[1][$ver] = $pver[1][$ver];
		else
		    errx("Broken version ID: '$ver' in /$yaml");
	    }
	}
	$record["vers"] = &$nver;
	$archive = (!count($nver[0]) && count($nver[1]));
	if (isset($data["Hidden"]))
	    $archive = true;
	unset($nver, $ver);

	// Check distros
	$arches = array();
	foreach ($data["Distros"] as $key) {
	    if (!isset($distids[$key])) {
		errx("Broken distro ID: '$key' in /$yaml");
		unset($data, $record, $arches);
		continue 2;
	    }
	    list($key, $arch) = explode("/", $key, 2);
	    if (isset($arches[$arch])) {
		unset($arch);
		continue;
	    }
	    if (!isset($hw_platforms[$arch])) {
		errx("Unknown platform name: '$arch' in /$yaml");
		unset($data, $record, $arches, $arch);
		continue 2;
	    }
	    $arches[$arch] = true;
	    unset($arch);
	}
	$arches = array_keys($arches);
	sort($arches);

	// Record all other
	if (isset($data["Status"]))
	    $record["state"] = $data["Status"];
	if (isset($data["Brief"]))
	    $record["brief"] = $data["Brief"];
	if (isset($data["Footnote"]))
	    $record["notes"] = $data["Footnote"];
	if (isset($data["Hidden"]))
	    $record["hide"] = true;
	$record["pgrp"]  = $pgrp;
	$record["list"]  = $plst;
	$record["check"] = $data["Checked"];
	$record["suite"] = $data["Suitable"];
	$record["dist"]  = $data["Distros"];
	$record["prods"] = isset($data["Products"]) ? $data["Products"]: null;
	$record["tags"]  = isset($data["Tags"]) ? explode(", ", $data["Tags"]): null;
	$record["arch"]  = &$arches;
	unset($arches);

	// Save record to the cache
	if ($archive) {
	    $old_idx[] = &$record;
	    $archive = "S";
	}
	else {
	    $act_idx[] = &$record;
	    $archive = "s";
	}
	unset($record);
	$srch = array();

	// Build new FTS index
	if (isset($data["Brief"]))
	    $srch[] = fts_string($data["Brief"]);
	if (isset($data["Status"]))
	    $srch[] = fts_string($data["Status"]);
	if (isset($data["Footnote"]))
	    $srch[] = fts_string($data["Footnote"]);
	if (isset($data["Products"])) {
	    $uniq = array();
	    foreach ($data["Products"] as $v_id => &$v_data) {
		$srch[] = fts_string($v_id);
		foreach ($v_data as &$p_id) {
		    if (isset($uniq[$p_id]))
			continue;
		    $srch[] = fts_string($p_id);
		    $uniq[$p_id] = true;
		}
		unset($p_id);
	    }
	    unset($v_id, $v_data, $uniq);
	}
	if (isset($data["Depends"])) {
	    $uniq = array();
	    foreach ($data["Depends"] as $unused => &$deplist) {
		foreach ($deplist as &$pkgname) {
		    if (isset($uniq[$pkgname]))
			continue;
		    $srch[] = fts_string($pkgname);
		    $uniq[$pkgname] = true;
		}
		unset($pkgname);
	    }
	    unset($unused, $deplist, $uniq);
	}
	if (isset($data["Features"])) {
	    $uniq = array();
	    foreach ($data["Features"] as $unused => &$flist) {
		foreach ($flist as &$fname) {
		    if (isset($uniq[$fname]))
			continue;
		    $srch[] = fts_string($fname);
		    $uniq[$fname] = true;
		}
		unset($fname);
	    }
	    unset($unused, $flist, $uniq);
	}
	if (isset($data["Restricts"])) {
	    $uniq = array();
	    foreach ($data["Restricts"] as $unused => &$rlist) {
		foreach ($rlist as &$rname) {
		    if (isset($uniq[$rname]))
			continue;
		    $srch[] = fts_string($rname);
		    $uniq[$rname] = true;
		}
		unset($rname);
	    }
	    unset($unused, $rlist, $uniq);
	}
	if (isset($data["NewsLinks"])) {
	    foreach ($data["NewsLinks"] as &$url)
		$srch[] = fts_string($url);
	    unset($url);
	}

	// Update FTS index in the cache
	update_fts_cache("{$archive}{$index}", "$VendorID:$ProductID:$cID", $srch);
	unset($data, $archive, $srch);
    }

    // Update CI-cache
    if (count($act_idx))
	arr2cache("c{$index}", $act_idx);
    else
	@unlink("$CACHEDIR/c{$index}.php");
    if (count($old_idx))
	arr2cache("C{$index}", $old_idx);
    else
	@unlink("$CACHEDIR/C{$index}.php");
    unset($tID, $dir, $ids, $pver, $act_idx, $old_idx);
}

?>