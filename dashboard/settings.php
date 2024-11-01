<?php

/**
 * @ Author: Bill Minozzi
 * @ Copyright: 2022 www.BillMinozzi.com
 * Created: 2022 - Sept 20
 */
if (!defined('ABSPATH')) {
    die('We\'re sorry, but you can not directly access this file.');
}
global $toolsfors3_region;
global $toolsfors3_secret_key;
global $toolsfors3_access_key;

use Aws\Exception\AwsException;
//

if (current_user_can('administrator')) {
    // The current user is an administrator
    //echo 'You are an administrator.';
} else {
    // The current user is not an administrator
    die('You are not an administrator.');
}


echo '<div class="wrap-toolsfors3 ">' . "\n";
echo '<h2 class="title">Amazon Settings</h2>' . "\n";
echo '<p class="description">';
echo esc_attr__("Fill out all this information below before open Amazon Tab.", "toolsfors3");
echo '</p>' . "\n";
echo '<br />';
esc_attr_e("You can get this information at Amazon website.", "toolsfors3");
echo '<br />';
echo '<br />';
if (isset($_GET['page']) && sanitize_text_field($_GET['page']) == 'toolsfors3_admin_page') {
    if (isset($_POST['process']) && $_POST['process'] == 'toolsfors3_admin_page') {

       // debug2();


        if (isset($_POST['toolsfors3_nonce_field']) && !wp_verify_nonce(sanitize_text_field($_POST['toolsfors3_nonce_field']), 'toolsfors3_nonce_action')) {
            //debug2($_POST['toolsfors3_nonce_field']);
            die('Invalid Nonce');
        }




        if (isset($_POST['region'])) {
            $toolsfors3_region = sanitize_text_field($_POST['region']);
            if (!update_option('toolsfors3_region', $toolsfors3_region))
                add_option('toolsfors3_region', $toolsfors3_region);
        }
        if (isset($_POST['secret_key'])) {
            $toolsfors3_secret_key = sanitize_text_field($_POST['secret_key']);
            if (!update_option('toolsfors3_secret_key', $toolsfors3_secret_key))
                add_option('toolsfors3_secret_key', $toolsfors3_secret_key);
        }
        if (isset($_POST['access_key'])) {
            $toolsfors3_access_key = sanitize_text_field($_POST['access_key']);
            if (!update_option('toolsfors3_access_key', $toolsfors3_access_key))
                add_option('toolsfors3_access_key', $toolsfors3_access_key);
        }
        toolsfors3_updated_message();
        echo '<br /><br />';
    }
}
if (isset($_GET['page']) && $_GET['page'] == 'toolsfors3_admin_page') {
    if (isset($_POST['process']) && $_POST['process'] == 'toolsfors3_admin_page_test') {
        // Test
        try {
            global $toolsfors3_region;
            global $toolsfors3_secret_key;
            global $toolsfors3_access_key;
            if (empty($toolsfors3_region) or empty($toolsfors3_secret_key) or empty($toolsfors3_access_key)) {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<br /><b>';
                echo esc_attr__('Please, fill out the 3 fields below', 'toolsfors3');
                echo '<br /><br /></div>';
                echo '<br /><br />';
            } else {

                $path = TOOLSFORS3PATH . "/functions/toolsfors3_connect.php";
                require_once $path;

                $buckets = $toolsfors3_s3->listBuckets();
                echo '<div class="notice notice-success is-dismissible">';
                echo '<br /><b>';
                echo esc_attr_e('Connection With Amazon S3 Successful!', 'toolsfors3');
                echo '<br /><br /></div>';
                echo '<br /><br />';
            }
        } catch (AWSException $e) {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<br /><b>';
            echo "<b>" . esc_attr($e->getStatusCode()) . "\n" . esc_attr($e->getAwsErrorCode()) . "</b>";
            echo esc_attr(explode(';', $e->getMessage())[1]);
            echo '<br /><br /></div>';
            echo '<br /><br />';
        }
    }
}
?>
<form class="toolsfors3 -form" method="post" action="admin.php?page=toolsfors3_admin_page&tab=settings">
    <input type="hidden" name="process" value="toolsfors3_admin_page" />



    <?php
    // Gera e adiciona o campo nonce ao formulário
    // wp_nonce_field('toolsfors3_nonce_action', 'toolsfors3_nonce_field');
    ?>
    <input type="text" name="toolsfors3_nonce_field" value="<?php echo wp_create_nonce('toolsfors3_nonce_action'); ?>">
    


    <label for="region"><?php esc_attr_e("Region", "toolsfors3"); ?>:</label>
    <input type="text" id="region" name="region" value="<?php echo esc_attr($toolsfors3_region); ?>">
    <br><br>
    <input type="hidden" name="process" value="toolsfors3_admin_page" />
    <label for="secret_key"><?php esc_attr_e("Secret Key", "toolsfors3"); ?>:</label>
    <input type="password" id="secret_key" name="secret_key" size="40" value="<?php echo esc_attr($toolsfors3_secret_key); ?>">
    <br><br>
    <input type="hidden" name="process" value="toolsfors3_admin_page" />
    <label for="access_key"><?php esc_attr_e("Access Key", "toolsfors3"); ?>:</label>
    <input type="text" id="access_key" name="access_key" size="40" value="<?php echo esc_attr($toolsfors3_access_key); ?>">
    <br><br>
    <br>
    <input type="hidden" name="process" value="toolsfors3_admin_page" />
    <?php
    echo '<input class="toolsfors3 -submit button-primary" type="submit" value="Update" />';
    echo '</form>' . "\n";
    ?>
    <br><br>
    <form class="toolsfors3 -form" method="post" action="admin.php?page=toolsfors3_admin_page&tab=settings">
        <input type="hidden" name="process" value="toolsfors3_admin_page_test" />
        <?php
        echo '<input class="toolsfors3 -submit button-secondary" type="submit" value="Test Connection" />';
    echo '</form>' . "\n";
        echo '<div class="main-notice">';
        echo '</div>' . "\n";
        echo '</div>';
    function toolsfors3_stripNonAlphaNumeric($string)
        {
            return preg_replace("/[^a-z0-9]/i", "", $string);
        }
