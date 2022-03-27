<?php

function E_yaml($path) {
    errx("Required YAML-file not found: $path");
}

function I_yaml($path) {
    errx("Invalid YAML-file: $path");
}

function U_file($path) {
    errx("Unexpected file: $path");
}

function S_file($path) {
    errx("Bad file size: $path");
}

function S_img($path) {
    errx("Bad image file: $path");
}

function U_dir($path) {
    errx("Unexpected directory: $path");
}

function E_dir($path) {
    errx("Empty directory not allowed here: $path");
}

function A_nopdf($path) {
    warnx("Asciidoc not compiled to $path");
}

function check_placeholder($dir) {
    global $GITPH;

    if (!file_exists("$dir/$GITPH")) {
	errx("Placeholder not found: /$dir/$GITPH");
	return -1;
    }
    if (filesize("$dir/$GITPH") > 0) {
	errx("Invalid placeholder file size: /$dir/$GITPH");
	return -1;
    }

    return 0;
}

function check_symlink($path) {
    if (is_link($path)) {
	errx("Symbolic link not allowed here: /$path");
	return -1;
    }

    return 0;
}

function check_yaml_file($filename, $canBeEmpty=false) {
    $data = read_yaml($GLOBALS["DATADIR"], $filename, "errx");
    return (!$canBeEmpty && !count(array_keys($data))) ? -1: 0;
}

?>