<?php

function update_version_cache($VendorID, $ProductID, $version, $index=false, $archive=false) {
    global $install;

    // Auto-detect index
    if ($index === false)
	$index = letter2idx(first_letter($VendorID));
    if (!isset($install))
	check_install();
    $srch = $major = array();
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

    // Check installation guide PDF
    if (file_exists("$dir/inst.pdf")) {
	if (isset($install["$dir/inst.pdf"]))
	    $major["InstPDF"] = "6:".$install["$dir/inst.pdf"];
    }
    elseif (file_exists("$dir/inst.ref")) {
	$rpath = ref2pdf($VendorID, $ProductID, "VERS/$version");
	if ($rpath)
	    $major["InstPDF"] = "6:".$install[$rpath];
	unset($rpath);
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