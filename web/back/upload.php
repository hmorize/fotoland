<?php
// include 'vendor/autoload.php';
// require ''

$source_file = $_FILES["img"]["tmp_name"];

// $target_file = $target_dir . basename($_FILES["img"]["name"]);
// $env = $_POST['env']; // prod
$env = 'prod'; // prod
$stdsize = $_POST["size"];
$resize = $_POST["resize"];
$uid = $_POST['uid'];
$ptype = $_POST["ptype"]; // ['TT', 'CA', 'FA', 'PA'] PA não tira o fundo


// $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

// Check if image file is a actual image or fake image
$check = getimagesize($source_file);
// print(json_encode($check));
// sleep(10);
$result = array(
	'files' => $_FILES
);

require_once 'dataset.php';
require_once 'sku.php';

try {
	if ($check === false) throw new Exception('Imagem nao reconhecida', 400);
	if ($check["mime"] == "image/jpeg") $target_ext = 'jpg';
	elseif ($check["mime"] == "image/png")  $target_ext = 'png';
	else throw new Exception('Tipo de imagem invalido', 400);

	// echo json_encode(size_decode($stdsize));
	list($height, $width) = size_decode($stdsize);
	$sku = sku_create($width, $height, $ptype, $uid);
	if (!$sku) throw new Exception('Parametros invalidos: ' . sprintf('%d %d %s %s', $height, $width, $ptype, $uid), 400);

	// echo '<PRE>'.json_encode(sku_decode($sku), JSON_PRETTY_PRINT) . "<br/>";

	$res1 = \dataset\build_image('S', $env, $source_file, $sku, $target_ext);
	$source_file = $res1[1]['path'];
	$res2 = \dataset\build_image('T', $env, $source_file, $sku, $target_ext);
	// echo $resize;
	if ($ptype === 'PA') {
		// Se for PA fazer o reduzido normal. Senão tirar o background
		$res3 = \dataset\build_image('R', $env, $source_file, $sku, $target_ext, size_decode($resize));
	} else {
		$res3 = \dataset\build_image('X', $env, $source_file, $sku, $target_ext);
	}

	//$res4 = \dataset\build_image('Z', $env, $res3[1]['path'], $sku, $target_ext);

	$result['code'] = max([$res1[0], $res2[0], $res3[0]]);
	$result['type'] = $check["mime"];
	$result['images'] = array(
		'source'     => $res1[1],
		'thumbnail'  => $res2[1],
		'resize'     => $res3[1],
		//'resizenobg' => $res4[1],
	);
	$result['product'] = [
		'sku' => $sku,
		'url' => \dataset\get_producturl($env, $sku)
	];

	// Save QRCode
	$image_url   = $res1[1]['url'];
	\dataset\build_qrcode($env, $sku, $image_url);

	$uploadOk = 1;
} catch (Exception $e) {
	$result['msg'] = $e->getMessage();
	$uploadOk = 0;
	$result['code'] = $e->getCode();
	http_response_code($e->getCode());
}

echo json_encode($result);
