#!/usr/bin/php
<?php

// Bootstrap
$BINDIR = @dirname(@realpath(__FILE__));
$LIBDIR = @dirname($BINDIR)."/lib";
$ADMIN  = "$LIBDIR/admin";
$CHECK  = "$ADMIN/check";
$INDEX  = "$ADMIN/index";
require_once("$ADMIN/cli.php");
require_once("$ADMIN/stat.php");
require_once("$ADMIN/save.php");
require_once("$CHECK/common.php");
require_once("$CHECK/gitlog.php");
require_once("$CHECK/datadir.php");
require_once("$CHECK/platforms.php");
require_once("$CHECK/distros.php");
require_once("$CHECK/install.php");
require_once("$CHECK/categories.php");
require_once("$CHECK/templates.php");
require_once("$CHECK/manuals.php");
require_once("$CHECK/vendors.php");
require_once("$CHECK/products.php");
require_once("$CHECK/legal.php");
require_once("$CHECK/certs.php");
require_once("$INDEX/common.php");
require_once("$INDEX/categories.php");
require_once("$INDEX/platforms.php");
require_once("$INDEX/distros.php");
require_once("$INDEX/gitlog.php");
require_once("$INDEX/recent.php");
require_once("$INDEX/vendor.php");
require_once("$INDEX/product.php");
require_once("$INDEX/version.php");
require_once("$INDEX/majorver.php");
require_once("$INDEX/compinfo.php");
require_once("$INDEX/vendor_r.php");
require_once("$INDEX/vendtab.php");
require_once("$INDEX/prodtab.php");
require_once("$INDEX/comptab.php");
unset($ADMIN, $CHECK, $INDEX);

$ENTITES = array (
    "Categories",
    "Certs",
    "Distros",
    "Documents",
    "Install",
    "History",
    "Legal",
    "Manuals",
    "Platforms",
    "Sources",
    "Templates",
    "Vendors"
);


// Entry point
@clearstatcache();
@set_time_limit(0);
$FORCE_MODE = (($argc > 1) && in_array("--force", $argv, true));
$IGNORE_MTIME = (($argc > 1) && in_array("--ignore-mtime", $argv, true));
$q_index = array();
$pwd = getcwd();
chdir($DATADIR);
reset_cache();

// Check CSI
check_gitlog();
check_datadir();
check_platforms();
check_distros();
check_install();
check_categories();
check_templates();
check_documents();
check_manuals();
check_vendors();
check_products();
check_certs();
check_legal();

// Save and output results
$finalmsg = " error(s) encounted, wich must be fixed first.";
if ($statinfo["errors"] > 0)
    fatal(strval( $statinfo["errors"] ).$finalmsg);

// Build next cache
reindex_categories();
reindex_platforms();
reindex_distros();
reindex_gitlog();
reindex_recent();
foreach ($vendids as $dummy)
    update_vendor_cache_r($dummy);
$statinfo["vendors"] = reindex_vendors_table();
$statinfo["products"] = reindex_products_table();
arr2cache("manuals", $install);
arr2cache("abc", $q_index);
foreach ($comp_ext_rules as $table => $dummy)
    reindex_compatibility($table);
$success = $_SERVER['HOME']."/SUCCESS";

// Create new CSV-files
if ($FORCE_MODE && !$statinfo["errors"]) {
    if ($statinfo["warnings"] > 0)
	echo "\n";
    $statinfo["updated"] = time();
    $upload = date("Ymd-His", $statinfo["updated"]);
    file_put_contents($success, $upload);

    // Create internal CSV-file for each table
    echo "Creating the Pivot Compatibity Tables:\n";
    $prevcsv = $tmpcsv = $statcsv = $diffcsv = "";
    foreach ($comp_ext_rules as $table => $dummy) {
	$prevcsv = trim(`ls -1 "History/$table/" |tail -n1`);
	$tmpcsv  = "$CACHEDIR/$table-$upload.csv";
	echo  "  - $table/$upload.csv ...\n";
	`"$BINDIR/mkcsv.php" "$table" >"$tmpcsv"`;
	if (!file_exists($success)) {
	    errx("Couldn't create internal CSV-file for $table");
	    @unlink($tmpcsv);
	    break;
	}
	elseif (!@filesize($tmpcsv))
	    @unlink($tmpcsv);
	else {
	    // Create public version of the table upload
	    `"$BINDIR/pubcsv.php" "$table" "$upload"`;
	    if (!file_exists($success)) {
		errx("Couldn't create public CSV-file for $table");
		@unlink($tmpcsv);
		break;
	    }

	    // Create statistics for each table
	    $statcsv = "$CACHEDIR/$table-stat.csv";
	    `"$BINDIR/tabstat.php" "$table" "$upload" >"$statcsv"`;
	    if (!file_exists($success)) {
		errx("CSV statistics failed for new $table");
		@unlink($statcsv);
		@unlink($tmpcsv);
		break;
	    }
	    if ($prevcsv) {
		$prevcsv = basename($prevcsv, ".csv");
		$dummy   = "$CACHEDIR/$table-prev.csv";
		`"$BINDIR/tabstat.php" "$table" "$prevcsv" >"$dummy"`;
		if (!file_exists($success)) {
		    errx("CSV statistics failed for previous $table");
		    @unlink($statcsv);
		    @unlink($tmpcsv);
		    @unlink($dummy);
		    break;
		}

		// Create the upload's differences
		$diffcsv = "$CACHEDIR/$table-diff.csv";
		`"$BINDIR/tabdiff.php" "$table" "$prevcsv" "$upload" >"$diffcsv"`;
		if (!file_exists($success)) {
		    errx("Make the differences failed for $table");
		    @unlink($diffcsv);
		    @unlink($statcsv);
		    @unlink($tmpcsv);
		    @unlink($dummy);
		    break;
		}
		elseif (!@filesize($diffcsv)) {
		    @unlink($diffcsv);
		    @unlink($statcsv);
		    @unlink($dummy);
		    continue;
		}
	    }

	    // Add unique internal CSV-file to the git tree
	    $dummy = "History/$table/$upload.csv";
	    @copy($tmpcsv, $dummy);
	    $dummy = `git add "$dummy" 2>&1`;
	}
    }

    if (!file_exists($success))
	$statinfo["updated"] = 0;
    else {
	// Create the main empty upload file
	$dummy = "History/uploads/$upload";
	file_put_contents($dummy, "");
	`git add "$dummy"`;
    }
    unset($prevcsv, $tmpcsv, $statcsv, $diffcsv, $upload);
}
unset($table, $dummy);

// Save and output results
if ($statinfo["errors"] > 0)
    fatal(strval( $statinfo["errors"] ).$finalmsg);
if (!$FORCE_MODE) {
    file_put_contents($success, "");
    $statinfo["updated"] = time();
}
show_statinfo();
unset($statinfo["errors"]);
unset($statinfo["warnings"]);
arr2cache("statinfo", $statinfo);
$finalmsg = "Program finished successfully!";
if ($NC)
    echo "\n{$finalmsg}\n";
else
    echo "\n\033[01;32m{$finalmsg}\033[00m\n";
chdir($pwd);

?>