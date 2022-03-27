<?php

function check_datadir() {
    global $ENTITES;

    foreach ($ENTITES as $entry) {
	if (is_link($entry) || !is_dir($entry)) {
	    errx("Important entity not found: /$entry");
	    continue;
	}
	check_placeholder($entry);
    }

    $dh = opendir(".");
    while (($entry = readdir($dh)) !== false) {
	if (check_symlink($entry))
	    continue;
	elseif (!is_dir($entry))
	    U_file("/$entry");
	elseif (in_array($entry, array(".", "..", ".git"), true))
	    continue;
	elseif (in_array($entry, $ENTITES, true))
	    continue;
	U_dir("/$entry");
    }
    closedir($dh);

    $entry = "History/uploads";
    if (is_link($entry) || !is_dir($entry))
	errx("Important directoty not found: /$entry");
    check_placeholder($entry);
}

?>