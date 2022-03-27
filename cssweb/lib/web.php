<?php

if (isset($LIBDIR) && !isset($CFGDIR))
    $CFGDIR = dirname($LIBDIR)."/etc";
$FATAL = "<h1>404 Not found</h1>\n";
$CACHEDIR = $DATADIR = $USERS = "";

if ((php_sapi_name() == "cli") ||
    !isset($LIBDIR) || !isset($CFGDIR) ||
    !file_exists("/etc/css/engine.php") ||
    !file_exists("$CFGDIR/config.php") ||
    !file_exists("$LIBDIR/common.php"))
{
    exit($FATAL);
}

$COMMON_LOGIN = $COMMON_PWHASH = "";
require_once("/etc/css/engine.php");
require_once("$CFGDIR/config.php");
if (!$USERS || !$DATADIR || !$CACHEDIR || !is_dir("$DATADIR/.git"))
    exit($FATAL);
$auth = $editor = false;
require_once("$LIBDIR/auth.php");
unset($COMMON_LOGIN, $COMMON_PWHASH);

if ($editor && isset($_COOKIE['EDITMODE'])) {
    $CACHEDIR = "$USERS/$auth/CSI-cache";
    $DATADIR = "$USERS/$auth/CSI-data";
}
if (!is_dir("$DATADIR/.git") || !file_exists("$CACHEDIR/statinfo.php"))
    exit($FATAL);
unset($FATAL, $USERS);

function fatal($msg) {
    exit("FATAL: $msg\n");
}

require_once("$LIBDIR/common.php");

?>