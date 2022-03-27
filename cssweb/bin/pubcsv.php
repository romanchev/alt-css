#!/usr/bin/php
<?php

// Bootstrap
$LIBDIR = @dirname(@dirname(@realpath(__FILE__)))."/lib";
require_once("$LIBDIR/admin/cli.php");
require_once("$LIBDIR/admin/fatal.php");

define("COMP1COL_IDX", 7);

// Entry point
if (($argc < 3) || !isset($comp_ext_rules[ $argv[1] ]))
    fatal("Usage: pubcsv <tableId> <uploadId> [--noexpand]");
$noexpand  = ($argc == 4) && ($argv[3] == "--noexpand");
$tableId   = $argv[1];
$uploadId  = $argv[2];
$input     = "$CACHEDIR/$tableId-$uploadId.csv";
$output    = "$CACHEDIR/$tableId.csv";
if (!file_exists($input)) {
    $input = "History/$tableId/$uploadId.csv";
    if (!file_exists("$DATADIR/$input"))
	fatal("Input CSV-file not found: /$input");
    $input = "$DATADIR/$input";
}
$datacols  = count(array_keys( $comp_ext_rules[$tableId] ));
$fp = @fopen($output, "w");
if (!$fp)
    fatal("Couldn't create output CSV-file: $tableId.csv");
$IN = file($input, FILE_IGNORE_NEW_LINES);
if (!$IN)
    fatal("Couldn't read input CSV-file");
foreach ($IN as &$row) {
    $cells = explode("|", $row);
    for ($i=0; $i < $datacols; $i++) {
	$item = &$cells[COMP1COL_IDX+$i];
	$item = substr($item, 1, -1);
	if ($item == '+')
	    $item = "Совместимы";
	elseif ($item == '#')
	    $item = ($noexpand ? "": "Совместимы");
	elseif (substr($item, 0, 1) == '+')
	    $item = "Совместимы".substr($item, 1);
	elseif (substr($item, 0, 1) == '#')
	    $item = ($noexpand ? "": "Совместимы".substr($item, 1));
	$item = '"'.$item.'"';
	unset($item);
    }
    fwrite($fp, implode("|", $cells)."\n");
}
fclose($fp);

?>