<?php

global $configuration,$wpdb;
$configuration['prefix'] = $wpdb->prefix;

define('LC_CLASSES_PATH',plugin_dir_path( __FILE__ ).'/classes');
define('LC_VIEWS_PATH',plugin_dir_path( __FILE__ ).'views');

define("TBL_EMAILS",     $configuration['prefix']."lc_subscriptions");

?>
