<?php

$allowed_users = [
    'admin',
    'expedito',
    'hmorize'
];


$user = $_SERVER["REMOTE_USER"];
if (!is_string($user) ||  strlen($user) < 2 || false === array_search($user, $allowed_users)) {
    header("HTTP/1.1 403 Forbidden");
    // header("WWW-Authenticate", 'Basic realm="Login required"');
    // header("Content-Type", 'application/json');
    echo "<p>Usuário {$user} ?> não pode visualizar esta página</p>";
    echo '<a href="/logout.php">Sair</a>';
    exit;
}

$env = $_GET['env'] ?? 'dev';
$page = $_GET['p'] ?? 0;
$pagesize = 20;

header('Content-Type', 'text/plain');

$result = [];

$keyforchar = [
    'S' => 'source',
    'T' => 'thumbnail',
    'R' => 'resize'
];

$wrongfiles = [];

require 'arquivos.php';

$basedir = \dataset\get_basedir($env);
$datedirs = scandir($basedir, SCANDIR_SORT_DESCENDING);


foreach ($datedirs as $datedir) {
    if ($datedir[0] !== '.' && $datedir[0] === '2') {
        // echo $datedir . '<br/>';
        $skudirs = scandir($basedir . $datedir, SCANDIR_SORT_DESCENDING);
        foreach ($skudirs as $skudir) {
            if ($skudir[0] !== '.' && $skudir[0] === '2') {
                // echo $skudir . '<br/>';
                $path = sprintf('%s/%s/%s/', $basedir, $datedir, $skudir);
                $files = scandir($path, SCANDIR_SORT_DESCENDING);

                $sku = $skudir;
                $result[$sku] = $result[$sku] ?? [
                    'images' => [
                        'source' => '',
                        'thumbnail' => '',
                        'resize' =>  ''
                    ],
                    // 'where' => [$datedir, $skudir],
                    'info' => []
                ];

                foreach ($files as $file) {
                    if ($file[0] !== '.') {
                        // echo $file . '<br/>';
                        if ((preg_match('/--[RST].jpg$/', $file) || preg_match('/--[RST].png$/', $file)) && strlen($file) > 10) {
                            $char = $file[-5]; //TODO dataset file extract field
                            // array_push($result[$sku]['where'], $file);
                            if (array_key_exists($char, $keyforchar)){
                                $result[$sku]['images'][$keyforchar[$char]] = $file;
                            }
                            else {
                                array_push($wrongfiles, $file);
                            }
                        } elseif (preg_match('/--Q.svg$/', $file)) {
                            $result[$sku]['qrcode'] = $file;
                        } elseif (preg_match('/.json$/', $file)) {
                            $result[$sku]['info'] = json_decode(file_get_contents($path . $file), JSON_OBJECT_AS_ARRAY);
                        }
                    }
                }
            }
        }
    }
}

$keys = array_slice(array_keys($result), $page * $pagesize, $pagesize);
?>

<html>

<head>
    <title>Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<style>
    img:not(.large-image) {
        width: 4rem;
        height: 4rem;
        object-fit: contain;
        font-size: small;
    }

    img.large-image {
        max-width: 40vw;
        max-height: 80vh;
        object-fit: contain;
    }

    table {
        margin-top: 2ch;
        width: 100%;
        /* height: 40%; */
    }

    tr {
        /* display: flex; */
        /* padding: 4px; */
        max-height: 5em;
    }

    td {
        position: relative;
    }

    tr:nth-of-type(odd) {
        background-color: lightgray;
    }

    tr:hover {
        background-color: coral;
    }

    .large {
        position: absolute;
        display: flex;
        flex-direction: column;
        left: -9999px;
        max-width: 40vw;
    }

    .large span {
        background-color: white;
        padding: 2px;
    }

    img.source:hover+.large,
    img.source:active+.large {
        position: fixed;
        z-index: 1;
        left: 50vw;
        top: 10vh;
        border: solid black 2px;
    }
</style>

<script>
</script>

<?
$tzBr    = new DateTimeZone('America/Sao_Paulo');
$tzUtc   = new DateTimeZone('UTC');

function getimgsize($url, $baseurl, $env, $sku)
{
    // global $basedir;
    $basename = basename($url);
    $date = substr($basename, 0, 8);
    // $filename = $basedir . $date . '/' . $basename;
    $filename = \dataset\get_targetdir($env, $sku) . $basename;
    if (is_file($filename))
        return implode('x', array_slice(getimagesize($filename), 0, 2));
    else
        return '0x0';
}
?>

<body>
    <h1>Admin</h1>

    <p>Bem Vindo, <?= $user ?>! [<a href="/logout.php">Sair</a> | <a href="/index.html">index.html</a> | <a href="/image.html">image.html</a>]</p>

    <div id="form">
        <label for="page">Página:</label>
        <input id="page" name="page" type="number" value="<?= $page ?>" min="0" />

        <span style="border: solid black 1px; padding: 2px; margin: 2px;">
            <input id="env-dev" type="radio" name="env" value="dev" <?= $env === 'dev' ? 'checked' : '' ?> />
            <label for="env-dev">dev</label>

            <input id="env-prod" type="radio" name="env" value="prod" <?= $env === 'prod' ? 'checked' : '' ?> />
            <label for="env-prod">prod</label>
        </span>

        <button id="refresh" value="Atualiza" onclick="alert">Atualizar</button>
    </div>

    <script>
        $refreshButton = document.getElementById("refresh");
        $pageInput = document.getElementById("page");

        function getSelectedEnv() {
            return document.querySelector('input[name="env"]:checked').value;
        }
        $refreshButton.onclick = e => window.location = `/admin.php?env=${getSelectedEnv()}&p=${$pageInput.value}`;
    </script>

    <table id="data">
        <tr>
            <td>Data</td>
            <td>Imagens</td>
            <td>SKU</td>
            <td>Produto</td>
            <td>Background</td>
            <td>Download</td>
        </tr>
        <? foreach ($keys as $sku) : ?>
            <?
            list($date, $datetime) = sku_extract($sku, ['date', 'datetime']);
            $datetime     = DateTime::createFromFormat('Ymd-His', $datetime, $tzUtc)->setTimeZone($tzBr)->format("d/m/Y H:i:s");
            $baseurl      = \dataset\get_producturl($env, $sku);
            $sourceImage  = $baseurl . ($result[$sku]['images'] ?? [])['source'];
            $thumbImage   = $baseurl . ($result[$sku]['images'] ?? [])['thumbnail'] ?? $sourceImage;
            $resizedImage = $baseurl . ($result[$sku]['images'] ?? [])['resize'] ?? $sourceImage;
            $qrcodeUrl    = $baseurl . ($result[$sku]['qrcode'] ?? null);
            $productUrl   = $result[$sku]['info']['product']['url'] ?? '-';
            $noBg         = $result[$sku]['info']['images']['noBg'] ?? '-';
            ?>
            <tr>
                <td><?= $datetime ?></td>
                <td>
                    <img src="<?= $thumbImage ?>" class="source" title="<?= $thumbImage ?>" alt="Thumbnail" />
                    <span class="large">
                        <img src="<?= $thumbImage ?>" class="large-image" title="<?= $thumbImage ?>">
                        <span><?= getimgsize($thumbImage, $baseurl, $env, $sku) ?> pixels</span>
                    </span>

                    <img src="<?= $sourceImage ?>" class="source" title="<?= $sourceImage ?>" alt="Cropped">
                    <span class="large">
                        <img src="<?= $sourceImage ?>" class="large-image" title="<?= $sourceImage ?>">
                        <span><?= getimgsize($sourceImage, $baseurl, $env, $sku) ?> pixels</span>
                    </span>

                    <img src="<?= $resizedImage ?>" class="source" title="<?= $resizedImage ?>" alt="Resized">
                    <span class="large">
                        <img src="<?= $resizedImage ?>" class="large-image" title="<?= $resizedImage ?>">
                        <span><?= getimgsize($resizedImage, $baseurl, $env, $sku) ?> pixels</span>
                    </span>

                    <img src="<?= $qrcodeUrl ?>" class="source" title="<?= $qrcodeUrl ?>" alt="QRCode">
                    <span class="large">
                        <img src="<?= $qrcodeUrl ?>" class="large-image" title="<?= $qrcodeUrl ?>">
                        <span>Alguma informação aqui</span>
                    </span>
                </td>
                <td><?= $sku ?></td>
                <td>
                    <? if ($productUrl !== '-') : ?>
                        <a href="<?= $productUrl ?>" target="_blank">Acessar</a>
                    <? else : ?>
                        -
                    <? endif; ?>
                </td>
                <td><?= $noBg === true ? 'Remover' : ($noBg === false ? 'Manter' : '-') ?></td>
                <td>
                    <span>
                        <a href="<?= $thumbImage ?>" target="_blank" download>Thumb</a>
                        <br />
                        <a href="<?= $sourceImage ?>" target="_blank" download>Cropped</a>
                        <br />
                        <a href="<?= $qrcodeUrl ?>" target="_blank" download>QRCode</a>
                        <br />
                        <a href="<?= $resizedImage ?>" target="_blank" download>Preview</a>
                        <br />
                        <a href="<?= $baseurl ?>" target="_blank">Explorar</a>
                    </span>
                </td>
            </tr>
        <? endforeach; ?>
    </table>

    <!-- <pre><= json_encode($result, JSON_PRETTY_PRINT); ?> </pre> -->
</body>

<script>
    var wrongfiles = <?= json_encode($wrongfiles) ?>;
    // console.log(wrongfiles);
</script>

</html>