<?php

function reindex_vendors_table() {
    global $q_index, $vendids;

    if (!isset($vendids))
	check_vendors();
    if (!count($vendids))
	fatal("Vendor definitions not found, nothing to do");
    if (!isset($q_index))
	$q_index = cache2arr("abc");
    $vendids = $data = array();

    foreach ($q_index as $index) {
	$v_part = cache2arr("v{$index}");
	if (!count($v_part))
	    continue;
	foreach ($v_part as $vID => &$vend) {
	    $record = array($vend["Name"], $vend["URI"],
			inquote_fast($vend["Name"]), $vID);
	    if (isset($vend["Note"]))
		$record[] = $vend["Note"];
	    $data[] = $record;
	    unset($record);
	}
	unset($v_part, $vID, $vend);
    }

    usort($data, "compare_vendors");

    foreach ($data as &$vend) {
	$footnote = (count($vend) == 5) ? array_pop($vend): null;
	$vendids[] = array_pop($vend);
	array_pop($vend);
	if ($footnote !== null)
	    $vend[] = $footnote;
	unset($footnote);
    }

    $n = count($vendids);
    $vendids = array_flip($vendids);
    arr2cache("vendids", $vendids);
    arr2cache("vendors", $data);
    unset($data, $vend);

    return $n;
}

function compare_vendors(&$a, &$b) {
    return mb_strcasecmp($a[2], $b[2]);
}

?>