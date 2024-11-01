<?php 
/**
 * @ Author: Bill Minozzi
 * @ Copyright: 2022 www.BillMinozzi.com
 * Created: 2022 - Sept 20 upd 2024/6/28
 */

if (defined("ABSPATH")) {
    exit();
} // Exit if accessed directly




$root = dirname(__FILE__);

// Traverse up the directory hierarchy until wp-load.php is found
while (!file_exists($root . '/wp-load.php')) {
    $root = dirname($root);
    if ($root == dirname($root)) {
        // If we can't go up any further, exit to avoid an infinite loop
        exit('Error: Could not locate wp-load.php');
    }
}

require_once($root . '/wp-load.php');

if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash ($_GET['nonce'])), 'toolsfors3_download')) {
    wp_die('The nonce verification failed. Please try again.');
}



use Aws\S3\Exception\S3Exception;

ini_set("memory_limit", "512M");
set_time_limit(600);

if (!function_exists("esc_attr")) {
    function esc_attr($text)
    {
        return str_replace("<?php ", "", $text);
    }
}
if (!function_exists("sanitize_text_field")) {
    function sanitize_text_field($text)
    {
        return strip_tags($text);
    }
}

/*
if (!isset($_GET["key"])) {
   // die("Fail Autentication (-1)");
}

if ($_GET["key"] != md5($_COOKIE["PHPSESSID"])) {
   // die("Fail Autentication (-2)");
}
*/

require "../vendor/autoload.php";

$config = [];
$config_val = [];
$config[0] = "bucket";
$config[1] = "file";
$config[2] = "region";
$config[3] = "access_key";
$config[4] = "secret_key";
$config[5] = "end_points";
for ($i = 0; $i < count($config); $i++) {
    if (isset($_GET[$config[$i]])) {
        $config_val[$i] = rawurldecode(sanitize_text_field($_GET[$config[$i]]));
    } else {
        ob_end_flush();
        die("Missing Parameter: " . $config[$i]);
    }
}

$config = [
    "s3-access" => [
        "key" => $config_val[3],
        "secret" => $config_val[4],
        "bucket" => $config_val[0],
        "region" => $config_val[2],
        "version" => "latest",
        "endpoint" => $config_val[5],
    ],
];
$s3cloud_access_key = $config_val[3];
$s3 = new Aws\S3\S3Client([
    "credentials" => [
        "key" => $config["s3-access"]["key"],
        "secret" => $config["s3-access"]["secret"],
    ],
    "use_path_style_endpoint" => true,
    "force_path_style" => true,
    "endpoint" => $config["s3-access"]["endpoint"],
    "version" => "latest",
    "region" => $config["s3-access"]["region"],
]);

try {
    if (headers_sent()) {
        ob_end_flush();
        die(
            "File: " .
                __FILE__ .
                " Line: " .
                __LINE__ .
                " Cannot dispatch file, headers already sent."
        );
    }

    $s3->registerStreamWrapper();

    $bucket_name = $config_val[0];
    $s3cloudkey = $config_val[1];

    if (!($stream = fopen("s3://" . $bucket_name . "/" . $s3cloudkey, "r"))) {
        ob_end_flush();
        die(
            "File: " . __FILE__ . " Line: " . __LINE__ . " Cannot open stream."
        );
    }

    $objInfo = $s3->headObject([
        "Bucket" => $bucket_name,
        "Key" => $s3cloudkey,
    ]);

    $filesize = $objInfo["ContentLength"];

    // define("S3CLOUDFILENAME", basename($config_val[1]));
    header("Content-Description: File Transfer");
    header("Content-Type: application/octet-stream"); // http://stackoverflow.com/a/20509354
    header(
        'Content-Disposition: attachment; filename="' .
            basename($config_val[1]) .
            '"'
    );
    header("Expires: 0");
    header("Cache-Control: must-revalidate");
    header("Pragma: public");
    header("Content-length: " . $filesize);
    if (ob_get_contents()) {
        ob_end_clean();
    }
    $buffer_size = 1024 * 1024;

    while (!feof($stream)) {
        echo fread($stream, $buffer_size);
    }
    fclose($stream);
    exit();
} catch (S3Exception $e) {
    echo esc_attr($e->getMessage()) . PHP_EOL;
    die();
}