<?php

function reindex_gitlog() {
    global $dateFormat, $dateFmtRegex;

    // Create temporary CSV file
    $csv = tempnam("", "CSI-gitlog_");
    if (!$csv)
	fatal("Couldn't create temporary file");
    @unlink($csv); $csv .= ".csv";
    $output = `(git log -n20 --no-merges --ignore-missing \
		--pretty=format:"%at|%an|%ae|%s"; echo) >"$csv"`;
    $fp = fopen($csv, "r");
    if (!$fp) {
	@unlink($csv);
	fatal("Couldn't read CSV-file: ".basename($csv));
    }

    // Fill array
    $git = array();
    while (!feof($fp)) {
	$str = rtrim(fgets($fp));
	if (!$str)
	    break;
	$git[] = explode("|", $str, 4);
	unset($str);
    }
    fclose($fp);
    @unlink($csv);

    // Parse data
    foreach ($git as &$entry) {
	if (!preg_match("/ от ($dateFmtRegex)$/u", $entry[3], $mac))
	    $entry[0] = date($dateFormat, @intval($entry[0]));
	else {
	    $entry[0] = $mac[1];
	    $entry[3] = mb_substr($entry[3], 0, -mb_strlen($mac[1])-4);
	}
    }

    // Save new data
    arr2cache("csigit", $git);
}

?>