<?php

function name_field_renderer($data, $model, $alt) {
    global $empty, $editor, $title, $logobase, $filter_args, $filter_word;

    $caption = str_replace(" ", $empty, htmlspecialchars($model["Caption"]));
    $output  = htmlspecialchars(strval($data));
    if (isset($model["Bold"])) {
	$caption = "<b>$caption</b>";
	$output  = "<b>$output</b>";
    }
    if (isset($title)) {
	if ($editor && isset($model["Paint"]))
	    $output = field_colorize($output);
	if (isset($logobase)) {
	    $logo = getLogo($logobase, $w, $h);
	    if ($logo) {
		if ($w)
		    $size = "width=\"$w\" ";
		elseif ($h)
		    $size = "height=\"$h\" ";
		else
		    $size = "";
		$output .= "<br/><img src=\"$logo\" alt=\"Логотип\" {$size}/>";
	    }
	    unset($logo, $w, $h, $size);
	}
	if (isset($filter_args) && isset($filter_word)) {
	    $output .= "<br/><br/><a href=\"".buildFilterQuery($filter_args)." \"".
		"target=\"main\" title=\"Отфильтровать по этому $filter_word...\">".
		"<img src=\"icons/filter.png\" alt=\"Фильтр\" /> фильтр</a>";
	}
    }

    return infoRow($alt, $caption.":", $output);
}

function vendor_field_renderer($data, $model, $alt) {
    global $empty;

    $output  = strval($data);
    $caption = str_replace(" ", $empty,
		htmlspecialchars($model["Caption"]));
    list($id, $name) = explode(":", $data, 2);
    $output  = "<a href=\"VendorView.php?VendorID=".
		htmlentities(urlencode($id)).
		"\" title=\"Перейти к карточке партнёра...\">$name</a>";
    return infoRow($alt, $caption.":", $output);
}

function list_field_renderer($data, $model, $alt) {
    global $empty;

    $output  = strval($data);
    $caption = str_replace(" ", $empty,
		htmlspecialchars($model["Caption"]));
    if (!in_array($output, array('0', '1', '2'), true))
	$output = '6';
    $output = "<img src=\"icons/{$output}.png\" ".
		"alt=\"$output\" title=\"Список {$output}\" />";
    return infoRow($alt, $caption.":", $output);
}

function platforms_field_renderer($data, $model, $alt) {
    $caption = str_replace(" ", $GLOBALS["empty"],
		htmlspecialchars($model["Caption"]));
    $output = empty($data) ? $empty: listPlatforms(", ", $data);
    return infoRow($alt, $caption.":", $output);
}

function mincomreg_field_renderer($data, $model, $alt) {
    global $empty;

    $caption = str_replace(" ", $empty,
		htmlspecialchars($model["Caption"]));
    $output = (!$data ? $empty:
		"<a href=\"https://reestr.digital.gov.ru/reestr/".
		htmlspecialchars($data)."/\" target=\"_blank\" ".
		"title=\"Открыть запись в реестре Минсвязи...\">#".
		htmlspecialchars($data)."</a>");
    return infoRow($alt, $caption.":", $output);
}

function verslist_type_renderer($data, $model, $alt) {
    $caption = str_replace(" ", $GLOBALS["empty"],
		htmlspecialchars($model["Caption"]));
    if (!is_array($data) || !count($data[0]))
	return infoRow($alt, $caption.":", "");
    $majors = array();
    foreach ($data[0] as $release => $ver) {
	if (preg_match("/^v\d+(\.\d+)?$/", $release))
	    $release = substr($release, 1);
	if (preg_match("/^v\d+(\.\d+)?$/", $ver))
	    $ver = substr($ver, 1);
	$release = str_replace("_", " ", $release);
	$ver = str_replace("_", " ", $ver);
	if (!isset($majors[$ver]))
	    $majors[$ver] = array();
	if (($ver != $release) || !preg_match("/^\d+(\.\d+)?$/", $ver))
	    $majors[$ver][] = $release;
    }
    $output = "";
    foreach ($majors as $ver => &$dsc) {
	if ($output)
	    $output .= ", ";
	$output .= htmlspecialchars($ver);
	if (!count($dsc))
	    continue;
	if ((count($dsc) == 1) && ($dsc[0] === $ver))
	    continue;
	$output .= htmlspecialchars(" (".implode(", ", $dsc).")");
    }

    if (count($data[1])) {
	$majors = array();
	foreach ($data[1] as $release => $ver) {
	    if (preg_match("/^v\d+(\.\d+)?$/", $release))
		$release = substr($release, 1);
	    if (preg_match("/^v\d+(\.\d+)?$/", $ver))
		$ver = substr($ver, 1);
	    $release = str_replace("_", " ", $release);
	    $ver = str_replace("_", " ", $ver);
	    if (!isset($majors[$ver]))
		$majors[$ver] = array();
	    if (($ver != $release) || !preg_match("/^\d+(\.\d+)?$/", $ver))
		$majors[$ver][] = $release;
	}
	foreach ($majors as $ver => &$dsc) {
	    if ($output)
		$output .= ", ";
	    $output .= "<span style=\"color:gray\">";
	    $output .= htmlspecialchars($ver);
	    if (count($dsc))
		$output .= htmlspecialchars(" (".implode(", ", $dsc).")");
	    $output .= "</span>";
	}
    }

    return infoRow($alt, $caption.":", $output);
}

function issues_type_renderer($data, $model, $alt) {
    global $empty;

    $output = "";
    $caption = str_replace(" ", $empty, htmlspecialchars($model["Caption"]));
    foreach ($data as $taskId) {
	if ($output)
	    $output .= ", ";
	$taskId  = htmlspecialchars($taskId);
	$output .= "<a href=\"https://my.basealt.space/issues/$taskId\"".
		    " target=\"_blank\" title=\"Открыть задачу...\"><b>#$taskId</b></a>";
    }

    return infoRow($alt, $caption.":", $output);
}

function distlist_type_renderer($data, $model, $alt) {
    global $empty, $distros, $distids;

    $output = "";
    $caption = str_replace(" ", $empty,
		htmlspecialchars($model["Caption"]));
    if (!isset($distros))
	$distros = cache2arr("distros");
    if (!isset($distids))
	$distids = cache2arr("distids");

    foreach ($data as $dID) {
	if ($output)
	    $output .= "<br/>";
	if (!isset($distids[$dID]))
	    $output .= htmlerr($dID);
	else {
	    $xID  = $distids[$dID];
	    $href = "DistroView.php?DistroID=".
		    htmlentities(urlencode($dID));
	    $desc = $distros[$xID][DIST_NameIDX]." (".
		    $distros[$xID][DIST_DateIDX].") /".
		    $distros[$xID][DIST_ArchIDX];
	    $output .= "<a href=\"$href\" title=\"Перейти ".
			"к описанию этого образа...\">".
			htmlspecialchars($desc)."</a>";
	    unset($href, $desc, $xID);
	}
    }

    return infoRow($alt, $caption.":", $output);
}

?>