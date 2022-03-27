<?php

// Bootstrap and parse arguments
$LIBDIR = @dirname(@realpath(__FILE__))."/lib";
if (php_sapi_name() != "cli") {
    require_once("$LIBDIR/web.php");
    $upload_static = false;
}
else {
    require_once("$LIBDIR/admin/cli.php");
    require_once("$LIBDIR/admin/fatal.php");
    $upload_static = true;
}
$tableId = httpGetStr("t");
if (!$tableId || !is_dir($TDIR = "$DATADIR/History/$tableId"))
    fatal("Invalid or undefined TableID!");
if (!file_exists("$CFGDIR/$tableId.php"))
    fatal("show_thead() for '$tableId' not defined!");
require_once("$LIBDIR/admin/index/common.php");
require_once("$CFGDIR/$tableId.php");
$noexpand = !httpGetBool("e");
$group_by = httpGetInt("v");
$upload   = httpGetStr("u");

// Constants
define("PLATFORM_IDX",	0);
define("VENDNAME_IDX",	1);
define("VENDLINK_IDX",	2);
define("PRODNAME_IDX",	3);
define("PRODLINK_IDX",	4);
define("MAJORVER_IDX",	5);
define("PRODINST_IDX",	6);
define("COMP1COL_IDX",	7);
//
define("VENDNOTE_IDX",	7);
define("PRODNOTE_IDX",	8);
define("MVERNOTE_IDX",	9);
define("CATEGORY_IDX", 10);
define("FIRSTCOL_IDX", 11);
//
define("GROUP_BY_PRODUCTS", 0);
define("GROUP_BY_VENDORS",  1);
define("GROUP_BY_GROUPS",   2);


// Compare two rows
function compare_rows(&$a, &$b) {
    global $group_by, $platforms;

    if ($a[PLATFORM_IDX] != $b[PLATFORM_IDX]) {
	$l = $platforms[ $a[PLATFORM_IDX] ];
	$r = $platforms[ $b[PLATFORM_IDX] ];
	return ($l < $r) ? -1: 1;
    }

    if ($group_by == GROUP_BY_GROUPS) {
	if ($a[CATEGORY_IDX] != $b[CATEGORY_IDX]) {
	    $soft = "Софт ";  $n = mb_strlen($soft);
	    $l = mb_substr($a[CATEGORY_IDX], 0, $n);
	    $r = mb_substr($b[CATEGORY_IDX], 0, $n);
	    if (($l == $soft) && ($r != $soft))
		return -1;
	    elseif (($l != $soft) && ($r == $soft))
		return 1;
	    return mb_strcasecmp($a[CATEGORY_IDX], $b[CATEGORY_IDX]);
	}
    }

    if ($group_by != GROUP_BY_PRODUCTS) {
	if ($a[VENDNAME_IDX] != $b[VENDNAME_IDX]) {
	    $l = inquote_fast( $a[VENDNAME_IDX] );
	    $r = inquote_fast( $b[VENDNAME_IDX] );
    	    return mb_strcasecmp($l, $r);
	}
    }

    if ($a[PRODNAME_IDX] != $b[PRODNAME_IDX]) {
	$l = inquote_fast( $a[PRODNAME_IDX] );
	$r = inquote_fast( $b[PRODNAME_IDX] );
        return mb_strcasecmp($l, $r);
    }

    if ($a[MAJORVER_IDX] != $b[MAJORVER_IDX]) {
	$l = $a[MAJORVER_IDX];
	$r = $b[MAJORVER_IDX];
	if (!$l)
	    return 1;
	elseif (!$r)
	    return -1;
	else {
	    $x = preg_match("/^\d+$/", $l);
	    $y = preg_match("/^\d+$/", $r);
	    if ($x && $y)
		return (@intval($l) < @intval($r)) ? 1: -1;
	    $x = preg_match("/^(\d+)\.(\d+)$/", $l, $lmatch);
	    $y = preg_match("/^(\d+)\.(\d+)$/", $r, $rmatch);
	    if (!$x || !$y)
		return mb_strcasecmp($l, $r);
	    $x = @intval($lmatch[1]);
	    $y = @intval($rmatch[1]);
	    if ($x < $y)
		return 1;
	    elseif ($x > $y)
		return -1;
	    $x = @intval($lmatch[2]);
	    $y = @intval($rmatch[2]);
	    return ($x < $y) ? 1: -1;
	}
    }

    return 0;
}

// Parse one row
function &csv2arr(&$str) {
    global $datacols, $noexpand;

    $list = explode("|", $str);
    foreach ($list as &$item)
	$item = substr($item, 1, -1);
    $arr = array();
    for ($i=PLATFORM_IDX; $i < COMP1COL_IDX; $i++)
	$arr[] = &$list[$i];
    unset($item);

    // VENDNOTE_IDX
    if (strstr($list[VENDNAME_IDX], "[[") === false)
	$arr[] = "";
    else {
	$x = explode("[[", $list[VENDNAME_IDX], 2);
	$arr[VENDNAME_IDX] = &$x[0];
	$x = substr($x[1], 0, -2);
	$arr[] = &$x;
	unset($x);
    }

    // PRODNOTE_IDX
    if (strstr($list[PRODNAME_IDX], "[[") === false)
	$arr[] = "";
    else {
	$x = explode("[[", $list[PRODNAME_IDX], 2);
	$arr[PRODNAME_IDX] = &$x[0];
	$x = substr($x[1], 0, -2);
	$arr[] = &$x;
	unset($x);
    }

    // MVERNOTE_IDX
    if (strstr($list[MAJORVER_IDX], "[[") === false)
	$arr[] = "";
    else {
	$x = explode("[[", $list[MAJORVER_IDX], 2);
	$arr[MAJORVER_IDX] = &$x[0];
	$x = substr($x[1], 0, -2);
	$arr[] = &$x;
	unset($x);
    }

    // CATEGORY_IDX
    $arr[] = &$list[COMP1COL_IDX+$datacols];

    // Compatibility data cells
    for ($i=0; $i < $datacols; $i++) {
	$cell = &$list[COMP1COL_IDX+$i];
	if (!$cell || ($cell == "+"))
	    $arr[] = &$cell;
	elseif ($cell == "Совместимы")
	    $arr[] = "+";
	elseif ($cell == "#")
	    $arr[] = ($noexpand ? "": "#");
	elseif (substr($cell, 0, 1) == "+")
	    $arr[] = "+";
	elseif (substr($cell, 0, 1) == "#")
	    $arr[] = ($noexpand ? "": "#");
	elseif (strstr($cell, "[[") === false)
	    $arr[] = &$cell;
	else {
	    $x = explode("[[", $cell, 2);
	    $arr[] = &$x[0];
	    unset($x);
	}
	unset($cell);
    }

    // Footnotes for compatibility cells
    for ($i=0; $i < $datacols; $i++) {
	$cell = &$list[COMP1COL_IDX+$i];
	if (!$cell || (strstr($cell, "[[") === false))
	    $arr[] = "";
	elseif ($noexpand && (substr($cell, 0, 1) == "#"))
	    $arr[] = "";
	else {
	    $x = explode("[[", $cell, 2);
	    $x = substr($x[1], 0, -2);
	    $arr[] = &$x;
	    unset($x);
	}
	unset($cell);
    }

    return $arr;
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
function show_one_row(&$row) {
    global $DATADIR, $datacols, $group_by, $empty;
    global $upload_static, $elastic, $noexpand, $help;

    // Product and vendor names
    $pname = htmlspecialchars($row[PRODNAME_IDX]);
    if ($group_by == GROUP_BY_GROUPS)
	$pname = "<b>$pname</b>";
    if ($row[PRODLINK_IDX])
	$pname = "<a href=\"".htmlspecialchars($row[PRODLINK_IDX]).
		    "\" target=\"_blank\" rel=\"nofollow\" ".
		    "title=\"Открыть описание в новом окне\">$pname</a>";
    if ($row[PRODNOTE_IDX])
	$pname .= add_footnote($row[PRODNOTE_IDX]);
    if ($group_by != GROUP_BY_GROUPS)
	$pname = "{$empty}{$empty}{$empty}{$empty}{$pname}";
    if ($row[MAJORVER_IDX]) {
	if (preg_match("/^[0-9\.]+$/", $row[MAJORVER_IDX]))
	    $pname .= " ".htmlspecialchars($row[MAJORVER_IDX]);
	else
	    $pname .= " (".htmlspecialchars($row[MAJORVER_IDX]).")";
	if ($row[MVERNOTE_IDX])
	    $pname .= add_footnote($row[MVERNOTE_IDX]);
    }
    if ($group_by == GROUP_BY_GROUPS) {
	$vname = htmlspecialchars($row[VENDNAME_IDX]);
	if ($row[VENDLINK_IDX])
	    $vname = "<a href=\"".htmlspecialchars($row[VENDLINK_IDX]).
			"\" target=\"_blank\" rel=\"nofollow\" ".
			"title=\"Открыть сайт в новом окне\">$vname</a>";
	if ($row[VENDNOTE_IDX])
	    $vname .= add_footnote($row[VENDNOTE_IDX]);
	$pname .= "<br/>$vname";
	unset($vname);
    }
    echo "<tr><td class=\"product\">$pname</td>";
    unset($pname);

    // Link to the installation guide
    if (isset($help)) {
	$v = $row[PRODINST_IDX];
	if (!$v)
	    $v = $empty;
	elseif (strstr($v, "://") !== false) {
	    $v = htmlspecialchars($v);
	    $v = "<a href=\"$v\" target=\"_blank\" rel=\"nofollow\" ".
		    "title=\"Открыть инструкцию в новом окне\">".
		    "<img src=\"icons/www.png\" alt=\"www\" /></a>";
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
    }

    // Compatibility cells
    $baseurl = ($upload_static ? "certs": "GetFile.php?fpath=Certs");
    for ($i=FIRSTCOL_IDX; $i < FIRSTCOL_IDX+$datacols; $i++) {
	if ((($v = $row[$i]) == "#") && $noexpand)
	    $v = "";
	if (!$v)
	    echo "<td class=\"empty\">$empty</td>";
	elseif ($v == "+") {
	    $v = "Совместимы";
	    if ($row[$i+$datacols])
		$v .= add_footnote($row[$i+$datacols]);
	    echo "<td class=\"compat\">$v</td>";
	}
	elseif ($v == "#") {
	    $v = ($upload_static ? "Совместимы": "Тоже совм.");
	    if ($row[$i+$datacols])
		$v .= add_footnote($row[$i+$datacols]);
	    echo "<td class=\"compat\">$v</td>";
	}
	else {
	    $cert = "<a href=\"$baseurl/$v.jpg\" target=\"_blank\" ".
			"title=\"Открыть в новом окне\">Сертификат</a>";
	    if ($row[$i+$datacols])
		$cert .= add_footnote($row[$i+$datacols]);
	    echo "<td class=\"compat\">$cert</td>";
	    unset($cert);
	}
    }

    if ($elastic)
	echo "<td class=\"empty\">$empty</td>";
    echo "</tr>\n";
}


// Entry point
if (($group_by < GROUP_BY_PRODUCTS) || ($group_by > GROUP_BY_GROUPS))
    $group_by = GROUP_BY_PRODUCTS;
if (!$upload)
    $upload = trim(`ls -1 "$DATADIR/History/uploads/" |tail -n1`);
if ($upload)
    $upload = basename($upload, ".csv");
if (!$upload || !file_exists($input = "$TDIR/$upload.csv")) {
    if (!$upload || !file_exists($input = "$CACHEDIR/$tableId-$upload.csv"))
	fatal("Input CSV-data not found!");
}
$platforms = $csv = array();
$datacols = count($avail_platforms[$tableId]);

// Parse and sort input CSV-data
$input = file($input, FILE_IGNORE_NEW_LINES);
foreach ($input as &$str) {
    $row = csv2arr($str);
    $platforms[$row[PLATFORM_IDX]] = true;
    $csv[] =& $row;
    unset($row);
}
$platforms = array_flip(array_keys( $platforms ));
usort($csv, "compare_rows");
unset($input, $str, $TDIR);

// HTML-header
$from = array (
	    "{BYGRPPAGE}",
	    "{BYVNDPAGE}",
	    "{BYPRDPAGE}",
	    "{UPLOAD}",
	    "{DATE}",
	    "{ARCHBUTTONS}"
);
if ($upload_static) {
    $to = array (
	    "$tableId-view2.html",
	    "$tableId-view1.html",
	    "$tableId-view0.html",
	    "$tableId.csv"
    );
}
else {
    $s  = @basename(__FILE__)."?t=".
	    htmlspecialchars("$tableId")."&amp;v";
    $to = array (
	    "$s=2".($noexpand ? "": "&amp;e=on"),
	    "$s=1".($noexpand ? "": "&amp;e=on"),
	    "$s=0".($noexpand ? "": "&amp;e=on")
    );
    if (file_exists("$TDIR/$upload.csv"))
	$to[] = getFileUrl("History/$tableId/$upload.csv");
    else
	$to[] = "static/$tableId.csv";
}
$to[] = htmlspecialchars(
	    substr($upload, 6, 2).".".
	    substr($upload, 4, 2).".".
	    substr($upload, 0, 4));
$s = "<table class=\"buttons\"><tr>\n";
foreach ($platforms as $arch => $dummy) {
    $arch = htmlspecialchars($arch);
    $s .= "<td><a href=\"#$arch\">";
    $s .= "<img src=\"icons/$arch.png\" ";
    $s .= "alt=\"$arch\" /><br/>$arch</a></td>\n";
}
$to[] = "$s</tr></table>";
echo makeHeader("CompTab-$tableId", $from, $to);
unset($from, $to, $s, $arch, $dummy);

// Pivot table body
$width = (isset($help) ? 2: 1) + ($elastic ? 1: 0) + $datacols;
$last_arch = $last_group = "";
$footnotes = array();
if ($group_by == GROUP_BY_GROUPS)
    $fcol = "Категория, продукт, производитель";
else
    $fcol = "Производитель, продукт";
foreach ($csv as &$row) {
    $arch = htmlspecialchars($row[PLATFORM_IDX]);
    if ($arch != $last_arch) {
	if ($last_arch)
	    echo "</tbody>\n</table>\n\n";
	$avail = array();
	foreach ($avail_platforms[$tableId] as $plist) {
	    $plist = array_flip(explode(",", $plist));
	    $avail[] = isset($plist[$arch]);
	    unset($plist);
	}
	$output = "<a name=\"$arch\" id=\"$arch\" />".
	    "<h2>Совместимость с дистрибутивами на архитектуре ".
		"$arch (".$hw_platforms[$arch].")</h2>\n".
	    "<table class=\"arch\">\n<thead>\n".
	    show_thead($avail)."</thead><tbody>\n";
	echo $output;
	$last_group = "";
	$last_arch = $arch;
	unset($avail, $output);
    }
    unset($arch);

    if ($group_by == GROUP_BY_GROUPS)
	$grp = htmlspecialchars($row[CATEGORY_IDX]);
    else
	$grp = htmlspecialchars($row[VENDNAME_IDX]);
    if ($last_group != $grp) {
	$last_group = $grp;
	if ($group_by != GROUP_BY_GROUPS) {
	    if ($row[VENDLINK_IDX])
		$grp = "<a href=\"".htmlspecialchars($row[VENDLINK_IDX]).
			    "\" target=\"_blank\" rel=\"nofollow\" ".
			    "title=\"Открыть сайт в новом окне\">$grp</a>";
	    if ($row[VENDNOTE_IDX])
		$grp .= add_footnote($row[VENDNOTE_IDX]);
	}
	echo "<tr><td colspan=\"$width\" class=\"group\">$grp</td></tr>\n";
    }
    unset($grp);

    show_one_row($row);
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
echo makeFooter("CompTab-$tableId");

?>