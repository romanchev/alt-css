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
define("PLATFORMS",	0);
define("TABLE_ROWS",	1);
define("UNIQ_VENDORS",	2);
define("UNIQ_PRODUCTS",	3);
define("UNIQ_GROUPS",	4);
define("COMPAT_CELLS",	5);
define("TOTAL_CERTS",	6);
define("UNIQ_CERTS",	7);
define("UNIQ_ELINKS",	8);
define("REFERENCES",	9);
define("TOTAL_PDFS",   10);
define("UNIQ_PDFS",    11);
define("FOOTNOTES",    12);

$titles = array (
    "Platforms",
    "Table rows",
    "Uniq vendors",
    "Uniq products",
    "Uniq groups",
    "Compat cells",
    "Total certs",
    "Uniq certs",
    "Uniq E-Links",
    "References",
    "Total PDFs",
    "Uniq PDFs",
    "Footnotes"
);

function &block_stat($first, $n_rows) {
    global $csv, $noexpand, $datacols;

    $n_comps = 0;
    $n_certs = 0;
    $n_notes = 0;
    $n_refs  = 0;
    $n_pdfs  = 0;

    $u_vends = array();
    $u_prods = array();
    $u_certs = array();
    $u_links = array();
    $u_pdfs  = array();
    $u_grps  = array();

    for ($j=0; $j < $n_rows; $j++) {
	$row = &$csv[ $first + $j ];

	$v = sha1( $row[VENDNAME_IDX]." ".$row[VENDLINK_IDX] );
	$u_vends[$v] = true;
	$v = sha1( "$v ".$row[PRODNAME_IDX]." ".$row[PRODLINK_IDX] );
	$u_prods[$v] = true;
	if (strstr($row[VENDNAME_IDX], "[["))
	    $n_notes ++;
	if (strstr($row[PRODNAME_IDX], "[["))
	    $n_notes ++;
	if (strstr($row[MAJORVER_IDX], "[["))
	    $n_notes ++;
	if ($row[VENDLINK_IDX]) {
	    $v = sha1( $row[VENDLINK_IDX] );
	    $u_links[$v] = true;
	}
	if ($row[PRODLINK_IDX]) {
	    $v = sha1( $row[PRODLINK_IDX] );
	    $u_links[$v] = true;
	}
	if (isset($row[ COMP1COL_IDX + $datacols ])) {
	    $v = sha1($row[ COMP1COL_IDX + $datacols ]);
	    $u_grps[$v] = true;
	}

	if (($cell = $row[PRODINST_IDX]) != "") {
	    if (strstr($cell, "://") === false) {
		if (!isset( $u_pdfs[$cell] ))
		    $u_pdfs[$cell] = true;
		$n_pdfs ++;
	    }
	    else {
		$v = sha1($cell);
		$u_links[$v] = true;
		$n_refs ++;
	    }
	}

	for ($i=0; $i < $datacols; $i++) {
	    $cell = $row[ COMP1COL_IDX + $i ];
	    if (!$cell)
		; /* Nothing */
	    elseif (($cell == "+") || ($cell == "Совместимы"))
		$n_comps ++;
	    elseif ($cell == "#") {
		if (!$noexpand)
		    $n_comps ++;
	    }
	    elseif (substr($cell, 0, 1) == "+") {
		if (strstr($cell, "[["))
		    $n_notes ++;
		$n_comps ++;
	    }
	    elseif (substr($cell, 0, 1) == "#") {
		if (!$noexpand) {
		    if (strstr($cell, "[["))
			$n_notes ++;
		    $n_comps ++;
		}
	    }
	    else {
		if (strstr($cell, "[[") === false)
		    $v = $cell;
		else {
		    $v = substr($cell, 0, 11);
		    $n_notes ++;
		}
		$u_certs[$v] = true;
		$n_certs ++;
		$n_comps ++;
	    }
	}
	unset($row);
    }

    $stat = array (
	0,			/* PLATFORMS     */
	$n_rows,		/* TABLE_ROWS    */
	count($u_vends),	/* UNIQ_VENDORS  */
	count($u_prods),	/* UNIQ_PRODUCTS */
	count($u_grps),		/* UNIQ_GROUPS   */
	$n_comps,		/* COMPAT_CELLS  */
	$n_certs,		/* TOTAL_CERTS   */
	count($u_certs),	/* UNIQ_CERTS    */
	count($u_links),	/* UNIQ_ELINKS   */
	$n_refs,		/* REFERENCES    */
	$n_pdfs,		/* TOTAL_PDFS    */
	count($u_pdfs),		/* UNIQ_PDFS     */
	$n_notes		/* FOOTNOTES     */
    );

    unset($u_vends, $u_prods, $u_certs, $u_links, $u_pdfs, $u_grps);
    unset($n_comps, $n_certs, $n_notes, $n_refs, $n_pdfs);

    return $stat;
}


// Entry point
if (($argc < 3) || !isset($comp_ext_rules[ $argv[1] ]))
    fatal("Usage: tabstat <tableId> <uploadId> [--noexpand]");
$noexpand  = ($argc == 4) && ($argv[3] == "--noexpand");
$tableId   = $argv[1];
$uploadId  = $argv[2];
$input     = "$CACHEDIR/$tableId-$uploadId.csv";
if (!file_exists($input)) {
    $input = "History/$tableId/$uploadId.csv";
    if (!file_exists("$DATADIR/$input"))
	fatal("Input CSV-file not found: /$input");
    $input = "$DATADIR/$input";
}
$IN = file($input);
if (!$IN)
    fatal("Couldn't read input CSV-file");
$datacols = count($comp_ext_rules[$tableId]);
$csv = $p = array();
$f = $n = 0;
$l = "";

foreach ($IN as &$row) {
    $cells = explode("|", rtrim($row));
    foreach ($cells as &$item)
	$item = substr($item, 1, -1);
    if ($l != $cells[0]) {
	if (count($p) > 0)
	    $p[ count($p)-1 ][2] = $n;
	$p[] = array($l = $cells[0], $f, $n = 0);
    }
    $f ++;
    $n ++;
    $csv[] = &$cells;
    unset($cells, $item, $row);
}

if (!count($p))
    fatal("Empty input CSV-file: $input");
$p[ count($p)-1 ][2] = $n;
$OUT = array();
unset($IN);

for ($j=0; $j < count($titles); $j++) {
    $OUT[$j] = array( '"'.$titles[$j].'"' );
    for ($i=0; $i < count($p); $i++)
	$OUT[$j][] = 0;
}

$l = block_stat(0, $f);
$l[PLATFORMS] = count($p);
for ($j=0; $j < count($titles); $j++)
    $OUT[$j][1] = $l[$j];
$i = 2;
foreach ($p as &$row) {
    $l = block_stat($row[1], $row[2]);
    $l[PLATFORMS] = '"'.$row[0].'"';
    for ($j=0; $j < count($titles); $j++)
	$OUT[$j][$i] = $l[$j];
    $i ++;
    unset($row);
}

foreach ($OUT as &$l)
    echo implode("|", $l)."\n";
unset($OUT, $l, $p);

?>