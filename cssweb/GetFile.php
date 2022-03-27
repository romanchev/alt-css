<?php

$LIBDIR = @dirname(@realpath(__FILE__))."/lib";
require_once("$LIBDIR/web.php");

if (!isset($_GET["fpath"]))
    fatal("fpath is required!");
if (!file_exists($file = $DATADIR."/".$_GET["fpath"]))
    fatal("File not found: /".$_GET["fpath"]);
if (headers_sent())
    fatal("Headers already sent, can't force download!");
$ext = pathinfo($file, PATHINFO_EXTENSION);

switch ($ext) {
    case "csv":
	$type = "text/csv";
	break;
    case "jpg":
	$type = "image/jpeg";
	break;
    case "pdf":
	$type = "application/pdf";
	break;
    default:
	$type = "application/octet-stream";
	break;
}

$fp = fopen($file, "rb");
header("Pragma: no-cache");
header("Cache-Control: no-cache, must-revalidate");
header("Content-Disposition: inline; filename=\"".
		@basename($_GET["fpath"])."\"");
header("Content-Type: $type");
header("Content-Length: ".filesize($file));
header("Content-Transfer-Encoding: binary");
fpassthru($fp);
fclose($fp);

?>