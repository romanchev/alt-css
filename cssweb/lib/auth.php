<?php

if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
    $login  = $_SERVER['PHP_AUTH_USER'];
    $passwd = $_SERVER['PHP_AUTH_PW'];
    $pwhash = "";

    if (!defined('EDITMODE_REQUIRED') && ($login === $COMMON_LOGIN)) {
	if (($COMMON_LOGIN != "") && ($COMMON_PWHASH != "")) {
	    $pwhash = $COMMON_PWHASH;
	    $auth = $COMMON_LOGIN;
	}
    }

    if (@file_exists("$CFGDIR/users.php")) {
	$authusers = include("$CFGDIR/users.php");
	if (is_array($authusers) && isset($authusers[$login])) {
	    if ($login !== $COMMON_LOGIN)
		$editor = $authusers[$login][0];
	    $pwhash = $authusers[$login][1];
	    $auth = $login;
	}
	unset($authusers);
    }

    if (($auth !== false) && (sha1("CSI:$auth:$passwd") !== $pwhash))
	$auth = $editor = false;
    unset($pwhash, $passwd, $login);
}

if (!$auth || (defined('EDITMODE_REQUIRED') && !$editor)) {
    header('WWW-Authenticate: Basic realm="CSS Area"');
    header('HTTP/1.0 401 Unauthorized');
    exit("<h1>401 Unauthorized</h1>\n");
}

/*
    At this point:
    1) $auth="guest", $editor=false         <= "Read-only user authenticated"
    2) $auth="klark", $editor="Leonid Krivoshein"    <= "Edit-mode available"

    To make pwhash type in console:
    echo -n "CSI:<login>:<passwd>" |sha1sum |awk '{print $1;}'
*/

?>