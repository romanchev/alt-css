<?php

function update_vendor_cache($VendorID, $index=false)
{
    // Auto-detect index
    if ($index === false)
	$index = letter2idx(first_letter($VendorID));
    $srch = array();

    // Extract primary info and index data for FTS
    $yaml = "Vendors/$VendorID/vendor.yml";
    $data = loadYamlFile($yaml, "vendor");
    $name = str_replace("_", " ", $VendorID);
    $list = intval($data["List"]);
    $link = isset($data["URI"]) ? $data["URI"]: "";
    $tags = isset($data["Tags"]) ? explode(", ", $data["Tags"]): null;
    $note = isset($data["Footnote"]) ? $data["Footnote"]: null;
    $stop = array("Name", "List", "Extern", "Tags");
    $srch[] = fts_string($name);
    if (isset($data["Name"])) {
	if ($data["Name"] != $name)
	    $srch[] = fts_string($data["Name"]);
	$name = $data["Name"];
    }
    //
    foreach ($data as $key => &$value) {
	if (in_array($key, $stop, true))
	    continue;
	if (!is_string($value) || ($value == "."))
	    continue;
	$s = fts_string($value);
	if ($s)
	    $srch[] = $s;
	unset($s);
    }
    unset($data);

    // Add contacts to the full text search index
    $yaml = "Vendors/$VendorID/contacts.yml";
    if (file_exists($yaml)) {
	$contacts = loadYamlFile($yaml, "contact", $unused, true);
	foreach ($contacts as &$record)
	    foreach ($record as &$value) {
		if (!is_string($value) || ($value == "."))
		    continue;
		$s = fts_string($value);
		if ($s)
		    $srch[] = $s;
		unset($s);
	    }
	unset($contacts, $unused, $record);
    }

    // Add events to the full text search index
    $yaml = "Vendors/$VendorID/events.yml";
    if (file_exists($yaml)) {
	$records = read_yaml($GLOBALS["DATADIR"], $yaml);
	foreach ($records as &$value) {
	    if (!is_string($value))
		continue;
	    $s = fts_string(trim($value));
	    if ($s)
		$srch[] = $s;
	    unset($s);
	}
	unset($records);
    }

    // Update data in the cache
    $v_idx = cache2arr("v{$index}");
    $v_idx[$VendorID] = array (
	"Name" => $name,
	"URI"  => $link,
	"List" => $list
    );
    if ($tags !== null)
	$v_idx[$VendorID]["Tags"] = $tags;
    if ($note !== null)
	$v_idx[$VendorID]["Note"] = $note;
    arr2cache("v{$index}", $v_idx);
    update_fts_cache("s{$index}", $VendorID, $srch);
    unset($v_idx, $name, $list, $link, $tags, $note, $srch);
}

?>