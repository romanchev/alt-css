<?php

$STATKEYS = array (
    "updated"     => "Last update",
    "CSI-commits" => "CSI commits",
    "compinfo"    => "CI records",
    "unicerts"    => "Certificates",
    "install"     => "Links to PDF", /* Links which found in the /Install. */
    "manuals"     => "Instructions", /* PDF-files which found in the /Vendors. */
    "Drafts"      => null,
    "Vendors"     => null,
    "Products"    => null,
    "Versions"    => null,
    "majorver"    => " - major",
    "releases"    => " - builds",
    "Categories"  => null,
    "softgroups"  => " - software",
    "hardgroups"  => " - hardware",
    "Distros"     => null,
    "Platforms"   => null,
    "Documents"   => null,
    "Templates"   => null,
    "Warnings"    => null,
    "Errors"      => null
);

$NC = false;


function reset_cache() {
    global $STATKEYS, $CACHEDIR, $statinfo;

    $statinfo = array();
    foreach ($STATKEYS as $key => $caption) {
	if ($caption === null)
	    $key = strtolower($key);
	$statinfo[$key] = 0;
    }
    if (is_dir($CACHEDIR))
	$ignore = `rm -rf -- "$CACHEDIR"`;
    mkdir($CACHEDIR, 0755, true);
    $statinfo["updated"] = time();
}

function errx($msg) {
    global $NC, $statinfo;

    fwrite(STDERR, $NC ? "E: $msg\n": "\033[01;31mE: {$msg}\033[00m\n");
    $statinfo["errors"] ++;
}

function warnx($msg) {
    global $NC, $statinfo;

    fwrite(STDERR, $NC ? "W: $msg\n": "\033[01;33mW: {$msg}\033[00m\n");
    $statinfo["warnings"] ++;
}

function show_statinfo() {
    global $NC, $STATKEYS, $statinfo, $dateFormat;

    if ($NC)
	$bold = $norm = "";
    else {
	$bold = "\033[01;37m";
	$norm = "\033[00m";
    }
    echo "\n";

    foreach ($STATKEYS as $key => $caption) {
	if ($caption === null)
	    $key = strtolower($caption = $key);
	$s1 = str_pad("$caption:", 14);
	$s3 = $statinfo[$key];
	if (($key != "updated") || !$s3)
	    printf("%s%s%d%s\n", $s1, $bold, $s3, $norm);
	else {
	    $s3 = date("$dateFormat H:i:s", $s3);
	    printf("%s%s%s%s\n", $s1, $bold, $s3, $norm);
	}
    }
}

function fatal($msg) {
    global $CACHEDIR, $NC, $pwd;

    show_statinfo();

    if ($NC)
	echo "\n{$msg}\n";
    else
	echo "\n\033[01;31m{$msg}\033[00m\n";
    $msg = "Abnormal program termination, cache not updated!";
    if ($NC)
	echo "{$msg}\n";
    else
	echo "\033[01;31m{$msg}\033[00m\n";
    chdir($pwd);
    exit;
}

function arr2cache($filename, &$data) {
    global $CACHEDIR;

    $path = ".$filename.php.tmp";
    $fp = fopen("$CACHEDIR/$path", "w");
    if (!$fp)
	fatal("Can't create file: $path");
    fwrite($fp, "<"."?php\n\nreturn ");
    fwrite($fp, var_export($data, true));
    fwrite($fp, ";\n\n?".">");
    fclose($fp);
    rename("$CACHEDIR/$path", "$CACHEDIR/$filename.php");
}

?>