#!/usr/bin/php
<?php

// Bootstrap
$LIBDIR = @dirname(@dirname(@realpath(__FILE__)))."/lib";
require_once("$LIBDIR/admin/cli.php");


function output_product() {
    global $vendors, $products, $platforms, $category;
    global $last_ci, $docref, $cells, $notes, $expand;
    global $rules, $avail, $e_row;

    $ci   = &$last_ci;
    $vend = &$vendors[ $ci[CTAB_VendIDX] ];
    $prod = &$products[ $ci[CTAB_ProdIDX] ];
    $arch = $platforms[ $ci[CTAB_ArchIDX] ];

    // Expand compatibility to the near cells
    for ($i=0; $i < count($cells); $i++) {
	$v = $cells[$i];
	if (($v === "") || ($v === "#"))
	    continue;
	$p = $expand[$i];

	if (($p == SUITES_UNIVERSAL) || ($p == SUITES_DESKTOP)) {
	    $a = $rules[$i][0];
	    if ($a) {
		foreach ($a as $k) {
		    if (isset( $avail[$k][$arch] ))
			if ($cells[$k] === "")
			    $cells[$k] = "#";
		}
		unset($k);
	    }
	    unset($a);
	}

	if (($p == SUITES_UNIVERSAL) || ($p == SUITES_SERVER)) {
	    $a = $rules[$i][1];
	    if ($a) {
		foreach ($a as $k) {
		    if (isset( $avail[$k][$arch] ))
			if ($cells[$k] === "")
			    $cells[$k] = "#";
		}
		unset($k);
	    }
	    unset($a);
	}
    }

    // Vendor, product and version
    $v = $vend[VEND_NameIDX];
    if (isset( $vend[VEND_NoteIDX] ))
	$v .= "[[" . $vend[VEND_NoteIDX] . "]]";
    $p = $prod[PROD_NameIDX];
    if (isset( $prod[PROD_NoteIDX] ))
	$p .= "[[" . $prod[PROD_NoteIDX] . "]]";
    $i = $ci[CTAB_VersIDX];
    if ($i && preg_match("/^v\d+(\.\d+)?$/", $i))
	$i = substr($i, 1);

    // Output single row
    $row = array (
	$arch,
	$v,
	$vend[VEND_PageIDX],
	$p,
	$prod[PROD_PageIDX],
	$i,
	($docref == "0:none") ? "": substr($docref, 2)
    );
    for ($i=0; $i < count($cells); $i++) {
	$v = $cells[$i];
	if ($v === -1)
	    $v = "+";
	if ($v && $notes[$i])
	    $v .= "[[" . $notes[$i] . "]]";
	$row[] = $v;
    }
    $row[] = $category[ $prod[PROD_CatgIDX] ][FCAT_FullIDX];
    $to = array("\\x22", "\\x7c", "\\n");
    $from = array('"', "|", "\n");
    foreach ($row as &$v)
	$v = '"'.str_replace($from, $to,  $v).'"';
    echo implode("|", $row)."\n";

    // Prepare next row
    $cells = $notes = $expand = $e_row;
    $docref = "";
}


// Entry point
$pwd = getcwd();
chdir($DATADIR);
if (($argc != 2) || !isset($comp_ext_rules[ $argv[1] ]))
    exit("Usage: {$argv[0]} <tableId>\n");
$tableId = $argv[1];
$e_row = array();
$rules = array();
$avail = array();

// Reindex compatibility extension rules
$comp_ext_rules = $comp_ext_rules[ $tableId ];
$columns = array_flip(array_keys( $comp_ext_rules ));
foreach ($comp_ext_rules as $col => $record) {
    $record = explode(":", $record, 2);
    if ($record[0]) {	// Desktop suites
	$record[0] = explode(",", $record[0]);
	foreach ($record[0] as &$fld) {
	    if ($fld)
		$fld = $columns[$fld];
	}
	unset($fld);
    }
    if ($record[1]) {	// Server suites
	$record[1] = explode(",", $record[1]);
	foreach ($record[1] as &$fld) {
	    if ($fld)
		$fld = $columns[$fld];
	}
	unset($fld);
    }
    $e_row[] = "";
    $rules[] = $record;
}

// Load hardware platform names
$avail_platforms = $avail_platforms[ $tableId ];
foreach ($avail_platforms as &$record)
    $avail[] = array_flip(explode(",", $record));
unset($col, $record, $columns, $comp_ext_rules, $avail_platforms);
$platforms = array_keys($hw_platforms);

// Load input data
$category  = cache2arr("catfull");
$vendors   = cache2arr("vendors");
$products  = cache2arr("products");
$compinfo  = cache2arr("comptab-".$tableId);

// Make the Pivot Compatibility Table
//
$docref    = "";
$last_vers = "";
$last_prod = -1;
$last_arch = -1;
$last_ci   = null;
$cells     = $e_row;
$notes     = $e_row;
$expand    = $e_row;
//
foreach ($compinfo as &$ci) {
    $prod_idx = $ci[CTAB_ProdIDX];
    if (($prod_idx != $last_prod) ||
	($ci[CTAB_VersIDX] != $last_vers) ||
	($ci[CTAB_ArchIDX] != $last_arch))
    {
	if ($last_prod >= 0)
	    output_product();
	if ($ci[CTAB_ArchIDX] != $last_arch)
	    $arch = $platforms[ $ci[CTAB_ArchIDX] ];
	$docref = $products[$prod_idx][PROD_InstIDX];
	if (!$docref)
	    $docref = "0:none";
	$last_prod = $prod_idx;
	$last_vers = $ci[CTAB_VersIDX];
    }
    unset($prod_idx);

    if ($ci[CTAB_InstIDX] && (ord($docref) < ord( $ci[CTAB_InstIDX] )))
	$docref = $ci[CTAB_InstIDX];
    if (isset( $ci[CTAB_NoteIDX] ))
	$notes[ $ci[CTAB_DcolIDX] ] = $ci[CTAB_NoteIDX];
    $expand[ $ci[CTAB_DcolIDX] ] = $ci[CTAB_SuitIDX];
    $cells[ $ci[CTAB_DcolIDX] ] = $ci[CTAB_CertIDX];
    $last_arch = $ci[CTAB_ArchIDX];
    $last_ci = &$ci;
}

if ($last_prod >= 0)
    output_product();
unset($compinfo, $vendors, $products, $platforms);
unset($category, $rules, $avail, $ci, $last_ci);
chdir($pwd);

?>