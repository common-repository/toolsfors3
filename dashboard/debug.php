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

if (current_user_can('administrator')) {
	// The current user is an administrator
	//echo 'You are an administrator.';
} else {
	// The current user is not an administrator
	die('You are not an administrator.');
}

?>

<br>
<big>
	If you need support, please, copy and paste the info below in our
	&nbsp;
	<a href="https://BillMinozzi.com/support">Support Site</a>
	<br><br>
	<textarea style="height:100vh;width:100%" readonly="readonly" onclick="this.focus(); this.select()"><?php echo esc_attr(toolsfors3_sysinfo_get()); ?></textarea>
	<?php
	function toolsfors3_sysinfo_get()
	{
		global $wpdb;
		$return  = '=== Begin System Info (Generated ' . date('Y-m-d H:i:s') . ') ===' . "\n\n";
		// WordPress configuration
		$return .= '-- WordPress Configuration' . "\n\n";
		$return .= 'Version:                  ' . get_bloginfo('version') . "\n";
		if (defined('WP_DEBUG'))
			$return .= 'WP_DEBUG:                 ' .  WP_DEBUG ? 'Enabled' : 'Disabled' . "\n";
		else
			$return .= 'WP_DEBUG:                 ' .  'Not Set\n';
		$return .= ' Memory Limit:             ' . WP_MEMORY_LIMIT . "\n";
		// Server configuration 
		//  $return = "\n\n";
		$return .= "\n" . '-- Webserver Configuration' . "\n\n";
		$return .= 'OS Type & Version:        ' . toolsfors3_OSName();
		$return .= 'PHP Version:              ' . PHP_VERSION . "\n";
		$return .= 'Webserver Info:           ' . sanitize_text_field($_SERVER['SERVER_SOFTWARE']) . "\n";

		// PHP configs... 
		$return .= "\n" . '-- PHP Configuration' . "\n\n";
		$return .= 'Memory Limit:             ' . ini_get('memory_limit') . "\n";
		$return .= 'Memory Usage Now:         ' . size_format(memory_get_usage()) . "\n";

		$return .= 'Upload Max Size:          ' . ini_get('upload_max_filesize') . "\n";
		$return .= 'Post Max Size:            ' . ini_get('post_max_size') . "\n";
		$return .= 'Upload Max Filesize:      ' . ini_get('upload_max_filesize') . "\n";
		$return .= 'Time Limit:               ' . ini_get('max_execution_time') . "\n";
		$return .= 'Max Input Vars:           ' . ini_get('max_input_vars') . "\n";
		$return .= 'Display Errors:           ' . (ini_get('display_errors') ? 'On (' . ini_get('display_errors') . ')' : 'N/A') . "\n";
		// PHP extensions and such
		$return .= "\n" . '-- PHP Extensions' . "\n\n";
		$return .= 'cURL:                     ' . (function_exists('curl_init') ? 'Supported' : 'Not Supported') . "\n";
		$return .= 'fsockopen:                ' . (function_exists('fsockopen') ? 'Supported' : 'Not Supported') . "\n";
		$return .= 'SOAP Client:              ' . (class_exists('SoapClient') ? 'Installed' : 'Not Installed') . "\n";
		$return .= 'Suhosin:                  ' . (extension_loaded('suhosin') ? 'Installed' : 'Not Installed') . "\n";
		$return .= "\n" . '=== End System Info ===';
		return $return;


	}
	function toolsfors3_OSName()
	{
		try {
		  if (false == function_exists("shell_exec") || false == @is_readable("/etc/os-release")   ) {
			return false;
		  }
		  $os = shell_exec('cat /etc/os-release | grep "PRETTY_NAME"');
		  return explode("=", $os)[1];
		}
		catch (Exception $e) {
		  return false;
		}
	}
	?>