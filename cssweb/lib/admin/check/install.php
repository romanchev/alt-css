<?php

function check_install() {
    global $GITPH, $FORCE_MODE, $IGNORE_MTIME, $install, $statinfo;

    if (!isset($statinfo))
	$statinfo = cache2arr("statinfo");
    $install = $changes = array();
    $dir = "Install";
    $objs = 0;

    if (!is_link($dir) && is_dir($dir) && (($dh = opendir($dir)) !== false)) {
	while (($entry = readdir($dh)) !== false) {
	    if (check_symlink("$dir/$entry"))
		; /* Nothing */
	    elseif (is_dir("$dir/$entry")) {
		if (($entry == ".") || ($entry == ".."))
		    continue;
		U_dir("/$dir/$entry");
	    }
	    elseif ($entry == $GITPH)
		; /* Nothing */
	    elseif (!preg_match("/^[0-9a-f]{32}$/", $entry))
		U_file("/$dir/$entry");
	    else {
		$target = trim(file_get_contents("$dir/$entry"));
		if (((substr($target, 0, 8) != "Manuals/") &&
		     (substr($target, 0, 8) != "Vendors/")) ||
		    (basename($target, ".pdf") == $target))
		{
		    errx("Bad link to PDF: /$dir/$entry => $target");
		}
		elseif (!$FORCE_MODE || $statinfo["errors"]) {
		    if (!file_exists($target))
			errx("Broken link to PDF: /$dir/$entry => $target");
		    elseif (!$IGNORE_MTIME && (filemtime($target) > filemtime("$dir/$entry")))
			errx("Deprecated link to PDF: /$dir/$entry => $target");
		    else
			$install[$target] = $entry;
		}
		elseif (!file_exists($target)) {
		    warnx("Broken link to PDF: /$dir/$entry => $target");
		    $changes[] = $entry;
		}
		elseif (!$IGNORE_MTIME && (filemtime($target) > filemtime("$dir/$entry"))) {
		    warnx("Deprecated link to PDF: /$dir/$entry => $target");
		    $changes[] = $entry;
		}
		else
		    $install[$target] = $entry;
		unset($target);
	    }
	    $objs ++;
	}
	closedir($dh);
	ksort($install);
    }

    if (!$objs)
	E_dir("/$dir");
    elseif (count($changes)) {
	foreach ($changes as $entry)
	    $entry = `git rm -f "$dir/$entry" 2>&1`;
	//$entry = count($changes);
	//$entry = `git commit -a -m "Удалено $entry ссылок на инструкции по установке"`;
    }
    $statinfo["install"] = count(array_keys($install));
    arr2cache("statinfo", $statinfo);
    unset($changes);

    return $statinfo["install"];
}

?>