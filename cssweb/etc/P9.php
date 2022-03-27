<?php

$help = "<a href=\"https://www.altlinux.org/\" target=\"_blank\" ".
	    "title=\"Инструкции по установке для дистрибутивов Альт ".
	    "на бранче p9 (в стадии наполнения). Если не указано, ".
	    "поищите на нашей ВиКи или запросите в отделе продаж.\">".
	    "<img src=\"icons/help.png\" alt=\"Помощь\" /></a>";
$elastic = false;

function show_thead(&$avail, $diff=false) {
    global $empty, $fcol, $help;

    if (!$avail[0] && !$avail[1]) {
	$output = "<tr>".(!$diff ? "":
	    "<th class=\"lineno\">#</th>").
	    "<th class=\"product\">$fcol</th>".
	    "<th class=\"help\">$help</th>".
	    "<th class=\"cell\">{$empty}</th>".
	    "<th class=\"cell\">{$empty}</th>".
	    "<th class=\"cell\">".(!$avail[2] ? $empty:
		"Альт{$empty}Рабочая{$empty}станция{$empty}9")."</th>".
	    "<th class=\"cell\">".(!$avail[3] ? $empty:
		"Альт{$empty}Образование{$empty}9")."</th>".
	    "<th class=\"cell\">".(!$avail[4] ? $empty:
		"Альт{$empty}Сервер{$empty}9")."</th>".
	    "</tr>\n";
    }
    else {
	$output = "<tr>".(!$diff ? "":
	    "<th rowspan=\"2\" class=\"lineno\">#</th>").
	    "<th rowspan=\"2\" class=\"product\">$fcol</th>".
	    "<th rowspan=\"2\" class=\"help\">$help</th>".
	    "<th colspan=\"2\" class=\"cell\">".
		"Альт{$empty}8{$empty}СП</th>".
	    "<th rowspan=\"2\" class=\"cell\">".(!$avail[2] ? $empty:
		"Альт{$empty}Рабочая{$empty}станция{$empty}9")."</th>".
	    "<th rowspan=\"2\" class=\"cell\">".($avail[3] ?
		"Альт{$empty}Образование{$empty}9": $empty)."</th>".
	    "<th rowspan=\"2\" class=\"cell\">".($avail[4] ?
		"Альт{$empty}Сервер{$empty}9": $empty)."</th>".
	    "</tr>\n<tr>".
	    "<th class=\"small\">".($avail[0] ?
		"(рабочая{$empty}станция)": $empty)."</th>".
	    "<th class=\"small\">(сервер)</th>".
	    "</tr>\n";
    }

    return $output;
}

?>