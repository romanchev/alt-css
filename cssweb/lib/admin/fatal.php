<?php

function fatal($msg) {
    global $argv;

    $prog = basename($argv[0], ".php");
    fprintf(STDERR, "\n\033[01;31m%s fatal: %s\033[00m\n", $prog, $msg);
    @unlink($_SERVER['HOME']."/SUCCESS");
    exit;
}

?>