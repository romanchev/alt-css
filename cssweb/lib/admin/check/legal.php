<?php

require_once("$LIBDIR/resize.php");

function check_legal_dayrec($year, $month, $dayrec) {
    global $FORCE_MODE, $IGNORE_MTIME, $statinfo;

    $dir = "Legal/$year/$month/$dayrec";
    list($day, $recno) = explode("-", $dayrec, 2);
    $day = @intval($day);
    $recono = @intval($recno);
    if (($day < 1) || ($day > 31)) {
	errx("Invalid day number: /$dir");
	return;
    }
    elseif (($recno < 0) || ($recno > 99)) {
	errx("Invalid record number: /$dir");
	return;
    }
    elseif (mktime(0,0,0, @intval($month), $day, @intval($year)) === false) {
	errx("Invalid date: /$dir");
	return;
    }
    if (($dh = opendir($dir)) === false) {
	E_dir("/$dir");
	return;
    }
    $objs = $files = 0;

    while (($entry = readdir($dh)) !== false) {
	if (check_symlink("$dir/$entry"))
	    ; /* Nothing */
	elseif (is_dir("$dir/$entry")) {
	    if (($entry == ".") || ($entry == ".."))
		continue;
	    U_dir("/$dir/$entry");
	}
	elseif (($entry == "cert.pdf") || ($entry == "cert.jpg")) {
	    if (!filesize("$dir/$entry")) {
		errx("Bad full-size certificate: /$dir/$entry");
		continue;
	    }
	    $thumb = "Certs/{$year}{$month}{$dayrec}.jpg";
	    if (!$FORCE_MODE || $statinfo["errors"]) {
		if (!file_exists($thumb))
		    errx("Certificate thumbnail not found: /$thumb");
		elseif (!filesize($thumb))
		    errx("Bad certificate thumbnail: /$thumb");
		elseif (!$IGNORE_MTIME && (filemtime($thumb) < filemtime("$dir/$entry")))
		    errx("Certificate thumbnail must be updated: /$thumb");
	    }
	    else {
		if (!file_exists($thumb))
		    warnx("Certificate thumbnail will be created: /$thumb");
		elseif (!filesize($thumb)) {
		    warnx("Bad certificate thumbnail, updating: /$thumb");
		    unlink($thumb);
		}
		elseif (!$IGNORE_MTIME && (filemtime($thumb) < filemtime("$dir/$entry"))) {
		    warnx("Old certificate thumbnail, updating: /$thumb");
		    unlink($thumb);
		}
		if (!file_exists($thumb)) {
		    if (!img_resize("$dir/$entry", $thumb, 0, 842))
			errx("Couldn't convert full-size certificate: /$dir/$entry");
		    else
			$entry = `git add "$thumb"`;
		}
	    }
	    $statinfo["unicerts"] ++;
	    unset($thumb);
	    $files ++;
	}
	elseif ($entry == "files.yml")
	    check_yaml_file("$dir/$entry");
	elseif ((basename($entry, ".pdf") == $entry) &&
		(basename($entry, ".jpg") == $entry))
	{
	    U_file("/$dir/$entry");
	}
	elseif (!filesize("$dir/$entry"))
	    errx("Bad full-size document scan: /$dir/$entry");
	else {
	    $rID = preg_replace("/\.(pdf|jpg)$/", "", $entry);
	    if (!isValidId($rID))
		errx("Invalid document scan ID: /$dir/$entry");
	    else
		$files ++;
	    unset($rID);
	}
	$objs ++;
    }

    if (!$objs)
	E_dir("/$dir");
    elseif (!file_exists("$dir/files.yml"))
	E_yaml("/$dir/files.yml");
    if (!$files)
	errx("No document scans found in /$dir");
    closedir($dh);
}

function check_legal_month($year, $month) {
    if (($dh = opendir($dir = "Legal/$year/$month")) === false) {
	E_dir("/$dir");
	return;
    }
    $objs = 0;

    while (($entry = readdir($dh)) !== false) {
	if (check_symlink("$dir/$entry"))
	    ; /* Nothing */
	elseif (!is_dir("$dir/$entry"))
	    U_file("/$dir/$entry");
	elseif (($entry == ".") || ($entry == ".."))
	    continue;
	elseif (!preg_match("/^\d\d\-\d\d$/", $entry))
	    U_dir("/$dir/$entry");
	else
	    check_legal_dayrec("$year", "$month", "$entry");
	$objs ++;
    }

    if (!$objs)
	E_dir("/$dir");
    closedir($dh);
}

function check_year_certs_lst($year) {
    /* TODO: ... */
}

function check_legal_year($year) {
    if (($dh = opendir($dir = "Legal/$year")) === false) {
	E_dir("/$dir");
	return;
    }
    $objs = 0;

    while (($entry = readdir($dh)) !== false) {
	if (check_symlink("$dir/$entry"))
	    ; /* Nothing */
	elseif (!is_dir("$dir/$entry"))
	    U_file("/$dir/$entry");
	elseif (($entry == ".") || ($entry == ".."))
	    continue;
	elseif ($entry == "certs.d")
	    check_year_certs_lst("$year");
	elseif (!preg_match("/^\d\d$/", $entry))
	    U_dir("/$dir/$entry");
	elseif ((intval($entry) < 1) || (intval($entry) > 12))
	    errx("Invalid month number: /$dir/$entry");
	else
	    check_legal_month("$year", "$entry");
	$objs ++;
    }

    if (!$objs)
	E_dir("/$dir");
    elseif (!is_dir("$dir/certs.d"))
	errx("Certificates list (certs.d) not found in /$dir");
    closedir($dh);
}

function check_legal() {
    global $GITPH, $statinfo;

    if (!isset($statinfo))
	$statinfo = cache2arr("statinfo");
    $statinfo["unicerts"] = $objs = 0;
    $dir = "Legal";

    if (!is_link($dir) && is_dir($dir) && (($dh = opendir($dir)) !== false)) {
	while (($entry = readdir($dh)) !== false) {
	    if (check_symlink("$dir/$entry"))
		; /* Nothing */
	    elseif (!is_dir("$dir/$entry")) {
		if ($entry != $GITPH)
		    U_file("/$dir/$entry");
	    }
	    elseif (($entry == ".") || ($entry == ".."))
		continue;
	    elseif (!preg_match("/^20\d\d$/", $entry))
		U_dir("/$dir/$entry");
	    else
		check_legal_year("$entry");
	    $objs ++;
	}

	if (!$objs)
	    E_dir("/$dir");
	closedir($dh);
    }

    arr2cache("statinfo", $statinfo);
    return $statinfo["unicerts"];
}

?>