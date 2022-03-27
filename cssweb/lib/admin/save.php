<?php

/**
* Save Base Types:
* ================
*
* _default_
* OneLine
* AutoStr
* Text
* Separator
* ShortList
* AutoList
* LongList
* ObjList
* Object
* Table
*/

function isSafeYamlStr(&$str) {
    if ("$str" === "")
	return false;
    if (preg_match("/^[|>\^\-]/", "$str"))
	return false;
    if (mb_substr("$str", 0, 2) === "!!")
	return false;
    if (strpbrk("$str", "\"#'{}[]") !== false)
	return false;
    if (strstr("$str", ": ") !== false)
	return false;
    return true;
}

function yaml_quote_string(&$data) {
    return "\"".addcslashes($data, "\"\t\r\n\\")."\"";
}

function yaml_array_type(&$arr) {
    $assoc = $nodes = false;

    foreach ($arr as $key => &$item) {
	if (is_array($item)) {
	    $nodes = true;
	    break;
	}
	elseif (is_string($item) && (strstr(", ", $item) !== false)) {
	    $nodes = true;
	    break;
	}
	if (!is_integer($key)) {
	    $assoc = true;
	    break;
	}
    }

    if ($assoc)		/* OBJECT */
	return 1;
    elseif (!$nodes)	/* LIST of the strings */
	return 0;
    return 2;		/* LIST with sub-nodes */
}

function mb_wordwrap($str, $width=75, $break="\n", $cut=false) {
    $str = trim(strval($str));
    $result = "";
    if (!$str)
	return $result;
    if ($cut) {
	if ($width > 0) {
	    $str = preg_replace("/\s+/", " ", $str);
	    $len = mb_strlen($str);

	    for ($i=0; $i < $len; $i += $width) {
		if ($len - $i > $width)
		    $line = mb_substr($str, $i, $width);
		else
		    $line = mb_substr($str, $i);
		if ($result)
		    $result .= $break;
		$result .= $line;
	    }
	}
	return $result;
    }

    $words = preg_split("/\s+/", $str);
    $str = $result;
    $currlen = 0;

    for ($i=0; $i < count($words); $i++) {
	$nextword = mb_strlen($words[$i]);
	if (!$currlen) {
	    $currlen = $nextword;
	    $str = $words[$i];
	}
	elseif ($currlen+1+$nextword <= $width) {
	    $currlen += 1+$nextword;
	    $str .= " ".$words[$i];
	}
	else {
	    if ($result)
		$result .= $break;
	    $result .= $str;
	    $currlen = $nextword;
	    $str = $words[$i];
	}
    }

    if ($result)
	$result .= $break;
    return $result.$str;
}

function &splitNotes(&$text, $indent=0) {
    $arr = explode("\n", mb_wordwrap($text, 76-$indent));
    $out = "";

    foreach ($arr as &$str) {
	$out .= str_repeat(" ", $indent);
	$out .= $str;
	$out .= "\n";
    }

    return $out;
}

function &writeYamlObjList_r(&$obj, $indent=0) {
    $max = 0;

    foreach ($obj as $key => &$data) {
	if ($max < mb_strlen($key))
	    $max = mb_strlen($key);
    }
    if ($max + abs($indent) < 65)
	$max += 3;
    else
	$max = 0;
    unset($data);
    $result = "";

    foreach ($obj as $key => $data) {
	$left = "";
	if ($indent < 0)
	    $indent = -$indent;
	elseif ($indent > 0)
	    $left = str_repeat(" ", $indent);
	$left .= ($max ? str_pad("{$key}:", $max): "{$key}: ");
	$result .= yaml_objlist_default_writer($left, $data, $indent, $max);
    }

    return $result;
}

function &writeYamlObject_r(&$obj, $indent=0) {
    $max = 0;

    foreach ($obj as $key => &$data) {
	if ($max < mb_strlen($key))
	    $max = mb_strlen($key);
    }
    if ($max + abs($indent) < 65)
	$max += 3;
    else
	$max = 0;
    unset($data);
    $result = "";

    foreach ($obj as $key => $data) {
	$left = "";
	if ($indent < 0)
	    $indent = -$indent;
	elseif ($indent > 0)
	    $left = str_repeat(" ", $indent);
	$left .= ($max ? str_pad("{$key}:", $max): "{$key}: ");
	$result .= yaml_default_system_writer($left, $data, $indent, $max);
    }

    return $result;
}

function &writeYamlList_r(&$lst, $indent=0) {
    $result = "";

    foreach ($lst as &$item) {
	if ($indent > 0)
	    $result .= str_repeat(" ", $indent);
	$result .= "- ";
	$result .= writeYamlObject_r($item, -($indent+2));
    }

    return $result;
}

function &writeYamlStrList(&$lst, $indent=0) {
    $out = "";

    foreach ($lst as &$str) {
	if ($indent > 0)
	    $out .= str_repeat(" ", $indent);
	if (($indent + 2 + mb_strlen($str) > 76) && (strstr($str, " ") !== false))
	    $out .= "- >\n".splitNotes($str, $indent+2);
	elseif (isSafeYamlStr($str))
	    $out .= "- ".$str."\n";
	else
	    $out .= "- ".yaml_quote_string($str)."\n";
    }

    return $out;
}

function &prepareYamlObject(&$data, &$model, $indent=0) {
    $max = 0;

    foreach ($model["Fields"] as $key => &$dsc) {
	if ($max < mb_strlen($key))
	    $max = mb_strlen($key);
    }
    if ($max + abs($indent) + 30 < 95)
	$max += 3;
    else
	$max = 0;
    $result = "";
    $base = baseDataTypes();

    foreach ($model["Fields"] as $key => &$dsc) {
	$type = $dsc["Type"];
	if (isset($base[$type]) && isset($base[$type]["Save"]))
	    $type = $base[$type]["Save"];
	if ($type == "Separator") {
	    unset($type);
	    continue;
	}
	$left = "";
	if ($indent < 0)
	    $indent = -$indent;
	elseif ($indent > 0)
	    $left = str_repeat(" ", $indent);
	if (!isset($dsc["Required"]) && !isset($data[$key])) {
	    $result .= $left.($max ? str_pad("#{$key}:", $max): "#{$key}: ")."?\n";
	    unset($type, $left);
	    continue;
	}

	if ($type != "Table") {
	    $ref = &$data[$key];
	    $left .= ($max ? str_pad("{$key}:", $max): "{$key}: ");
	    $cb = "yaml_".mb_strtolower($type)."_system_writer";
	    if (!function_exists($cb))
		$cb = "yaml_default_system_writer";
	    $result .= call_user_func($cb, $left, $ref, $indent, $max);
	    unset($ref);
	}
	else {
	    $result .= "{$key}:\n";
	    if (isset($model["Fileds"][$key]["Nested"])) {
		$k2 = $model["Fileds"][$key]["Nested"];
		$cb = array("Model"  => "Nested:".$k2,
			    "Fields" => $model["Tables"][$k2]);
		unset($k2);
	    }
	    elseif (isset($model["Fileds"][$key]["Extern"])) {
		$mod2name = $model["Fileds"][$key]["Extern"];
		$cb = loadDataModel($mod2name);
		if (!is_array($cb))
		    exit("Invalid data model: $mod2name\n");
		unset($mod2name);
	    }
	    else {
		$result .= writeYamlList_r($data[$key], $indent+2);
		unset($type, $left, $cb);
		continue;
	    }
	    $result .= prepareYamlObjList($data[$key], $cb, $indent+2);
	}
	unset($type, $left, $cb);
    }

    return $result;
}

function &prepareYamlObjList(&$table, &$model, $indent=0) {
    $result = "";

    foreach ($table as &$record) {
	if ($indent > 0)
	    $result .= str_repeat(" ", $indent);
	$result .= "- ";
	$result .= prepareYamlObject($record, $model, -($indent+2));
    }

    return $result;
}

function yaml_oneline_system_writer($left, &$data, $indent, $width) {
    if (isSafeYamlStr($data))
	return "{$left}{$data}\n";
    return $left.yaml_quote_string($data)."\n";
}

function yaml_text_system_writer($left, &$data, $indent, $width) {
    $indent += 2;
    $first = true;
    $out = $left.">\n";
    $plist = explode("\n", $data);

    foreach ($plist as &$str) {
	if (!$first)
	    $out .= str_repeat(" ", $indent)."\n";
	$out .= splitNotes($str, $indent);
	$first = false;
    }

    return $out;
}

function yaml_autostr_system_writer($left, &$data, $indent, $width) {
    if ((!$width || ($indent + $width + mb_strlen($data) > 95)) && (strstr($data, " ") !== false))
	return $left.">\n".splitNotes($data, $indent+2);
    return yaml_oneline_system_writer($left, $data, $indent, $width);
}

function yaml_shortlist_system_writer($left, &$data, $indent, $width) {
    $first = true;
    $out = $left."[";

    foreach ($data as &$item) {
	if (!$first)
	    $out .= ", ";
	if (isSafeYamlStr($item))
	    $out .= $item;
	else
	    $out .= yaml_quote_string($item);
	$first = false;
    }

    return $out."]\n";
}

function yaml_longlist_system_writer($left, &$data, $indent, $width) {
    return rtrim($left)."\n".writeYamlStrList($data, $indent+2);
}

function yaml_autolist_system_writer($left, &$data, $indent, $width) {
    $line = yaml_shortlist_system_writer($left, $data, $indent, $width);
    if (mb_strlen($line) - 1 <= 76)
	return $line;
    return yaml_longlist_system_writer($left, $data, $indent, $width);
}

function yaml_object_system_writer($left, &$data, $indent, $width) {
    return rtrim($left)."\n".writeYamlObject_r($data, $indent+2);
}

function yaml_objlist_system_writer($left, &$data, $indent, $width) {
    return rtrim($left)."\n".writeYamlObjList_r($data, $indent+2);
}

function yaml_objlist_default_writer($left, &$data, $indent, $width) {
    if (is_string($data))
	return yaml_autostr_system_writer($left, $data, $indent, $width);
    if (!is_array($data))
	return yaml_autostr_system_writer($left, strval($data), $indent, $width);
    if (!count($data))
	return yaml_shortlist_system_writer($left, $data, $indent, $width);
    $arrtype = yaml_array_type($data);
    if ($arrtype == 0)
	return yaml_longlist_system_writer($left, $data, $indent, $width);
    if ($arrtype == 1)
	return rtrim($left)."\n".writeYamlObjList_r($data, $indent+2);
    return rtrim($left)."\n".writeYamlList_r($data, $indent+2);
}

function yaml_default_system_writer($left, &$data, $indent, $width) {
    if (is_string($data))
	return yaml_autostr_system_writer($left, $data, $indent, $width);
    if (!is_array($data))
	return yaml_autostr_system_writer($left, strval($data), $indent, $width);
    if (!count($data))
	return yaml_shortlist_system_writer($left, $data, $indent, $width);
    $arrtype = yaml_array_type($data);
    if ($arrtype == 0)
	return yaml_autolist_system_writer($left, $data, $indent, $width);
    if ($arrtype == 1)
	return rtrim($left)."\n".writeYamlObject_r($data, $indent+2);
    return rtrim($left)."\n".writeYamlList_r($data, $indent+2);
}

?>