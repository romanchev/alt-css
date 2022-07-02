<?php

$help = "<a href=\"https://www.altlinux.org/\" target=\"_blank\" ".
	    "title=\"Инструкции по установке для Simply Linux. ".
	    "Если не указано, поищите на нашей ВиКи.\">".
	    "<img src=\"icons/help.png\" alt=\"Помощь\" /></a>";
$elastic = true;

function show_thead(&$avail, $diff=false) {
    global $empty, $fcol, $help;

    $output = "<tr>".(!$diff ? "":
	"<th class=\"lineno\">#</th>").
	"<th class=\"product\">$fcol</th>".
	"<th class=\"help\">$help</th>".
	"<th class=\"cell\">Simply{$empty}Linux{$empty}10</th>".
	"<th class=\"empty\">{$empty}</th>".
	"</tr>\n";

    return $output;
}

?>