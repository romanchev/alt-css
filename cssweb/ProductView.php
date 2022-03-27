<?php

// Authentication
$LIBDIR = @dirname(@realpath(__FILE__))."/lib";
require_once("$LIBDIR/web.php");
require_once("$LIBDIR/render.php");

$SUITES = array (
    "Universal" => "Универсальный продукт",
    "Desktop"   => "Только для настольных ОС",
    "Server"    => "Только для серверных ОС",
    "NoExpand"  => "Ограниченного применения"
);

// Form body
$vID = httpGetId("VendorID");
$pID = httpGetId("ProductID");
$vendfile = "Vendors/$vID/vendor.yml";
$filename = "Vendors/$vID/$pID/product.yml";
if (!$vID || !$pID ||
    !file_exists("$DATADIR/$vendfile") ||
    !file_exists("$DATADIR/$filename"))
{
    fatal("Where is valid VendorID and/or ProductID?\n");
}
$vend = loadYamlFile($vendfile, "vendor");
$data = loadYamlFile($filename, "product", $model);
$data["Path"] = "/$filename";
if (!isset($data["Suitable"]))
    $data["Suitable"] = category2suitable($data["Category"]);
if (isset( $SUITES[$data["Suitable"]] ))
    $data["Suitable"] = $SUITES[$data["Suitable"]];
if (!isset($data["Name"]))
    $data["Name"] = str_replace("_", " ", $pID);
$data["Category"] = str_replace("_", " ", $data["Category"]);
$vname = htmlId($vend, $vID);
$pname = htmlId($data, $pID);
if (isset($data["Hidden"]))
    $data["Hidden"] = "Да";
unset($vendfile, $vend);
$data["Vendor"] = "$vID:$vname";
$data["ActualVers"] = rebuildVersions($data, "$vID/$pID/VERS");
fillOptionalFields($data, $model["Fields"]);
$title = $model["View"]["Title"];
$title = str_replace("{PRODUCT}", $pname, $title);
$title = htmlspecialchars($title);
$logobase = "$vID/$pID/product";
$filter_word = "продукту";
$filter_args = array("v"=>$vID, "p"=>$pID);
echo makeHeader("InfoView", "{TITLE}", $title);
buildOutputForm($data, $model, false);
echo makeFooter("InfoView");
unset($logobase, $filter_word, $filter_args);
unset($title, $vname, $pname, $model, $data);

?>