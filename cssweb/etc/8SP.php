<?php

$elastic = true;

function show_thead(&$avail, $diff=false) {
    global $empty, $fcol;

    $h = "<img src=\"icons/help.png\" alt=\"Помощь\" />";

    $output = "<tr>".(!$diff ? "":
	"<th rowspan=\"2\" class=\"lineno\">#</th>").
	"<th rowspan=\"2\" class=\"product\">$fcol</th>".
	(!$diff ? "":
	    "<th rowspan=\"2\" class=\"help\">$h</th>").
	"<th colspan=\"2\" class=\"cell\">".
	    "Альт{$empty}8{$empty}СП{$empty}".
	    "(ИК:{$empty}март{$empty}2020)</th>".
	"<th rowspan=\"2\" class=\"empty\">{$empty}</th>".
	"</tr>\n<tr>".
	"<th class=\"small\">(рабочая{$empty}станция)</th>".
	"<th class=\"small\">(сервер)</th>".
	"</tr>\n";

    return $output;
}

?>