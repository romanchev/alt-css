<?php

// Authentication
$LIBDIR = @dirname(@realpath(__FILE__))."/lib";
require_once("$LIBDIR/web.php");

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Рабочая таблица Службы обеспечения совместимости</title>
<meta name="robots" content="noindex, nofollow" />
</head>
<frameset cols="61%,*">
    <frame src="WorkTable.php" name="main" scrolling="auto" frameborder="0" />
    <frame src="EntryFrame.php" name="info" scrolling="auto" frameborder="0" />
</frameset>
</html>