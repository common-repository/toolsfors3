<?php
/**
 * @ Author: Bill Minozzi
 * @ Copyright: 2020 www.BillMinozzi.com
 * @ Modified time: 2022-12-05
 */
if (!defined('ABSPATH'))
  exit; // Exit if accessed directly
global $wpdb;

if (current_user_can('administrator')) {
  // The current user is an administrator
  //echo 'You are an administrator.';
} else {
  // The current user is not an administrator
  die('You are not an administrator.');
}

$table_name = $wpdb->prefix . "toolsfors3_copy";
///$q = $wpdb->get_var("
//SELECT count(*) FROM `$table_name`");
$q = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM %i", $table_name));

if($q < 1){
 echo '<h3>'. esc_attr__("No transfeer made yet.","toolsfors3"). '</h3>';
 echo esc_attr__("Go to TRANSF tab to make a new transfer.","toolsfors3");
 return;
}
echo '<h3>'. esc_attr__("Transfer Debug","toolsfors3"). '</h3>';
//$r = $wpdb->get_var("SELECT debug from `$table_name` ORDER BY id DESC limit 1");
$r = $wpdb->get_var(
  $wpdb->prepare(
      "SELECT debug FROM %i ORDER BY id DESC LIMIT 1",
      $table_name
  )
);


if (empty($r))
   echo esc_attr__("No Debug found...","toolsfors3");
else {
/*
  $r2 = $wpdb->get_var("SELECT date_end from `$table_name` ORDER BY id DESC limit 1");
  $r3 = $wpdb->get_var("SELECT qfiles from `$table_name` ORDER BY id DESC limit 1");
  $r4 = $wpdb->get_var("SELECT `from` from `$table_name` ORDER BY id DESC limit 1");
  $r5 = $wpdb->get_var("SELECT `bucket` from `$table_name` ORDER BY id DESC limit 1");
  $r6 = $wpdb->get_var("SELECT `folder_cloud` from `$table_name` ORDER BY id DESC limit 1");
  $r7 = $wpdb->get_var("SELECT `folder_server` from `$table_name` ORDER BY id DESC limit 1");
  */
  $r2 = $wpdb->get_var($wpdb->prepare("SELECT date_end FROM %i ORDER BY id DESC LIMIT 1", $table_name));
  $r3 = $wpdb->get_var($wpdb->prepare("SELECT qfiles FROM %i ORDER BY id DESC LIMIT 1", $table_name));
  $r4 = $wpdb->get_var($wpdb->prepare("SELECT `from` FROM %i ORDER BY id DESC LIMIT 1", $table_name));
  $r5 = $wpdb->get_var($wpdb->prepare("SELECT `bucket` FROM %i ORDER BY id DESC LIMIT 1", $table_name));
  $r6 = $wpdb->get_var($wpdb->prepare("SELECT `folder_cloud` FROM %i ORDER BY id DESC LIMIT 1", $table_name));
  $r7 = $wpdb->get_var($wpdb->prepare("SELECT `folder_server` FROM %i ORDER BY id DESC LIMIT 1", $table_name));
  
  
  echo esc_attr__("Date Transfer : ","toolsfors3").esc_attr(substr($r2,0,10));
   echo '<br>';
   echo esc_attr__("Quantify of Files : ","toolsfors3").esc_attr($r3);
   echo '<br>';
   echo esc_attr__("(Number can include splitted files.)","toolsfors3");
   echo '<br>'; 
   echo esc_attr__("Transfer From : ","toolsfors3").esc_attr($r4);
   echo '<br>';
   echo esc_attr__("Bucket : ","toolsfors3").esc_attr($r5);
   echo '<br>';
   echo esc_attr__("Folder Cloud : ","toolsfors3").esc_attr($r6);
   echo '<br>';
   echo esc_attr__("Folder Server : ","toolsfors3").esc_attr($r7);
   echo '<hr>';
   $allowed_atts = array(
    'align'      => array(),
    'class'      => array(),
    'type'       => array(),
    'id'         => array(),
    'dir'        => array(),
    'lang'       => array(),
    'style'      => array(),
    'xml:lang'   => array(),
    'src'        => array(),
    'alt'        => array(),
    'href'       => array(),
    'rel'        => array(),
    'rev'        => array(),
    'target'     => array(),
    'novalidate' => array(),
    'type'       => array(),
    'value'      => array(),
    'name'       => array(),
    'tabindex'   => array(),
    'action'     => array(),
    'method'     => array(),
    'for'        => array(),
    'width'      => array(),
    'height'     => array(),
    'data'       => array(),
    'title'      => array(),
    'checked' => array(),
    'selected' => array(),
  );
   $my_allowed['form'] = $allowed_atts;
   $my_allowed['select'] = $allowed_atts;
   // select options
   $my_allowed['option'] = $allowed_atts;
   $my_allowed['style'] = $allowed_atts;
   $my_allowed['label'] = $allowed_atts;
   $my_allowed['input'] = $allowed_atts;
   $my_allowed['textarea'] = $allowed_atts;
       //more...future...
   $my_allowed['form']     = $allowed_atts;
   $my_allowed['label']    = $allowed_atts;
   $my_allowed['input']    = $allowed_atts;
   $my_allowed['textarea'] = $allowed_atts;
   $my_allowed['iframe']   = $allowed_atts;
   $my_allowed['script']   = $allowed_atts;
   $my_allowed['style']    = $allowed_atts;
   $my_allowed['strong']   = $allowed_atts;
   $my_allowed['small']    = $allowed_atts;
   $my_allowed['table']    = $allowed_atts;
   $my_allowed['span']     = $allowed_atts;
   $my_allowed['abbr']     = $allowed_atts;
   $my_allowed['code']     = $allowed_atts;
   $my_allowed['pre']      = $allowed_atts;
   $my_allowed['div']      = $allowed_atts;
   $my_allowed['img']      = $allowed_atts;
   $my_allowed['h1']       = $allowed_atts;
   $my_allowed['h2']       = $allowed_atts;
   $my_allowed['h3']       = $allowed_atts;
   $my_allowed['h4']       = $allowed_atts;
   $my_allowed['h5']       = $allowed_atts;
   $my_allowed['h6']       = $allowed_atts;
   $my_allowed['ol']       = $allowed_atts;
   $my_allowed['ul']       = $allowed_atts;
   $my_allowed['li']       = $allowed_atts;
   $my_allowed['em']       = $allowed_atts;
   $my_allowed['hr']       = $allowed_atts;
   $my_allowed['br']       = $allowed_atts;
   $my_allowed['tr']       = $allowed_atts;
   $my_allowed['td']       = $allowed_atts;
   $my_allowed['p']        = $allowed_atts;
   $my_allowed['a']        = $allowed_atts;
   $my_allowed['b']        = $allowed_atts;
   $my_allowed['i']        = $allowed_atts;
   echo wp_kses(nl2br($r), $my_allowed);
} 