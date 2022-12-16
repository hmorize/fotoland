<?

/**
 * Dimensions (or size) encoding:
 *   000x000     height x width, in centimeters
 */

function size_encode($width, $height, $validate = true) {
    if ($validate) {
        if ($width < 1 || $width > 999) return false;
        if ($height < 1 || $height > 999) return false;
    }
    return sprintf("%03dx%03d", $height, $width);
}

function size_decode($size) {
    if (!preg_match('/^([0-9]+)x([0-9]+)$/', $size)) return false;
    return array_map( function ($e) { return (int) $e; }, explode('x', $size, 2));
}



/**
 * SKU Pattern:
 * 
 *   yyyymmdd-hhmmss-tt000x000-rrzzzzzzzzzzzz (ex. 20221123-003141-TT195x095-b8fulano)
 * 
 * Where:
 *   yyyymmdd    The creation date (UTC)
 *   hhmmss      The creation time (UTC)
 *   tt          Type: TT=totem, CA=bighead, FA, PA, ...
 *   000x000     Dimensions: height x width, in centimeters
 *   rr          Random value (hexadecimal: 00-ff) to prevent name collision
 *   zzzz...     User id, until 12 characters (may be the Wix username).
 */

 $skuFields = [
    'date'     => [ 'range' => [ 0,  8], 'type' => 'str' ],  // offset, length
    'time'     => [ 'range' => [ 9,  6], 'type' => 'str' ],
    'datetime' => [ 'range' => [ 0, 15], 'type' => 'str' ],  // 8+1+6
    'type'     => [ 'range' => [16,  2], 'type' => 'str' ],
    'height'   => [ 'range' => [18,  3], 'type' => 'int' ],
    'width'    => [ 'range' => [22,  3], 'type' => 'int' ],
    'size'     => [ 'range' => [18,  7], 'type' => 'str' ],  // 3+1+3
    'rand'     => [ 'range' => [26,  2], 'type' => 'str' ],
    'user'     => [ 'range' => [28, 12], 'type' => 'str' ],
];

function sku_gettypes() {
    return ['TT', 'CA', 'FA', 'PA'];
}


/**
 * Create an SKU using current date and time.
 * 
 * Parameters:
 *   width      in centimeters
 *   height     in centimeters
 *   type       TT, CA, FA, ... (totem, bighead, ...)
 *   uid        user id (from Wix)
 */
function sku_create($width, $height, $type, $uid) {
    $datetime = gmdate("Ymd-His");
    $rnd = random_int(0, 255);
    $sku = sku_encode($datetime, $width, $height, $type, $uid, $rnd);
    return $sku;
}

/**
 * Generate an SKU pattern from given fields.
 * 
 * If $datetime is null, current date and time will be used.
 * If $rnd is null, a random value is generated.
 * 
 * Parameters:
 *   datetime   date and time (see string pattern above)
 *   width      in centimeters
 *   height     in centimeters
 *   type       T, H or W (totem, bighead or wallpaer)
 *   uid        user id (from Wix)
 *   rnd        2-char random value
 */
function sku_encode($datetime, $width, $height, $type, $uid, $rnd = null) {
    if ($datetime === null) $datetime = gmdate("Ymd-His");
    if ($rnd === null) $rnd = $rnd = random_int(0,255);
    $size = size_encode($width, $height);
    if ($size === false || !in_array($type, sku_gettypes())) return false;
    
    $sku = sprintf("%s-%s%s-%02x%s", $datetime, $type, $size, $rnd, $uid ?? '');
    $sku = substr($sku, 0, 40);
    return $sku;
}

// TODO
function sku_validate() {
    return true;//TODO
}

/**
 * Extracts one or more fields from a SKU.
 * 
 * sku      string matching the SKU pattern above
 * fields   array of field names: date, time, datetime, width, height, type, ...
 */
function sku_extract($sku, $fields) {
    if (!sku_validate($sku)) return false;
    global $skuFields;
    $values = [];
    foreach ($fields as $field) {
        if (array_key_exists($field, $skuFields)) {
            // echo $field . "<br/>";
            // echo json_encode($skuFields[$field]['range']) . "<br/>";
            $value = substr($sku, ...$skuFields[$field]['range']);
            // echo $value . "<br/>";
            switch ($skuFields[$field]['type']) {
                case 'int':
                    $value = intval($value);
                    break;
                case 'str':
                    break;
                default:
                    $value = null;
                    break;
            }
        }
        else $value = null;   // invalid field name

        array_push($values, $value);
    }
    return $values;
}

function sku_decode($sku) {
    if (!sku_validate($sku)) return false;
    global $skuFields;
    $fields = array_keys($skuFields);
    $values = sku_extract($sku, $fields);
    $result = [];
    foreach ($fields as $index => $field) $result[$field] = $values[$index];
    return $result;
}

?>
