<?php

if (isset($LIBDIR) && !isset($CFGDIR))
    $CFGDIR = dirname($LIBDIR)."/etc";
$FATAL = "<h1>404 Not found</h1>\n";
$CACHEDIR = $DATADIR = $USERS = "";

if ((php_sapi_name() != "cli") ||
    !isset($LIBDIR) || !isset($CFGDIR) ||
    !file_exists("/etc/css/engine.php") ||
    !file_exists("$CFGDIR/config.php") ||
    !file_exists("$LIBDIR/common.php"))
{
    exit($FATAL);
}

require_once("/etc/css/engine.php");
require_once("$CFGDIR/config.php");

if (!$USERS || !$DATADIR ||
    !is_dir("$DATADIR/.git") ||
    !isset($_SERVER['HOME']) ||
    !isset($_SERVER['CSS_USER']))
{
    exit($FATAL);
}

$editor = true;
$auth   = $_SERVER['CSS_USER'];
if (isset($_SERVER['CSS_CACHEDIR']) &&
    (isset($_SERVER['CSS_DATADIR'])))
{
    $CACHEDIR = $_SERVER['CSS_CACHEDIR'];
    $DATADIR  = $_SERVER['CSS_DATADIR'];
}
else {
    $CACHEDIR = "$USERS/$auth/CSI-cache.next";
    $DATADIR  = $_SERVER['HOME']."/CSI-data";
}
if (!is_dir("$DATADIR/.git"))
    exit($FATAL);
unset($FATAL, $USERS);

require_once("$LIBDIR/common.php");

?>