<?php

/**
* Load Base Types:
* ================
*
* _default_
* Float
* Integer
* IntList
* FltList
* Object
* Table
*/
$field_subst_regex = "/[\s\.\-]+/";

if (extension_loaded('yaml')) {
    @ini_set('yaml.decode_timestamp', 0);
    @ini_set('yaml.decode_binary', 0);
    @ini_set('yaml.decode_php', 0);
}

function &read_yaml($base, $relpath, $errfunc="fatal") {
    $filename = "$base/$relpath";

    if (extension_loaded('yaml'))
	$res = @yaml_parse_file($filename);
    else {
	$src = tempnam("", "CSI-yaml_").".json"; @unlink($src);
	$ignore = `yq -Mc <"$filename" >"$src" 2>/dev/null`;
	$res = @json_decode($src, true, 8,
			JSON_BIGINT_AS_STRING |
			JSON_INVALID_UTF8_IGNORE);
	@unlink($src);
    }

    if (!is_array($res)) {
	if (intval(`grep -scvE '^\s*(#|$)' "$filename"`) > 0)
	    $errfunc("Invalid YAML-file: /$relpath");
	$res = array();
    }

    return $res;
}

function &loadDataModel($modelname) {
    $cssdev = dirname($GLOBALS["LIBDIR"]);
    $filename = "model/$modelname.yml";
    $result = read_yaml($cssdev, $filename);
    if (!isset( $result["Model"] ))
	$result["Model"] = $modelname;
    return $result;
}

function &baseDataTypes() {
    global $LIBDIR, $__baseDataTypes;

    if (!isset($__baseDataTypes)) {
	$cssdev = dirname($LIBDIR);
	$filename = "model/datatypes.yml";
	$__baseDataTypes = read_yaml($cssdev, $filename);
    }

    return $__baseDataTypes;
}

function &parseYamlData(&$data, &$model, &$ctx) {
    global $field_subst_regex;

    $out = array();
    $base = baseDataTypes();
    $fieldset = &$model["Fields"];

    foreach ($fieldset as $key => &$dsc) {
	if (!isset($dsc["Required"]) && !isset($data[$key]))
	    continue;
	$ctx["Key"] = $key;

	// Define callback in client code as:
	// yaml_<FIELD_NAME>_field_reader($data, &$model, &$ctx)
	// yaml_<FIELD_TYPE>_type_reader($data, &$model, &$ctx)
	//
	$cb = "yaml_".preg_replace($field_subst_regex, " ",
			mb_strtolower($key))."_field_reader";
	if (!function_exists($cb)) {
	    $type = $dsc["Type"];
	    if (isset($base[$type]) && isset($base[$type]["Load"]))
		$type = $base[$type]["Load"];
	    $cb = "yaml_".mb_strtolower($type)."_type_reader";
	    if (!function_exists($cb)) {
		$cb = "yaml_".mb_strtolower($type)."_system_reader";
		if (!function_exists($cb))
		    $cb = "yaml_default_system_reader";
	    }
	    unset($type);
	}
	$out[$key] = call_user_func($cb, $data[$key], $model, $ctx);
	unset($cb);
    }

    return $out;
}

function yaml_num2str_r(&$arr) {
    foreach ($arr as &$value) {
	if (is_array($value))
	    yaml_num2str_r($value);
	elseif (is_numeric($value))
	    $value = strval($value);
	elseif (is_string($value))
	    $value = trim($value);
    }
}

function fillOptionalFields(&$data, &$model) {
    $base = baseDataTypes();

    foreach ($model as $key => &$dsc) {
	if (!isset($dsc["Required"]) || isset($data[$key]))
	    continue;
	$type = $dsc["Type"];
	if (isset($base[$type]) && isset($base[$type]["Load"]))
	    $type = $base[$type]["Load"];
	if (($type == "Object") || ($type == "Table")) {
	    unset($type);
	    continue;
	}
	if (($type == "IntList") || ($type == "FltList"))
	    $empty = array();
	else
	    $empty = "";
	if (!isset($dsc["Default"]))
	    $data[$key] = $empty;
	elseif (($type == "Integer") || ($type == "Float"))
	    $data[$key] = strval($dsc["Default"]);
	else
	    $data[$key] = $dsc["Default"];
	unset($type, $empty);
    }
}

function &combineAdditional(&$fields, &$additional) {
    $before = array(); $result = array();

    foreach ($additional as $key => &$dsc)
	if (isset($dsc["Before"])) {
	    $addfld = $dsc["Before"];
	    if (!isset($before[$addfld]))
		$before[$addfld] = array();
	    $before[$addfld][] = $key;
	}

    foreach ($fields as $key => &$dsc) {
	if (isset($before[$key]))
	    foreach ($before[$key] as $addfld) {
		$result[$addfld] = $additional[$addfld];
		unset($result[$addfld]["Before"]);
	    }
	$result[$key] = $dsc;
    }

    foreach ($additional as $key => &$dsc) {
	if (!isset($dsc["Before"]))
	    $result[$key] = $dsc;
    }

    return $result;
}

function &parseYamlRecords(&$table, &$model, &$ctx) {
    $out = array();
    foreach ($table as &$record)
	$out[] = parseYamlData($record, $model, $ctx);
    return $out;
}

function &loadYamlFile($filename, $modelname, &$ref=null, $table=false) {
    global $DATADIR, $LIBDIR;

    $cssdev = dirname($LIBDIR);
    if (!file_exists("$DATADIR/$filename"))
	fatal("YAML-file not found: /$filename");
    if (!file_exists("$cssdev/model/$modelname.yml"))
	fatal("Data model not found: /$modelname");
    $context = array("Source"=>$filename, "Model"=>$modelname);
    $data = read_yaml($DATADIR, $filename);
    if ($ref)
	$model = &$ref;
    else {
	$model = loadDataModel($modelname);
	$ref = $model;
    }
    if ($table) {
	$context["Table"] = true;
	return parseYamlRecords($data, $model, $context);
    }

    return parseYamlData($data, $model, $context);
}

function category2suitable($category) {
    global $DATADIR;

    $model    = loadDataModel("category");
    $list     = explode("/", $category);
    $suitable = "NoExpand";
    $currdir  = "/Categories";

    foreach ($list as $item) {
	$currdir .= "/$item";
	$filename = "$currdir/category.yml";
	if (file_exists("$DATADIR/$filename")) {
	    $dummy = loadYamlFile($filename, "category", $model);
	    if (isset( $dummy["Suitable"] ))
		$suitable = $dummy["Suitable"];
	    unset($dummy);
	}
	unset($filename);
    }

    return $suitable;
}

function &rebuildVersions(&$data, $relpath) {
    global $DATADIR, $dateFmtRegex;

    $model = loadDataModel("version");
    $relpath = "Vendors/$relpath";
    $all = $act = $old = array();

    if (is_dir("$DATADIR/$relpath") &&
	(($dh = opendir("$DATADIR/$relpath")) !== false))
    {
	while (($entry = readdir($dh)) !== false) {
	    if (is_link("$DATADIR/$relpath/$entry"))
		continue;
	    if (!is_dir("$DATADIR/$relpath/$entry"))
		continue;
	    if (($entry == ".") || ($entry == ".."))
		continue;
	    $filename = "$relpath/$entry/version.yml";
	    $major = $entry;	/* release by default */
	    if (preg_match("/^{$dateFmtRegex}$/", $major))
		$major = "";
	    $all[$entry] = $major;
	    $yaml = read_yaml($DATADIR, $filename);
	    if (isset($yaml["Builds"])) {
		foreach ($yaml["Builds"] as &$in) {
		    if (!isset($in["Name"])) {
			errx("Invalid [Builds] block in /$filename");
			continue;
		    }
		    $all[$in["Name"]] = $major;
		}
		unset($in);
	    }
	    unset($filename, $yaml, $major);
	}
	closedir($dh);
	unset($entry, $dh);
    }

    if (isset($data["Hidden"]))
	$actual_versions = array();
    elseif (isset($data["ActualVers"]))
	$actual_versions = $data["ActualVers"];
    else
	$actual_versions = array_keys($all);
    unset($model);

    foreach ($all as $release => $major) {
	if (in_array($release, $actual_versions))
	    $act[$release] = $major;
	elseif (in_array($major, $actual_versions))
	    $act[$release] = $major;
	else
	    $old[$release] = $major;
    }

    $result = array($act, $old);
    unset($actual_versions, $act, $old, $all);

    return $result;
}

function yaml_default_system_reader($data, $model, $ctx) {
    if (is_numeric($data))
	return strval($data);
    elseif (is_string($data))
	return trim($data);
    elseif (is_array($data))
	yaml_num2str_r($data);
    return $data;
}

function yaml_integer_system_reader($data, $model, $ctx) {
    return strval($data);
}

function yaml_float_system_reader($data, $model, $ctx) {
    return strval($data);
}

function yaml_intlist_system_reader($data, $model, $ctx) {
    $out = array();
    foreach ($data as $value)
	$out[] = strval($value);
    return $out;
}

function yaml_fltlist_system_reader($data, $model, $ctx) {
    $out = array();
    foreach ($data as $value)
	$out[] = strval($value);
    return $out;
}

function yaml_object_system_reader($data, $model, $ctx) {
    yaml_num2str_r($data);
    return $data;
}

function yaml_table_system_reader($data, $model, $ctx) {
    $key = $ctx["Key"];

    if (isset($model["Fileds"][$key]["Nested"])) {
	$key2 = $model["Fileds"][$key]["Nested"];
	$mod2 = array("Model"  => "Nested:".$key2,
		      "Fields" => $model["Tables"][$key2]);
	unset($key2);
    }
    elseif (!isset($model["Fileds"][$key]["Extern"])) {
	yaml_num2str_r($data);
	return $data;
    }
    else {
	$mod2name = $model["Fileds"][$key]["Extern"];
	$mod2 = loadDataModel($mod2name);
	if (!is_array($mod2))
	    fatal("Invalid data model: $mod2name");
	unset($mod2name);
    }
    $data = parseYamlData($data, $mod2, $ctx);

    return $data;
}

?>