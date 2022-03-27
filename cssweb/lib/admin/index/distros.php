<?php

function reindex_distros() {
    global $DATADIR, $statinfo, $distids;

    $dir = "Distros";
    $distros = array();
    if (!isset($distids))
	check_distros();
    elseif (!isset($distids[0]))
	$distids = array_flip($distids);
    if (!count($distids))
	fatal("Distro definitions not found, nothing to do");
    $model = loadDataModel("distinfo");

    foreach ($distids as $dID) {
	$yaml = "$dir/".str_replace("/", ".", $dID).".yml";
	$record = loadYamlFile($yaml, "distinfo", $model);
	$distros[] = array (
	    $record["Name"],
	    (isset($record["Brief"]) ? $record["Brief"]: null),
	    $record["PubDate"],
	    basename($dID),
	    isset($record["Hidden"]),
	    (isset($record["CMLabel"]) ? $record["CMLabel"]: null)
	);
	unset($record, $yaml);
    }

    if (!isset($statinfo))
	$statinfo = cache2arr("statinfo");
    $statinfo["distros"] = count($distids);
    $distids = array_flip($distids);
    arr2cache("statinfo", $statinfo);
    arr2cache("distros", $distros);
    arr2cache("distids", $distids);

    return $statinfo["distros"];
}

?>