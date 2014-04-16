<?php

global $configuration,$wpdb;
$configuration['prefix'] = $wpdb->prefix;

define('list_saver_CLASSES_PATH',plugin_dir_path( __FILE__ ).'/classes');
define('list_saver_VIEWS_PATH',plugin_dir_path( __FILE__ ).'views');

define("TBL_EMAILS",     $configuration['prefix']."lc_subscriptions");

?>
