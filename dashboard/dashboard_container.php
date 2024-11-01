<?php

/**
 * @ Author: Bill Minozzi
 * @ Copyright: 2022 www.BillMinozzi.com
 * Created: 2022 - Sept 20
 * 
 */
if (!defined('ABSPATH')) {
  die('We\'re sorry, but you can not directly access this file.');
}
?>
<div id="toolsfors3-logo">
  <img src="<?php echo esc_attr(TOOLSFORS3IMAGES); ?>/logo.png" width="175">
</div>
<?php



if (current_user_can('administrator')) {
  // The current user is an administrator
  //echo 'You are an administrator.';
} else {
  // The current user is not an administrator
  // die('You are not an administrator.');
  return;
}




if (isset($_GET['tab']))
  $active_tab = sanitize_text_field($_GET['tab']);
else
  $active_tab = 'dashboard';
?>
<h2 class="nav-tab-wrapper">
  <a href="tools.php?page=toolsfors3_admin_page&tab=dashboard" class="nav-tab">Dashboard</a>
  <a href="tools.php?page=toolsfors3_admin_page&tab=settings" class="nav-tab">Settings</a>
  <a href="tools.php?page=toolsfors3_admin_page&tab=debug" class="nav-tab">Debug</a>
  <a href="tools.php?page=toolsfors3_admin_page&tab=amazon" class="nav-tab">Amazon</a>
  <!-- <a href="tools.php?page=toolsfors3_admin_page&tab=transf" class="nav-tab">Transf</a> -->
  <a href="tools.php?page=toolsfors3_admin_page&tab=transf&toolsfors3_nonce=<?php echo esc_attr(wp_create_nonce('toolsfors3_action')); ?>" class="nav-tab">Transf</a>

</h2>
<?php
if ($active_tab == 'settings') {
  require_once(TOOLSFORS3PATH . 'dashboard/settings.php');
} elseif ($active_tab == 'amazon') {
  echo '<div class=wrap-toolsfors3>';
  require_once(TOOLSFORS3PATH . 'dashboard/amazon.php');
  echo '</div>';
} elseif ($active_tab == 'debug') {
  echo '<div class=wrap-toolsfors3>';
  require_once(TOOLSFORS3PATH . 'dashboard/debug.php');
  echo '</div>';
} elseif ($active_tab == 'toolsfors3_delete') {
  echo '<div class=wrap-toolsfors3>';
  require_once(TOOLSFORS3PATH . "/s3api/toolsfors3_delete.php");
  echo '</div>';
} elseif ($active_tab == 'transf') {
  echo '<div class=wrap-toolsfors3>';
  require_once(TOOLSFORS3PATH . 'dashboard/transf.php');
  echo '</div>';
} elseif ($active_tab == 'transfer_debug') {
  echo '<div class=wrap-toolsfors3>';
  require_once(TOOLSFORS3PATH . 's3api/transfer_debug.php');
  echo '</div>';


} else {
  require_once(TOOLSFORS3PATH . 'dashboard/dashboard.php');
}
