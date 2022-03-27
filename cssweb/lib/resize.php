<?php

function img_resize($src, $dest, $width, $height, $rgb = 0xFFFFFF, $quality = 95) {
    if (!file_exists($src))
	return false;
    $pdf = false;
    if (pathinfo($src, PATHINFO_EXTENSION) === 'pdf') {
	$pdf   = tempnam("", "pdf2jpeg");
	@unlink($pdf);
	$pdf  .= ".jpg";
	$dummy = `convert "$src" "$pdf" 2>&1`;
	if ($dummy) {
	    @unlink($pdf);
	    return false;
	}
	unset($dummy);
	$src = $pdf;
    }
    $size = getimagesize($src);
    if ($size === false) {
	if ($pdf)
	    @unlink($pdf);
	return false;
    }
    $format = strtolower(substr($size['mime'], strpos($size['mime'], '/') + 1));
    $icfunc = 'imagecreatefrom' . $format;
    if (!function_exists($icfunc)) {
	if ($pdf)
	    @unlink($pdf);
	return false;
    }
    $x_ratio = $width  / $size[0];
    $y_ratio = $height / $size[1];
    if ($height == 0) {
	$y_ratio = $x_ratio;
	$height  = $y_ratio * $size[1];
    }
    elseif ($width == 0) {
	$x_ratio = $y_ratio;
	$width   = $x_ratio * $size[0];
    }
    $ratio       = min($x_ratio, $y_ratio);
    $use_x_ratio = ($x_ratio == $ratio);
    $new_width   = $use_x_ratio  ? $width:  floor($size[0] * $ratio);
    $new_height  = !$use_x_ratio ? $height: floor($size[1] * $ratio);
    $new_left    = $use_x_ratio  ? 0: floor(($width - $new_width)   / 2);
    $new_top     = !$use_x_ratio ? 0: floor(($height - $new_height) / 2);

    if (($size[0] < $new_width) && ($size[1] < $new_height)) {
	copy($src, $dest);
	if ($pdf)
	    @unlink($pdf);
	return true;
    }
    $isrc  = $icfunc($src);
    $idest = imagecreatetruecolor($width, $height);
    imagefill($idest, 0, 0, $rgb);
    imagecopyresampled($idest, $isrc, $new_left, $new_top, 0, 0,
			$new_width, $new_height, $size[0], $size[1]);
    $i = strrpos($dest, '.');
    if (!$i) {
	if ($pdf)
	    @unlink($pdf);
	return '';
    }
    $l = strlen($dest) - $i;
    $ext = substr($dest, $i+1, $l);
    switch ($ext) {
	case 'jpeg':
	case 'jpg':
	    imagejpeg($idest, $dest, $quality);
	    break;
	case 'gif':
	    imagegif($idest, $dest);
	    break;
	case 'png':
	    imagepng($idest, $dest);
	    break;
	default:
	    if ($pdf)
		@unlink($pdf);
	    return false;
    }
    imagedestroy($isrc);
    imagedestroy($idest);

    if ($pdf)
	@unlink($pdf);
    return true;
}

?>