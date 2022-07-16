<?php

// Authentication
$LIBDIR = @dirname(@realpath(__FILE__))."/lib";
require_once("$LIBDIR/web.php");

// Load data
$platforms = array_keys($hw_platforms);
$statinfo  = cache2arr("statinfo");
$catids    = cache2arr("catids");
$recent    = cache2arr("recent");
$git       = cache2arr("csigit");

// Dictionary
$dic = array (
    "CSI-commits" => "Коммитов",
    "vendors"     => "Партнёров",
    "products"    => "Продуктов",
    "versions"    => "Версий",
    "majorver"    => "- основных",
    "releases"    => "- выпусков",
    "compinfo"    => "CI-записей",
    "unicerts"    => "Сертификатов",
    "distros"     => "Образов ОС",
    "categories"  => "Категорий",
    "softgroups"  => "- программы",
    "hardgroups"  => "- оборудование",
    "platforms"   => "Платформ",
    "install"     => "Ссылок на PDF",
    "manuals"     => "PDF-инструкций",
    "documents"   => "Документов",
    "templates"   => "Шаблонов",
    "drafts"      => "Черновиков"
);

// Platforms
$counter = 4; $p = "";
foreach ($platforms as $name) {
    $p .= " <option value=\"$counter\">".
	    htmlspecialchars("Только $name")."</option>\n";
    $counter ++;
}
unset($platforms);

// Categories
$s = $h = "";
$soft = array();
$hard = array();
foreach ($catids as $name => $counter) {
    $arr = explode("/", $name);
    $counter += 3;
    if ($arr[0] == "ПО") {
	$name = str_replace("_", " ", $arr[1]);
	if (!isset($soft[$name])) {
	    $s .= "  <option value=\"$counter\">".
		    htmlspecialchars($name)."</option>\n";
	    $soft[$name] = true;
	}
    }
    else {
	$name = str_replace("_", " ", $arr[0]);
	if (!isset($hard[$name])) {
	    $h .= "  <option value=\"$counter\">".
		    htmlspecialchars($name)."</option>\n";
	    $hard[$name] = true;
	}
    }
    unset($arr);
}
unset($soft, $hard, $catids, $counter, $name);

// Form header
echo makeHeader("MainView", array("{ALL-PLATFORMS}",
		"{SOFT-GROUPS}", "{HARD-GROUPS}"),
		array($p, $s, $h));
if ($editor) {
    $p = ""; $s = "guest@";
    if (isset($_COOKIE["EDITMODE"]))
	$h = "публичную версию";
    else {
	$s = htmlspecialchars("{$auth}@ ($editor)");
	$h = "личный оверлей";
	$p = "1";
    }
    $s = "<a href=\"SwitchTree.php?overlay=$p\" ".
	    "target=\"_top\" title=\"$s\"><b>$h</b></a>";
    echo infoRow(false, "Перключиться на:", $s);
}
unset($p, $s, $h);
$alt = false;

// Form body: recent documents
if (count($recent)) {
    echo grpRow("КЛЮЧЕВЫЕ ДОКУМЕНТЫ");
    foreach ($recent as &$entry) {
	$date = htmlspecialchars($entry["date"]);
	$nick = htmlspecialchars($entry["nick"]);
	$name = htmlspecialchars($entry["name"]);
	$nwin = $entry["w"];
	$text = htmlspecialchars($entry["text"]);
	$link = htmlspecialchars($entry["link"]);
	$title = htmlspecialchars($entry["title"]);
	if (!$title && $nwin)
	    $title = "Открыть в отдельной вкладке...";
	$href1 = "<a href=\"mailto:{$nick}@basealt.ru\" ".
		    "title=\"$name\"><b>$nick</b>@</a>";
	$href2 = "<a href=\"" . (isUrl($link) ? $link:
		    "GetFile.php?fpath=" . $link) . "\"";
	if ($nwin)
	    $href2 .= " target=\"_blank\"";
	$href2 .= " title=\"$title\">$text</a>";
	echo infoRow($alt, "{$date}{$empty}{$href1}", $href2);
	unset($date, $nick, $name, $nwin, $text, $link);
	unset($title, $href1, $href2);
	$alt = !$alt;
    }
    unset($recent, $entry);
    $alt = false;
}

// Form body: last updates
echo grpRow(htmlspecialchars("ПОСЛЕДНИЕ ИЗМЕНЕНИЯ (на ".
		date("$dateFormat H:i", $statinfo["updated"]).")"));
foreach ($git as &$entry) {
    $date = htmlspecialchars($entry[0]);
    $name = htmlspecialchars($entry[1]);
    $mail = htmlspecialchars($entry[2]);
    $text = htmlspecialchars($entry[3]);
    $nick = preg_replace("/@.*$/", "", $mail);
    $href = "<a href=\"mailto:$mail\" title=\"$name\"><b>$nick</b>@</a>";
    echo infoRow($alt, "{$date}{$empty}{$href}", $text);
    unset($date, $name, $mail, $text, $nick, $href);
    $alt = !$alt;
}
unset($git, $entry);
$alt = false;

// Form body: statistic
echo grpRow("ОБЩАЯ ИНФОРМАЦИЯ");
foreach ($dic as $key => $title) {
    echo infoRow($alt, "$title:", "<b>".strval($statinfo[$key])."</b>");
    $alt = !$alt;
}

// Form footer
echo makeFooter("MainView");
unset($statinfo, $dic, $key, $title, $alt);

?>