#!/usr/bin/php
<?php

// Bootstrap
$LIBDIR = @dirname(@dirname(@realpath(__FILE__)))."/lib";
require_once("$LIBDIR/admin/cli.php");
require_once("$LIBDIR/admin/fatal.php");

// Constants
define("PLATFORM_IDX",	0);
define("VENDNAME_IDX",	1);
define("VENDLINK_IDX",	2);
define("PRODNAME_IDX",	3);
define("PRODLINK_IDX",	4);
define("MAJORVER_IDX",	5);
define("PRODINST_IDX",	6);
define("COMP1COL_IDX",	7);
//
define("VENDNOTE_OUT",	7);
define("PRODNOTE_OUT",	8);
define("MVERNOTE_OUT",	9);
define("NOTEHASH_OUT", 10);
define("CATEGORY_OUT", 11);
define("FIRSTCOL_OUT", 12);
//
define("MIN_SIMILARITY",  30);
define("MAX_SIMILARITY",  200);


function &csv2arr(&$str) {
    global $datacols, $noexpand;

    $list = explode("|", $str);
    foreach ($list as &$item)
	$item = substr($item, 1, -1);
    $arr = array();
    for ($i=PLATFORM_IDX; $i < COMP1COL_IDX; $i++)
	$arr[] = &$list[$i];
    $notes = "";

    // VENDNOTE_OUT
    if (strstr($list[VENDNAME_IDX], "[[") === false)
	$arr[] = "";
    else {
	$x = explode("[[", $list[VENDNAME_IDX], 2);
	$arr[VENDNAME_IDX] = &$x[0];
	$x = substr($x[1], 0, -2);
	$notes .= "V:$x ";
	$arr[] = &$x;
	unset($x);
    }

    // PRODNOTE_OUT
    if (strstr($list[PRODNAME_IDX], "[[") === false)
	$arr[] = "";
    else {
	$x = explode("[[", $list[PRODNAME_IDX], 2);
	$arr[PRODNAME_IDX] = &$x[0];
	$x = substr($x[1], 0, -2);
	$notes .= "P:$x ";
	$arr[] = &$x;
	unset($x);
    }

    // MVERNOTE_OUT
    if (strstr($list[MAJORVER_IDX], "[[") === false)
	$arr[] = "";
    else {
	$x = explode("[[", $list[MAJORVER_IDX], 2);
	$arr[MAJORVER_IDX] = &$x[0];
	$x = substr($x[1], 0, -2);
	$notes .= "M:$x ";
	$arr[] = &$x;
	unset($x);
    }

    // NOTEHASH_OUT, empty by default
    $arr[] = "";

    // CATEGORY_OUT
    if (isset( $list[COMP1COL_IDX+$datacols] ))
	$arr[] = &$list[COMP1COL_IDX+$datacols];
    else
	$arr[] = "";

    // Compatibility data cells
    for ($i=0; $i < $datacols; $i++) {
	$cell = &$list[COMP1COL_IDX+$i];
	if (!$cell || ($cell == "+"))
	    $arr[] = &$cell;
	elseif ($cell == "Совместимы")
	    $arr[] = "+";
	elseif ($cell == "#")
	    $arr[] = ($noexpand ? "": "#");
	elseif (substr($cell, 0, 1) == "+")
	    $arr[] = "+";
	elseif (substr($cell, 0, 1) == "#")
	    $arr[] = ($noexpand ? "": "#");
	elseif (strstr($cell, "[[") === false)
	    $arr[] = &$cell;
	else {
	    $x = explode("[[", $cell, 2);
	    $arr[] = &$x[0];
	    unset($x);
	}
	unset($cell);
    }

    // Footnotes for compatibility cells
    for ($i=0; $i < $datacols; $i++) {
	$cell = &$list[COMP1COL_IDX+$i];
	if (!$cell || (strstr($cell, "[[") === false))
	    $arr[] = "";
	elseif ($noexpand && (substr($cell, 0, 1) == "#"))
	    $arr[] = "";
	else {
	    $x = explode("[[", $cell, 2);
	    $x = substr($x[1], 0, -2);
	    $notes .= "D$i:$x ";
	    $arr[] = &$x;
	    unset($x);
	}
	unset($cell);
    }

    // NOTEHASH_OUT again
    if ($notes)
	$arr[NOTEHASH_OUT] = md5($notes);

    // Temporary vis-a-vis column
    $arr[] = null;

    return $arr;
}

function arr2csv($difftype, $arch, $record) {
    array_pop($record);	// vis-a-vis column
    $lineno = $record[PLATFORM_IDX];
    $record[PLATFORM_IDX] = $arch;
    echo "{$difftype}|{$lineno}";
    foreach ($record as &$column)
	echo "|\"{$column}\"";
    echo "\n";
}

function get_similarity(&$lrow, &$rrow) {
    $vendname = ($lrow[VENDNAME_IDX] == $rrow[VENDNAME_IDX]);
    $vendlink = ($lrow[VENDLINK_IDX] == $rrow[VENDLINK_IDX]);
    $prodname = ($lrow[PRODNAME_IDX] == $rrow[PRODNAME_IDX]);
    $prodlink = ($lrow[PRODLINK_IDX] == $rrow[PRODLINK_IDX]);
    $version  = ($lrow[MAJORVER_IDX] == $rrow[MAJORVER_IDX]);
    $prodinst = ($lrow[PRODINST_IDX] == $rrow[PRODINST_IDX]) || IGNORE_PRODINST;

    if ($prodlink && $prodname && $vendlink && $vendname && $prodinst && $version)
	return MAX_SIMILARITY;
    if (!$prodlink && !$prodname && !$vendlink && !$vendname && !$prodinst && !$version)
	return 0;
    $fillvers = (($lrow[MAJORVER_IDX] == "") && ($rrow[MAJORVER_IDX] != ""));

    if ($prodlink) {
	if ($vendlink) {
	    if ($prodname && $vendname) {
		if ($version)
		    return MAX_SIMILARITY - 10;
		if ($fillvers) {
		    if ($prodinst)
			return MAX_SIMILARITY - 20;
		    return MAX_SIMILARITY - 30;
		}
	    }
	    if ($version)
		return MAX_SIMILARITY - 40;
	    if ($prodinst && $fillvers)
		return MAX_SIMILARITY - 50;
	}

	if ($version || ($prodinst && $fillvers)) {
	    $cnt = 0;
	    if ($prodname)
		$cnt ++;
	    if ($vendname)
		$cnt ++;
	    if ($vendlink)
		$cnt ++;
	    if ($cnt >= 2) {
		if ($version)
		    return MAX_SIMILARITY - 60;
		return MAX_SIMILARITY - 70;
	    }
	    if ($version)
		return MAX_SIMILARITY - 80;
	    return MAX_SIMILARITY - 90;
	}
    }

    if ($prodname) {
	if ($vendlink) {
	    if ($vendname) {
		if ($version)
		    return MAX_SIMILARITY - 15;
		if ($fillvers) {
		    if ($prodinst)
			return MAX_SIMILARITY - 25;
		    return MAX_SIMILARITY - 35;
		}
	    }
	    if ($version)
		return MAX_SIMILARITY - 45;
	    if ($prodinst && $fillvers)
		return MAX_SIMILARITY - 55;
	}

	if ($version || ($prodinst && $fillvers)) {
	    $cnt = 0;
	    if ($vendname)
		$cnt ++;
	    if ($vendlink)
		$cnt ++;
	    if ($cnt >= 1) {
		if ($version)
		    return MAX_SIMILARITY - 65;
		return MAX_SIMILARITY - 75;
	    }
	    if ($version)
		return MAX_SIMILARITY - 85;
	    return MAX_SIMILARITY - 95;
	}
    }

    if ($vendlink && $vendname && $prodname) {
	if ($version)
	    return MAX_SIMILARITY - 100;
	if ($prodinst && $fillvers)
	    return MAX_SIMILARITY - 110;
    }

    return 0;
}

function is_equal(&$lrow, &$rrow) {
    global $datacols, $ignore_category;

    // Compare low-priority data columns first
    if ($lrow[NOTEHASH_OUT] != $rrow[NOTEHASH_OUT])
	return false;
    if (!$ignore_category && ($lrow[CATEGORY_OUT] != $rrow[CATEGORY_OUT]))
	return false;
    $certlen = (IGNORE_CERTNUMB ? 8: 11);

    // Compare compatibility cell's or certificate dates only
    for ($i=FIRSTCOL_OUT; $i < FIRSTCOL_OUT+$datacols; $i++) {
	if ($lrow[$i] == $rrow[$i])
	    continue;
	if (($lrow[$i] == "+") && ($rrow[$i] == "#"))
	    continue;
	if (($lrow[$i] == "#") && ($rrow[$i] == "+"))
	    continue;
	if (!$lrow[$i] || !$rrow[$i])
	    return false;
	if (($lrow[$i] == "+") || ($lrow[$i] == "#"))
	    return false;
	if (($rrow[$i] == "+") || ($rrow[$i] == "#"))
	    return false;
	$l = substr($lrow[$i], 0, $certlen);
	$r = substr($rrow[$i], 0, $certlen);
	if ($l != $r)
	    return false;
	unset($l, $r);
    }

    return true;
}


// Entry point
if (($argc < 4) || !isset($comp_ext_rules[ $argv[1] ]))
    fatal("Usage: tabdiff <tableId> <upload1> <upload2> [--noexpand]");
$noexpand = ($argc == 5) && ($argv[4] == "--noexpand");
$tableId  = $argv[1];
$CSVs     = "/History/$tableId/";
$upload1  = $CSVs.$argv[2].".csv";
$upload2  = $CSVs.$argv[3].".csv";
if (!file_exists($DATADIR.$upload1))
    fatal("Input CSV-file not found: $upload1");
if (file_exists($DATADIR.$upload2))
    $upload2 = $DATADIR.$upload2;
else {
    $upload3 = "$CACHEDIR/$tableId-".$argv[3].".csv";
    if (!file_exists($upload3))
	fatal("Input CSV-file not found: $upload2");
    $upload2 = $upload3;
    unset($upload3);
}
$lfile = file($DATADIR.$upload1, FILE_IGNORE_NEW_LINES);
if (!$lfile)
    fatal("Couldn't read input CSV-file: $upload1");
$rfile = file($upload2, FILE_IGNORE_NEW_LINES);
if (!$rfile)
    fatal("Couldn't read second input CSV-file");
$datacols = count( $comp_ext_rules[$tableId] );
unset($CSVs, $upload1, $upload2);
$l_cnt = count($lfile);
$r_cnt = count($rfile);
if (!$l_cnt || !$r_cnt)
    fatal("Empty input CSV-file(s) not allowed");
$VISAVIS_IDX = $datacols * 2 + FIRSTCOL_OUT;
$l_tab = $r_tab = $archs = array();

// Exclude absolute duplicates
for ($r_idx=0; $r_idx < $r_cnt; $r_idx++) {
    $search =& $rfile[$r_idx];
    for ($l_idx=0; $l_idx < $l_cnt; $l_idx++)
	if ($lfile[$l_idx] === $search) {
	    $lfile[$l_idx] = null;
	    $rfile[$r_idx] = null;
	    break;
	}
    unset($search);
}
for ($l_idx=0; $l_idx < $l_cnt; $l_idx++) {
    if ($lfile[$l_idx] === null)
	continue;
    $search =& $lfile[$l_idx];
    for ($r_idx=0; $r_idx < $r_cnt; $r_idx++) {
	if ($rfile[$r_idx] === null)
	    continue;
	if ($rfile[$r_idx] === $search) {
	    $rfile[$r_idx] = null;
	    $lfile[$l_idx] = null;
	    break;
	}
    }
    unset($search);
}

// Parse right CSV-records to the PHP array
for ($r_idx=0; $r_idx < $r_cnt; $r_idx++) {
    if ($rfile[$r_idx] === null)
	continue;
    $raw = csv2arr($rfile[$r_idx]);
    $archname = $raw[PLATFORM_IDX];
    if (!isset( $r_tab[$archname] )) {
	if (!in_array($archname, $archs, true))
	    $archs[] = $archname;
	$r_tab[$archname] = array();
    }
    $raw[PLATFORM_IDX]  = $r_idx + 1;
    $r_tab[$archname][] = $raw;
    unset($raw);
}
unset($rfile);

// Parse left CSV-records to the PHP array
for ($l_idx=0; $l_idx < $l_cnt; $l_idx++) {
    if ($lfile[$l_idx] === null)
	continue;
    $raw = csv2arr($lfile[$l_idx]);
    $archname = $raw[PLATFORM_IDX];
    if (!isset( $l_tab[$archname] )) {
	if (!in_array($archname, $archs, true))
	    $archs[] = $archname;
	$l_tab[$archname] = array();
    }
    $raw[PLATFORM_IDX]  = $l_idx + 1;
    $l_tab[$archname][] = $raw;
    unset($raw);
}
unset($lfile);

// Search vis-a-vis for the left CSV-records
foreach ($l_tab as $archname => &$l_records) {
    if (!isset( $r_tab[$archname] ))
	continue;
    $r_records =& $r_tab[$archname];
    $r_cnt = count($r_records);
    $l_cnt = count($l_records);

    if (!isset($ignore_category)) {
	$ignore_category = false;
	if (!$l_records[0][CATEGORY_OUT])
	    if ($r_records[0][CATEGORY_OUT])
		$ignore_category = true;
    }

    for ($l_idx=0; $l_idx < $l_cnt; $l_idx++) {
	$search =& $l_records[$l_idx];
	$last_similarity = 0;
	$last_visavis = null;

	for ($r_idx=0; $r_idx < $r_cnt; $r_idx++) {
	    if ($r_records[$r_idx][$VISAVIS_IDX] !== null)
		continue;
	    $similarity = get_similarity($search, $r_records[$r_idx]);
	    if ($similarity == MAX_SIMILARITY) {
		$last_similarity = $similarity;
		$last_visavis = $r_idx;
		break;
	    }
	    if ($similarity > $last_similarity) {
		$last_similarity = $similarity;
		$last_visavis = $r_idx;
	    }
	    unset($similarity);
	}

	if ($last_similarity >= MIN_SIMILARITY) {
	    if (($last_similarity != MAX_SIMILARITY) ||
		!is_equal($search, $r_records[$last_visavis]))
	    {
		$search[$VISAVIS_IDX] = $last_visavis;
		$r_records[$last_visavis][$VISAVIS_IDX] = $l_idx;
	    }
	    else {
		$search[$VISAVIS_IDX] = -1;
		$r_records[$last_visavis][$VISAVIS_IDX] = -1;
	    }
	}

	unset($search, $last_similarity, $last_visavis);
    }
    unset($l_records, $r_records);
}

// Search vis-a-vis for the right CSV-records
foreach ($r_tab as $archname => &$r_records) {
    if (!isset($l_tab[$archname]))
	continue;
    $l_records =& $l_tab[$archname];
    $l_cnt = count($l_records);
    $r_cnt = count($r_records);

    for ($r_idx=0; $r_idx < $r_cnt; $r_idx++) {
	if ($r_records[$r_idx][$VISAVIS_IDX] !== null)
	    continue;
	$search =& $r_records[$r_idx];
	$last_similarity = 0;
	$last_visavis = null;

	for ($l_idx=0; $l_idx < $l_cnt; $l_idx++) {
	    if ($l_records[$l_idx][$VISAVIS_IDX] !== null)
		continue;
	    $similarity = get_similarity($l_records[$l_idx], $search);
	    if ($similarity == MAX_SIMILARITY) {
		$last_similarity = $similarity;
		$last_visavis = $l_idx;
		break;
	    }
	    if ($similarity > $last_similarity) {
		$last_similarity = $similarity;
		$last_visavis = $l_idx;
	    }
	    unset($similarity);
	}

	if ($last_similarity >= MIN_SIMILARITY) {
	    if (($last_similarity != MAX_SIMILARITY) ||
		!is_equal($l_records[$last_visavis], $search))
	    {
		$search[$VISAVIS_IDX] = $last_visavis;
		$l_records[$last_visavis][$VISAVIS_IDX] = $r_idx;
	    }
	    else {
		$search[$VISAVIS_IDX] = -1;
		$l_records[$last_visavis][$VISAVIS_IDX] = -1;
	    }
	}

	unset($search, $last_similarity, $last_visavis);
    }
    unset($l_records, $r_records);
}

// Output results
foreach ($archs as $archname) {
    if (isset( $l_tab[$archname] )) {
	$l_records =& $l_tab[$archname];
	foreach ($l_records as &$row) {
	    $visavis = $row[$VISAVIS_IDX];
	    if ($visavis === -1)
		continue;
	    if ($visavis !== null)
		continue;
	    arr2csv("-", $archname, $row);
	}
	unset($l_records, $row);
    }

    if (isset( $r_tab[$archname] )) {
	if (isset( $l_tab[$archname] ))
	    $l_records =& $l_tab[$archname];
	else
	    $l_records = null;
	$r_records =& $r_tab[$archname];

	foreach ($r_records as &$row) {
	    $visavis = $row[$VISAVIS_IDX];
	    if ($visavis === -1)
		continue;
	    if ($visavis === null)
		continue;
	    arr2csv("<", $archname, $l_records[$visavis]);
	    arr2csv(">", $archname, $row);
	}

	foreach ($r_records as &$row) {
	    $visavis = $row[$VISAVIS_IDX];
	    if ($visavis === -1)
		continue;
	    if ($visavis !== null)
		continue;
	    arr2csv("+", $archname, $row);
	}

	unset($l_records, $r_records, $row);
    }
}

unset($l_tab, $r_tab);

?>