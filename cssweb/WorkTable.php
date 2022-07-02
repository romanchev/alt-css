<?php

// Authentication
$LIBDIR = @dirname(@realpath(__FILE__))."/lib";
require_once("$LIBDIR/web.php");

// Normalize filter names and values
//
$searchText      = httpGetStr("t");
$selectDistro    = httpGetStr("d");
$selectVendor    = httpGetStr("v");
$selectProduct   = httpGetStr("p");
$selectResult    = httpGetInt("r");
$selectList      = httpGetInt("l");
$selectArch      = httpGetInt("a");
$selectWhoChk    = httpGetInt("w");
$selectGroup     = httpGetInt("g");
$selectView      = httpGetInt("s");
$mergeMajors     = httpGetBool("m");
$showOldVersions = httpGetBool("O");
$showOldIncompat = httpGetBool("I");
$showOldTesting  = httpGetBool("T");

// Constants
//
define("ONLY_COMPAT",	1);
define("ONLY_WITHCERT",	2);
define("ONLY_INCOMPAT",	3);
define("ONLY_INQUEUE",	4);
//
define("ONLY_LIST0",	1);
define("ONLY_LIST1",	2);
define("ONLY_LIST2",	3);
//
define("ONLY_IX86",	1);
define("ONLY_NOX86",	2);
define("ONLY_ELBRUS",	3);
define("ONLY_ARCH",	4);
//
define("ONLY_WE_CHK",	1);
define("ONLY_THEY_CHK",	2);
define("ONLY_BOTH_CHK",	3);
//
define("ONLY_DESKTOP",	1);
define("ONLY_SERVER",	2);
define("ONLY_WS8SP",	3);
define("ONLY_SR8SP",	4);
define("ONLY_SM10",	5);
define("ONLY_WS10",	6);
define("ONLY_WK10",	7);
define("ONLY_ED10",	8);
define("ONLY_SR10",	9);
define("ONLY_SV10",	10);
define("ONLY_WS9",	11);
define("ONLY_WK9",	12);
define("ONLY_ED9",	13);
define("ONLY_SR9",	14);
define("ONLY_SV9",	15);
define("ONLY_DISTRO",	16);
//
define("ONLY_SOFTWARE",	1);
define("ONLY_HARDWARE",	2);
define("ONLY_GROUP",	3);
//
define("VendorIDX",     0);
define("ProductIDX",    1);
define("CompInfoIDX",   2);
define("StartIDX",      3);
define("FinishIDX",     4);
define("ResultIDX",     5);
define("ListIDX",       6);
define("GroupIDX",      7);
define("StateIDX",      8);
define("ActVersIDX",    9);
define("OldVersIDX",   10);
define("DistrosIDX",   11);
define("ArchesIDX",    12);
//
define("GROUP_BY_DEFAULT", 0);
define("GROUP_BY_VENDORS", 1);
define("GROUP_BY_GROUPS",  2);
define("GROUP_BY_ARCHES",  3);
define("GROUP_BY_DISTROS", 4);


function listVersions($sep, &$act, &$old) {
    global $empty, $mergeMajors, $showOldVersions;

    if (!count($act) && !count($old))
	return $empty;
    $out = $maj = array();

    if (count($act)) {
	foreach ($act as $release => $version) {
	    if (preg_match("/^v\d+(\.\d+)?$/", $release))
		$release = substr($release, 1);
	    if (preg_match("/^v\d+(\.\d+)?$/", $version))
		$version = substr($version, 1);
	    $release = str_replace("_", " ", $release);
	    $version = str_replace("_", " ", $version);

	    if (!$mergeMajors)
		$out[] = htmlspecialchars($release);
	    elseif (!isset($maj[$version])) {
		if (($release != $version) || !preg_match("/^\d+(\.\d+)?$/", $version)) {
		    $out[] = htmlspecialchars($version);
		    $maj[$version] = true;
		}
	    }
	}
    }

    if ($showOldVersions && count($old)) {
	foreach ($old as $release => $version) {
	    if (preg_match("/^v\d+(\.\d+)?$/", $release))
		$release = substr($release, 1);
	    if (preg_match("/^v\d+(\.\d+)?$/", $version))
		$version = substr($version, 1);
	    $release = str_replace("_", " ", $release);
	    $version = str_replace("_", " ", $version);

	    if (!$mergeMajors)
		$out[] = "<span style=\"color:gray;\">".
			 htmlspecialchars($release)."</span>";
	    elseif (!isset($maj[$version])) {
		if (($release != $version) || !preg_match("/^\d+(\.\d+)?$/", $version)) {
		    $out[] = "<span style=\"color:gray;\">".
			     htmlspecialchars($version)."</span>";
		    $maj[$version] = true;
		}
	    }
	}
    }

    if (!count($act))
	return $empty;
    return implode($sep, $out);
}

function newgrp($content) {
    global $colspan;
    return "<tr><td colspan=\"$colspan\" class=\"group\">{$content}</td></tr>\n";
}

function compare_products(&$a, &$b) {
    if ($a[ProductIDX] != $b[ProductIDX])
	return ($a[ProductIDX] < $b[ProductIDX]) ? -1: 1;
    return strcmp($a[CompInfoIDX], $b[CompInfoIDX]);
}

function compare_vendors(&$a, &$b) {
    if ($a[VendorIDX] != $b[VendorIDX])
	return ($a[VendorIDX] < $b[VendorIDX]) ? -1: 1;
    return compare_products($a, $b);
}

function compare_groups(&$a, &$b) {
    if ($a[GroupIDX] != $b[GroupIDX])
	return ($a[GroupIDX] < $b[GroupIDX]) ? -1: 1;
    return compare_products($a, $b);
}

function compare_compat(&$a, &$b) {
    if ($a[ResultIDX] != $b[ResultIDX]) {
	if ($a[ResultIDX] == "NO")
	    $l = 2;
	elseif ($a[ResultIDX] == "INFO")
	    $l = 1;
	else
	    $l = 0;
	if ($b[ResultIDX] == "NO")
	    $r = 2;
	elseif ($b[ResultIDX] == "INFO")
	    $r = 1;
	else
	    $r = 0;
	if ($l != $r)
	    return ($l < $r) ? -1: 1;
	unset($l, $r);
    }
    if ($a[ListIDX] != $b[ListIDX])
	return ($a[ListIDX] < $b[ListIDX]) ? -1: 1;
    return compare_products($a, $b);
}


// Load supplimental
//
$q_index = cache2arr("abc");
$catids  = cache2arr("catids");
$catfull = cache2arr("catfull");
$distids = cache2arr("distids");
$vendids = cache2arr("vendids");
$prodids = cache2arr("prodids");
$arches  = array_keys($hw_platforms);
$archrev = array_flip($arches);
$catrev  = array_flip($catids);

$citable  = array();
$onlyArch = $onlyDistro = false;
$onlyGroupMin = $onlyGroupMax = false;

// Use additional archives
$showOld  = ($showOldVersions || $showOldIncompat || $showOldTesting);

// Rebuild arrays
$distros  = array();
$vendors  = array();
$products = array();
$list = cache2arr("vendors");
foreach ($list as &$record)
    $vendors[] = &$record[0];
$list = cache2arr("products");
foreach ($list as &$record)
    $products[] = &$record[2];
$list = cache2arr("distros");
foreach ($list as $record)
    $distros[] = &$record[0];
unset($list, $record);

if ($selectArch < 0)
    $selectArch = 0;
elseif ($selectArch >= ONLY_ARCH) {
    if ($selectArch - ONLY_ARCH < count($arches))
	$onlyArch = $arches[$selectArch - ONLY_ARCH];
    else
	$selectArch = 0;
}

if ($selectDistro) {
    if (preg_match("/^\d+$/", $selectDistro))
	$selectDistro = @intval($selectDistro);
    else {
	$selectDistro = str_replace(".", "/", $selectDistro);
	if (!isset($distids[$selectDistro]))
	    $selectDistro = 0;
	else {
	    $onlyDistro = $selectDistro;
	    $selectDistro = $distids[$selectDistro] + ONLY_DISTRO;
	}
    }
}

if ($selectGroup < 0)
    $selectGroup = 0;
elseif ($selectGroup >= ONLY_GROUP) {
    if ($selectGroup - ONLY_GROUP >= count($catids))
	$selectGroup = 0;
    else {
	$dummy = $catids[$selectGroup - ONLY_GROUP];
	if (!preg_match("/\\/$/", $dummy))
	    $onlyGroupMin = $onlyGroupMax = $selectGroup - ONLY_GROUP;
	else {
	    $last_index = count($catids) - 1;
	    $onlyGroupMin = mb_strlen($dummy);
	    $onlyGroupMax = $selectGroup - ONLY_GROUP;
	    while ($onlyGroupMax < $last_index)
		if (mb_substr($catids[++$onlyGroupMax], 0, $onlyGroupMin) != $dummy) {
		    $onlyGroupMax --;
		    break;
		}
	    $onlyGroupMin = $selectGroup - ONLY_GROUP;
	    unset($last_index);
	}
	unset($dummy);
    }
}

if ($selectVendor) {
    if (!isset($vendids[$selectVendor]))
	$selectVendor = $selectProduct = "";
    elseif ($selectProduct) {
	if (!isset($prodids["$selectVendor:$selectProduct"]))
	    $selectProduct = "";
    }
}

if ($searchText) {
    $searchText = mb_strtolower($searchText);
    $searchText = str_replace("ё", "е", $searchText);
    $searchText = preg_replace("/[[:space:]]+/", " ", $searchText);
}

// Filter input data
foreach ($q_index as $index) {
    $cisrc = cache2arr("c{$index}");
    $strings = false;
    if ($searchText)
	$strings = cache2arr("s{$index}");
    if ($showOld) {
	$citmp = cache2arr("C{$index}");
	if (count($citmp))
	    $cisrc = array_merge($cisrc, $citmp);
	if ($searchText) {
	    $citmp = cache2arr("S{$index}");
	    if (count($citmp))
		$strings = array_merge($strings, $citmp);
	}
	unset($citmp);
    }

    foreach ($cisrc as &$ci) {
	if ($showOld && !count($ci["vers"][0]) && count($ci["vers"][1])) {
	    if (!$showOldTesting && ($ci["type"] == "INFO"))
		continue;
	    if (!$showOldIncompat && ($ci["type"] == "NO"))
		continue;
	    if (!$showOldVersions)
		continue;
	}

	if ($selectList) {
	    switch ($selectList) {
		case ONLY_LIST0:
		    if ($ci["list"] !== 0)
			continue 2;
		    break;
		case ONLY_LIST1:
		    if ($ci["list"] !== 1)
			continue 2;
		    break;
		case ONLY_LIST2:
		    if ($ci["list"] !== 2)
			continue 2;
		    break;
		default:
		    $selectList = 0;
		    break;
	    }
	}

	if ($selectWhoChk) {
	    switch ($selectWhoChk) {
		case ONLY_WE_CHK:
		    if ($ci["check"] !== "We")
			continue 2;
		    break;
		case ONLY_THEY_CHK:
		    if ($ci["check"] !== "They")
			continue 2;
		    break;
		case ONLY_BOTH_CHK:
		    if ($ci["check"] !== "All")
			continue 2;
		    break;
		default:
		    $selectWhoChk = 0;
		    break;
	    }
	}

	if ($selectResult) {
	    switch ($selectResult) {
		case ONLY_COMPAT:
		    if (($ci["type"] !== "CERT") && ($ci["type"] !== "YES"))
			continue 2;
		    break;
		case ONLY_WITHCERT:
		    if ($ci["type"] !== "CERT")
			continue 2;
		    break;
		case ONLY_INCOMPAT:
		    if ($ci["type"] !== "NO")
			continue 2;
		    break;
		case ONLY_INQUEUE:
		    if ($ci["type"] !== "INFO")
			continue 2;
		    break;
		default:
		    $selectResult = 0;
		    break;
	    }
	}

	if ($selectVendor) {
	    if ($ci["vID"] != $selectVendor)
		continue;
	    if ($selectProduct) {
		if ($ci["pID"] != $selectProduct)
		    continue;
	    }
	}

	if ($selectGroup) {
	    switch ($selectGroup) {
		case ONLY_SOFTWARE:
		    if (mb_substr($ci["pgrp"], 0, 3) !== "ПО/")
			continue 2;
		    break;
		case ONLY_HARDWARE:
		    if (mb_substr($ci["pgrp"], 0, 3) === "ПО/")
			continue 2;
		    break;
		default:
		    if ($onlyGroupMin === false)
			$selectGroup = 0;
		    else {
			if (isset($catrev[$ci["pgrp"]]))
			    $grpidx = $catrev[$ci["pgrp"]];
			elseif (isset($catrev[$ci["pgrp"]."/"]))
			    $grpidx = $catrev[$ci["pgrp"]."/"];
			else
			    $grpidx = -1;
			if (($grpidx < $onlyGroupMin) ||
			    ($grpidx > $onlyGroupMax))
			{
			    unset($grpidx);
			    continue 2;
			}
			unset($grpidx);
		    }
		    break;
	    }
	}

	if ($selectArch) {
	    switch ($selectArch) {
		case ONLY_IX86:
		    if (!in_array("x86_64", $ci["arch"], true) &&
			!in_array("i586",   $ci["arch"], true))
		    {
			continue 2;
		    }
		    break;
		case ONLY_NOX86:
		    if (in_array("x86_64", $ci["arch"], true) ||
			in_array("i586",   $ci["arch"], true))
		    {
			continue 2;
		    }
		    break;
		case ONLY_ELBRUS:
		    if (!in_array("e2kv5", $ci["arch"], true) &&
			!in_array("e2kv4", $ci["arch"], true) &&
			!in_array("e2k",   $ci["arch"], true))
		    {
			continue 2;
		    }
		    break;
		default:
		    if (!$onlyArch)
			$selectArch = 0;
		    elseif (!in_array($onlyArch, $ci["arch"], true))
			continue 2;
		    break;
	    }
	}

	if ($selectDistro) {
	    $found = $regex = false;

	    switch ($selectDistro) {
		case ONLY_DESKTOP: $regex = "/^(ws|wk|ed|sm)/";	break;
		case ONLY_SERVER:  $regex = "/^(sr|sv|ed)/";	break;
		case ONLY_WS8SP:   $regex = "/^ws8[1-9]?sp/";	break;
		case ONLY_SR8SP:   $regex = "/^sr8[1-9]?sp/";	break;
		case ONLY_SM10:	   $regex = "/^sm10/";		break;
		case ONLY_WS10:	   $regex = "/^ws10/";		break;
		case ONLY_WK10:	   $regex = "/^wk10/";		break;
		case ONLY_ED10:	   $regex = "/^ed10/";		break;
		case ONLY_SR10:	   $regex = "/^sr10/";		break;
		case ONLY_SV10:	   $regex = "/^sv10/";		break;
		case ONLY_WS9:	   $regex = "/^ws9/";		break;
		case ONLY_WK9:	   $regex = "/^wk9/";		break;
		case ONLY_ED9:	   $regex = "/^ed9/";		break;
		case ONLY_SR9:	   $regex = "/^sr9/";		break;
		case ONLY_SV9:	   $regex = "/^sv9/";		break;
	    }

	    if ($regex) {
		foreach ($ci["dist"] as &$dist)
		    if (preg_match($regex, $dist)) {
			$found = true;
			break;
		    }
		unset($dist);
	    }
	    elseif (!$onlyDistro) {
		$selectDistro = 0;
		$found = true;
	    }
	    else {
		foreach ($ci["dist"] as &$dist)
		    if ($dist == $onlyDistro) {
			$found = true;
			break;
		    }
		unset($dist);
	    }

	    if (!$found) {
		unset($regex, $found);
		continue;
	    }
	    unset($regex, $found);
	}

	if ($searchText) {
	    $found = false;
	    $tmpid = $ci["vID"];
	    $ids   = array($tmpid);
	    $tmpid.= ":".$ci["pID"];
	    $ids[] = $tmpid;
	    $ids[] = $tmpid.":".$ci["cID"];

	    foreach ($ci["vers"][0] as $release => $version) {
		if (!in_array($tmpid.":".$version, $ids))
		    $ids[] = $tmpid.":".$version;
	    }
	    foreach ($ci["vers"][1] as $release => $version) {
		if (!in_array($tmpid.":".$version, $ids))
		    $ids[] = $tmpid.":".$version;
	    }
	    unset($release, $version);

	    foreach ($ids as $tmpid) {
		if ($found)
		    break;
		if (isset($strings[$tmpid])) {
		    $list = &$strings[$tmpid];
		    foreach ($list as &$text)
			if (mb_strstr($text, $searchText) !== false) {
			    $found = true;
			    break;
			}
		    unset($list, $text);
		}
	    }
	    unset($ids, $tmpid);

	    if (!$found) {
		unset($found);
		continue;
	    }
	    unset($found);
	}

	if (isset($catids[$ci["pgrp"]]))
	    $grpidx = $catids[$ci["pgrp"]];
	elseif (isset($catids[$ci["pgrp"]."/"]))
	    $grpidx = $catids[$ci["pgrp"]."/"];
	else
	    $grpidx = -1;
	$record = array(
	    $vendids[$ci["vID"]],
	    $prodids[$ci["vID"].":".$ci["pID"]],
	    $ci["cID"],
	    $ci["start"],
	    $ci["finish"],
	    $ci["type"],
	    $ci["list"],
	    $grpidx,
	    $ci["state"],
	    $ci["vers"][0],
	    $ci["vers"][1],
	    $ci["dist"],
	    $ci["arch"],
	);
	$citable[] =& $record;
	unset($record, $grpidx);
    }
    unset($cisrc, $strings, $ci);
}

$vendids = array_flip($vendids);
$prodids = array_flip($prodids);

$showListColumn = ($selectView != GROUP_BY_DEFAULT);
$showArchColumn = ($selectView != GROUP_BY_ARCHES);
$showDistColumn = ($selectView != GROUP_BY_DISTROS);

// Cleanup
unset($searchText);
unset($selectResult);
unset($selectList);
unset($selectArch);
unset($selectWhoChk);
unset($selectDistro);
unset($selectGroup);
unset($selectVendor);
unset($selectProduct);
unset($q_index, $index);

// Sort results
switch ($selectView) {
    case GROUP_BY_VENDORS:
	usort($citable, "compare_vendors");
	break;

    case GROUP_BY_GROUPS:
	usort($citable, "compare_groups");
	break;

    case GROUP_BY_ARCHES:
	$by_arches = array();
	foreach ($citable as &$ci)
	    foreach ($ci[ArchesIDX] as &$arch) {
		if ($onlyArch && ($arch != $onlyArch))
		    continue;
		if (!isset($by_arches[$arch]))
		    $by_arches[$arch] = array();
		$by_arches[$arch][] =& $ci;
	    }
	unset($ci, $arch);
	$citable = array();
	$arch_groups = array_keys($by_arches);
	sort($arch_groups);
	foreach ($arch_groups as &$arch) {
	    usort($by_arches[$arch], "compare_vendors");
	    foreach ($by_arches[$arch] as $ci) {
		$ci[GroupIDX] = $archrev[$arch];
		$citable[] = $ci;
	    }
	}
	unset($by_arches, $arch_groups, $arch, $ci);
	break;

    case GROUP_BY_DISTROS:
	$by_distros = array();
	foreach ($citable as &$ci)
	    foreach ($ci[DistrosIDX] as &$dist) {
		if ($onlyDistro && ($dist != $onlyDistro))
		    continue;
		$dname = $distros[$distids[$dist]];
		if (!isset($by_distros[$dname]))
		    $by_distros[$dname] = array();
		$by_distros[$dname][] =& $ci;
		unset($dname);
	    }
	unset($ci, $dist);
	$citable = array();
	$dist_groups = array_keys($by_distros);
	sort($dist_groups);
	foreach ($dist_groups as &$dname) {
	    usort($by_distros[$dname], "compare_vendors");
	    foreach ($by_distros[$dname] as $ci) {
		$ci[GroupIDX] = $dname;
		$citable[] = $ci;
	    }
	}
	unset($by_distros, $dname, $ci);
	break;

    default:
	$selectView = GROUP_BY_DEFAULT;
	usort($citable, "compare_compat");
	break;
}

// Count visible columns
//
$colspan = 4;
if ($showListColumn)
    $colspan ++;
if ($showArchColumn)
    $colspan ++;
if ($showDistColumn)
    $colspan ++;
$counter = 0;

// Start output data
echo makeHeader("WorkTable");
$no   = "<a href=\"EntryFrame.php\" title=\"Вернуться в начало\">№</a>";
$prod = "<a href=\"WorkTable.php?s=1\" target=\"_self\" ".
	"title=\"Сгрупировать по партнёрам и продуктам\">ПРОДУКТ</a>";
$arch = "<a href=\"WorkTable.php?s=3\" target=\"_self\" ".
	"title=\"Сгруппировать по платформам\">ПЛАТФОРМЫ</a>";
$dist = "<a href=\"WorkTable.php?s=4\" target=\"_self\" ".
	"title=\"Сгруппировать по дистрибутивам\">ДИСТРИБУТИВЫ</a>";
$comp = "<a href=\"WorkTable.php\" target=\"_self\" ".
	"title=\"Сгруппировать по умолчанию\">СОВМ?</a>";
$output = "<thead>\n<tr>\n".
	"<th class=\"no\">{$no}</th>\n".
	"<th class=\"prod\">{$prod}</th>\n".
	($showListColumn ? "<th class=\"list\">СП#</th>\n": "").
	($showArchColumn ? "<th class=\"arch\">{$arch}</th>\n": "").
	"<th class=\"vers\">ВЕРСИИ</th>\n".
	($showDistColumn ? "<th class=\"dist\">{$dist}</th>\n": "").
	"<th class=\"result\">{$comp}</th>\n".
	"</tr>\n</thead><tbody>\n";
echo $output;
if (!count($citable))
    echo newgrp("ДАННЫХ ПО ЭТОМУ ЗАПРОСУ НЕ НАЙДЕНО!");
unset($no, $prod, $arch, $dist, $comp);
$old_group = "<EMPTY>";

foreach ($citable as &$ci) {
    if ($selectView == GROUP_BY_ARCHES) {
	$output = $ci[GroupIDX];
	if ($old_group !== $output) {
	    echo newgrp(htmlspecialchars("Платформа: ".$arches[$output]));
	    $old_group = $output;
	}
    }
    elseif ($selectView == GROUP_BY_DISTROS) {
	$output = $ci[GroupIDX];
	if ($old_group !== $output) {
	    echo newgrp(htmlspecialchars("Дистрибутив: ".$output));
	    $old_group = $output;
	}
    }
    elseif ($selectView == GROUP_BY_GROUPS) {
	$output = $ci[GroupIDX];
	if ($old_group !== $output) {
	    echo newgrp(htmlspecialchars($catfull[$output][FCAT_FullIDX]));
	    $old_group = $output;
	}
    }
    elseif ($selectView == GROUP_BY_DEFAULT) {
	$output = strval($ci[ListIDX]).", ";
	if ($ci[ResultIDX] == "NO")
	    $output .= "несовместимы";
	elseif ($ci[ResultIDX] == "INFO")
	    $output .= "пока неизвестно";
	else
	    $output .= "совместимы";
	if ($old_group !== $output) {
	    echo newgrp(htmlspecialchars("Список #".$output));
	    $old_group = $output;
	}
    }

    /* Real ID's and link */
    $cID = $ci[CompInfoIDX];
    $tID = $prodids[$ci[ProductIDX]];
    list($vID, $pID) = explode(":", $tID, 2);
    $ciref = "CompInfoView.php?".htmlentities(
		"VendorID=".urlencode($vID)."&".
		"ProductID=".urlencode($pID)."&".
		"CompinfoID=".urlencode($cID)
    );

    /* Counter */
    $start = $ci[StartIDX];
    $finish = $ci[FinishIDX] ? $ci[FinishIDX]: mktime(0, 0, 0);
    $duration = @intval(abs($finish - $start) / (24 * 60 * 60));
    $d2 = $duration % 100;
    $dummy = $duration % 10;
    if (($d2 > 4) && ($d2 < 21))
	$duration = "$duration дней";
    elseif ($dummy == 1)
	$duration = "$duration день";
    elseif (($dummy > 1) && ($dummy < 5))
	$duration = "$duration дня";
    else
	$duration = "$duration дней";
    $counter ++;
    $no = "<a href=\"$ciref\" title=\"".
		date($dateFormat, $start).
		" ... ".
		date($dateFormat, $finish).
		" ($duration), запись #".
		@intval(mb_substr($cID, 6, 2)).
		"\">$counter</a>.";
    unset($start, $finish, $duration, $d2, $dummy);

    /* PRODUCT & VENDOR */
    $plink = "ProductView.php?".htmlentities(
		"VendorID=".urlencode($vID).
		"&ProductID=".urlencode($pID));
    $pname = htmlspecialchars($products[$ci[ProductIDX]]);
    if ($selectView == GROUP_BY_VENDORS) {
	if ($old_group !== $ci[VendorIDX]) {
	    $old_group = $ci[VendorIDX];
	    echo newgrp(htmlspecialchars($vendors[$old_group]));
	}
	$prod  = "<a href=\"$plink\">$pname</a>";
    }
    else {
	$vlink = "VendorView.php?".htmlentities(
		    "VendorID=".urlencode($vID));
	$vname = htmlspecialchars($vendors[$ci[VendorIDX]]);
	$prod  = "<a href=\"$plink\"><b>$pname</b></a><br/>".
		 "<a href=\"$vlink\" class=\"vendor\">$vname</a>";
	unset($vlink, $vname);
    }
    unset($plink, $pname);

    /* LIST */
    $list = $ci[ListIDX];
    if (!in_array($list, array(0, 1, 2), true))
	$list = 6;
    $list = "<img src=\"icons/$list.png\" alt=\"$list\" title=\"Список $list\" />";

    /* PLATFORMS & VERSIONS */
    $arch = listPlatforms(",<br/>", $ci[ArchesIDX], $onlyArch);
    $vers = listVersions(",<br/>", $ci[ActVersIDX], $ci[OldVersIDX]);

    /* DISTROS */
    $dist = array();
    foreach ($ci[DistrosIDX] as &$distID) {
	if ($onlyDistro && ($distID != $onlyDistro))
	    continue;
	if (isset($distids[$distID]))
	    $dist[htmlspecialchars( $distros[$distids[$distID]] )] = true;
	else
	    $dist[htmlErr($distID)] = true;
    }
    $dist = array_keys($dist);
    sort($dist);
    $dist = implode(",<br/>", $dist);
    unset($distID);

    /* COMPATIBILITY */
    $alt = htmlspecialchars($ci[ResultIDX]);
    switch ($alt) {
	case "YES":
	    $defstate = "Ждём проверки и подписания с их стороны";
	    $pic = "yes.png";
	    break;
	case "NO":
	    $defstate = "Проверка завершена, несовместимы";
	    $pic = "no.png";
	    break;
	case "CERT":
	    $defstate = "Совместимы, сертификат оформлен";
	    $pic = "cert.jpg";
	    break;
	default:
	    $defstate = "В очереди на тестирование";
	    $pic = "info.png";
    }
    $state = htmlspecialchars($ci[StateIDX] ? $ci[StateIDX]: $defstate);
    $comp = "<a href=\"$ciref\" title=\"$state\">".
	    "<img src=\"icons/$pic\" alt=\"$alt\" /></a>";
    unset($alt, $pic, $state, $defstate);

    $output = "<tr>".
	"<td class=\"no\">$no</td>".
	"<td class=\"prod\">$prod</td>".
	($showListColumn ? "<td class=\"list\">$list</td>": "").
	($showArchColumn ? "<td class=\"arch\">$arch</td>": "").
	"<td class=\"vers\">$vers</td>".
	($showDistColumn ? "<td class=\"dist\">$dist</td>": "").
	"<td class=\"result\">$comp</td></tr>\n";
    echo $output;

    unset($cID, $tID, $vID, $pID, $ciref);
    unset($no, $prod, $list, $arch, $vers, $dist, $comp);
}

echo "</tbody>\n";
echo makeFooter("WorkTable");

// Cleanup
unset($citable, $ci, $counter, $output, $vendors, $products);
unset($distros, $old_group, $LIBDIR, $dateFormat, $selectView);
unset($catfull, $catids, $arches, $distids, $vendids, $prodids);
unset($showListColumn, $showArchColumn, $showDistColumn, $archrev);
unset($showOldVersions, $showOldIncompat, $showOldTesting, $catrev);

?>