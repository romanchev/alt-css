<?php

// Minimal bootstrap
$LIBDIR = @dirname(@dirname(@realpath(__FILE__)));
require_once("$LIBDIR/admin/web.php");
phpinfo();

?>