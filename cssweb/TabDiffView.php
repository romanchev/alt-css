<?php

// Bootstrap and parse arguments
$LIBDIR = @dirname(@realpath(__FILE__))."/lib";
if (php_sapi_name() != "cli") {
    require_once("$LIBDIR/web.php");
    $lname = $rname = false;
    $upload_static = false;
}
else {
    require_once("$LIBDIR/admin/cli.php");
    require_once("$LIBDIR/admin/fatal.php");
    $lname = httpGetStr("l");
    $rname = httpGetStr("r");
    $upload_static = true;
}
$tableId = httpGetStr("t");
if (!$tableId || !is_dir("$DATADIR/History/$tableId"))
    fatal("Invalid or undefined TableID!");
if (!file_exists("$CFGDIR/$tableId.php"))
    fatal("show_thead() for '$tableId' not defined!");
require_once("$CFGDIR/$tableId.php");
$noexpand = !httpGetBool("e");
$use_last_uploads = false;

// Constants
define("DIFFTYPE_IDX",	0);
define("LINENUMB_IDX",	1);
define("PLATFORM_IDX",	2);
define("VENDNAME_IDX",	3);
define("VENDLINK_IDX",	4);
define("PRODNAME_IDX",	5);
define("PRODLINK_IDX",	6);
define("MAJORVER_IDX",	7);
define("PRODINST_IDX",	8);
define("VENDNOTE_IDX",	9);
define("PRODNOTE_IDX", 10);
define("MVERNOTE_IDX", 11);
define("NOTEHASH_IDX", 12);
define("CATEGORY_IDX", 13);
define("FIRSTCOL_IDX", 14);


// What are the differences?
function what_diff(&$lrow, &$rrow) {
    global $datacols, $noexpand, $ignore_category;

    $list = array();

    if ($lrow[VENDNAME_IDX] != $rrow[VENDNAME_IDX])
	$list[] = "названии партнёра";
    if ($lrow[VENDLINK_IDX] != $rrow[VENDLINK_IDX])
	$list[] = "ссылке на сайт партнёра";
    if ($lrow[PRODNAME_IDX] != $rrow[PRODNAME_IDX])
	$list[] = "названии продукта";
    if ($lrow[PRODLINK_IDX] != $rrow[PRODLINK_IDX])
	$list[] = "ссылке на сайт продукта";
    if ($lrow[MAJORVER_IDX] != $rrow[MAJORVER_IDX])
	$list[] = "версии продукта";
    if (!IGNORE_PRODINST) {
	if ($lrow[PRODINST_IDX] != $rrow[PRODINST_IDX])
	    $list[] = "ссылке на инструкцию";
    }

    $clen = (IGNORE_CERTNUMB ? 8: 11);
    $comp = false;
    $cert = 0;

    for ($i=FIRSTCOL_IDX; $i < FIRSTCOL_IDX+$datacols; $i++) {
	if (($L = $lrow[$i]) == "#")
	    $L = ($noexpand ? "": "+");
	if (($R = $rrow[$i]) == "#")
	    $R = ($noexpand ? "": "+");
	if ($L == $R)
	    continue;
	if (!$L && $R) {
	    if ($R != "+")
		$cert ++;
	    $comp = true;
	}
	elseif ($L && !$R) {
	    if ($L != "+")
		$cert ++;
	    $comp = true;
	}
	elseif (($L == "+") && ($R != "+"))
	    $cert ++;
	elseif (($L != "+") && ($R == "+"))
	    $cert ++;
	elseif (substr($L, 0, $clen) != substr($R, 0, $clen))
	    $cert ++;
    }

    if ($comp)
	$list[] = "совместимости";
    if ($cert == 1)
	$list[] = "сертификате";
    elseif ($cert >= 2)
	$list[] = "сертификатах";
    if ($lrow[NOTEHASH_IDX] != $rrow[NOTEHASH_IDX])
	$list[] = "сносках";
    if (!$ignore_category && ($lrow[CATEGORY_IDX] != $rrow[CATEGORY_IDX]))
	$list[] = "названии категории";
    if (!count($list))
	$list[] = "неустановленных данных";

    return "Изменения в ".implode(", ", $list).":";
}

// Show and store footnote
function add_footnote($text) {
    global $footnotes, $empty;

    foreach ($footnotes as $i => &$v)
	if ($v == $text) {
	    $ref = $i + 1;
	    break;
	}

    if (!isset($ref)) {
	$footnotes[] = $text;
	$ref = count($footnotes);
    }

    $out = "<sup><a href=\"#notes\" title=\"".
	    htmlspecialchars($text).
	    "\"><b>{$ref})</b></a></sup>";
    return $out;
}

// Output one table row
function show_one_move($row, $lineno)
{
    // Line number
    echo "<tr><td class=\"lineno3\">$lineno.</td>";

    // Product and vendor names
    $pname = "<b>".htmlspecialchars($row[PRODNAME_IDX])."</b>";
    if ($row[PRODLINK_IDX])
	$pname = "<a href=\"".htmlspecialchars($row[PRODLINK_IDX]).
		    "\" target=\"_blank\" rel=\"nofollow\" ".
		    "title=\"Открыть описание в новом окне\">$pname</a>";
    if ($row[MAJORVER_IDX]) {
	if (preg_match("/^[0-9\.]+$/", $row[MAJORVER_IDX]))
	    $pname .= " ".htmlspecialchars($row[MAJORVER_IDX]);
	else
	    $pname .= " (".htmlspecialchars($row[MAJORVER_IDX]).")";
    }
    $vname = htmlspecialchars($row[VENDNAME_IDX]);
    if ($row[VENDLINK_IDX])
	$vname = "<a href=\"".htmlspecialchars($row[VENDLINK_IDX]).
		    "\" target=\"_blank\" rel=\"nofollow\" ".
		    "title=\"Открыть сайт в новом окне\">$vname</a>";
    echo "<td class=\"product\">$pname<br/>$vname</td>";
    unset($pname, $vname);

    // From/to category names
    $from = htmlspecialchars(array_pop($row));
    $to   = htmlspecialchars($row[CATEGORY_IDX]);
    echo "<td class=\"category\">$from</td>";
    echo "<td class=\"category\">$to</td>";
    unset($from, $to);
}

// Output one table row
function show_one_row(&$row) {
    global $DATADIR, $datacols, $noexpand, $empty;
    global $upload_static, $prev_row, $help, $elastic;

    // Line number
    $diff = $row[DIFFTYPE_IDX];
    $next = ($diff == ">");
    $line = strval($row[LINENUMB_IDX]).".";
    $diff = ($diff == "<") || ($diff == "-") ? "1": "2";
    echo "<tr><td class=\"lineno{$diff}\">$line</td>";
    unset($diff, $line);

    // Product and vendor names
    $pname = "<b>".htmlspecialchars($row[PRODNAME_IDX])."</b>";
    if ($row[PRODLINK_IDX])
	$pname = "<a href=\"".htmlspecialchars($row[PRODLINK_IDX]).
		    "\" target=\"_blank\" rel=\"nofollow\" ".
		    "title=\"Открыть описание в новом окне\">$pname</a>";
    if ($row[PRODNOTE_IDX])
	$pname .= add_footnote($row[PRODNOTE_IDX]);
    if ($row[MAJORVER_IDX]) {
	if (preg_match("/^[0-9\.]+$/", $row[MAJORVER_IDX]))
	    $pname .= " ".htmlspecialchars($row[MAJORVER_IDX]);
	else
	    $pname .= " (".htmlspecialchars($row[MAJORVER_IDX]).")";
	if ($row[MVERNOTE_IDX])
	    $pname .= add_footnote($row[MVERNOTE_IDX]);
    }
    $vname = htmlspecialchars($row[VENDNAME_IDX]);
    if ($row[VENDLINK_IDX])
	$vname = "<a href=\"".htmlspecialchars($row[VENDLINK_IDX]).
		    "\" target=\"_blank\" rel=\"nofollow\" ".
		    "title=\"Открыть сайт в новом окне\">$vname</a>";
    if ($row[VENDNOTE_IDX])
	$vname .= add_footnote($row[VENDNOTE_IDX]);
    echo "<td class=\"product\">$pname<br/>$vname</td>";
    unset($pname, $vname);

    // Link to the installation guide
    $v = $row[PRODINST_IDX];
    if (!$v || IGNORE_PRODINST || !isset($help))
	$v = $empty;
    elseif (strstr($v, "://") !== false) {
	$v = htmlspecialchars($v);
	$v = "<a href=\"$v\" target=\"_blank\" rel=\"nofollow\" ".
		"title=\"Открыть инструкцию в новом окне\">".
		"<img src=\"icons/www.png\" alt=\"www\" /></a>";
    }
    elseif (!file_exists("$DATADIR/Install/$v")) {
	$v = htmlspecialchars($v);
	$v = "<img src=\"icons/no.png\" alt=\"$v\" title=\"$v\" />";
    }
    else {
	if ($upload_static)
	    $v = "instr/".htmlspecialchars($v).".pdf";
	else {
	    $v = trim(file_get_contents("$DATADIR/Install/$v"));
	    $v = getFileUrl($v);
	}
	$v = "<a href=\"$v\" target=\"_blank\" ".
		"title=\"Открыть инструкцию в новом окне\">".
		"<img src=\"icons/pdf.png\" alt=\"PDF\" /></a>";
    }
    echo "<td class=\"help\">$v</td>";

    // Compatibility cells
    $baseurl = ($upload_static ? "certs": "GetFile.php?fpath=Certs");
    for ($i=FIRSTCOL_IDX; $i < FIRSTCOL_IDX+$datacols; $i++) {
	if ((($v = $row[$i]) == "#") && $noexpand)
	    $v = "";
	if (!$v)
	    echo "<td class=\"empty\">$empty</td>";
	elseif ($v == "+") {
	    $v = "Совместимы";
	    if ($next && ($prev_row[$i] != "+") && ($prev_row[$i] != "#"))
		$v = "<b>$v</b>";
	    if ($row[$i+$datacols])
		$v .= add_footnote($row[$i+$datacols]);
	    echo "<td class=\"compat\">$v</td>";
	}
	elseif ($v == "#") {
	    $v = "Тоже совм.";
	    if ($next && ($prev_row[$i] != "#"))
		$v = "<b>$v</b>";
	    if ($row[$i+$datacols])
		$v .= add_footnote($row[$i+$datacols]);
	    echo "<td class=\"compat\">$v</td>";
	}
	else {
	    $cert = substr($v, 6, 2).".".
		    substr($v, 4, 2).".".
		    substr($v, 0, 4)."/".
		    substr($v, 9, 2);
	    if ($next && ($prev_row[$i] != $v))
		$cert = "<b>".htmlspecialchars($cert)."</b>";
	    else
		$cert = htmlspecialchars($cert);
	    if (file_exists("$DATADIR/Certs/$v.jpg"))
		$cert = "<a href=\"$baseurl/$v.jpg\" target=\"_blank\" ".
			    "title=\"Открыть в новом окне\">$cert</a>";
	    if ($row[$i+$datacols])
		$cert .= add_footnote($row[$i+$datacols]);
	    echo "<td class=\"compat\">$cert</td>";
	    unset($cert);
	}
    }

    if ($elastic)
	echo "<td class=\"empty\">$empty</td>";
    echo "</tr>\n";
    $prev_row = $row;
}


// Try to find last CSV's
if (!$lname || !$rname) {
    $lname = $rname = "";
    $list = explode("\n", `ls -1 "$DATADIR/History/$tableId/" |tail -n2`);
    if (count($list) == 3) {
	$lname = basename($list[0], ".csv");
	$rname = basename($list[1], ".csv");
    }
    unset($list);
    $use_last_uploads = true;
}

// To be sure what specified input CSV-files realy exists
if (!file_exists("$DATADIR/History/$tableId/$lname.csv") ||
    !file_exists("$DATADIR/History/$tableId/$rname.csv"))
{
    $lname = $rname = "";
}

// Determinate sources and load CSV-data
if ($lname && $rname) {
    if ($use_last_uploads) {
	$lmain = "$CACHEDIR/$tableId-prev.csv";
	$rmain = "$CACHEDIR/$tableId-stat.csv";
	$tdiff = "$CACHEDIR/$tableId-diff.csv";
	if (!file_exists($lmain) || !file_exists($rmain))
	    $use_last_uploads = false;
	elseif (!file_exists($tdiff))
	    $use_last_uploads = false;
	else {
	    $lmain = file($lmain, FILE_IGNORE_NEW_LINES);
	    $rmain = file($rmain, FILE_IGNORE_NEW_LINES);
	    $tdiff = file($tdiff, FILE_IGNORE_NEW_LINES);
	    if (!$lmain || !$rmain || !$tdiff)
		$lname = $rname = "";
	}
    }
    if (!$use_last_uploads) {
	$expopt = ($noexpand ? "--noexpand": "");
	$prefix = "CSS_CACHEDIR=\"$CACHEDIR\" ".
		    "CSS_DATADIR=\"$DATADIR\" ".
		    "CSS_USER=\"$auth\" ".
		    @dirname(@realpath(__FILE__))."/bin";
	$lmain  = `$prefix/tabstat.php "$tableId" "$lname" $expopt`;
	$rmain  = `$prefix/tabstat.php "$tableId" "$rname" $expopt`;
	$tdiff  = `$prefix/tabdiff.php "$tableId" "$lname" "$rname" $expopt`;
	if (!$lmain || !$rmain || !$tdiff)
	    $lname = $rname = "";
	else {
	    $lmain = explode("\n", $lmain);
	    $rmain = explode("\n", $rmain);
	    $tdiff = explode("\n", $tdiff);
	    array_pop($lmain);
	    array_pop($rmain);
	    array_pop($tdiff);
	}
	unset($prefix, $expopt);
    }
}
if (!$lname || !$rname)
    fatal("Input CSV-data not found!");
$moves = $footnotes = array();
$archs = $rdiff = array();
$lstat = $rstat = array();
$lstat["ALL"] = array();
$rstat["ALL"] = array();

// Parse input CSV-data
foreach ($lmain as &$row)
    $row = explode("|", $row);
foreach ($rmain as &$row)
    $row = explode("|", $row);
foreach ($tdiff as &$row) {
    $cells = explode("|", $row);
    $cells[LINENUMB_IDX] = @intval($cells[LINENUMB_IDX]);
    for ($i=PLATFORM_IDX; $i < count($cells); $i++)
	$cells[$i] = substr($cells[$i], 1, -1);
    $rdiff[] = $cells;
    if ($cells[DIFFTYPE_IDX] == ">") {
	$p = count($rdiff) - 2;
	$c = $rdiff[$p][CATEGORY_IDX];
	if ($cells[CATEGORY_IDX] != $c) {
	    if (!isset($ignore_category))
		$ignore_category = (!$cells[CATEGORY_IDX] || !$c);
	    if (!$ignore_category) {
		$k = "";
		for ($i=VENDNAME_IDX; $i <= MAJORVER_IDX; $i++) {
		    if ($cells[$i])
			$k .= "$i:".$cells[$i]." ";
		}
		$k = md5($k);
		if (!isset($moves[$k])) {
		    $cells[] = $c;
		    $moves[$k] = $cells;
		}
		unset($k);
	    }
	}
	unset($p, $c);
    }
    unset($cells, $i);
}
if (!isset($ignore_category))
    $ignore_category = false;
for ($j=1; $j < count($rmain); $j++)
    $rstat["ALL"][] = @intval($rmain[$j][1]);
for ($j=1; $j < count($lmain); $j++)
    $lstat["ALL"][] = @intval($lmain[$j][1]);
for ($i=2; $i < count($rmain[0]); $i++) {
    $arch = substr($rmain[0][$i], 1, -1);
    $rstat[$arch] = array();
    for ($j=1; $j < count($rmain); $j++)
	$rstat[$arch][] = @intval($rmain[$j][$i]);
    if (!isset($archs[$arch]))
	$archs[$arch] = true;
    unset($arch);
}
for ($i=2; $i < count($lmain[0]); $i++) {
    $arch = substr($lmain[0][$i], 1, -1);
    $lstat[$arch] = array();
    for ($j=1; $j < count($lmain); $j++)
	$lstat[$arch][] = $lmain[$j][$i];
    if (!isset($archs[$arch]))
	$archs[$arch] = true;
    unset($arch);
}
unset($lmain, $rmain, $tdiff, $row, $i, $j);

// Normalize upload's names
$ldate = substr($lname,6,2).".".substr($lname,4,2).".".substr($lname,0,4);
$rdate = substr($rname,6,2).".".substr($rname,4,2).".".substr($rname,0,4);
$ltime = substr($lname,9,2).":".substr($lname,11,2).":".substr($lname,13,2);
$rtime = substr($rname,9,2).":".substr($rname,11,2).":".substr($rname,13,2);

// HTML-header
$head = array (
	"Строк в таблице",
	"Уникальных партнёров",
	"Уникальных продуктов",
	"Уникальных категорий",
	"Всего совместимостей",
	"Всего сертификатов",
	"Уник. сертификатов",
	"Уникальных ссылок",
	"Внешних INST-ссылок",
	"Внутр. ссылок на PDF",
	"Загруженных PDF",
	"Всего сносок"
);
$from = array (
	"{BYGRPPAGE}",
	"{BYVNDPAGE}",
	"{BYPRDPAGE}",
	"{DATETIME1}",
	"{DATETIME2}",
	"{ARCHBUTTONS}"
);
if ($upload_static) {
    $to = array (
	"$tableId-view2.html",
	"$tableId-view1.html",
	"$tableId-view0.html"
    );
    $L = htmlspecialchars("$tableId-$lname.csv");
    $R = htmlspecialchars("$tableId-$rname.csv");
}
else {
    $to = array (
	"CompTableView.php?t=$tableId&amp;v=2".($noexpand ? "": "&amp;e=on"),
	"CompTableView.php?t=$tableId&amp;v=1".($noexpand ? "": "&amp;e=on"),
	"CompTableView.php?t=$tableId&amp;v=0".($noexpand ? "": "&amp;e=on")
    );
    $L = $R = "/";
    if (file_exists("$DATADIR/History/$tableId/$lname.csv"))
	$L = getFileUrl("History/$tableId/$lname.csv");
    if (file_exists("$DATADIR/History/$tableId/$rname.csv"))
	$R = getFileUrl("History/$tableId/$rname.csv");
}
$to[] = htmlspecialchars($ldate." ".$ltime);
$to[] = htmlspecialchars($rdate." ".$rtime);
$output = "<table class=\"buttons\"><tr>\n";
foreach ($archs as $arch => $dummy) {
    $output .=  "<td><a href=\"#$arch\">".
		"<img src=\"icons/$arch.png\" ".
		"alt=\"$arch\" /><br/>".
		"$arch</a></td>\n";
}
$to[] = "$output</tr></table>";
echo makeHeader("TabDiffView", $from, $to);
unset($from, $to, $output, $arch, $dummy);

// Information about specified uploads
echo "<table class=\"diff\">\n<thead>\n";
echo "<tr><th class=\"h\">{$empty}</th>";
echo "<th class=\"l\">Файл{$empty}#1</th>";
echo "<th class=\"e\">{$empty}</th>";
echo "<th class=\"r\">Файл{$empty}#2</th>";
echo "</tr>\n</thead><tbody>\n";
//
echo "<tr><td class=\"h\">Скачать{$empty}выгрузку:</td>";
echo "<td class=\"l\"><a href=\"$L\" ";
echo "title=\"Скачать CSV-файл для служебного пользования...\">";
echo "<img src=\"icons/csv.png\" alt=\"CSV\" /><br/>csv</a></td>";
echo "<td class=\"e\">{$empty}</td>";
echo "<td class=\"r\"><a href=\"$R\" ";
echo "title=\"Скачать CSV-файл для служебного пользования...\">";
echo "<img src=\"icons/csv.png\" alt=\"CSV\" /><br/>csv</a></td>";
echo "</tr>\n";
//
echo "<tr><td class=\"h\">Дата{$empty}выгрузки:</td>";
echo "<td class=\"l\"><b>".htmlspecialchars($ldate)."</b></td>";
echo "<td class=\"e\">{$empty}</td>";
echo "<td class=\"r\"><b>".htmlspecialchars($rdate)."</b></td>";
echo "</tr>\n";
//
echo "<tr><td class=\"h\">Время{$empty}выгрузки:</td>";
echo "<td class=\"l\">".htmlspecialchars($ltime)."</td>";
echo "<td class=\"e\">{$empty}</td>";
echo "<td class=\"r\">".htmlspecialchars($rtime)."</td>";
echo "</tr>\n";
//
$L = count($lstat) - 1;
$R = count($rstat) - 1;
echo "<tr><td class=\"h\">Уникальных{$empty}платформ:</td>";
echo "<td class=\"l\">$L</td>";
if ($L > $R)
    echo "<td class=\"x\">-".strval($L - $R)."</td>";
elseif ($R > $L)
    echo "<td class=\"y\">+".strval($R - $L)."</td>";
else
    echo "<td class=\"e\">{$empty}</td>";
echo "<td class=\"r\">$R</td>";
echo "</tr>\n</tbody></table><br />\n\n";
unset($L, $R);

// Statistic details
echo "<table class=\"diff\"><thead>\n";
echo "<tr><th class=\"h\">{$empty}</th>";
echo "<th class=\"f\" colspan=\"3\">ИТОГО</th>";
foreach ($archs as $arch => $dummy)
    echo "<th class=\"f\" colspan=\"3\">$arch</th>";
echo "</tr>\n<tr><th class=\"h\">{$empty}</th>";
echo "<th class=\"l\">#1</th>";
echo "<th class=\"e\">{$empty}</th>";
echo "<th class=\"r\">#2</th>";
foreach ($archs as $arch => $dummy) {
    echo "<th class=\"l\">#1</th>";
    echo "<th class=\"e\">{$empty}</th>";
    echo "<th class=\"r\">#2</th>";
}
echo "</tr>\n</thead><tbody>\n";
//
for ($j=0; $j < count($head); $j++) {
    echo "<tr><td class=\"h\">";
    echo str_replace(" ", $empty, $head[$j]);
    echo ":</td>";

    $L = $lstat["ALL"][$j];
    $R = $rstat["ALL"][$j];
    echo "<td class=\"l\">$L</td>";
    if ($L > $R)
	echo "<td class=\"x\">-".strval($L-$R)."</td>";
    elseif ($R > $L)
	echo "<td class=\"y\">+".strval($R-$L)."</td>";
    else
	echo "<td class=\"e\">{$empty}</td>";
    echo "<td class=\"r\">$R</td>";

    foreach ($archs as $arch => $dummy) {
	$L = $E = $R = $empty; $C = "e";
	if (isset($lstat[$arch]) && !isset($rstat[$arch]))
	    $L = $lstat[$arch][$j];
	elseif (!isset($lstat[$arch]) && isset($rstat[$arch]))
	    $R = $rstat[$arch][$j];
	else {
	    $L = $lstat[$arch][$j];
	    $R = $rstat[$arch][$j];
	    if ($L > $R) {
		$E = "-" . strval($L - $R);
		$C = "x";
	    }
	    elseif ($R > $L) {
		$E = "+" . strval($R - $L);
		$C = "y";
	    }
	}
	echo "<td class=\"l\">$L</td>";
	echo "<td class=\"$C\">$E</td>";
	echo "<td class=\"r\">$R</td>";
    }

    echo "</tr>\n";
    unset($arch, $dummy, $L, $E, $R);
}
echo "</tbody>\n</table>\n\n";
unset($head, $archs, $lstat, $rstat);
unset($ldate, $ltime, $lname, $rdate, $rtime, $rname);
$fcol = "Производитель, продукт";

// Products moves between categories
if (count($moves)) {
    echo "<h2>Перемещения продуктов в другие категории</h2>\n";
    echo "<table class=\"arch\">\n<thead>\n";
    echo "<tr><th class=\"lineno\">#</th>";
    echo "<th class=\"product\">$fcol</th>";
    echo "<th class=\"empty\">Откуда</th>";
    echo "<th class=\"empty\">Куда</th>";
    echo "</tr>\n</thead><tbody>\n";
    $lineno = 0;
    foreach ($moves as &$row)
	show_one_move($row, ++$lineno);
    echo "</tbody>\n</table>\n\n";
    unset($row, $lineno);
}
unset($moves);

// The differences between two uploads
$datacols = count($avail_platforms[$tableId]);
$last_arch = $last_diff = "";
$prev_row = null;
for ($i=0; $i < count($rdiff); $i++) {
    $arch = $rdiff[$i][PLATFORM_IDX];

    if ($last_arch != $arch) {
	if ($last_arch)
	    echo "</tbody>\n</table>\n\n";
	$avail = array();
	foreach ($avail_platforms[$tableId] as $row) {
	    $row = array_flip(explode(",", $row));
	    $avail[] = isset( $row[$arch] );
	    unset($row);
	}
	$output = "<a name=\"$arch\" id=\"$arch\" />".
	    "<h2>Совместимость с дистрибутивами на архитектуре ".
		"$arch (".$hw_platforms[$arch].")</h2>\n".
	    "<table class=\"arch\">\n<thead>\n".
	    show_thead($avail, true).
	    "</thead><tbody>\n";
	echo $output;
	$last_diff = "";
	$last_arch = $arch;
    }

    unset($arch);
    $diff = $rdiff[$i][DIFFTYPE_IDX];

    if ($last_diff != $diff) {
	if ($diff == "-")
	    $output = "Следующие строки были удалены:";
	elseif ($diff == "+")
	    $output = "Следующие строки были добавлены:";
	else {
	    $output = what_diff($rdiff[$i], $rdiff[$i+1]);
	    $diff = "<";
	}
	echo "<tr><td colspan=\"".strval(($elastic ? 4: 3) + $datacols).
		"\" class=\"group\">$output</td></tr>\n";
	$last_diff = $diff;
	unset($output);
    }

    show_one_row($rdiff[$i]);

    if ($diff == "<") {
	show_one_row($rdiff[++$i]);
	$last_diff = "";
    }
    unset($diff);
}

if ($last_arch)
    echo "</tbody>\n</table>\n\n";

// Footnotes
if (count($footnotes)) {
    echo "<a name=\"notes\" id=\"notes\" />";
    echo "<h2>Примечания</h2><ol>\n";
    foreach ($footnotes as $text)
	echo "<li>".htmlspecialchars($text)."</li>";
    echo "</ol>\n\n";
}

// HTML-footer
echo makeFooter("TabDiffView");

?>