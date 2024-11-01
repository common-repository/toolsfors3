<?php
/*
Plugin Name: toolsfors3
Plugin URI: http://toolsfors3.com
Description: Browser for Amazon s3 tool. This plugin connect you with your Amazon AWS S3 Object Storage, using S3-compatible API.
Version: 2.22
Text Domain: toolsfors3
Author: Bill Minozzi
Author URI: http://billminozzi.com
License:     GPL2
Copyright (c) 2022 Bill Minozzi
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/
// Make sure the file is not directly accessible.
if (!defined('ABSPATH')) {
    die('We\'re sorry, but you can not directly access this file.');
}

$toolsfors3_request_url = trim(sanitize_url($_SERVER['REQUEST_URI']));
define('TOOLSFORS3URL', plugin_dir_url(__file__));
$plugin = plugin_basename(__FILE__);
define('TOOLSFORS3PATH', plugin_dir_path(__file__));
define('TOOLSFORS3DOMAIN', get_site_url());
define('TOOLSFORS3IMAGES', plugin_dir_url(__file__) . 'images');
define('TOOLSFORS3PAGE', trim(sanitize_text_field($GLOBALS['pagenow'])));
define('TOOLSFORS3HOMEURL', admin_url());
define('TOOLSFORS3ADMURL', admin_url());
$toolsfors3_request_url = sanitize_url($_SERVER['REQUEST_URI']);
$toolsfors3_plugin_data = get_file_data(__FILE__, array('Version' => 'Version'), false);
$toolsfors3_plugin_version = sanitize_text_field($toolsfors3_plugin_data['Version']);
define('TOOLSFORS3VERSION', sanitize_text_field($toolsfors3_plugin_version));
$toolsfors3_region = trim(sanitize_text_field(get_option('toolsfors3_region', '')));
$toolsfors3_secret_key = trim(sanitize_text_field(get_option('toolsfors3_secret_key', '')));
$toolsfors3_access_key = trim(sanitize_text_field(get_option('toolsfors3_access_key', '')));
if (!function_exists('wp_get_current_user')) {
    require_once(ABSPATH . "wp-includes/pluggable.php");
}
require_once TOOLSFORS3PATH . "functions/functions.php";
add_action('admin_menu', 'toolsfors3_init');
function toolsfors3_add_admstylesheet()
{
    global $toolsfors3_request_url;
    $pos = strpos($toolsfors3_request_url, 'toolsfors3_admin_page');
    if ($pos) {
        wp_enqueue_script('jquery');
        wp_register_style('toolsfors3-css', TOOLSFORS3URL . 'assets/css/toolsfors3.css');
        wp_enqueue_style('toolsfors3-css');
        wp_register_style('toolsfors3-bs', TOOLSFORS3URL . 'assets/css/bootstrap.min.css', false);
        wp_enqueue_style('toolsfors3-bs');

        // 2024
        wp_enqueue_script('botstrap', TOOLSFORS3URL .
        'assets/js/bootstrap.bundle.min.js', array('jquery'));


        wp_register_style('bootstrap-treeview', TOOLSFORS3URL . 'assets/css/bootstrap-treeview.min.css');
        wp_enqueue_style('bootstrap-treeview');
        wp_register_style('toolsfors3-bsi', TOOLSFORS3URL . 'assets/icons-main/font/bootstrap-icons.css', false);
        wp_enqueue_style('toolsfors3-bsi');
        wp_register_script('toolsfors3-list', TOOLSFORS3URL . 'assets/list/dist/list.js', false);
        wp_enqueue_script('toolsfors3-list');
        wp_register_style('toolsfors3-drop-css', TOOLSFORS3URL . 'assets/css/dropzone.min.css', false);
        wp_enqueue_style('toolsfors3-drop-css');
        wp_register_script('toolsfors3-drop-js', TOOLSFORS3URL . 'assets/js/dropzone.min.js', false);
        wp_enqueue_script('toolsfors3-drop-js');
        if(strpos($toolsfors3_request_url, 'transf') === false ) {
            wp_register_script('toolsfors3-js', TOOLSFORS3URL . 'assets/js/toolsfors3.js', false);
            wp_enqueue_script('toolsfors3-js');
        }
        if(strpos($toolsfors3_request_url, 'tab=trans') !== false)  {
            wp_register_script('toolsfors3-filesys-js', TOOLSFORS3URL . 'assets/js/toolsfors3_filesys.js', false);
            wp_enqueue_script('toolsfors3-filesys-js');
            wp_register_script('toolsfors3-copy-js', TOOLSFORS3URL . 'assets/js/toolsfors3_copy.js', false);
            wp_enqueue_script('toolsfors3-copy-js');
        }
wp_register_script('toolsfors3-bootstrap-treeview', TOOLSFORS3URL . 'assets/js/bootstrap-treeview.min.js', false);
wp_enqueue_script('toolsfors3-bootstrap-treeview');
    }
}
if (is_admin()) {
    add_action('admin_enqueue_scripts', 'toolsfors3_add_admstylesheet');
}
// Add settings link on plugin page
function toolsfors3_plugin_settings_link($links)
{
    $settings_link = '<a href="tools.php?page=toolsfors3_admin_page">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'toolsfors3_plugin_settings_link');
register_activation_hook(__FILE__, 'toolsfors3_activated');
// Pointer
function toolsfors3_activated()
{
    ob_start();
    $r = update_option('toolsfors3_was_activated', '1');
    if (!$r) {
        add_option('toolsfors3_was_activated', '1');
    }
    $pointers = get_user_meta(get_current_user_id(), 'dismissed_wp_pointers', true);
    $pointers = ''; 
    update_user_meta(get_current_user_id(), 'dismissed_wp_pointers', $pointers);
    ob_end_clean();
}
if (is_admin() or is_super_admin()) {
    if (get_option('toolsfors3_was_activated', '0') == '1') {
        add_action('admin_enqueue_scripts', 'toolsfors3_adm_enqueue_scripts2');
    }
}
function toolsfors3_adm_enqueue_scripts2()
{
    global $toolsfors3_current_screen;
    wp_enqueue_script('wp-pointer');
    require_once ABSPATH . 'wp-admin/includes/screen.php';
    $myscreen = get_current_screen();
    $toolsfors3_current_screen = $myscreen->id;
    $dismissed_string = get_user_meta(get_current_user_id(), 'dismissed_wp_pointers', true);
    if (!empty($dismissed_string)) {
        $r = update_option('toolsfors3_was_activated', '0');
        if (!$r) {
            add_option('toolsfors3_was_activated', '0');
        }
        return;
    }
    if (get_option('toolsfors3_was_activated', '0') == '1')
        add_action('admin_print_footer_scripts', 'toolsfors3_admin_print_footer_scripts');
}
function toolsfors3_admin_print_footer_scripts()
{
    global $toolsfors3_current_screen;
    $pointer_content = 'Open Tools for S3 Here!';
    $pointer_content2 = 'Just Click Over Tools, then Click over Tools for S3.';
?>
    <script>
        jQuery(document).ready(function($) {
            $('#menu-tools').pointer({
                content: '<?php echo '<h3>' . esc_attr($pointer_content) . '</h3><p>' . esc_attr($pointer_content2); ?>',
                position: {
                    edge: 'left',
                    align: 'right'
                },
                close: function() {
                    // Once the close button is hit
                    $.post(ajaxurl, {
                        pointer: '<?php echo esc_attr($toolsfors3_current_screen); ?>',
                        action: 'dismiss-wp-pointer'
                    });
                }
            }).pointer('open');
        });
    </script>
<?php
}
