<?php

// Authentication
$LIBDIR = @dirname(@realpath(__FILE__))."/lib";
require_once("$LIBDIR/web.php");
require_once("$LIBDIR/render.php");

// Form body
$vID = httpGetId("VendorID");
$filename = "Vendors/$vID/vendor.yml";
if (!$vID || !file_exists("$DATADIR/$filename"))
    fatal("Where is valid VendorID?\n");
$data = loadYamlFile($filename, "vendor", $model);
$basepath = @dirname($filename);
$data["Path"] = "/$filename";
if (!isset($data["Name"]))
    $data["Name"] = str_replace("_", " ", $vID);
fillOptionalFields($data, $model["Fields"]);
if ($data["Extern"])
    $data["Extern"] = "Их";
else {
    $data["Extern"] = "Наша";
    unset($model["Fields"]["Extern"]["Bold"]);
}
$vname = htmlId($data, $vID);
$title = $model["View"]["Title"];
$title = str_replace("{VENDOR}", $vname, $title);
$title = htmlspecialchars($title);
$logobase = $vID."/vendor";
$filter_word = "партнёру";
$filter_args = array("v" => $vID);
echo makeHeader("InfoView", "{TITLE}", $title);
buildOutputForm($data, $model, false);
unset($logobase, $filter_word, $filter_args);
unset($title, $vname, $model);
$alt = false;

// Products list
if (($dh = opendir("$DATADIR/$basepath")) !== false) {
    echo grpRow("ПРОДУКТЫ");
    while (($entry = readdir($dh)) !== false) {
	if (!is_dir("$DATADIR/$basepath/$entry"))
	    continue;
	if (in_array($entry, array(".", "..", ".DRAFTS", ".INSTALL")))
	    continue;
	$filename = "$basepath/$entry/product.yml";
	if (!file_exists("$DATADIR/$filename"))
	    continue;
	$prod  = loadYamlFile($filename, "product");
	$pname = htmlId($prod, $entry);
	$href  = "ProductView.php?VendorID=".
		    htmlentities(urlencode($vID).
		    "&ProductID=".urlencode($entry));
	$output = "<a href=\"$href\" title=\"Перейти к ".
		    "карточке продукта...\">$pname</a>";
	echo infoRow($alt, $empty, $output);
	unset($prod, $pname, $href, $output);
	$alt = !$alt;
    }
    closedir($dh);
}
unset($dh);
$alt = true;

// Contacts list
$filename = "$basepath/contacts.yml";
if (file_exists("$DATADIR/$filename")) {
    $data = loadYamlFile($filename, "contact", $model, true);
    $model["Caption"] = "КОНТАКТЫ";
    foreach ($data as &$cont) {
	buildOutputForm($cont, $model, $alt);
	$alt = null;
    }
    unset($data, $model, $cont);
}

// Events list
$filename = "$basepath/events.yml";
if (file_exists("$DATADIR/$filename")) {
    $list = read_yaml($DATADIR, $filename);
    echo grpRow("СОБЫТИЯ");
    $alt = false;
    foreach ($list as &$event) {
	if (!is_string($event))
	    continue;
	list($date, $text) = explode(" ", trim($event), 2);
	$date = htmlspecialchars($date);
	$text = htmlspecialchars($text);
	echo infoRow($alt, "<b>$date</b>", $text);
	unset($date, $text);
	$alt = !$alt;
    }
    unset($list, $event);
}

// Finish and cleanup
echo makeFooter("InfoView");
unset($vID, $filename, $alt);

?>