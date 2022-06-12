<?php

function check_instref_dir($dir, $ver) {
    global $FORCE_MODE, $statinfo, $install, $revinst;
    global $check_table_regex, $comp_ext_rules;

    if (($dh = opendir($dir)) === false) {
	E_dir("/$dir");
	return 0;
    }
    if (isset($check_table_regex))
	$tabrg =& $check_table_regex;
    else {
	$tabrg = "";
	$t_ids = array_keys($comp_ext_rules);
	foreach ($t_ids as $tableId)
	    $tabrg .= ($tabrg ? "|": "") . "\\." . $tableId;
	$tabrg = "inst(" . $tabrg . ")?";
	$check_table_regex =& $tabrg;
	unset($t_ids, $tableId);
    }
    $docs = "\\.(odt|tex|src)";
    $objs = $pdfs = $refs = $logo = $sources = 0;

    while (($entry = readdir($dh)) !== false) {
	if (check_symlink("$dir/$entry"))
	    ; /* Nothing */
	elseif (is_dir("$dir/$entry")) {
	    if (($entry == ".") || ($entry == ".."))
		continue;
	    elseif (!preg_match("/^".$tabrg."\\.(adoc|book)$/", $entry))
		U_dir("/$dir/$entry");
	    else {
		$dest = preg_replace("/\\.(adoc|book)$/", ".pdf", $entry);
		if (!file_exists("$dir/$dest"))
		    A_nopdf("/$dir/$dest");
		check_asciidoc_dir("$dir/$entry");
		unset($dest);
		$sources ++;
	    }
	}
	elseif ($ver && ($entry == "version.yml"))
	    check_yaml_file("$dir/$entry", true);
	elseif ($ver && ($entry == "version.jpg")) {
	    if (!filesize("$dir/$entry"))
		S_img("/$dir/$entry");
	    $logo ++;
	}
	elseif ($ver && ($entry == "version.png")) {
	    if (!filesize("$dir/$entry"))
		S_img("/$dir/$entry");
	    $logo ++;
	}
	elseif (preg_match("/^".$tabrg."\\.pdf$/", $entry)) {
	    if (!isset( $install["$dir/$entry"] )) {
		if (!$FORCE_MODE || $statinfo["errors"])
		    errx("Link to PDF not found in /Install: /$dir/$entry");
		else {
		    $pair = trim(`md5sum "$dir/$entry"`);
		    list($hash, $relpath) = explode("  ", $pair);
		    if (isset( $revinst[$hash] ))
			errx("Duplicate PDF found by /Install/$hash:\n" .
				"   /{$revinst[$hash]} => /$dir/$entry");
		    else {
			warnx("Link to PDF will be created:\n" .
				"   /Install/$hash => /$dir/$entry");
			file_put_contents("Install/$hash", "$dir/$entry");
			$relpath = `git add "Install/$hash" 2>&1`;
			$install["$dir/$entry"] = $hash;
			$revinst[$hash] = "$dir/$entry";
		    }
		    unset($hash, $pair, $relpath);
		}
	    }
	    $pdfs ++;
	}
	elseif (preg_match("/^".$tabrg."\\.ref$/", $entry)) {
	    $dlist = explode('/', $dir);
	    $vID = $dlist[1]; $pID = $dlist[2];
	    $rID = trim(file_get_contents("$dir/$entry"));
	    if (!filesize("$dir/$entry"))
		S_file("/$dir/$entry");
	    elseif (!$rID || !isValidId($rID))
		errx("Invalid reference ID: '$rID' in /$dir/$entry");
	    elseif (!file_exists("Manuals/$rID.pdf") &&
		    !file_exists("Vendors/$vID/.INSTALL/$rID.pdf") &&
		    !file_exists("Vendors/$vID/$pID/ARCH/ALL/$rID.pdf"))
	    {
		errx("Broken reference: '$rID' in /$dir/$entry");
	    }
	    $refs ++;
	    unset($dlist, $vID, $pID);
	}
	elseif (preg_match("/^".$tabrg."\\.txt$/", $entry)) {
	    $dest = preg_replace("/\\.txt$/", ".pdf", $entry);
	    if (!file_exists("$dir/$dest"))
		A_nopdf("/$dir/$dest");
	    if (!filesize("$dir/$entry"))
		S_file("/$dir/$entry");
	    check_asciidoc("$dir/$entry");
	    unset($dest);
	    $sources ++;
	}
	elseif (!preg_match("/^".$tabrg.$docs."$/", $entry))
	    U_file("/$dir/$entry");
	else {
	    $dest = preg_replace("/".$docs."$/", ".pdf", $entry);
	    if (!file_exists("$dir/$dest"))
		warnx("Source not exported to PDF: /$dir/$entry");
	    if (!filesize("$dir/$entry"))
		S_file("/$dir/$entry");
	    unset($dest);
	    $sources ++;
	}
	$objs ++;
    }

    if (!$objs)
	E_dir("/$dir");
    if ($pdfs - $sources == 1)
	warnx("PDF has no source in /$dir/");
    elseif ($pdfs > $sources)
	warnx("PDF's have no sources in /$dir/");
    if ($logo > 1)
	errx("One more logo files found in /$dir/");
    if ($ver && !file_exists("$dir/version.yml"))
	E_yaml("$dir/version.yml");
    closedir($dh);

    return $pdfs;
}

function check_arch_subdir($dir) {
    global $hw_platforms;

    if (($dh = opendir($dir)) === false) {
	E_dir("/$dir");
	return 0;
    }
    $objs = $pdfs = 0;

    while (($entry = readdir($dh)) !== false) {
	if (check_symlink("$dir/$entry"))
	    ; /* Nothing */
        elseif (!is_dir("$dir/$entry"))
	    U_file("/$dir/$entry");
	elseif (($entry == ".") || ($entry == ".."))
	    continue;
	elseif ($entry == "ALL")
	    $pdfs += check_common_inst_dir("$dir/$entry");
	elseif (isset($hw_platforms[$entry]))
	    $pdfs += check_instref_dir("$dir/$entry", false);
	else
	    U_dir("/$dir/$entry");
	$objs ++;
    }

    if (!$objs)
	E_dir("/$dir");
    closedir($dh);

    return $pdfs;
}

function check_vers_subdir($dir) {
    if (($dh = opendir($dir)) === false) {
	E_dir("/$dir");
	return 0;
    }
    $objs = $pdfs = 0;

    while (($entry = readdir($dh)) !== false) {
	if (check_symlink("$dir/$entry"))
	    ; /* Nothing */
        elseif (!is_dir("$dir/$entry"))
	    U_file("/$dir/$entry");
	elseif (($entry == ".") || ($entry == ".."))
	    continue;
	elseif (isValidId($entry) && file_exists("$dir/$entry/version.yml"))
	    $pdfs += check_instref_dir("$dir/$entry", true);
	else
	    U_dir("/$dir/$entry");
	$objs ++;
    }

    if (!$objs)
	E_dir("/$dir");
    closedir($dh);

    return $pdfs;
}

function check_ci_subdir($dir) {
    if (($dh = opendir($dir)) === false) {
	E_dir("/$dir");
	return 0;
    }
    $objs = $records = 0;

    while (($entry = readdir($dh)) !== false) {
	if (check_symlink("$dir/$entry"))
	    ; /* Nothing */
        elseif (is_dir("$dir/$entry")) {
	    if (($entry != ".") && ($entry != "..")) {
		U_dir("/$dir/$entry");
		$objs ++;
	    }
	    continue;
	}
	elseif (!preg_match("/^\d{8}\.yml$/", $entry))
	    U_file("/$dir/$entry");
	elseif (!check_yaml_file("$dir/$entry"))
	    $records ++;
	$objs ++;
    }

    if (!$objs)
	E_dir("/$dir");
    closedir($dh);

    return $records;
}

function check_products() {
    global $statinfo, $prodids, $install, $revinst;

    if (!isset($prodids))
	check_vendors();
    if (!isset($install))
	check_install();
    if (!isset($revinst))
	$revinst = array_flip($install);
    if (!isset($statinfo))
	$statinfo = cache2arr("statinfo");
    $statinfo["compinfo"] = 0;

    foreach ($prodids as $tID) {
	list($vID, $pID) = explode(":", $tID);
	$dh = opendir($dir = "Vendors/$vID/$pID");
	$logo = 0;

	while (($entry = readdir($dh)) !== false) {
	    if (check_symlink("$dir/$entry"))
		continue;
	    elseif (!is_dir("$dir/$entry")) {
		if ($entry == "product.yml")
		    check_yaml_file("$dir/$entry");
		elseif (($entry != "product.jpg") && ($entry != "product.png"))
		    U_file("/$dir/$entry");
		else {
		    if (!filesize("$dir/$entry"))
			S_img("/$dir/$entry");
		    $logo ++;
		}
		continue;
	    }
	    elseif (($entry == ".") || ($entry == ".."))
		continue;
	    elseif ($entry == "ARCH")
		$statinfo["manuals"] += check_arch_subdir("$dir/$entry");
	    elseif ($entry == "VERS")
		$statinfo["manuals"] += check_vers_subdir("$dir/$entry");
	    elseif ($entry == "CI")
		$statinfo["compinfo"] += check_ci_subdir("$dir/$entry");
	    else
		U_dir("/$dir/$entry");
	}

	if ($logo > 1)
	    errx("One more logo files found in /$dir");
	closedir($dh);
    }
    arr2cache("statinfo", $statinfo);

    return $statinfo["compinfo"];
}

?>