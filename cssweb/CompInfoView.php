<?php

// Authentication
$LIBDIR = @dirname(@realpath(__FILE__))."/lib";
require_once("$LIBDIR/web.php");
require_once("$LIBDIR/render.php");

$SUITES = array (
    "Universal" => "Универсальный продукт",
    "Desktop"   => "Только для настольных ОС",
    "Server"    => "Только для серверных ОС",
    "NoExpand"  => "Ограниченного применения"
);

$CHK_BY = array (
    "All"	=> "Все",
    "We"	=> "Мы",
    "They"	=> "Они"
);


function record_field_renderer($data, $model, $alt) {
    global $empty;

    $caption = "<b>".str_replace(" ", $empty,
		htmlspecialchars($model["Caption"]))."</b>";
    $output = "<b>".htmlspecialchars($data)."</b>";

    return infoRow($alt, $caption.":", $output);
}

function product_field_renderer($data, $model, $alt) {
    global $empty;

    $caption = "<b>".str_replace(" ", $empty,
		htmlspecialchars($model["Caption"]))."</b>";
    list($vID, $pID, $name) = explode(":", strval($data), 3);
    $output  = "<a href=\"ProductView.php?VendorID=".
		htmlentities(urlencode($vID)."&ProductID=".
		urlencode($pID))."\" title=\"Перейти к ".
		"карточке продукта...\"><b>$name</b></a>";
    return infoRow($alt, $caption.":", $output);
}

function iscompat_field_renderer($data, $model, $alt) {
    global $empty;

    $caption = str_replace(" ", $empty,
		htmlspecialchars($model["Caption"]));
    $output = "<span style=\"font-weight:900; color:dark";
    if ($data == "Yes")
	$output .= "green\">Да</span>";
    elseif (!$data || ($data == "No"))
	$output .= "red\">Нет</span>";
    else {
	$data = "GetFile.php?fpath=".htmlentities(urlencode($data));
	$output .= "green\">Да</span>, сертификат оформлен:<br/>".
		    "<a href=\"$data\" target=\"_blank\" ".
		    "title=\"Открыть с увеличением...\">".
		    "<img src=\"$data\" height=\"420\" ".
		    "alt=\"Сертификат\" /></a>";
    }

    return infoRow($alt, $caption.":", $output);
}

function products_field_renderer($data, $model, $alt) {
    global $empty;

    if (!is_array($data) || !count($data))
	return "";
    $output = grpRow(mb_strtoupper($model["Caption"]));

    foreach ($data as $vID => &$prods) {
	$href = "VendorView.php?VendorID=".
		htmlentities(urlencode($vID));
	$file = "Vendors/$vID/vendor.yml";
	$vend = loadYamlFile($file, "vendor");
	$vname = htmlId($vend, $vID);
	$output .=  "<tr><td colspan=\"2\" class=\"alt\">- <a href=\"$href\" ".
		    "title=\"Перейти к карточке партнёра...\">".
		    "<b>$vname</b></a>:</td></tr>\n";
	foreach ($prods as $pID) {
	    $href = "ProductView.php?VendorID=".
		    htmlentities(urlencode($vID).
		    "&ProductID=".urlencode($pID));
	    $file = "Vendors/$vID/$pID/product.yml";
	    $prod = loadYamlFile($file, "product");
	    $pname = htmlId($prod, $pID);
	    $cell = "<a href=\"$href\" title=\"Перейти к ".
		    "карточке продукта...\">$pname</a>";
	    $output .= infoRow(false, $empty, $cell);
	    unset($prod, $pname, $cell);
	}
	unset($href, $file, $vend, $vname, $pID);
    }
    unset($prods, $vID);

    return $output;
}

function depends_field_renderer($data, $model, $alt) {
    global $empty;

    if (!is_array($data) || !count($data))
	return "";
    $output = grpRow(mb_strtoupper($model["Caption"]));

    foreach ($data as $id => &$deps) {
	$output .=  "<tr><td colspan=\"2\" class=\"alt\">- <b>".
		    ($id != "ALL" ? htmlspecialchars($id):
		    "Для всех проверенных образов").
		    "</b>:</td></tr>\n";
	foreach ($deps as $pkgname) {
	    if (strstr($pkgname, " ") !== false)
		$cell = $pkgname;
	    else {
		$href = "https://packages.altlinux.org/ru/sisyphus/binary/";
		$cell = "<a href=\"$href".htmlentities(urlencode($pkgname))."/\" ".
			    "target=\"_blank\" title=\"Открыть описание пакета...\">".
			    htmlspecialchars($pkgname)."</a>";
		unset($href);
	    }
	    $output .= infoRow(false, $empty, $cell);
	}
	unset($pkgname);
    }
    unset($deps, $id);

    return $output;
}

function objlist_type_renderer($data, $model, $alt) {
    global $empty;

    if (!is_array($data) || !count($data))
	return "";
    $output = grpRow(mb_strtoupper($model["Caption"]));

    foreach ($data as $id => &$dsc) {
	$output .=  "<tr><td colspan=\"2\" class=\"alt\">- <b>".
		    ($id != "ALL" ? htmlspecialchars($id):
		    "Для всех проверенных образов").
		    "</b>:</td></tr>\n";
	foreach ($dsc as &$text)
	    $output .= infoRow(false, $empty, htmlspecialchars($text));
	unset($text);
    }
    unset($dsc, $id);

    return $output;
}

function newslinks_field_renderer($data, $model, $alt) {
    global $empty;

    if (!is_array($data) || !count($data))
	return "";
    $output = grpRow(mb_strtoupper($model["Caption"]));

    foreach ($data as $href) {
	$href = htmlspecialchars($href);
	if (!isUrl($href))
	    $href = "https://www.basealt.ru/about/news/archive/view/$href";
	$cell = "<a href=\"$href\" target=\"_blank\" ".
			"title=\"Открыть эту новость...\">$href</a>";
	$output .= infoRow(false, $empty, $cell);
    }

    return $output;
}


// Load data
$vID = httpGetId("VendorID");
$pID = httpGetId("ProductID");
$cID = httpGetId("CompinfoID");
$vendfile = "Vendors/$vID/vendor.yml";
$prodfile = "Vendors/$vID/$pID/product.yml";
$filename = "Vendors/$vID/$pID/CI/$cID.yml";
if (!$vID || !$pID || !$pID ||
    !file_exists("$DATADIR/$vendfile") ||
    !file_exists("$DATADIR/$prodfile") ||
    !file_exists("$DATADIR/$filename"))
{
    fatal("Where is valid VendorID, ProductID and/or CompinfoID?\n");
}
$vend = loadYamlFile($vendfile, "vendor");
$prod = loadYamlFile($prodfile, "product");
$data = loadYamlFile($filename, "compinfo", $model);
fillOptionalFields($data, $model["Fields"]);
if (isset($data["Products"]) && !count($data["Products"]))
    unset($data["Products"]);
if (isset($data["Depends"]) && !count($data["Depends"]))
    unset($data["Depends"]);
if (isset($data["Features"]) && !count($data["Features"]))
    unset($data["Features"]);
if (isset($data["Restricts"]) && !count($data["Restricts"]))
    unset($data["Restricts"]);
if (isset($data["NewsLinks"]) && !count($data["NewsLinks"]))
    unset($data["NewsLinks"]);
$versions = rebuildVersions($prod, "$vID/$pID/VERS");

// Convert data model
$vname = htmlId($vend, $vID);
$pname = htmlId($prod, $pID);
$data["Path"] = "/$filename";
$data["Vendor"] = "$vID:$vname";
$data["Product"] = "$vID:$pID:$pname";
$data["List"] = isset($prod["List"]) ? $prod["List"]: $vend["List"];
if (!isset($data["Suitable"]) || !$data["Suitable"]) {
    $data["Suitable"] = isset($prod["Suitable"]) ? $prod["Suitable"]:
				category2suitable($prod["Category"]);
}
if (isset( $SUITES[$data["Suitable"]] ))
    $data["Suitable"] = $SUITES[$data["Suitable"]];
if (isset( $CHK_BY[$data["Checked"]] ))
    $data["Checked"] = $CHK_BY[$data["Checked"]];
if (!isset($data["Notes"]))
    $data["Notes"] = "";
if (!isset($data["Status"]))
    $data["Status"] = "";
unset($vendfile, $prodfile, $vend, $prod);

// Record no
$year = substr($cID, 0, 2);
$mon  = substr($cID, 2, 2);
$day  = substr($cID, 4, 2);
$rec  = substr($cID, 6, 2);
$data["Record"] = "#".intval($rec)." от $day.$mon.20{$year}";
$data["Started"] = "$day.$mon.20{$year}";
unset($year, $mon, $day, $rec);

// Compatibility type
if (isset($data["IsCompat"]) && isset($data["CertLink"])
    && $data["IsCompat"] && $data["CertLink"])
{
    $title = "Сертификат совместимости";
    $type = "CERT";
}
elseif (isset($data["IsCompat"]) && !$data["IsCompat"]) {
    $title = "Информация о несовместимости";
    $type = "NO";
}
elseif (isset($data["IsCompat"]) && $data["IsCompat"]) {
    $title = "Информация о совместимости";
    $type = "YES";
}
else {
    $title = "Информация о тестировании";
    $type = "INFO";
}
$model["Caption"] = mb_strtoupper($title);
$title .= " с $pname";

// Certificate
if ($type == "CERT") {
    $link = $data["CertLink"];
    $year = substr($link, 6, 4);
    $mon  = substr($link, 3, 2);
    $day  = substr($link, 0, 2);
    $rec  = substr($link, 11, 2);
    $link = "Certs/{$year}{$mon}{$day}-{$rec}.jpg";
    if (!file_exists("$DATADIR/$link"))
	fatal("Broken certificate in the record $vID/$pID/$cID\n");
    $data["IsCompat"] = $link;
    if (!$data["Tested"])
	$data["Tested"] = "$day.$mon.$year";
    unset($year, $mon, $day, $rec, $link);
}
elseif (!isset($data["Tested"]) || !$data["Tested"])
    $data["Tested"] = "ещё не завершено";
unset($data["CertLink"]);

// Platforms
$data["Platforms"] = array();
foreach ($data["Distros"] as $dist) {
    $dist = substr(strstr($dist, "/"), 1);
    if (!isset($data["Platforms"][$dist]))
	$data["Platforms"][$dist] = true;
}
$data["Platforms"] = array_keys($data["Platforms"]);

// Versions
$nver = array(array(), array());
foreach ($data["Versions"] as &$ver) {
    if (isset($versions[0][$ver]))
	$nver[0][$ver] = $versions[0][$ver];
    elseif (isset($versions[1][$ver]))
	$nver[1][$ver] = $versions[1][$ver];
}
$data["Versions"] = $nver;
unset($nver, $ver);

// Form body
echo makeHeader("InfoView", "{TITLE}", $title);
buildOutputForm($data, $model, false);
echo makeFooter("InfoView");
unset($vname, $pname, $model, $data);

?>