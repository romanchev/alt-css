<?php

@ini_set('serialize_precision', 2);
require_once("$LIBDIR/load.php");


function letter2idx($letter) {
    global $f_letters, $i_letters;

    if (!isset( $f_letters )) {
	$f_letters = "0123456789abcdefghijklmnopqrstuvwxyz".
		     "АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ";
    }

    if (!isset( $i_letters )) {
	$i_letters = array();
	$n = mb_strlen($f_letters);
	for ($i=0; $i < $n; $i++)
	    $i_letters[mb_substr($f_letters, $i, 1)] = sprintf("%02d", $i);
	unset($n, $i);
    }

    return $i_letters[$letter];
}

function idx2letter($idx) {
    $dummy = letter2idx("0");
    return mb_substr($GLOBALS["f_letters"], intval($idx), 1);
}

function first_letter($id) {
    $l = mb_substr($id, 0, 1);
    if ((ord($l) > 0x40) && (ord($l) < 0xB5))
	$l = strtolower($l);
    return $l;
}

function isValidId($id) {
    $bad = "/([\.\-\+_][\.\-\+_]|[\.\-_]$)/u";
    $regex = "/^[0-9A-Za-zА-Яа-яЁё][0-9A-Za-zА-Яа-яЁё\.\-\+_]*$/u";
    return (mb_strlen($id) <= 32) && !preg_match($bad, $id) && preg_match($regex, $id);
}

function &cache2arr($filename) {
    global $CACHEDIR;

    $fullpath = "$CACHEDIR/$filename.php";
    if (file_exists( $fullpath ))
	$arr = include($fullpath);
    else
	$arr = array();
    return $arr;
}

function httpGet($param) {
    global $CACHEDIR, $cli_runtime_args;

    if (php_sapi_name() != "cli") {
	if (isset( $_GET[$param] ))
	    return $_GET[$param];
    }
    else {
	if (!isset( $cli_runtime_args )) {
	    if (file_exists( "$CACHEDIR/query.yml" ))
		$cli_runtime_args = read_yaml($CACHEDIR, "query.yml");
	}
	if (isset( $cli_runtime_args )) {
	    if (isset( $cli_runtime_args[$param] ))
		return $cli_runtime_args[$param];
	}
    }

    return false;
}

function httpGetStr($param) {
    $value = httpGet($param);
    return ($value === false) ? "": trim(strval($value));
}

function httpGetInt($param) {
    $value = httpGet($param);
    return ($value === false) ? 0: @intval($value);
}

function httpGetBool($param) {
    $value = httpGet($param);
    if (!$value)
	return false;
    $value = trim(strval($value));
    return ($value == "on") || ($value == "1");
}

function httpGetId($param) {
    $value = httpGetStr($param);
    return $value && isValidId($value) ? $value: "";
}

function isUrl($str) {
    return ((substr($str, 0, 7) == "http://") ||
	    (substr($str, 0, 8) == "https://") ||
	    (substr($str, 0, 6) == "ftp://") ||
	    (substr($str, 0, 7) == "ftps://")
    );
}

function htmlId(&$in, $id) {
    return htmlspecialchars(isset($in["Name"]) ?
		$in["Name"]: str_replace("_", " ", $id));
}

function buildFilterQuery($args) {
    $qs = "";

    foreach ($args as $key => $value) {
	if ($qs)
	    $qs .= "&";
	$qs .= urlencode($key)."=".urlencode($value);
    }

    return "WorkTable.php?".htmlentities($qs);
}

function makeHeader($template, $from=false, $to=false) {
    $CSSDEV = dirname($GLOBALS["LIBDIR"]);
    $o = file_get_contents("$CSSDEV/templ/$template-header.tpl");
    if ($from !== false)
	return str_replace($from, $to, $o);
    return $o;
}

function makeFooter($template) {
    $CSSDEV = dirname($GLOBALS["LIBDIR"]);
    return file_get_contents("$CSSDEV/templ/$template-footer.tpl");
}

function infoRow($alt, $left, $right) {
    $class = ($alt ? " class=\"alt\"": "");
    return "<tr$class><td class=\"left\">$left</td>".
	    "<td class=\"right\">$right</td></tr>\n";
}

function grpRow($content, $sep=true) {
    global $empty;

    $h = ($sep ? "": "<a href=\"EntryFrame.php\" ".
			"title=\"Вернуться в начало...\">".
			"<img src=\"icons/home.png\" alt=\"Назад\" /></a> ");
    $o = ($sep ? "<tr><td colspan=\"2\">$empty</td></tr>\n": "");

    return "$o<tr><td colspan=\"2\" class=\"group\">{$h}{$content}</td></tr>\n";
}

function htmlErr($content) {
    return "<span style=\"color:red\"><b>$content</b></span>";
}

function &loadVendorsList() {
    global $DATADIR;

    $list = array();
    $basepath = "$DATADIR/Vendors";
    if (($dh = opendir($basepath)) !== false) {
	while (($entry = readdir($dh)) !== false) {
	    if (is_link("$basepath/$entry"))
		continue;
	    if (!is_dir("$basepath/$entry"))
		continue;
	    if (($entry == ".") || ($entry == ".."))
		continue;
	    if (!file_exists("$basepath/$entry/vendor.yml"))
		continue;
	    $list[] = $entry;
	}
	closedir($dh);
    }
    sort($list);

    return $list;
}

function &loadVendorProductsList($vendor_id) {
    global $DATADIR;

    $list = array();
    $basepath = "$DATADIR/Vendors/$vendor_id";
    if (($dh = opendir($basepath)) !== false) {
	while (($entry = readdir($dh)) !== false) {
	    if (is_link("$basepath/$entry"))
		continue;
	    if (!is_dir("$basepath/$entry"))
		continue;
	    if (in_array($entry, array(".", "..", ".DRAFTS", ".INSTALL")))
		continue;
	    if (!file_exists("$basepath/$entry/product.yml"))
		continue;
	    $list[] = $entry;
	}
	closedir($dh);
    }
    sort($list);

    return $list;
}

function &getActualVersions($vendor_id, $product_id, $index=false) {
    global $CACHEDIR;

    $result = array();
    if ($index === false)
	$index = letter2idx(first_letter($vendor_id));
    $filename = "$CACHEDIR/p{$index}.php";
    if (!file_exists($filename))
	return $result;
    $data = include($filename);
    $TID = "$vendor_id:$product_id";
    if (isset( $data[$TID] ))
	$result = $data[$TID]["Versions"][0];
    unset($data);

    return $result;
}

function &getArchiveVersions($vendor_id, $product_id, $index=false) {
    global $CACHEDIR;

    $result = array();
    if ($index === false)
	$index = letter2idx(first_letter($vendor_id));
    $filename = "$CACHEDIR/p{$index}.php";
    if (!file_exists($filename))
	return $result;
    $data = include($filename);
    $TID = "$vendor_id:$product_id";
    if (isset( $data[$TID] ))
	$result = $data[$TID]["Versions"][1];
    unset($data);

    return $result;
}

function getFileUrl($path) {
    global $DATADIR;

    if (!file_exists("$DATADIR/$path"))
	fatal("File not found: /$path");
    $url = htmlentities(urlencode($path));
    return "GetFile.php?fpath=$url";
}

function getLogo($relpath, &$width, &$height) {
    global $DATADIR;

    $width = $height = false;
    $logo = "Vendors/$relpath";
    $basepath = "$DATADIR/$logo";
    if (file_exists("$basepath.png")) {
	$srcimg = "$basepath.png";
	$logo .= ".png";
    }
    elseif (!file_exists("$basepath.jpg"))
	$logo = false;
    else {
	$logo .= ".jpg";
	$srcimg = "$basepath.jpg";
    }

    if ($logo) {
	$imginfo = getimagesize($srcimg);
	$w = $imginfo[0]; $h = $imginfo[1];
	if (($w > 300) || ($h > 400)) {
	    if ($w > $h)
		$width = 250;
	    else
		$height = 300;
	}
	$logo = getFileUrl($logo);
	unset($imginfo, $w, $h);
    }

    return $logo;
}

function listPlatforms($sep, $in, $only=false) {
    global $arches, $hw_platforms;

    sort($in);
    $out = array();
    if (!isset($arches)) {
	$arches = array_keys($hw_platforms);
	sort($arches);
    }

    foreach ($in as &$item) {
	if ($only && ($item !== $only))
	    continue;
	if (!in_array($item, $arches, true))
	    $arch = htmlErr(htmlspecialchars($item));
	elseif (($item !== "i586") && ($item !== "x86_64"))
	    $arch = "<b>".htmlspecialchars($item)."</b>";
	else
	    $arch = htmlspecialchars($item);
	$out[] = $arch;
    }

    return implode($sep, $out);
}

function field_colorize($data) {
    return preg_replace("/([ЁА-Яёа-я]+)/u",
		"<span style=\"color:darkcyan\">$1</span>", $data);
}

function default_system_renderer($data, $model, $alt) {
    global $empty, $editor;

    $caption = str_replace(" ", $empty,
		htmlspecialchars($model["Caption"]));
    $output = trim(strval($data));
    if (!$output)
	$output = $empty;
    else {
	$output = str_replace("\n", "<br/><br/>", htmlspecialchars($data));
	if ($editor && isset($model["Paint"]))
	    $output = field_colorize($output);
	if (isset($model["Bold"]))
	    $output = "<b>".$output."</b>";
    }

    return infoRow($alt, $caption.":", $output);
}

function uri_system_renderer($data, $model, $alt) {
    global $empty;

    $caption = str_replace(" ", $empty, htmlspecialchars($model["Caption"]));
    $output = $uri = htmlspecialchars(strval($data));
    $title = "Открыть сайт в отдельном окне...";
    if (isset($model["Bold"]))
	$output = "<b>".$output."</b>";
    $output = "<a href=\"".$uri."\" title=\"".$title.
		"\" target=\"_blank\">".$output."</a>";
    return infoRow($alt, $caption.":", $output);
}

function email_system_renderer($data, $model, $alt) {
    global $empty;

    $caption = str_replace(" ", $empty, htmlspecialchars($model["Caption"]));
    $output = $uri = htmlspecialchars(strval($data));
    $title = "Отправить письмо...";
    if (isset($model["Bold"]))
	$output = "<b>".$output."</b>";
    $output = "<a href=\"mailto:".$uri."\" title=\"".$title."\">".$output."</a>";

    return infoRow($alt, $caption.":", $output);
}

function hidden_system_renderer($data, $model, $alt) {
    return "";
}

function dateslist_system_renderer($data, $model, $alt) {
    global $empty;

    $caption = str_replace(" ", $empty, htmlspecialchars($model["Caption"]));
    if (!is_array($data) || !count($data))
	$output = $empty;
    else
	$output = htmlspecialchars(implode(", ", $data));
    return infoRow($alt, $caption.":", $output);
}

function buildOutputForm(&$data, &$model, $sep=true, $zebra=true) {
    global $baseDataTypes;

    if (!isset($model["View"]["Additional"]))
	$fieldset = &$model["Fields"];
    else
	$fieldset = combineAdditional($model["Fields"], $model["View"]["Additional"]);
    $output = htmlspecialchars($model["Caption"]);
    if ($sep !== null)
	echo grpRow($output, $sep);
    $alt = false;

    foreach ($fieldset as $key => &$description) {
	if (!isset($description["Required"])) {
	    if (!isset($data[$key]))
		continue;
	}

	// Define callback in client code as:
	// FILEDNAME_field_renderer($data, $model, $alt)
	$callback = preg_replace("/[\s\.\-]+/", "_",
			mb_strtolower($key))."_field_renderer";
	if (function_exists($callback)) {
	    $output = call_user_func($callback,
			$data[$key], $description, $zebra && $alt);
	    echo $output;
	    $alt = !$alt;
	    continue;
	}

	// Define callback in client code as:
	// TYPENAME_type_renderer($data, $model, $alt)
	$type = $description[isset($description["View"]) ? "View": "Type"];
	$callback = preg_replace("/[\s\.\-]+/", "_",
			mb_strtolower($type))."_type_renderer";
	if (!function_exists($callback)) {
	    if (isset($baseDataTypes[$type]))
		$callback = preg_replace("/[\s\.\-]+/", "_",
				mb_strtolower($baseDataTypes[$type]))."_type_renderer";
	    if (!function_exists($callback))
		$callback = preg_replace("/[\s\.\-]+/", "_",
				mb_strtolower($type))."_system_renderer";
	    if (!function_exists($callback))
		$callback = "default_system_renderer";
	}
	$output = call_user_func($callback, $data[$key], $description, $zebra && $alt);
	echo $output;
	$alt = !$alt;
    }
}

?>