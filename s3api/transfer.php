<?php
/**
 * @ Author: Bill Minozzi
 * @ Copyright: 2022 www.BillMinozzi.com
 * Created: 2022 - Dec 5 //
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/*
error_reporting(E_ALL);
ini_set('display_errors', 1);
*/
ini_set('max_execution_time', 3600);
set_time_limit(3600);
ini_set('memory_limit', '128M');
//set_time_limit(0);
global $toolsfors3_region;
global $toolsfors3_secret_key;
global $toolsfors3_access_key;

if (current_user_can('administrator')) {
	// The current user is an administrator
	//echo 'You are an administrator.';
} else {
	// The current user is not an administrator
	die('You are not an administrator.');
}

// $endpoints = "https://" . $toolsfors3_region . ".amazonstorage.com";

$path = TOOLSFORS3PATH . "/functions/toolsfors3_connect.php";
require_once esc_url($path);

if (empty($_SESSION['post'])) {
    $_SESSION['post'] = sanitize_text_field($_POST);
}
?>
<div class="container">
	<h3>Transfer Folders Server-Cloud</h3>
<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="basicToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header"></div>
        <div class="toast-body"></div>
    </div>
</div>
<?php
if (empty($toolsfors3_region) or empty($toolsfors3_secret_key) or empty($toolsfors3_access_key)) {
    echo '<div class="toolsfors3_alert">';
    echo 'Please, fill out the 3 fields at Settings TAB';
    echo '</div>';
    return;
}
?>
<div style="float:right">
	<i class="bi bi-file-text" style="font-size:20px;"></i>
	<div id="toolsfors3_debug" style="margin-top: -30px; margin-left: 24px;"><a href="<?php esc_url(TOOLSFORS3HOMEURL);?>/wp-admin/tools.php?page=toolsfors3_admin_page&tab=transfer_debug">Last Transfer Debug</a></div>	
</div>
<form name="toolsfors3_pre_transfer" id="toolsfors3_pre_transfer" action="#" method='GET' >
	<br>
	   Transfer From: &nbsp;
	  <label>
        <input name="toolsfors3_server_cloud" type="radio" value="server" checked />
        <span>Server</span>
      </label>
      <label>
        <input name="toolsfors3_server_cloud" type="radio" value="cloud" />
        <span>Cloud</span>
	  </label>
	  <br>
	  <br>
	<!-- 1 step -->
	<!-- Please, choose one folder From <b>Server</b> below. -->
	<div class="toolsfors3_transfer_row">	
	    <i class="bi bi-hdd" style="font-size:24px;"></i>
		<div id="toolsfors3_selected" style="margin-top: -30px; margin-left: 30px;">Please, choose one folder From <b>Server</b> below.</div>	
        <br>
		<div class="row">	
			<div id="treeview">
				<div class="spinner-border" style="width: 34px; height: 34px;"  role="status">
				<span class="sr-only"></span>
				</div>
			</div>	
		</div>	
	</div> <!-- end 1 step -->
	<br>
	<br>
	<!-- 2 step -->
    <div class="toolsfors3_transfer_row">
      <i class="bi bi-bucket" style="font-size:24px;"></i>
	  <div id="toolsfors3_bucket_selected" style="margin-top: -30px; margin-left: 30px;">Please, choose one bucket From <b>Cloud</b> below.</div>	
	<?php
	use Aws\Exception\AwsException;

	// $path = TOOLSFORS3PATH . "/vendor/autoload.php";
	// require_once($path);

	$path = TOOLSFORS3PATH . "/functions/toolsfors3_connect.php";
    require_once esc_url($path);


	$baseurl = $endpoints;
	$credjson = base64_decode($toolsfors3_access_key);
	$credarray = json_decode($credjson);


	//foreach ($_GET as $key => $value) {
    //    DEBUG2($key);
    //}

	//debug2($_GET['toolsfors3_nonce']);

if (!isset($_GET['toolsfors3_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash ($_GET['toolsfors3_nonce'])), 'toolsfors3_action')) {
   wp_die('The nonce verification failed. Please try again.');
 }

 //debug2($toolsfors3_nonce);

 global $toolsfors3_nonce;

 $toolsfors3_nonce = sanitize_text_field(wp_unslash ($_GET['toolsfors3_nonce']));
   

	if (isset($_GET['bucket'])) {
		$toolsfors3_bucket_name = sanitize_text_field($_GET['bucket']);
	}
	if (!isset($toolsfors3_bucket_name))
		$toolsfors3_bucket_name = '';

	if (isset($_GET['prefix'])) $path = sanitize_text_field($_GET['prefix']);
	else $path = '';

	//$config = ['s3-access' => ['key' => $toolsfors3_access_key, 'secret' => $toolsfors3_secret_key, 'bucket' => $toolsfors3_bucket_name, 'region' => $toolsfors3_region, 'version' => 'latest', 'endpoint' => $endpoints]];
	// die(var_export(__LINE__));
    
	
	try {
		//$s3 = new Aws\S3\S3Client(['credentials' => ['key' => $config['s3-access']['key'], 'secret' => $config['s3-access']['secret']], 'use_path_style_endpoint' => true, 'force_path_style' => true, 'endpoint' => $config['s3-access']['endpoint'], 'version' => 'latest', 'region' => $config['s3-access']['region']]);
		// TEST
		 $buckets = $toolsfors3_s3 ->listBuckets();
	} catch (AWSException $e) {
		echo '<div class="toolsfors3_alert">';
		echo "<b>" . esc_attr($e->getStatusCode()) . "\n" . esc_attr($e->getAwsErrorCode()) . "</b>";
		echo esc_attr(explode(';', $e->getMessage())[1]);
		echo "</div>";
		return;
	}
	

	// $url = "$baseurl/{$config['s3-access']['bucket']}";

	if (isset($_GET['prefix'])) {
		$prefix = sanitize_text_field($_GET['prefix']);
	} else {
		$prefix = '';
	}
	?>
	<br>
		<div class="endpoint">
			Current Endpoint: &nbsp;
			<?php echo esc_attr(htmlspecialchars($toolsfors3_config['s3-access']['endpoint'])) ?>
		</div>

    <div class="col-ld-6 center">
            <?php if (!empty($toolsfors3_bucket_name)) {
                echo '<span class="h5">';
                echo 'Current S3 Bucket: ' . esc_attr(htmlspecialchars($toolsfors3_config['s3-access']['bucket']));
                echo '</span>';
            }
            ?>
            <?php
            echo '<br>';
            echo '<label for="buckets">Choose a Bucket:</label>';
            echo '&nbsp;';
            echo '<select  name="bucket" id="select_bucket">';
            foreach ($buckets['Buckets'] as $bucket) {
                echo '<option value="';
                echo esc_attr($bucket['Name']);
                echo '"';
                if ($toolsfors3_bucket_name == $bucket['Name']) echo ' selected';
                echo '>';
                echo esc_attr($bucket['Name']);
                echo '</option>';
            }
            echo '</select>';
            echo '<input type="hidden" size="10" name="page" value="toolsfors3_admin_page">';
            echo '<input type="hidden" size="10" name="tab" value="amazon">';
            echo '&nbsp;<button id="toolsfors3_choose_bucket" class="btn btn-outline-success" type="submit"><i class="bi bi-arrow-right"></i></button>';
            ?>
        </form>
    </div>
	</div> <!-- end 2 step -->
	<br>

	
	<!-- 3 step -->


	<div class="toolsfors3_transfer_row" style="display:none">
      <div id= "toolsfors3_choose_folder" >
		<i class="bi bi-cloud" style="font-size:24px;"></i>
		<div id="toolsfors3_selected_cloud" style="margin-top: -30px; margin-left: 30px;">Please, choose one folder From <b>Cloud</b> below.</div>	
		<br>
		<div class="row">	
		  <div id="toolsfors3_treeview2">
		</div>	
	    </div>
	  </div> 
	</div> <!-- end  3 step -->
<div style="margin-top:20px;">
	<input type="radio" class="speed" id="very_slow" name="speed" value="very_slow">
    <label for="slow"><?php esc_attr_e('Very Slow',"toolsfors3"); ?></label>
</div>
<div>
    <input type="radio" class="speed" id="slow" name="speed" value="slow">
    <label for="slow"><?php esc_attr_e('Slow',"toolsfors3"); ?></label>
</div>
<div>
    <input type="radio" class="speed" id="normal" name="speed"" value=" normal" checked>
    <label for="normal"><?php esc_attr_e('Normal',"toolsfors3"); ?></label>
</div>
<div>
    <input type="radio" class="speed" id="fast" name="speed"" value="fast">
    <label for="fast"><?php esc_attr_e('Fast',"toolsfors3"); ?></label>
</div>
<div>
    <input type="radio" class="speed" id="very_fast" name="speed"" value="very_fast">
    <label for="fast"><?php esc_attr_e('Very Fast',"toolsfors3"); ?></label>
</div>
<br>
<br>
<button id="open_transfer" style="margin-left: -10px; display:none" class="btn btn-primary toolsfors3_main_btn" data-bs-toggle="modal" data-bs-target="#transfer-form" data-backdrop='static'  data-keyboard='false'>Transfer<i class="bi bi-arrow-right"></i></button>
<button id="valid_open_transfer" style="margin-left: -10px;" class="btn btn-primary toolsfors3_main_btn">Transfer<i class="bi bi-arrow-right"></i></button>
<!-- Modal -->

<div class="modal fade" id="transfer-form" tabindex="-1" aria-labelledby="transfer-form" aria-hidden="true">
    <div style="min-width:800px; "class="modal-dialog">
        <div class="modal-content">
            <?php
            if (!isset($prefix)) $prefix = '';
            else $prefix = trim($prefix);
            if (empty($prefix)) echo '<form action="tools.php?page=toolsfors3_admin_page&tab=toolsfors3_add" method="POST">';
            else echo '<form action="tools.php?page=toolsfors3_admin_page&tab=toolsfors3_add&prefix=' . esc_attr($prefix) . '" method="POST">';
		    ?>
            <form action="#" method='POST'>
                <div class="modal-header">
                    <h3 class="modal-title"><div id="toolsfors3_server_cloud"></div></h3>
                    <!-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button> -->
                </div>
                <div class="modal-body">
				  <div class="toolsfors3_modal_row">		
				    Folder Server: 	<div style="margin-top: -28px; margin-left: 140px; margin-right: 10px;" id="toolsfors3_server_folder_modal"></div>
		          </div>	
					<div class="toolsfors3_modal_row">
					Bucket Name: <div style="margin-top: -28px; margin-left: 140px; margin-right: 10px;"  id="toolsfors3_bucket_name"></div>
					</div>
					<div class="toolsfors3_modal_row">
					Folder Cloud: <div style="margin-top: -28px; margin-left: 140px; margin-right: 10px;"  id="toolsfors3_cloud_folder_modal"></div>
					</div>
					<div class="toolsfors3_modal_row">
					 <div  id="toolsfors3_status_label">Status:</div><div style="margin-top: -25px; margin-left: 120px;"  id="toolsfors3_tansferring_status"></div><div id="toolsfors3_tansferring" style="margin-top: -25px; margin-left: 65px;"></div>
					</div>
					<div class="toolsfors3_modal_row">
						<div class="toolsfors3_log">	
							<label class="toolsfors3_log" for="w3review">Log:</label>
							<textarea id="toolsfors3_log" class="toolsfors3_log" name="toolsfors3_log">
							</textarea> 
					</div>

					<?php echo '<div id="toolsfors3_nonce" style="display:none;" >'. esc_attr(wp_create_nonce('toolsfors3_copy')); ?></div>
					<?php echo '<div id="toolsfors3_truncate" style="display:none;" >'. esc_attr(wp_create_nonce('toolsfors3_ajax_truncate')); ?></div>

				</div>
                <div class="modal-footer">
				<div id="toolsfors3-transfer-spinner" style="display:none;"class="spinner-border" role="status">
					  <span class="sr-only"></span>
				</div>
				<button id="resume_transfer" style="display:none"; class="btn btn-primary"  type="submit">Resume<i class="bi bi-arrow-right"></i></button>
				<button id="pause_transfer" style="display:none";  class="btn btn-primary"  type="submit">Pause<i class="bi bi-arrow-right"></i></button>
				<button id="close_transfer" class="btn btn-secondary" type="submit">Cancel<i class="bi bi-arrow-right"></i></button>
                <button id="start_transfer" class="btn btn-primary" type="submit">Begin Transfer <i class="bi bi-arrow-right"></i></button>
                </div>

				<?php
				$nonce = wp_create_nonce('toolsfors3_action');
				echo '<input type="hidden" name="toolsfors3_nonce" id="toolsfors3_nonce" value="' . esc_attr($nonce) . '">';
				?>



            </form>
        </div>
    </div>
</div> 
<!-- END Modal -->
</div> <!-- end container -->
