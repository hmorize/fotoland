<?php

require __DIR__ . '/vendor/autoload.php';

require_once 'escrevelog.php';
require_once 'comprimepng.php';
/**
 *
 * $image         path completo pra imagem a ter o background removido
 */
function tira_backgroud($image)
{
    $pos = strpos($image, '.jpg');
    if ($pos === false) {
        return $image;
    }
    $image = str_replace('--X.jpg', '--S.jpg', $image);
    // $img_sem_bg = substr($image, 0, $pos) . '.png';
    $img_sem_bg = str_replace('--S.jpg', '--R.png', $image);
    loga_linha('tira_backgroud imagem orig', $image);
    loga_linha('tira_backgroud img_sem_bg', $img_sem_bg);

    $client = new GuzzleHttp\Client();
    $handle = fopen($image, 'r');
    if ($handle === false) {
        loga_linha('tira_backgroud não conseguiu abrir: ', $image);
        return $img_sem_bg;
    }
    $res = $client->post('https://sdk.photoroom.com/v1/segment', [
        'multipart' => [
            ['name'     => 'image_file', 'contents' => $handle],
            ['name'     => 'size', 'contents' => 'preview'] // preview auto
        ],
        'headers' => ['X-Api-Key' => '15fdebbdd967800135e432107622de397d674fdb']
    ]);

    if ($res->getStatusCode() !== 200) {
        loga_linha('tira_backgroud: ERROR', $res->getStatusCode());
    }
    $fp = fopen($img_sem_bg, "wb");
    if ($fp === false) {
        loga_linha('tira_backgroud não conseguiu abrir wb: ', $img_sem_bg);
        return $img_sem_bg;
    }
    fwrite($fp, $res->getBody());
    fclose($fp);
    loga_linha('tira_backgroud: OK:', $img_sem_bg);
    compress_png($img_sem_bg);
    return $img_sem_bg;
}
