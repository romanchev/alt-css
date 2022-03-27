<?php

function check_majorver_cache($VendorID, $ProductID, $index=false)
{
    // Auto-detect index
    if ($index === false)
	$index = letter2idx(first_letter($VendorID));
    $major = $builds = array();

    // Load product versions
    $prod = cache2arr("p{$index}");
    $versions = $prod["$VendorID:$ProductID"]["Versions"];
    $archive = isset($prod["$VendorID:$ProductID"]["Hidden"]);
    unset($prod);

    // Iterate product versions and releases
    foreach ($versions[0] as $rel => $ver) {
	if ($rel != $ver)
	    $builds[$rel] = $archive;
	if ($ver)
	    $major[$ver] = $archive;
    }
    foreach ($versions[1] as $rel => $ver) {
	if ($rel != $ver)
	    $builds[$rel] = true;
	if ($ver && !isset($major[$ver]))
	    $major[$ver] = true;
    }

    // Reindex major versions cache
    foreach ($major as $ver => $archive)
	update_version_cache($VendorID, $ProductID, $ver, $index, $archive);

    // Load product.yml and check ActualVers, if defined
    $yaml = "Vendors/$VendorID/$ProductID/product.yml";
    $data = loadYamlFile($yaml, "product");
    if (isset($data["ActualVers"])) {
	foreach ($data["ActualVers"] as $rel)
	    if (!isset($major[$rel]) && !isset($builds[$rel]))
		errx("Broken version ID: '$rel' in /$yaml");
    }
}

?>