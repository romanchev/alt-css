<?php

// Authentication
define("EDITMODE_REQUIRED", true);
$LIBDIR = @dirname(@realpath(__FILE__))."/lib";
require_once("$LIBDIR/web.php");

if ($editor) {
    if (httpGetInt("overlay"))
	@setcookie("EDITMODE", "1");
    else
	@setcookie("EDITMODE", "", time()-3600);
}

@header("Location: /");

?>