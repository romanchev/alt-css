<?php

function reindex_recent() {
    $fname = "History/recent.csv";
    if (!file_exists($fname))
	return;
    $fp = fopen($fname, "r");
    if (!$fp)
	fatal("Couldn't read CSV-file: /".$fname);
    $recent = array();
    $header = null;

    while (!feof($fp)) {
	$str = rtrim(fgets($fp));
	if (!$str)
	    break;
	if ($header === null)
	    $row = explode("|", $str);
	else
	    $row = explode("|", $str, count($header));
	foreach ($row as &$cell)
	    $cell = rtrim($cell);
	if ($header === null)
	    $header = $row;
	else {
	    $record = array();
	    for ($i=0; $i < count($header); $i++)
		$record[ $header[$i] ] =& $row[$i];
	    $recent[] = $record;
	    unset($i, $record);
	}
	unset($str, $row, $cell);
    }

    fclose($fp);
    arr2cache("recent", $recent);
}

?>