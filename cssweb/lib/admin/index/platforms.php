<?php

function reindex_platforms() {
    global $statinfo, $hw_platforms, $platforms;

    $dir = "Platforms";
    $archdesc = array();
    if (!isset($platforms))
	check_platforms();
    if (!count(array_keys($platforms)))
	fatal("Platform definitions not found, nothing to do");
    $model = loadDataModel("platform");

    foreach ($hw_platforms as $arch => &$caption) {
	if (!isset($platforms[$arch]))
	    continue;
	$record = loadYamlFile("$dir/$arch.yml", "platform", $model);
	if (isset($record["Complexes"])) {
	    if (check_strlst_field("Complexes", $record["Complexes"], "$dir/$arch.yml"))
		unset($record["Complexes"]);
	}
	if (isset($record["Links"])) {
	    if (check_strlst_field("Links", $record["Links"], "$dir/$arch.yml"))
		unset($record["Complexes"]);
	}
	$record["Caption"] = $caption;
	$archdesc[$arch] = $record;
	unset($record);
    }

    if (!isset($statinfo))
	$statinfo = cache2arr("statinfo");
    $statinfo["platforms"] = count(array_keys($platforms));
    arr2cache("statinfo", $statinfo);
    arr2cache("archdesc", $archdesc);
    unset($platforms);

    return $statinfo["platforms"];
}

function check_platforms_list(&$list, $path) {
    global $hw_platforms, $platforms;

    if (!isset($platforms))
	check_platforms();
    foreach ($list as $arch) {
	if (!isset($hw_platforms[$arch]))
	    errx("Unknown platform name: '$arch' in /$path");
    }
}

?>