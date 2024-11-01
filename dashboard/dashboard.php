<?php

/**
 * @ Author: Bill Minozzi
 * @ Copyright: 2020 www.BillMinozzi.com
 * Created: 2022 - Sept 20
 */
if (!defined('ABSPATH')) {
    die('We\'re sorry, but you can not directly access this file.');
}
echo '<div class="wrap-toolsfors3 ">' . "\n";
echo '<h2 class="title">TOOLSFORS3 Instructions</h2>' . "\n";
echo '<p class="description">';
echo esc_attr__("This plugin connect you with your Amazon S3-compatible Object Storage, using S3-compatible API.", "toolsfors3") . '</p>' . "\n";
echo '<br> ';
echo '<b> ';
   
    echo  esc_attr__("To Start, after order their service, go to Amazon Web Services console and click on the name of your account (it is located in the top right corner of the console)", "toolsfors3");  
    echo '<br> ';
    echo  esc_attr__("Click on Security Credentials.", "toolsfors3"); 
    echo '<br> ';
    echo  esc_attr__("Find Access Keys...","toolsfors3");   
    echo '</b> ';
    echo '<br> ';
    echo '<br> ';

echo  esc_attr__("Copy Access Key and Secret Key.", "toolsfors3");
echo '<br>';
echo  esc_attr__("Then, paste them on the tab Settings of this plugin.", "toolsfors3");
echo '<br> ';
echo  esc_attr__("After that, go to tab: Amazon to navigate on your Cloud.", "toolsfors3");
echo '<br> ';
echo  esc_attr__("Or Go to Transfer to make folders transfer from/to Server and Cloud.", "toolsfors3");
echo '<br>';
echo  esc_attr__("If you need cancel de transfer, click CANCEL BUTTON and wait.", "toolsfors3");
echo '<br>';
echo  esc_attr__("Don't use the BACK or STOP buttons on your browser, neither close it.", "toolsfors3");
echo '<br>';
echo  esc_attr__("Otherwise, temporary files will not be deleted.", "toolsfors3");
echo '<br>';
echo '<br> ';
esc_attr_e('Visit the plugin site for more details.', 'toolsfors3');
echo '<br>';
echo '<br>';
echo '<a href="https://toolsfors3.com/" class="button button-primary">' . esc_attr__('Plugin Site', 'toolsfors3') . '</a>';
echo '&nbsp;&nbsp;';
echo '<br>';
echo '<br>';
echo '</div>';
