<?php

function reindex_compatibility($tableId) {
    global $SUITES, $comp_ext_rules, $q_index;
    global $hw_platforms, $distids, $vendids, $prodids;

    if (!isset($q_index))
	$q_index = cache2arr("abc");
    $citab = $saved_notes = array();

    // Hardware platforms order
    $platforms = array_flip(array_keys( $hw_platforms ));

    // Pivot table column labels
    $columns = array_flip(array_keys( $comp_ext_rules[$tableId] ));

    // Load other data
    if (!isset($distids))
	$distids = cache2arr("distids");
    elseif (isset($distids[0]))
	$distids = array_flip($distids);
    if (!isset($vendids))
	$vendids = cache2arr("vendids");
    elseif (isset($vendids[0]))
	$vendids = array_flip($vendids);
    if (!isset($prodids)) {
	$prodids = cache2arr("prodids");
	$products = array_flip($prodids);
    }
    elseif (!isset($prodids[0]))
	$products = array_flip($prodids);
    else {
	$products = $prodids;
	$prodids = array_flip($products);
    }
    $distros = cache2arr("distros");

    // Read CI-records from L2-cache
    foreach ($q_index as $index) {
	$cisrc  = cache2arr("c{$index}");
	$civer  = cache2arr("C{$index}");
	if (count($civer))
	    $cisrc = array_merge($cisrc, $civer);
	$civer  = cache2arr("m{$index}");
	$ciprod = cache2arr("M{$index}");
	if (count($ciprod))
	    $civer = array_merge($civer, $ciprod);
	$ciprod = cache2arr("p{$index}");
	foreach ($cisrc as &$ci)
	{
	    // Check compatibility results and primary conditions
	    if (!isset($ci["certID"]) && ($ci["type"] != "YES"))
		continue;
	    if (isset($ci["hide"]))
		continue;
	    $recID = "/Vendors/" . $ci["vID"] . "/" . $ci["pID"] .
			"/CI/" . $ci["cID"] . ".yml";
	    $tID = $ci["vID"] . ":" . $ci["pID"];
	    if (!isset($vendids[ $ci["vID"] ])) {
		errx("Internal: broken Vendor ID in #$index: $recID");
		unset($tID, $recID);
		continue;
	    }
	    if (!isset( $prodids[$tID] )) {
		errx("Internal: broken Product ID in #$index: $recID");
		unset($tID, $recID);
		continue;
	    }
	    if (isset( $ci["certID"] )) {
		if (strlen($ci["certID"]) != 11) {
		    errx("Internal: invalid CertLink in #$index: $recID");
		    unset($tID, $recID);
		    continue;
		}
	    }
	    $prod = &$ciprod[$tID];
	    if (isset($prod["Hidden"])) {
		unset($prod, $tID, $recID);
		continue;
	    }

	    // Make major versions list
	    $distimg = $majv = array();
	    foreach ($ci["vers"][0] as $rel => $ver)
		if (!isset($civer["$tID:$ver"]) ||
		    !isset($civer["$tID:$ver"]["Hidden"]))
		{
		    $majv[$ver] = true;
		}
	    foreach ($ci["vers"][1] as $rel => $ver)
		if (!isset($civer["$tID:$ver"]) ||
		    !isset($civer["$tID:$ver"]["Hidden"]))
		{
		    $majv[$ver] = true;
		}
	    $majv = array_keys($majv);
	    if (!count($majv))
		$majv = array("");
	    unset($rel, $ver);

	    // Make table columns list
	    foreach ($ci["dist"] as &$id) {
		if (!isset( $distids[$id] )) {
		    errx("Internal: broken Distro ID '$id' in #$index: $recID");
		    continue;
		}
		$dIndex = $distids[$id];
		if (!isset( $distros[$dIndex] )) {
		    errx("Internal: Distro '$id' by index #".
			strval($dIndex)." not found in $recID");
		    unset($dIndex);
		    continue;
		}
		$arch = $distros[$dIndex][DIST_ArchIDX];
		if (!isset( $platforms[$arch] )) {
		    errx("Internal: broken Platform ID '$arch' in #index: $recID");
		    unset($dIndex, $arch);
		    continue;
		}
		$label = $distros[$dIndex][DIST_LablIDX];
		if (!$label || !isset($columns[$label])) {
		    unset($dIndex, $arch, $label);
		    continue;
		}
		$found = -1;
		$sdown = "4:{$tableId}@";
		$ndown = strlen($sdown);
		$stext = "5:{$arch}=";
		$ntext = strlen($stext);
		$spref = "6:{$tableId}@{$arch}=";
		$npref = strlen($spref);
		$label = $columns[$label];
		$arch  = $platforms[$arch];
		for ($i=0; $i < count($distimg); $i++)
		    if (($distimg[$i][0] == $label) && ($distimg[$i][1] == $arch)) {
			$found = $i;
			break;
		    }
		if ($found == -1) {
		    $manual = false;
		    if (isset($prod["InstPDF"])) {
			foreach ($prod["InstPDF"] as $pdf) {
			    if (substr($pdf, 0, $npref) == $spref)
				$manual = "6:".substr($pdf, $npref);
			    elseif (substr($pdf, 0, $ntext) == $stext)
				$manual = "5:".substr($pdf, $ntext);
			    elseif (substr($pdf, 0, $ndown) == $sdown)
				$manual = "4:".substr($pdf, $ndown);
			}
			unset($pdf);
		    }
		    $distimg[] = array($label, $arch, $manual);
		    unset($manual);
		}
		unset($dIndex, $arch, $label, $i, $stext, $ntext);
		unset($sdown, $ndown, $spref, $npref, $found);
	    }
	    unset($id);

	    // Check other pre-requires
	    if (!count($distimg)) {
		unset($distimg, $majv, $prod, $tID, $recID);
		continue;
	    }
	    $p_idx = $prodids[ $tID ];
	    $v_idx = $vendids[ $ci["vID"] ];
	    $suite = $SUITES[ $ci["suite"] ];
	    $cert  = isset($ci["certID"]) ? $ci["certID"]: -1;

	    // Iterate distros, versions and remove duplicates
	    foreach ($distimg as &$dist) {
		foreach ($majv as $ver) {
		    $manual = $dist[2];
		    $found  = false;
		    $notes  = false;

		    foreach ($citab as &$nci) {
			if ($nci[CTAB_DcolIDX] != $dist[0])
			    continue;
			if ($nci[CTAB_ArchIDX] != $dist[1])
			    continue;
			if ($nci[CTAB_VendIDX] != $v_idx)
			    continue;
			if ($nci[CTAB_ProdIDX] != $p_idx)
			    continue;
			if ($nci[CTAB_VersIDX] == $ver) {
			    $found = true;
			    break;
			}
		    }

		    if ($ver != "") {
			$id = "$tID:$ver";
			if (isset( $civer[$id] )) {
			    if (isset( $civer[$id]["Footnote"] ))
				$notes = $civer[$id]["Footnote"];
			    if (isset( $civer[$id]["Install"] ))
				$manual = "7:".$civer[$id]["Install"];
			    if (isset( $civer[$id]["InstPDF"] )) {
				$left = $manual ? @intval(substr($manual, 0, 1)): 0;
				$pdfs = $civer[$id]["InstPDF"];
				foreach ($pdfs as $cpdf) {
				    $right = @intval(substr($cpdf, 0, 1));
				    if ($right == 9) {
					$stext = "9:{$tableId}@";
					$ntext = strlen($stext);
					if (substr($cpdf, 0, $ntext) != $stext) {
					    unset($stext, $ntext);
					    continue;
					}
					$cpdf = "9:".substr($cpdf, $ntext);
					unset($stext, $ntext);
				    }
				    if ($left < $right)
					$manual = $cpdf;
				    unset($cpdf);
				}
				unset($left, $right, $pdfs);
			    }
			}
			unset($id);
		    }

		    if (!$found) {
			$record = array($dist[0], $dist[1], $v_idx,
					$p_idx, $ver, $ci["cID"],
					$cert, $suite, $manual);
			if (isset($ci["notes"]))
			    $record[] = $ci["notes"];
			if ($notes)
			    $saved_notes[ count($citab) ] = $notes;
			$citab[] = $record;
			unset($record);
		    }
		    elseif ($cert !== -1) {
			if ($nci[CTAB_CertIDX] !== -1)
			    warnx("Certificate '" . $nci[CTAB_CertIDX] .
				    "' assigned by /Vendors/" .
				    str_replace(":", "/",
				        $products[$nci[CTAB_ProdIDX]]) .
				    "/CI/" . $nci[CTAB_CompIDX] . ".yml\n" .
				    "   ignoring certificate '$cert' in $recID"
			    );
			else {
			    if (isset($nci[CTAB_NoteIDX]))
				unset($nci[CTAB_NoteIDX]);
			    if (isset($ci["notes"]))
				$nci[CTAB_NoteIDX] = $ci["notes"];
			    $nci[CTAB_CompIDX] = $ci["cID"];
			    $nci[CTAB_CertIDX] = $cert;
			    $nci[CTAB_SuitIDX] = $suite;
			}
		    }

		    unset($nci, $found, $manual, $notes);
		}
		unset($ver);
	    }
	    unset($distimg, $majv, $recID, $tID);
	    unset($prod, $v_idx, $p_idx, $dist);
	}
	unset($cisrc, $civer, $ciprod, $ci);
    }

    for ($i=0; $i < count($citab); $i++) {
	$ver = $citab[$i][CTAB_VersIDX];
	if (!$ver)
	    continue;
	if (!isset( $saved_notes[$i] ))
	    $ver = str_replace("_", " ", $ver);
	else {
	    if (preg_match("/^v\d+(\.\d+)?$/", $ver))
		$ver = substr($ver, 1);
	    else
		$ver = str_replace("_", " ", $ver);
	    $ver .= "[[" . $saved_notes[$i] . "]]";
	}
	$citab[$i][CTAB_VersIDX] = $ver;
	unset($ver);
    }

    usort($citab, "compare_compinfo");
    arr2cache("comptab-".$tableId, $citab);
    unset($citab, $saved_notes, $columns, $platforms);
}

function compare_compinfo(&$a, &$b) {
    if ($a[CTAB_ArchIDX] != $b[CTAB_ArchIDX])
	return ($a[CTAB_ArchIDX] < $b[CTAB_ArchIDX]) ? -1: 1;
    if ($a[CTAB_ProdIDX] != $b[CTAB_ProdIDX])
	return ($a[CTAB_ProdIDX] < $b[CTAB_ProdIDX]) ? -1: 1;
    if ($a[CTAB_VersIDX] != $b[CTAB_VersIDX]) {
	if (!$a[CTAB_VersIDX])
	    return 1;
	elseif (!$b[CTAB_VersIDX])
	    return -1;
	return ($a[CTAB_VersIDX] < $b[CTAB_VersIDX]) ? -1: 1;
    }
    if ($a[CTAB_DcolIDX] != $b[CTAB_DcolIDX])
	return ($a[CTAB_DcolIDX] < $b[CTAB_DcolIDX]) ? -1: 1;
    return 0;
}

?>