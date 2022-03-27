<?php

function check_asciidoc($source) {
    /* TODO: ... */
}

function check_asciidoc_dir($dir) {
    if (file_exists("$dir/index.txt"))
	check_asciidoc("$dir/index.txt");
    else
	errx("Asciidoc enrty not found: /$dir/index.txt");
}

function check_common_inst_dir($dir) {
    global $GITPH, $ENTITES, $FORCE_MODE;
    global $install, $revinst, $statinfo;

    if (!isset($install))
	check_install();
    if (!isset($revinst))
	$revinst = array_flip($install);
    if (!isset($statinfo))
	$statinfo = cache2arr("statinfo");
    if (($dh = opendir($dir)) === false) {
	E_dir("/$dir");
	return 0;
    }
    $objs = $pdfs = 0;
    $docs = array(".odt", ".tex");

    while (($entry = readdir($dh)) !== false) {
	if (check_symlink("$dir/$entry"))
	    ; /* Nothing */
	elseif (is_dir("$dir/$entry")) {
	    if (($entry == ".") || ($entry == ".."))
		continue;
	    elseif (basename($entry, ".adoc") != $entry) {
		if (!isValidId($entry = basename($entry, ".adoc")))
		    errx("Invalid rID ($entry): /$dir/$entry.adoc");
		if (!file_exists("$dir/$entry.pdf"))
		    A_nopdf("/$dir/$entry.pdf");
		check_asciidoc_dir("$dir/$entry.adoc");
	    }
	    elseif (basename($entry, ".book") == $entry)
		U_dir("/$dir/$entry");
	    else {
		if (!isValidId($entry = basename($entry, ".book")))
		    errx("Invalid rID ($entry): /$dir/$entry.book");
		if (!file_exists("$dir/$entry.pdf"))
		    A_nopdf("/$dir/$entry.pdf");
		check_asciidoc_dir("$dir/$entry.book");
	    }
	}
	elseif (($entry == $GITPH) && in_array($dir, $ENTITES, true))
	    ; /* Nothing */
	elseif (basename($entry, ".txt") != $entry) {
	    if (!isValidId($entry = basename($entry, ".txt")))
		errx("Invalid rID ($entry): /$dir/$entry.txt");
	    if (!file_exists("$dir/$entry.pdf"))
		A_nopdf("/$dir/$entry.pdf");
	    if (!filesize("$dir/$entry.txt"))
		S_file("/$dir/$entry.txt");
	    check_asciidoc("$dir/$entry.txt");
	}
	elseif (basename($entry, ".pdf") != $entry) {
	    if ((substr($dir, 0, 8) == "Vendors/") ||
		(substr($dir, 0, 8) == "Manuals/"))
	    {
		if (!isset( $install["$dir/$entry"] )) {
		    if (!$FORCE_MODE || $statinfo["errors"])
			errx("Link to /$dir/$entry not found in /Install");
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
	    }
	    $sources = 0;
	    $entry = basename($entry, ".pdf");
	    if (!is_link("$dir/$entry.adoc") && is_dir("$dir/$entry.adoc"))
		$sources ++;
	    if (!is_link("$dir/$entry.book") && is_dir("$dir/$entry.book"))
		$sources ++;
	    foreach ($docs as $ext)
		if (file_exists("$dir/{$entry}{$ext}")) {
		    $sources ++;
		    break;
		}
	    if (!$sources)
		warnx("PDF has no source: /$dir/$entry.pdf");
	    elseif ($sources > 1)
		warnx("PDF has one more sources: /$dir/$entry.pdf");
	    unset($sources, $ext);
	    $pdfs ++;
	}
	else {
	    $found = false;
	    foreach ($docs as $ext) {
		if (basename($entry, $ext) != $entry) {
		    if (!isValidId($entry = basename($entry, $ext)))
			errx("Invalid rID ($entry): /$dir/{$entry}{$ext}");
		    if (!file_exists("$dir/$entry.pdf"))
			warnx("Source ($ext) not exported to /$dir/$entry.pdf");
		    if (!filesize("$dir/{$entry}{$ext}"))
			S_file("/$dir/{$entry}{$ext}");
		    $found = true;
		    break;
		}
	    }
	    if (!$found)
		U_file("/$dir/$entry");
	    unset($found, $ext);
	}
	$objs ++;
    }

    if (!$objs)
	E_dir("/$dir");
    closedir($dh);

    return $pdfs;
}

function check_documents() {
    global $statinfo;

    if (is_link("Documents") || !is_dir("Documents"))
	return 0;
    if (!isset($statinfo))
	$statinfo = cache2arr("statinfo");
    $statinfo["documents"] = check_common_inst_dir("Documents");
    arr2cache("statinfo", $statinfo);

    return $statinfo["documents"];
}

function check_manuals() {
    global $statinfo;

    if (is_link("Manuals") || !is_dir("Manuals"))
	return 0;
    if (!isset($statinfo))
	$statinfo = cache2arr("statinfo");
    $n = check_common_inst_dir("Manuals");
    $statinfo["manuals"] += $n;
    arr2cache("statinfo", $statinfo);

    return $n;
}

?>