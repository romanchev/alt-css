<?php

function update_vendor_cache_r($VendorID, $index=false) {
    global $q_index;

    // Auto-detect index
    if ($index === false)
	$index = letter2idx(first_letter($VendorID));
    update_vendor_cache($VendorID, $index);
    $list = loadVendorProductsList($VendorID);

    foreach ($list as $pID) {
	update_product_cache($VendorID, $pID, $index);
	check_majorver_cache($VendorID, $pID, $index);
	update_cmpinfo_cache($VendorID, $pID, $index);
    }

    // Update root index
    if (!in_array($index, $q_index, true)) {
	$q_index[] = $index;
	sort($q_index);
    }
}

?>