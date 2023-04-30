<?php

// Check point for static
if (php_sapi_name() != "cli")
    exit();

// Constants
define("GROUP_BY_PRODUCTS", 0);
define("GROUP_BY_VENDORS",  1);
define("GROUP_BY_GROUPS",   2);
define("MAKE_JAVASCRIPT",  -1);

// Bootstrap
$LIBDIR = @dirname(@realpath(__FILE__))."/lib";
$NTMPSDIR = @dirname(@realpath(__FILE__))."/ntmps";
require_once("$LIBDIR/admin/cli.php");
require_once("$LIBDIR/admin/fatal.php");

function writeMenuBlock($resp, $filename, $regexp) {
    preg_match_all($regexp, $resp, $matches);
    $menu = $matches[0][0];
    $s = strlen($menu);
    if ($s <= 5000)
	fatal("Content is too small. It seems page is wrong. Not updated!");
    $s = file_put_contents($filename, $menu);
}

// Parse arguments
$platform = httpGetStr("t");
if (!$platform || !is_dir($TDIR = "$DATADIR/History/$platform"))
    fatal("Invalid or undefined TableID!");
$upload = httpGetStr("u");
if (!$upload)
    $upload = trim(`ls -1 "$DATADIR/History/uploads/" |tail -n1`);
if ($upload)
    $upload = basename($upload, ".csv");
if (!$upload || !file_exists($input_file = "$TDIR/$upload.csv")) {
    if (!file_exists($input_file = "$CACHEDIR/$platform-$upload.csv"))
	fatal("Input CSV-data not found!");
}
if (!$upload)
    $upload = basename($input_file, ".csv");
$group_by = httpGetInt("v");
if (($group_by < GROUP_BY_PRODUCTS) || ($group_by > GROUP_BY_GROUPS))
    $group_by = MAKE_JAVASCRIPT;
unset($TDIR);

// Entry point
$pubdate = htmlspecialchars(
		substr($upload, 6, 2).".".
		substr($upload, 4, 2).".".
		substr($upload, 0, 4));
if ($group_by == GROUP_BY_GROUPS)
    $fcol = "Категория, продукт, производитель";
else
    $fcol = "Производитель, продукт";
$categoryClass = $productClass = $vendorClass = "";

// Download and parse menu blocks only once
if (!file_exists("$CACHEDIR/P-html-menu.php") ||
    !file_exists("$CACHEDIR/P-html-menu-mobile.php"))
{
    $url = "$CACHEDIR/design.html";
    $res = file_get_contents($url);
    $url = "$CACHEDIR/P-html-menu.php";
    if (!file_exists($url))
	writeMenuBlock($res, $url, '/<ul id="main_menu">(.*)<\/ul>/');
    $url = "$CACHEDIR/P-html-menu-mobile.php";
    if (!file_exists($url))
	writeMenuBlock($res, $url, '/<div id="mobile_menu">(.*?)\n<\/div>/s');
    unset($url, $res);
}
if (!file_exists("$CACHEDIR/P-html-menu.php"))
    copy("$NTMPSDIR/P-html-menu.php", "$CACHEDIR/P-html-menu.php");
if (!file_exists("$CACHEDIR/P-html-menu-mobile.php"))
    copy("$NTMPSDIR/P-html-menu-mobile.php", "$CACHEDIR/P-html-menu-mobile.php");
$view = "javascript";

// Include external supplimental code
require_once("$NTMPSDIR/P-make-lib.php");

// Process through main template
require_once("$NTMPSDIR/P.php");

?>