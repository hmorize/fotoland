<?
require_once 'dataset.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST'):
    $sku = $_POST['sku'];
    $info = [
        'product' => [
            'sku'  => $sku,
            'url' => $_POST['purl']
        ],
        'images' => [
            'noBg' => boolval($_POST['noBg']),
            // 'source' => [
            //     'url' => 
            // ]
        ]
    ];
    $data = json_encode($info, JSON_PRETTY_PRINT);
    $env = $_POST['env'];
    $target_dir = \dataset\get_targetdir($env, $sku);
    $filename = $target_dir . '/' . $sku . '.json';
    // echo $filename;
    if (file_put_contents($filename, $data)):
        $result = [ 'code' => 200, 'info' => $info ];
    else:
        $result = [ 'code' => 500, 'info' => $info ];
    endif;
    echo json_encode($result);
endif;
?>
