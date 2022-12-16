<?php

namespace dataset;

require_once 'sku.php';
require_once 'removebg.php';
require_once 'escrevelog.php';

function get_subdir($env, $sku) {
    return sprintf("%s/%s/%s/", $env, sku_extract($sku, ['date'])[0], $sku);
}

function get_basedir($env) {
    return sprintf("/var/www/data/images/%s/", $env);
}

function get_targetdir($env, $sku) {
    return "/var/www/data/images/" . get_subdir($env, $sku);
}

function get_producturl($env, $sku) {
    return 'https://' . $_SERVER['HTTP_HOST'] . '/images/' . get_subdir($env, $sku);
}

require_once 'qrcode.php';

function build_qrcode($env, $sku, $text) {
	$target_dir = \dataset\get_targetdir($env, $sku);
	if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

	$target_name = sprintf('%s--Q.svg', $sku);
	$target_file = $target_dir . $target_name;

	// writeQrCode('google.com/search?q=foobar', $target_file . '.svg');
	writeQrCode($text, $target_file);
}

/**
 *
 * $type          one of 'S' (source), 'T' (thumbnail), 'R' (resized), 'X' (resized without background)
 * $env           dev or prod
 * $source_file   source image
 * $sku           SKU
 * $target_ext    image file extension without '.'
 * $resize        array(height, width) specifying max dimensions for 'R'
 */
function build_image($type, $env, $source_file, $sku, $target_ext, $resize = null) {
	$target_dir = \dataset\get_targetdir($env, $sku);
	if (!is_dir($target_dir)) {
		mkdir($target_dir, 0777, true);
	}
	// $product_type = sku_extract($sku, ['type'])[0];
	// echo \json_encode(sku_extract($sku, ['type']));
	$target_name = sprintf('%s--%s.%s', $sku, $type, $target_ext);
	$target_file = $target_dir . $target_name;
	$product_url = \dataset\get_producturl($env, $sku);
	$image_url = $product_url . $target_name;

	$extra = [];
	$code = 200;
	switch ($type) {
		case 'S':
			list($width, $height) = getimagesize($source_file);
			move_uploaded_file($source_file, $target_file);
			break;
		case 'T':
			list($width, $height) = getimagesize($source_file);
			$image = imagecreatefromjpeg($source_file);
			$factor = 0.15;
			$side = (int)((1.0 - 2 * $factor) * $width);
			$rect = [
				'x' => $factor * $width,
				'y' => 0,
				'width' => $side,
				'height' => $side
			];
			$image = imagecrop($image, $rect);
			$thumb = imagecreatetruecolor(96, 96);
			imagecopyresized($thumb, $image, 0, 0, 0, 0, 96, 96, $side, $side);
			imagejpeg($thumb, $target_file);
			$width = $height = 96;
			break;
		case 'R':
			if ($resize !== null && is_array($resize) && count($resize) === 2) {
				list($maxHeight, $maxWidth) = $resize;
				list($width1, $height1) = getimagesize($source_file);
				$ratio = $width1 / (float)($height1);
				array_push($extra, [$maxWidth, $maxHeight], [$width1, $height1], $ratio);

				$w = 0;  // new width
				$h = 0;  // new height
				if ($ratio > 1.0) {
					if ($width1 < $maxWidth) $w = $width1;
					else $w = $maxWidth;
					$h = floor($height1 / ($width1 / $w));
				}
				else {
					if ($height1 < $maxHeight) $h = $height1;
					else $h = $maxHeight;
					$w = floor($width1 / ($height1 / $h));
				}
				array_push($extra, [$w, $h]);

				if ($w > 0 && $h > 0) {
					$image1 = imagecreatefromjpeg($source_file);
					$image2 = imagecreatetruecolor($w, $h);

					imagecopyresized($image2, $image1, 0, 0, 0, 0, $w, $h, $width1, $height1);
					imagejpeg($image2, $target_file);

					$width = $w;
					$height = $h;
				}
				else {
					error_log('invalid R parameters');
					$code = 500;
				}
			}
			else {
				error_log('invalid R parameters');
				$code = 400;
			}
			break;
		case 'X':
			$code = 200;
			$image_sembg = tira_backgroud($target_file);
			$image_url = str_replace('--X.jpg', '--R.png', $image_url);
			$target_name = str_replace('--X.jpg', '--R.png', $target_name);
			$target_file = str_replace('--X.jpg', '--R.png', $target_file);
			list($width, $height) = getimagesize($target_file);
			break;

		case 'Z': //não faz nada
			$width = 0;
			$height = 0;
			// $code = 500;  // TODO em caso de erro
			break;
	}

	//loga_linha('build_image ', "type: {$type} ext: {$target_ext}");
	//loga_linha('build_image source_file=>', $source_file);
	//loga_linha('build_image target_name=>', $target_name);
	//loga_linha('build_image target_file=>', $target_file);
	//loga_linha('build_image product_url=>', $product_url);
	//loga_linha('build_image image_url=>', $image_url);

	return [
		$code,
		array(
			'name' => $target_name,
			'path' => $target_file,
			'url'  => $image_url,
			'width' => $width,
			'height' => $height,
			'extra' => $extra
		)
	];
}

