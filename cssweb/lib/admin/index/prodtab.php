<?php

function reindex_products_table() {
    global $SUITES, $q_index, $catids, $vendids, $prodids, $statinfo;

    if (!isset($prodids))
	check_products();
    if (!count($prodids))
	fatal("Product definitions not found, nothing to do");
    if (!isset($catids))
	$catids = cache2arr("catids");
    if (!isset($vendids))
	$vendids = cache2arr("vendids");
    if (!isset($q_index))
	$q_index = cache2arr("abc");
    $prodids = $data = array();

    foreach ($q_index as $index) {
	$p_part = cache2arr("p{$index}");
	if (!count($p_part))
	    continue;
	foreach ($p_part as $tID => &$prod) {
	    list($vID, $pID) = explode(":", $tID, 2);
	    if (isset($catids[ $prod["Category"] ]))
		$catId = $catids[ $prod["Category"] ];
	    else
		$catId = $catids[ $prod["Category"]."/" ];
	    $manual = $prod["Install"] ? "2:".$prod["Install"]: "";
	    if (isset($prod["InstPDF"])) {
		foreach ($prod["InstPDF"] as $pdfentry) {
		    if (substr($pdfentry, 0, 2) == "3:")
			$manual = $pdfentry;
		}
		unset($pdfentry);
	    }
	    $name = $prod["Name"];
	    if ((mb_strlen($name) > 36) && (strstr($name, "(") !== false))
		$name = preg_replace("/[[:space:]]+\\(.*$/", "", $name);
	    $record = array (
		inquote_fast($name),
		$vendids[$vID],
		$catId,
		$name,
		$prod["URI"],
		$SUITES[ $prod["Suitable"] ],
		$manual
	    );
	    if (isset($prod["Footnote"]))
		$record[] = $prod["Footnote"];
	    $record[] = $tID;
	    $data[] = $record;
	    unset($record, $name, $manual, $vID, $pID);
	}
	unset($p_part, $tID, $prod);
    }

    usort($data, "compare_products");

    foreach ($data as &$prod) {
	$prodids[] = array_pop($prod);
	array_shift($prod);
    }

    $n = count($prodids);
    $prodids = array_flip($prodids);
    $statinfo["versions"] = $statinfo["majorver"] + $statinfo["releases"];
    arr2cache("statinfo", $statinfo);
    arr2cache("prodids", $prodids);
    arr2cache("products", $data);
    unset($data);

    return $n;
}

function compare_products(&$a, &$b) {
    return mb_strcasecmp($a[0], $b[0]);
}

?>