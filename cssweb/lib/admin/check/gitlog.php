<?php

function check_gitlog() {
    global $statinfo;

    $csi_commits = `git log --oneline 2>/dev/null |wc -l`;
    $csi_commits = @intval(trim( $csi_commits ));
    if ($csi_commits <= 0)
	fatal("CSI directory is not valid git repository");
    if (!isset($statinfo))
	$statinfo = cache2arr("statinfo");
    $statinfo["CSI-commits"] = $csi_commits;
    arr2cache("statinfo", $statinfo);

    return $csi_commits;
}

?>