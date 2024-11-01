<?php 

    $path = TOOLSFORS3PATH . "/vendor/autoload.php";
    require_once esc_url($path);
    // $region = "eu2";
    //$endpoints = "https://eu2.contabostorage.com";

    global $toolsfors3_region;
    global $toolsfors3_secret_key;
    global $toolsfors3_access_key;
    global $toolsfors3_s3;
    global $toolsfors3_config;


    if(!isset($toolsfors3_region) or !isset($toolsfors3_secret_key) or !isset($toolsfors3_access_key)) {
        error_log("Fail to Connect to Cloud: (-51)");
    }

    if(empty($toolsfors3_region) or empty($toolsfors3_secret_key) or empty($toolsfors3_access_key)) {
        error_log("Fail to Connect to Cloud: (-52)");
    }

    // $endpoints = "https://" . $toolsfors3_region . ".contabostorage.com";
    $endpoints = "https://s3." . $toolsfors3_region . ".amazonaws.com";

    if (isset($_POST['bucket'])) 
        $bucket_name =  sanitize_text_field($_POST['bucket']);

    if (!isset($bucket_name))
       $bucket_name = '';  
    

    try{
        $toolsfors3_config = [
        "s3-access" => [
            'key' => $toolsfors3_access_key,
            'secret' => $toolsfors3_secret_key,
            'bucket' => $bucket_name,
            'region' => $toolsfors3_region,
            'version' => 'latest',
            'endpoint' => $endpoints
        ],
    ];


    $toolsfors3_s3 = new Aws\S3\S3Client([
        "credentials" => [
            "key" => $toolsfors3_config["s3-access"]["key"],
            "secret" => $toolsfors3_config["s3-access"]["secret"],
        ],
        "use_path_style_endpoint" => true,
        "force_path_style" => true,
        "endpoint" => $toolsfors3_config["s3-access"]["endpoint"],
        "version" => "latest",
        "region" => $toolsfors3_config["s3-access"]["region"],
    ]);
    } catch (S3Exception $e) {
        error_log("Fail to Connect to Cloud: ".$key);
        error_log($e->getMessage());
        // wp_die('fail_delete');
    }

    if(!isset($toolsfors3_s3)) {
       error_log("Fail to Connect to Cloud: (-5) ".$key);
    }
