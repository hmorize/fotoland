<?php

include 'vendor/autoload.php';

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

function writeQrCode(string $content, string $destfile = null) {
    $renderer = new ImageRenderer(
        new RendererStyle(400),
        new SvgImageBackEnd()
    );
    $writer = new Writer($renderer);
    //Exibe o QR code na tela

    if ($destfile === null) {
        $writer->writeString($content);
        return true;
    }
    else
        return $writer->writeFile($content, $destfile);
}

?>

