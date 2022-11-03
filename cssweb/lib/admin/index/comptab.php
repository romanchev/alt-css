<?php

function reindex_compatibility($tabId) {
    global $SUITES, $comp_ext_rules, $q_index, $catids, $catfull;
    global $hw_platforms, $distids, $vendids, $prodids;

    if (!isset($q_index))
	$q_index = cache2arr("abc");
    $citab = $saved_notes = array();
    $tableId = ($tabId == "S10") ? "P10": $tabId;

    // Hardware platforms order
    $platforms = array_flip(array_keys( $hw_platforms ));

    // Pivot table column labels
    $columns = array_flip(array_keys( $comp_ext_rules[$tabId] ));

    // Load other data
    if (!isset($catids))
	$catids = cache2arr("catids");
    if (!isset($catfull))
	$catfull = cache2arr("catfull");
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

	    // Locate the preferred product installation guide
	    $p_guide = $prod["Install"] ? "3:".$prod["Install"]: false;
	    if (isset($prod["Manuals"]) && isset($prod["Manuals"][$tabId]))
		$p_guide = "4:".$prod["Manuals"][$tabId];
	    elseif (isset($prod["Manuals"]) && isset($prod["Manuals"][$tableId]))
		$p_guide = "4:".$prod["Manuals"][$tableId];
	    $ArchDefs = array();
	    if (!$p_guide) {
		$ctree = explode("/", $prod["Category"]);
		do {
		    $ccurr = implode("/", $ctree);
		    if (isset( $catids[$ccurr] ))
			$catId = $catids[$ccurr];
		    elseif (isset( $catids[$ccurr."/"] ))
			$catId = $catids[$ccurr."/"];
		    else {
			array_pop($ctree);
			unset($ccurr);
			continue;
		    }
		    if (isset( $catfull[$catId][FCAT_DefsIDX] )) {
			$ArchDefs = $catfull[$catId][FCAT_DefsIDX];
			unset($catId, $ccurr);
			break;
		    }
		    array_pop($ctree);
		    unset($catId, $ccurr);
		} while(count($ctree));
		unset($ctree);
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

		$manual = $p_guide;
		if (!$manual && isset($ArchDefs[$arch])) {
		    if (isset($ArchDefs[$arch]["Manuals"]) &&
			isset($ArchDefs[$arch]["Manuals"][$tabId]))
		    {
			$manual = "2:".$ArchDefs[$arch]["Manuals"][$tabId];
		    }
		    elseif (isset($ArchDefs[$arch]["Manuals"]) &&
			isset($ArchDefs[$arch]["Manuals"][$tableId]))
		    {
			$manual = "2:".$ArchDefs[$arch]["Manuals"][$tableId];
		    }
		    elseif (isset($ArchDefs[$arch]["Install"]))
		    {
			$manual = "1:".$ArchDefs[$arch]["Install"];
		    }
		}

		$found = -1;
		$sdprf = "6:{$tabId}@";
		$ndprf = strlen($sdprf);
		$sdown = "6:{$tableId}@";
		$ndown = strlen($sdown);
		$stext = "7:{$arch}=";
		$ntext = strlen($stext);
		$spref = "8:{$tableId}@{$arch}=";
		$npref = strlen($spref);
		$label = $columns[$label];
		for ($i=0; $i < count($distimg); $i++)
		    if (($distimg[$i][0] == $label) && ($distimg[$i][1] == $platforms[$arch])) {
			$found = $i;
			break;
		    }
		if ($found == -1) {
		    if (isset($prod["InstPDF"])) {
			foreach ($prod["InstPDF"] as $cpdf) {
			    if (substr($cpdf, 0, $npref) == $spref)
				$manual = "8:".substr($cpdf, $npref);
			    elseif (substr($cpdf, 0, $ntext) == $stext)
				$manual = "7:".substr($cpdf, $ntext);
			    elseif (substr($cpdf, 0, $ndprf) == $sdprf)
				$manual = "6:".substr($cpdf, $ndprf);
			    elseif (substr($cpdf, 0, $ndown) == $sdown)
				$manual = "6:".substr($cpdf, $ndown);
			    elseif (substr($cpdf, 0, 2) == "5:")
				$manual = $cpdf;
			    unset($cpdf);
			}
		    }
		    $distimg[] = array($label, $platforms[$arch], $manual);
		}
		unset($dIndex, $arch, $label, $i, $sdprf, $ndprf, $stext);
		unset($ntext, $sdown, $ndown, $spref, $npref, $found, $manual);
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
			    if (isset( $civer[$id]["Manuals"] ) &&
				isset( $civer[$id]["Manuals"][$tabId] ))
			    {
				$manual = "A:".$civer[$id]["Manuals"][$tabId];
			    }
			    elseif (isset( $civer[$id]["Manuals"] ) &&
				isset( $civer[$id]["Manuals"][$tableId] ))
			    {
				$manual = "A:".$civer[$id]["Manuals"][$tableId];
			    }
			    elseif (isset( $civer[$id]["Install"] ))
			    {
				$manual = "9:".$civer[$id]["Install"];
			    }
			    if (isset( $civer[$id]["InstPDF"] )) {
				$pdfs  = $civer[$id]["InstPDF"];
				$stprf = "C:{$tabId}@";
				$ntprf = strlen($stprf);
				$stext = "C:{$tableId}@";
				$ntext = strlen($stext);
				foreach ($pdfs as $cpdf) {
				    if (substr($cpdf, 0, $ntprf) == $stprf)
					$manual = "C:".substr($cpdf, $ntprf);
				    elseif (substr($cpdf, 0, $ntext) == $stext)
					$manual = "C:".substr($cpdf, $ntext);
				    elseif (substr($cpdf, 0, 2) == "B:")
					$manual = $cpdf;
				    unset($cpdf);
				}
				unset($pdfs, $stprf, $ntprf, $stext, $ntext);
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
    arr2cache("comptab-".$tabId, $citab);
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