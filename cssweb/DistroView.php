<?php

// Authentication
$LIBDIR = @dirname(@realpath(__FILE__))."/lib";
require_once("$LIBDIR/web.php");
require_once("$LIBDIR/render.php");

// Form body
$dID = httpGetStr("DistroID");
if ($dID) {
    $filter_word = "дистрибутиву";
    $filter_args = array("d" => $dID);
    $dID = str_replace("/", ".", $dID);
    $filename = "Distros/$dID.yml";
}
if (!$dID || !file_exists("$DATADIR/$filename"))
    fatal("Where is valid DistroID?\n");
$data = loadYamlFile($filename, "distinfo", $model);
$data["dID"] = "/$filename";
if (isset($data["Download"]) && !isUrl($data["Download"]))
    $model["Fields"]["Download"]["Type"] = "OneLine";
fillOptionalFields($data, $model["Fields"]);
if (isset($data["Build"])) {
    if (preg_match("/^v\d+(\.\d+)?$/", $data["Build"]))
	$data["Build"] = substr($data["Build"], 1);
}
$title = $model["View"]["Title"];
$title = str_replace("{DISTRO}", $data["Name"], $title);
$title = htmlspecialchars($title);
echo makeHeader("InfoView", "{TITLE}", $title);
buildOutputForm($data, $model, false);
echo makeFooter("InfoView");

?>