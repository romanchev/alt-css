<?php

function update_fts_cache($filename, $key, &$list) {
    global $CACHEDIR;

    $s_idx = cache2arr($filename);
    if (count($list))
	$s_idx[$key] = $list;
    elseif (isset($s_idx[$key]))
	unset($s_idx[$key]);
    if (count($s_idx))
	arr2cache($filename, $s_idx);
    else
	@unlink("$CACHEDIR/$filename.php");
    unset($s_idx);
}

function mb_strcasecmp(&$a, &$b) {
    $l = mb_strtolower($a);
    $r = mb_strtolower($b);
    if (preg_match("/^[0-9]/", $l) && preg_match("/^[a-z]/", $r))
	return -1;
    elseif (preg_match("/^[a-z]/", $l) && preg_match("/^[0-9]/", $r))
	return 1;
    elseif (preg_match("/^[0-9a-z]/", $l) && preg_match("/^[а-яё]/u", $r))
	return -1;
    elseif (preg_match("/^[а-яё]/u", $l) && preg_match("/^[0-9a-z]/", $r))
	return 1;
    return ($l < $r) ? -1: ($l > $r ? 1: 0);
}

function inquote_fast($src) {
    $str = mb_strtolower($src);
    if (mb_strstr($str, "«") === false)
	return $str;
    if (mb_strstr($str, "»") === false)
	return $str;
    return preg_replace(array("/^.*«/u", "/».*$/u"), array("", ""), $str);
}

function fts_string($str) {
    return str_replace("ё", "е", mb_strtolower($str));
}

function check_strlst_field($field, &$value, $path) {
    $err = 0;

    if (!is_array($value) || !count(array_keys($value)))
	$err = 1;
    else {
	$prevkey = -1;
	foreach ($value as $key => &$str) {
	    if (!is_integer($key) || ($prevkey+1 !== $key) || !is_string($str)) {
		$err = 1;
		break;
	    }
	    $prevkey = $key;
	}
    }

    if ($err)
	errx("Invalid list format: '$field' in /$path");

    return $err;
}

function check_object_field($field, &$value, $path) {
    $err = 0;

    if (!is_array($value) || !count(array_keys($value)))
	$err = 1;
    else {
	foreach ($value as $key1 => &$arr1) {
	    if (("$key1" === "0") || !is_array($arr1) || !count(array_keys($arr1))) {
		$err = 1;
		break;
	    }
	    $prevkey = -1;
	    foreach ($arr1 as $key2 => &$str2) {
		if (!is_integer($key2) || ($prevkey+1 !== $key2) || !is_string($str2)) {
		    $err = 1;
		    break 2;
		}
		$prevkey = $key2;
	    }
	}
    }

    if ($err)
	errx("Invalid object format: '$field' in /$path");

    return $err;
}

function ref2pdf($vID, $pID, $rpath) {
    global $install;

    if (!file_exists($ref = "Vendors/$vID/$pID/$rpath/inst.ref")) {
	errx("Reference not found: /$ref");
	return false;
    }
    if (!isValidId($rID = trim(file_get_contents($ref)))) {
	errx("Invalid reference ID: '$rID' in /$ref");
	return false;
    }
    if (!isset($install))
	check_install();
    $src = "Vendors/$vID/$pID/ARCH/ALL/$rID.pdf";
    if (($rpath == "ARCH/ALL") || !isset($install[$src])) {
	$src = "Vendors/$vID/.INSTALL/$rID.pdf";
	if (!isset($install[$src])) {
	    $src = "Manuals/$rID.pdf";
	    if (!isset($install[$src])) {
		errx("Broken reference: '$rID' in /$ref");
		return false;
	    }
	}
    }

    return $src;
}

?>