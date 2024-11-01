<?php
/**
 * @ Author: Bill Minozzi
 * @ Copyright: 2022 www.BillMinozzi.com
 * Created: 2022 - Sept 20
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
set_time_limit(60);

global $toolsfors3_region;
global $toolsfors3_secret_key;
global $toolsfors3_access_key;
global $toolsfors3_s3;
global $toolsfors3_config;

if (current_user_can('administrator')) {
    // The current user is an administrator
    //echo 'You are an administrator.';
} else {
    // The current user is not an administrator
    die('You are not an administrator.');
}

?>
<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="basicToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
        </div>
        <div class="toast-body">
        </div>
    </div>
</div>
<?php
if (empty($toolsfors3_region) or empty($toolsfors3_secret_key) or empty($toolsfors3_access_key)) {
    echo '<div class="toolsfors3_alert">';
    echo 'Please, fill out the 3 fields at Settings TAB';
    echo '</div>';
    return;
}
$endpoints = "https://" . $toolsfors3_region . ".amazonstorage.com";
if (empty($_SESSION['post'])) {
    $_SESSION['post'] = sanitize_text_field($_POST);
}

use Aws\Exception\AwsException;

$path222 = esc_url(TOOLSFORS3PATH) . "/functions/toolsfors3_connect.php";
require_once esc_url($path222);


if (isset($_GET['bucket'])) {
    $toolsfors3_bucket_name = sanitize_text_field($_GET['bucket']);
}

if (isset($_GET['prefix'])) {
    $prefix = sanitize_text_field($_GET['prefix']);
} else {
    $prefix = '';
}

if (isset($_GET['prefix'])) 
  $s3path = sanitize_text_field($_GET['prefix']);
else 
  $s3path = '';

/*
if (!isset($toolsfors3_bucket_name))
    $toolsfors3_bucket_name = '';
if (isset($_GET['prefix'])) $path = sanitize_text_field($_GET['prefix']);
else $path = '';
$toolsfors3_config = ['s3-access' => ['key' => $toolsfors3_access_key, 'secret' => $toolsfors3_secret_key, 'bucket' => $toolsfors3_bucket_name, 'region' => $toolsfors3_region, 'version' => 'latest', 'endpoint' => $endpoints]];
*/



try {
    //$s3 = new Aws\S3\S3Client(['credentials' => ['key' => $toolsfors3_config['s3-access']['key'], 'secret' => $toolsfors3_config['s3-access']['secret']], 'use_path_style_endpoint' => true, 'force_path_style' => true, 'endpoint' => $toolsfors3_config['s3-access']['endpoint'], 'version' => 'latest', 'region' => $toolsfors3_config['s3-access']['region']]);
    // TEST
    $buckets = $toolsfors3_s3->listBuckets();
} catch (AWSException $e) {
    echo '<div class="toolsfors3_alert">';
    echo "<b>" . esc_attr($e->getStatusCode()) . "\n" . esc_attr($e->getAwsErrorCode()) . "</b>";
    echo esc_attr(explode(';', $e->getMessage())[1]);
    echo "</div>";
    return;
}

// $url = "$baseurl/{$toolsfors3_config['s3-access']['bucket']}";

/*
if (isset($_GET['prefix'])) {
    $prefix = sanitize_text_field($_GET['prefix']);
} else {
    $prefix = '';
}
*/




?>
<?php
?>
<div class="row">
    <div class="col-md-6 center">
        <form action="tools.php" method='GET'>
            <?php if (!empty($toolsfors3_bucket_name)) {
                echo '<span class="h5">';
                echo 'Current S3 Bucket: ' . esc_attr(htmlspecialchars($toolsfors3_bucket_name));
                echo '</span>';
            }
            ?>
            <?php
            echo '<br>';
            echo '<br>';
            echo '<label for="buckets">Choose a Bucket:</label>';
            echo '&nbsp;';
            echo '<select  name="bucket" id="select_bucket">';
            foreach ($buckets['Buckets'] as $bucket) {
                echo '<option value="';
                echo esc_attr($bucket['Name']);
                echo '"';

                if (isset($toolsfors3_bucket_name) && $toolsfors3_bucket_name == $bucket['Name']) {
                    echo ' selected';
                }

                


                echo '>';
                echo esc_attr($bucket['Name']);
                echo '</option>';
            }
            echo '</select>';
            echo '<input type="hidden" size="10" name="page" value="toolsfors3_admin_page">';
            echo '<input type="hidden" size="10" name="tab" value="amazon">';
            echo '&nbsp;<button class="btn btn-outline-success" type="submit"><i class="bi bi-arrow-right"></i></button>';
            echo '<br>';
            echo '<br>';
            echo '<br>';


 
            $nonce = wp_create_nonce('toolsfors3_action');
            echo '<input type="hidden" name="toolsfors3_nonce" id="toolsfors3_nonce" value="' . esc_attr($nonce) . '">';
            


    // id="toolsfors3_nonce"
?>

        </form>
    </div>
    <div class="col-md-2">
        <span><small><?php echo 'Current Endpoint: ' .esc_attr(htmlspecialchars($endpoints)) ?><br /></small></span>
    </div>
</div>
<br />


<?php 

if (! isset($toolsfors3_bucket_name) or empty($toolsfors3_bucket_name)) {
     return;
}


 


?>

<div id="browser-table" class="container-fluid">
    <?php
    if (!empty($toolsfors3_bucket_name)) {
    ?>
        <form action="#" method='POST'>
            <div id="alert_placeholder"></div>
            <?php
            echo '<input type="hidden" id="toolsfors3_prefix" name="toolsfors3_prefix" value="' . esc_attr($prefix) . '">';
            echo '<input type="hidden" id="toolsfors3_bucket" name="toolsfors3_bucket" value="' . esc_attr($toolsfors3_bucket_name) . '">';
            ?>
            <div id="action-btns">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <input class="search form-control" placeholder="Search Table" onkeypress="return event.keyCode != 13;" />
                    <span class="btn btn-primary toolsfors3_main_btn float-right mr-3" data-bs-toggle="modal" data-bs-target="#upload">Upload <i class="bi bi-cloud-upload-fill"></i></span>
                    <span class="btn btn-success toolsfors3_main_btn float-right mr-3" data-bs-toggle="modal" data-bs-target="#new-folder">New Folder <i class="bi bi-plus h5"></i></span>
                    <button id="delete-button" class="btn btn-danger toolsfors3_main_btn float-right mr-3" type="submit">Delete Selected <i class="bi bi-trash-fill"></i></button>
                </div>
            </div>
            <div id="loading" class="container-fluid center">
                <div class="spinner-border" role="status"></div>
                <div class="spinner-text">Loading... Please Wait.<br></div>
            </div>
            <div id="deleting" class="container-fluid center">
                <div class="spinner-border" role="status"></div>
                <div class="spinner-text">Deleting... Please Wait.</div>
            </div>
            <div id="creating" class="container-fluid center">
                <div class="spinner-border" role="status"></div>
                <div class="spinner-text">Creating Folder... Please Wait.</div>
            </div>
            <div id="wait" class="container-fluid center">
                <div class="spinner-border" role="status"></div>
                <div class="spinner-text">Please Wait...<br><br></div>
            </div>
            <table id="table" class="table table-hover" style="display:none">
                <thead class="thead-dark">
                    <tr>
                        <th scope="col"><input class="form-check-input" type="checkbox" name="delete-list-all[]" id="delete-list-all" />&nbsp<label class='form-check-label' for='delete-list-all'>Select</label></th>
                        <th scope="col"></th>
                        <th class="sort" data-sort="filename" scope="col">Filename / Folder</th>
                        <th style="display:none;" class="sort" data-sort="size" scope="col">Size</th>
                        <th class="sort" data-sort="size" scope="col">Size</th>
                        <th class="sort" data-sort="last_modified" scope="col">Last Modified</th>
                    </tr>
                </thead>
                <tbody class="list" id="browser-tbody">
                    <?php
                    $objectNumber = 0;
                    if (!empty($prefix)) {
                        $back = preg_replace('/^\.*$/', '', dirname($prefix));
                        echo '<tr><td></td>';
                        echo '<td><a href=tools.php?page=toolsfors3_admin_page&tab=amazon&bucket=';
                        echo esc_attr($toolsfors3_bucket_name);
                        echo '&prefix=' . esc_attr($back);
                        if (dirname($prefix) !== '.')
                            echo "/";
                        else
                            echo "";
                        echo "><i class='bi bi-arrow-90deg-up'></i> ../</a>", '</td><td></td><td></td><td></td>', '</tr>';
                    }
                    try {
                        // usando o Delimiter nao vem diretorios
                        $output = $toolsfors3_s3->getIterator('ListObjects', array(
                            'Bucket' => $toolsfors3_bucket_name,
                            'Prefix' => "$prefix",
                            'Delimiter' => '/'
                        ));
                        /*
                        PAGINATOR? Retorna mais de 1000
                        $output =$toolsfors3_s3->getPaginator(
                            'ListObjects',
                            array(
                                'Bucket' => $toolsfors3_config['s3-access']['bucket'],
                                'Prefix' => "$prefix",
                               // 'Delimiter' => '/'
                            )
                        );
                        */
                        $results = $toolsfors3_s3->getPaginator('ListObjects', ['Bucket' => $toolsfors3_bucket_name, 'Prefix' => "$prefix", 'Delimiter' => '/']);
                        $expression = '[CommonPrefixes[].Prefix][]';
                        foreach ($results->search($expression) as $_prefix) {
                            $name = $_prefix;

                            // 2024
                            /*
                            if (substr($name, 0, strlen($prefix)) == $prefix) {
                                $name = substr($_prefix, strlen($prefix));
                                if (substr($name, -1) == '/') $name = substr($name, 0, strlen($name) - 1);
                            }
                            */
                            if (!empty($prefix)){
                                if (strpos($name, $prefix) === 0) {
                                    $name = substr($name, strlen($prefix));
                                }
                            }


                            //if (strpos($name, $prefix) === 0) {
                                //$name = substr($name, strlen($prefix));


                                if (substr($name, 0, 1) == '/') {
                                    $name = substr($name, 1);
                                }
                                if (substr($name, -1) == '/') {
                                    $name = substr($name, 0, -1);
                                }
                           // }


                            $objectNumber++;
                            //echo '<tr>',
                            //'<td>', "<div class='form-check'><input class='form-check-input' type='checkbox' name='delete-list[]' value='$_prefix' id='$_prefix'><label class='form-check-label' for='$_prefix'></label></div>",
                            //'</td>';
                            echo '<tr>',
                            '<td>', "<div class='form-check'><input class='form-check-input' type='checkbox' name='delete-list[]' value='" . esc_attr($_prefix) . "' id='" . esc_attr($_prefix) . "'><label class='form-check-label' for='" . esc_attr($_prefix) . "'></label></div>",
                            '</td>';

                            echo '<td>', '<img src="';
                            echo esc_attr(TOOLSFORS3IMAGES);
                            echo '/folder.png"  width=24px ></td>';
                            echo '<td class="filename"><a href=tools.php?page=toolsfors3_admin_page&tab=amazon&bucket=';
                            echo esc_attr($toolsfors3_bucket_name);
                            echo '&prefix=' . esc_attr($_prefix) . '>' . esc_attr($name) . '</a>';
                            echo '</td>', '<td class="size">', "Folder", '</td>', '<td class="last_modified">', '-', '</td>',
                            '</tr>';
                            // End Folder
                        }
                        $totalSize = '0';
                        foreach (($output) as $object) {
                            global $totalSize;
                            if ($object['Key'] == $prefix) continue;
                            $name = $object['Key'];
                            $pos = strpos($name, '/');
                            $name = substr($name, $pos + 1);
                            if (empty($name)) continue;
                            $objectNumber++;
                            if (empty($object['Size'])) {
                                $totalSize = '0';
                            } else {
                                $totalSize += $object['Size'];
                            }
                            if (isset($object['Key'])) {
                                $name = $object['Key'];
                                $pos = strrpos($name, '/');
                                $name = substr($name, $pos);
                                if (substr($name, 0, 1) == '/')
                                    $name = substr($name, 1);
                                /*
                                $toolsfors3_config[0] = 'bucket';
                                $toolsfors3_config[1] = 'file';
                                $toolsfors3_config[2] = 'region';
                                $toolsfors3_config[3] = 'access_key';
                                $toolsfors3_config[4] = 'secret_key';
                                $toolsfors3_config[5] = 'end_points';
                                */
  
                                echo '<tr><td><div class="form-check">';
                                echo ' <input class="form-check-input" type="checkbox" name="delete-list[]"';
                                echo ' value="'. esc_attr($object['Key']).'"';
                                echo ' id="'. esc_attr($object['Key']). '><label class="form-check-label"';
                                echo ' for='. esc_attr($object['Key']);
                                echo '></label></div></td>';
                                echo '<td><img src="' . esc_attr(TOOLSFORS3IMAGES) . '/file.png" width=24px ></td>';
  
           
                                
                                echo "<td class='filename'>";

                                $nonce = wp_create_nonce('toolsfors3_download');

                                /*
                                echo '<a target="_blank" id="' . esc_attr($object['Size']) . '" class="wait_download300" href="' . esc_attr(TOOLSFORS3URL) . 's3api/download.php' .
                                    '?file=' . rawurlencode(esc_attr($object['Key'])) .
                                    '&bucket=' . rawurlencode(esc_attr($toolsfors3_bucket_name)) .
                                    '&region=' . rawurlencode(esc_attr($toolsfors3_config["s3-access"]["region"])) .
                                    '&access_key=' . rawurlencode(esc_attr($toolsfors3_access_key)) .
                                    '&secret_key=' . rawurlencode(esc_attr($toolsfors3_secret_key)) .
                                    '&end_points=' . rawurlencode(esc_attr($endpoints)) .
                                    '&key=' . rawurlencode(md5(sanitize_text_field($_COOKIE['PHPSESSID']))).
                                    '&nonce=' . rawurlencode($nonce);  
                                echo '" >';
                                */

                                echo '<a target="_blank" id="' . esc_attr($object['Size']) . '" class="wait_download300" href="' . esc_attr(TOOLSFORS3URL) . 's3api/download.php' .
                                '?file=' . rawurlencode(esc_attr($object['Key'])) .
                                '&bucket=' . rawurlencode(esc_attr($toolsfors3_bucket_name)) .
                                '&region=' . rawurlencode(esc_attr($toolsfors3_config["s3-access"]["region"])) .
                                '&access_key=' . rawurlencode(esc_attr($toolsfors3_access_key)) .
                                '&secret_key=' . rawurlencode(esc_attr($toolsfors3_secret_key)) .
                                '&end_points=' . rawurlencode(esc_attr($endpoints)) .
                                '&nonce=' . rawurlencode($nonce);  
                            echo '" >';

                                echo esc_attr($name) . '</a>';
                                echo '</td>';
                                echo '<td style="display:none;" class="size">', esc_attr($object['Size']), '</td>', '<td class="size">', esc_attr(size_format($object['Size'])), '</td>', '<td class="last_modified">', esc_attr($object['LastModified']), '</td>',
                                '</tr>';
                            }
                        }
                    } catch (AWSException $e) {
                        echo '<div class="alert alert-danger alert-dismissible mt-3 mb-3"><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                        echo "<b>" . esc_attr($e->getStatusCode()) . "\n" . esc_attr($e->getAwsErrorCode()) . "</b>";
                        echo esc_attr(explode(';', $e->getMessage())[1]);
                        echo "</div></div><script>document.getElementById('loading').style.visibility = 'hidden';</script>";
                    }
                    ?>
                    <div id="modal-dialog" class="modal">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h3>Are you sure</h3>
                                    <a href="#" id="btnClose" data-dismiss="modal" aria-hidden="true" class="close">×</a>
                                </div>
                                <div class="modal-body">
                                    <p>Do you want to submit the form?</p>
                                </div>
                                <div class="modal-footer">
                                    <a href="#" id="btnYes" class="btn confirm">Yes</a>
                                    <a href="#" id="btnNo" data-dismiss="modal" aria-hidden="true" class="btn secondary">No</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                    echo '<div class="row mt-2">';
                    echo '<div class="col-9">';
                    echo "<a href=tools.php?page=toolsfors3_admin_page&tab=";
                    echo "amazon&bucket=";
                    echo esc_attr($toolsfors3_config['s3-access']['bucket']);
                    // Breadcrumb
                    echo "><i class='bi bi-house'></i></a>";
                    if ($s3path != '') {
                        $exploded = explode('/', $s3path);
                        $count = count($exploded);
                        $array = array();
                        $parent = '';
                        for ($i = 0; $i < $count; $i++) {
                            $parent = trim($parent . '/' . $exploded[$i], '/');
                            echo "/";
                            echo '<a href="tools.php?page=toolsfors3_admin_page&tab=amazon';
                            if ($toolsfors3_bucket_name != '')
                                echo '&bucket=' . esc_attr($toolsfors3_bucket_name);
                            if ($parent != '')
                                echo '&prefix=' . esc_attr($parent) . '/';
                            echo '"/>';
                            echo esc_attr($exploded[$i]) . '</a>';
                        }
                    }
                    echo "</div>", "<div class='col-3 d-flex justify-content-end gap-1'>";
                    $FriendlytotalSize = size_format($totalSize);
                    echo '<b>Size:</b>' . esc_attr($FriendlytotalSize);
                    if ($objectNumber >= '1000') {
                        echo ' <b>Number of Objects:</b>' . esc_attr($objectNumber) . ' <i class="text-warning bi bi-exclamation-triangle-fill" data-bs-toggle="tooltip" data-bs-placement="top" title="Lots of objects, filter and sort may be slow."></i>';
                    } else {
                        echo ' <b>Number of Objects:</b> ' . esc_attr($objectNumber);
                    }
                    echo "</div>";
                    echo "</div>";
                    ?>
                </tbody>
            </table>
            <div id="toolsfors3_pagination" style="display:none" ;>
                Rows per Page: 20<ul class="pagination"></ul>
            </div>
        <?php
    }; ?>
        </form>
</div>
<!-- // Modals... -->
<div class="modal fade" id="upload" tabindex="-1" aria-labelledby="uploader" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Files</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <div class="modal-body">
                <p>Select files, when you're finished click "Upload"</p>
                <div class="dropzone"></div>
            </div>
            <div class="modal-footer">
                <button id="cancelUpload" class="btn btn-danger">Cancel Upload <i class="bi bi-x"></i></button>
                <button id="startUpload" class="btn btn-primary">Upload <i class="bi bi-arrow-right"></i></button>
                
            </div>
        </div>
    </div>
</div>
<?php
?>
<div class="modal fade" id="new-folder" tabindex="-1" aria-labelledby="new-folder" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <?php
            if (!isset($prefix)) $prefix = '';
            else $prefix = trim($prefix);
            if (empty($prefix)) echo '<form action="tools.php?page=toolsfors3_admin_page&tab=toolsfors3_add" method="POST">';
            else echo '<form action="tools.php?page=toolsfors3_admin_page&tab=toolsfors3_add&prefix=' . esc_attr($prefix) . '" method="POST">';
            ?>
            <form action="#" method='POST'>
                <div class="modal-header">
                    <h5 class="modal-title">Add Folder (Prefix)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body">
                    <input type="text" name="new-folder" id="new-folder-input" placeholder="Folder Name" class="form-control w-100  me-2" required>
                </div>
                <div class="modal-footer">
                    <button id="add_folder" class="btn btn-primary" type="submit">Add Folder <i class="bi bi-arrow-right"></i></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
$path = TOOLSFORS3PATH . "/s3api/toolsfors3_js.php";
require_once($path);
