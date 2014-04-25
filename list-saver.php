<?php
/*
  Plugin Name: List Saver

  Description: Check if email subscribed or not.

  Version: 1.0.0

  Author: Sandeep Kumar, Arun Sivashankaran, Eduardo Aguilar

  Author URI: www.funnelenvy.com

 */

add_action('init', 'list_saver_manage_init');

include_once('configuration.php');



add_action( 'list_saver_mailchimp_event', 'list_saver_mailchimp_subscriber_event' );


function list_saver_mailchimp_subscriber_event($is_cron=true){
 
  include_once( list_saver_CLASSES_PATH . '/class.subscription.php');
  
  $subscription = new List_Saver_Subscription();

  $results = $subscription->Get(array(array('sub_status','=','p')));
  
  update_option("list_saver_cron_status", time() );
 
  if( !$results )
  return;
  
  $mandrill_mail = false;
  
  include_once( list_saver_CLASSES_PATH . '/class.mailchimp.php');
	
  $settings=get_option('list_saver_settings');
  
  if( $settings['mandrill'] == 'yes'){
	
	include( list_saver_CLASSES_PATH .'/class.mandrill.php');  
	
	$mandrill = new List_Saver_Mandrill(trim($settings['mandrll_api_key']));
	
	$resp = $mandrill->call("/users/ping", array());
	
	if($resp == 'PONG!')
	
	$mandrill_mail = true;
  
  }
    
  
  
  $api_key=$settings['list_saver_api_key'];
  
  $list_id=$settings['list_saver_list_id'];
  
  $interval_days=$settings['list_saver_days'];

  $mc = new list_saver_MailChimp($settings['list_saver_api_key']);

  $connection = List_Saver_Database::Connect();

  $admin_email =  get_option('admin_email');
  
  $custom_message =  stripslashes($settings['list_saver_confirm_email']);
  
  // Added from email and name setting on email transction.
  
  $from_name =  $settings['list_saver_from_name'] ? stripslashes(trim($settings['list_saver_from_name'])) : get_option("blogname");  // new field from name
  
  $from_email =  $settings['list_saver_from_email'] ? stripslashes(trim($settings['list_saver_from_email'])) : $admin_email ;

  foreach( $results as $subscriber ){

   if(!$subscriber->sub_email)
   continue;
 
   // Check Interval Here
   
  
	
   if( $mc->is_subscribed_user($subscriber->sub_email,$list_id) ){
     
     List_Saver_Database::InsertOrUpdate($subscription->table,array('sub_status' => 'a'),array('sub_id' => $subscriber->sub_id) );
     
   }else{

if($subscriber->sub_email_sent=='true')
continue;	   

if($is_cron==true)
{
   $ts2=strtotime(date('Y-m-d'));
   $ts1=strtotime(date('Y-m-d',strtotime($subscriber->sub_date)));
   $seconds    = abs($ts2 - $ts1); # difference will always be positive
   $days = $seconds/(60*60*24);
	
	if($days < $interval_days)
	{
		continue;
	}
	

}

		
		 
		 if(!$custom_message)
		 continue;
			 
		 $member_info = $mc->call('lists/member-info', array(
		   'id'        => $list_id,
		   'emails'    => array(array('email'=>$subscriber->sub_email))
		 ));


		 		
		 if( isset($member_info['data']) ){
		  		
		    $euid = 	$member_info['data'][0]['euid'];
				    
			if( get_option( 'permalink_structure' ))
			
			$confirm_url = trim(site_url(), '/').'/subscribe/confirm/'.$euid.'/';
			
			else
			
			$confirm_url = trim(site_url(), '/').'/index.php?subscribe=confirm&euid='.$euid;
			
			$custom_message = str_replace(array("{subscriber_email}","{confirm_link}") , array($subscriber->sub_email , $confirm_url), $custom_message );
       
			$subject = $settings['list_saver_mail_subject'] ? stripslashes($settings['list_saver_mail_subject']) : "Confirm subscriber";
				    				    			   
			add_filter ("wp_mail_content_type", "list_saver_send_email_html");
			
			if($mandrill_mail){
				
			  // two new parametar passed $from_email and $from_name also changes in this class function.	
				
			  if( $mandrill->send_mail($subscriber->sub_email, $subject, nl2br($custom_message), $from_email, $from_name ) )
			  
			  List_Saver_Database::InsertOrUpdate($subscription->table,array('sub_email_sent' => 'true'),array('sub_id' => $subscriber->sub_id) );
	
				
			}else{
				 
				 /*Two new filters added from and from name*/
				 
				add_filter( 'wp_mail_from', create_function(false, "return '$from_email';"));
				
				add_filter( 'wp_mail_from_name', create_function(false, "return '$from_name';"));
				
				if(wp_mail($subscriber->sub_email, $subject, nl2br($custom_message)) )
				  // Now Update email_sent to true
				List_Saver_Database::InsertOrUpdate($subscription->table,array('sub_email_sent' => 'true'),array('sub_id' => $subscriber->sub_id) );

				
			}


		 }
else
{

echo "<div id='message' class='error'>".$member_info['error']."</div>";
return false;
}
		   
	  }
	  
      
   }
   
  

}




/**
 * This function used to register all hooks.
 */
function list_saver_manage_init(){
 	
	
	wp_enqueue_style( 'list_saver_manage_plugin_style1', plugins_url( '/css/bootstrap/css/bootstrap.css', __FILE__ ) );
	wp_enqueue_style( 'list_saver_manage_plugin_style3', plugins_url( '/css/list-checker.css', __FILE__ ) );
	wp_enqueue_script('jquery');
	wp_enqueue_script('list_saver_manage_plugin_scrpt', plugins_url( '/css/bootstrap/js/bootstrap.min.js', __FILE__ ));
	add_action('admin_menu', 'list_saver_register_list_saver_manage_menu_page');
	

}


add_action( 'wp_enqueue_scripts', 'list_saver_admin_enqueue_scripts' );

function list_saver_admin_enqueue_scripts(){
	$settings = get_option('list_saver_settings');
	wp_enqueue_script( 'general', plugins_url('js/lc-script.js',__FILE__), array('jquery') );
	wp_localize_script( 'general', 'wtm', array('ajaxUrl' => admin_url('admin-ajax.php'), 'form_action_url' => $settings['list_saver_formaction_url'] ) );
}

/**
 * This function used to display List Chekcer menu in backend.
 */
function list_saver_register_list_saver_manage_menu_page(){
	
	add_options_page('List Saver', 'List Saver', 'manage_options','list_saver_manage', 'list_saver_manage_display', '');

}

/**
 * This function used to display List Checker Dashboard.
 */

function list_saver_manage_display(){
	
	$tabs = array( 'list_saver_active_subscriptions' => 'Active Subscriptions','list_saver_pending_subscriptions' => 'Pending Subscriptions','list_saver_settings' => 'Settings', 'list_saver_status' => 'Status' );
	
	echo '<div class="wrap">';
	
	echo '<h2 class="nav-tab-wrapper">';
	
	$current_tab = $_GET['tab'] ? $_GET['tab'] : 'list_saver_active_subscriptions'; 
	
	foreach($tabs as $tab => $tab_title ){
      
      $active_class = '';
      
      if( $current_tab == $tab )
      
      $active_class = 'nav-tab-active';
      
     echo '<a href="'.admin_url('admin.php?page=list_saver_manage&tab='.$tab).'" class="nav-tab '.$active_class.'">'.$tab_title.'</a>'; 		 
	
	}
	
	echo '</h2>';
	
	echo '<br>';
	
	switch( $current_tab ){
		
	  case 'list_saver_active_subscriptions'	:   list_saver_active_subscriptions(); break;	
	 
	  case 'list_saver_pending_subscriptions'	:   list_saver_pending_subscriptions(); break;

	  case 'list_saver_settings'	:   list_saver_settings(); break;
	  
	  case 'list_saver_status'	:   list_saver_status(); break;
	  
	  default : list_saver_active_subscriptions();
		
	}
	
	echo '</div>';
	
}


/**
 * This function used to view active subscriptions in backend.
 */
function list_saver_active_subscriptions(){
	
	include_once( list_saver_CLASSES_PATH . '/class.subscription.php');
	
	$tag = new List_Saver_Subscription();
	
	echo $tag->view_subscriptions("a");
	
}

/**
 * This function used to view pending subscriptions in backend.
 */
function list_saver_pending_subscriptions(){
	
	include_once( list_saver_CLASSES_PATH . '/class.subscription.php');
	
	$tag = new List_Saver_Subscription();
	
	echo $tag->view_subscriptions("p");
	
}


/**
 * This function used to settings in backend.
 */

function list_saver_settings()
{
	$list_saver_settings=get_option('list_saver_settings');
  
?>
<div class="wrap">  
<div id="icon-options-general" class="icon32"><br></div>
		
        <form class='form' role="form" method="post" action="options.php">  
        <?php wp_nonce_field('update-options') ?>
        <h2><?php _e( 'Mailchimp Settings', 'list_saver_language' ) ?></h2><br>
        <div class='form-group'> 
		<label>API KEY</label>
		<input type="textbox"  class="form-control" name="list_saver_settings[list_saver_api_key]" id="list_saver_api_key" value="<?php echo $list_saver_settings['list_saver_api_key']; ?>" />
		</div> 
		<div class='form-group'> 
		<label>LIST ID</label>
		<input type="textbox" class="form-control" name="list_saver_settings[list_saver_list_id]" id="list_saver_list_id" value="<?php echo $list_saver_settings['list_saver_list_id']; ?>" />
		</div> 
        <div class='form-group'> 
		<label>Form Action URL</label>
		<input type="textbox" class="form-control" name="list_saver_settings[list_saver_formaction_url]" id="list_saver_formaction_url" value="<?php echo $list_saver_settings['list_saver_formaction_url']; ?>" />
		</div>
		<div class='form-group'> 
		<label>Thank You Page URL</label>
		<input type="textbox" class="form-control" name="list_saver_settings[list_saver_thankyou_url]" id="list_saver_thankyou_url" value="<?php echo $list_saver_settings['list_saver_thankyou_url']; ?>" />
		</div> 	 		
		<div class='form-group'> 
		<label>Reminder Interval (Days)</label>
                <select name="list_saver_settings[list_saver_days]" id="list_saver_days" class="form-control" style="width:98px;">
		<?php for( $n = 1 ; $n<=30; $n++): ?>
                 <option value="<?php echo $n ?>" <?php selected( $list_saver_settings['list_saver_days'], $n ) ?>><?php echo $n ?></option>
                <?php endfor; ?> 
                </select>
                <p class="description">Set number of days for reminder email after the user signs up </p>
		</div>
		<h2><?php _e( 'Email/Message Settings', 'list_saver_language' ) ?></h2><br>
		<!-- Move fields in Email/Message section -->
		<div class='form-group'> 
		<label>Use Mandrill Email</label>
		 <select name="list_saver_settings[mandrill]" id="mandrill" class="form-control" style="width:70px;" onChange="if(this.value == 'yes') jQuery('#mandrill_options').show(); else jQuery('#mandrill_options').hide();">
		   <option value="no" <?php selected($list_saver_settings['mandrill'], 'no'); ?>>No</option>
		   <option value="yes" <?php selected($list_saver_settings['mandrill'], 'yes'); ?>>Yes</option>
		 </select>
		</div> 	
		<div class='form-group' id="mandrill_options" style="display:<?php if($list_saver_settings['mandrill'] == 'yes') echo "inline"; else echo "none"; ?>;"> 
		<label>Mandrill Api Key</label>
		 <input type="text" name="list_saver_settings[mandrll_api_key]" id="mandrill_api_key" value="<?php echo esc_attr($list_saver_settings['mandrll_api_key']) ?>" class="form-control">
		</div> 
		<!-- Move End -->
        
        <!-- Added two new fields from email and from name -->
        <div class='form-group'> 
		<label>From Name</label>
		<input type="text" class="form-control" style="width:500px;" name="list_saver_settings[list_saver_from_name]" id="list_saver_from_name" value="<?php echo stripslashes($list_saver_settings['list_saver_from_name']); ?>" />
		</div> 	 
		
		<div class='form-group'> 
		<label>From Email</label>
		<input type="text" class="form-control" style="width:500px;" name="list_saver_settings[list_saver_from_email]" id="list_saver_from_email" value="<?php echo stripslashes($list_saver_settings['list_saver_from_email']); ?>" />
		</div> 	 
         <!-- Added -->
         
		<div class='form-group'> 
		<label>Subject</label>
		<input type="text" class="form-control" style="width:500px;" name="list_saver_settings[list_saver_mail_subject]" id="list_saver_mail_subject" value="<?php echo stripslashes($list_saver_settings['list_saver_mail_subject']); ?>" />
		</div> 	 
		<div class='form-group'> 
		  <?php wp_editor( $list_saver_settings['list_saver_confirm_email'], 'list_saver_confirm_email', array('textarea_rows' => 10, 'textarea_name' => 'list_saver_settings[list_saver_confirm_email]')); ?>
		  <p>Available Tags: <b>{confirm_link}</b>, <b>{subscriber_email}</b> copy and paste message content body.</p>
		</div>
		<input type="hidden" name="action" value="update" />  
		<input type="hidden" name="page_options" value="list_saver_settings" />  
		<p class="submit">
		<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Save', 'list_saver_language' ) ?>">
		</p>
		</form>
 </div>
     	
<?php	
}

function list_saver_status(){

  $date_format = get_option("date_format");
  $time_format = get_option("time_format");	
  $next_cron = wp_next_scheduled( 'list_saver_mailchimp_event' );
  
  if( $prev_cron = get_option("list_saver_cron_status"))
  echo '<div id="messages" class="updated widefat"><p>Previous schedule run : <b>'.date($date_format." ".$time_format,$prev_cron).'</b></p></div>';
  
  if($next_cron)
  echo '<div id="messages" class="error"><p>Next schedule run : <b>'.date($date_format." ".$time_format,$next_cron).'</b></p></div>';
  
 
   
 
  //echo wp_next_scheduled( 'list_saver_mailchimp_event' );

}
/**
 * This function used to show success/failure message in backend.
 */
function list_saver_manage_show_message($message, $errormsg = false){
	if(empty($message))
		return;
	if ($errormsg)
		echo '<div id="message" class="error">';
	else 
		echo '<div id="message" class="updated">';
	echo "<p><strong>$message</strong></p></div>";
}


register_activation_hook(__FILE__, 'list_saver_manage_plugin_activation');

register_deactivation_hook(__FILE__, 'list_saver_manage_plugin_deactivation'); 

/**
 * This function used to install required tables in database.
 */
function list_saver_manage_plugin_activation(){
	
	global $wpdb;
	
	
	$sql = "CREATE TABLE IF NOT EXISTS ".TBL_EMAILS." (
             sub_id INT UNSIGNED AUTO_INCREMENT,
			 sub_email VARCHAR(100) NOT NULL,
			 sub_first_name VARCHAR(100) NOT NULL,
			 sub_last_name VARCHAR(100) NOT NULL,
			 sub_ip VARCHAR(30) NOT NULL,
			 sub_status ENUM('a', 'd', 'p') DEFAULT 'p',
			 sub_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			 sub_email_sent ENUM('true','false') DEFAULT 'false',
			 PRIMARY KEY(sub_id)
            ); ";
  
  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  
  dbDelta($sql);	
  
  // Add a Daily Cron Job here...
  
  if ( ! wp_next_scheduled('list_saver_mailchimp_event') ) {
	 
	  wp_schedule_event( time(), 'daily', 'list_saver_mailchimp_event');
	
	}

}

/**
 * This function used to do required action on deactivation.
 */
function list_saver_manage_plugin_deactivation(){
	
	//remove cron job here
	
	wp_clear_scheduled_hook( 'list_saver_mailchimp_event' );

	
}

/**
 * This function used to clean up everything if plugin deleted.
 */

register_uninstall_hook( __FILE__, 'list_saver_plugin_clean_up' );

function list_saver_plugin_clean_up()
{
   
	//remove settings here
	delete_option('list_saver_settings');
	
	//cron job
	if ( wp_next_scheduled( 'list_saver_mailchimp_event' ) ) 
	{
      wp_clear_scheduled_hook( 'list_saver_mailchimp_event' );
    }
    
    //delete database
    global $wpdb;
	
	$sql = "DROP TABLE IF EXISTS ".TBL_EMAILS;
	$wpdb->query($sql);
	
} 

add_action( 'wp_ajax_list_saver_ajax_mailchimp_subscribe', 'list_saver_ajax_mailchimp_subscribe' );

add_action( 'wp_ajax_nopriv_list_saver_ajax_mailchimp_subscribe', 'list_saver_ajax_mailchimp_subscribe' );

function list_saver_ajax_mailchimp_subscribe(){
	
  global $wpdb;	
   
   $ajax_response = array(); 
  
   $email = $_POST['sub_email'];

   $fname = $_POST['first_name'];

   $lname = $_POST['last_name'];
  
   $data['sub_first_name'] = stripslashes($first_name);
   
   $data['sub_last_name'] = stripslashes($lname);
   
   $data['sub_email'] = $email;
   
   $data['sub_ip'] = $_SERVER['REMOTE_ADDR'];
   
   if( $email && is_email($email) ){
    
    $email_exists = $wpdb->get_var( $wpdb->prepare("SELECT sub_email from ".TBL_EMAILS." where sub_email = %s", $email ) );
     
    if( ! $email_exists )
    
    $wpdb->insert( TBL_EMAILS, $data);
   
   }

   $ajax_response['success'] = 1;
   
   $ajax_response['message'] = "Thankyou for subscription.";
   
   echo json_encode($ajax_response);
   exit;    
   
}


function list_saver_set_action_url($query_args){
  	
 return add_query_arg($query_args);

}

function list_saver_mailchimp_response_in_cron_run($dta){

 global $mailchimp_response;

 return $mailchimp_response;	

}

function list_saver_send_email_html(){
return "text/html";	
}

add_filter('rewrite_rules_array', 'list_saver_add_rewrite_rules_custom');

add_filter('query_vars', 'list_saver_query_vars');

function list_saver_query_vars($new_var) {
$new_var[] = "subscribe";
$new_var[] = "euid";
return $new_var;
}


function list_saver_add_rewrite_rules_custom($rules){
  
  $subscriber_rules = array('subscribe/([^/]*)/([^/]*)/?$'=>'index.php?&subscribe=$matches[1]&euid=$matches[2]');
  $rules = $subscriber_rules+$rules;
  return $rules;
}

add_action( 'template_redirect', 'list_saver_confirm_subscription' );

function list_saver_confirm_subscription(){
 
 global $wp_query;
  
    
  $subscribe =  $wp_query->query_vars['subscribe'] ? $wp_query->query_vars['subscribe'] : $_GET['subscribe'];
  
  if($subscribe == 'confirm'){
	  
	  $euid=  $wp_query->query_vars['euid'] ? $wp_query->query_vars['euid'] : $_GET['euid'];
	  
	  if( !$euid )
	  return;
	  
	   include_once( list_saver_CLASSES_PATH . '/class.mailchimp.php');
	  
	   $settings=get_option('list_saver_settings');
  
       $api_key=$settings['list_saver_api_key'];
  
       $list_id=$settings['list_saver_list_id'];

       $mc = new list_saver_MailChimp($settings['list_saver_api_key']);
       
       $member_info = $mc->call('lists/member-info', array(
				  'id'        => $list_id,
				  'emails'    => array(array('euid'=>$euid))
				));
				
				
	  if( !isset($member_info['data']) )
	  
	  return;
	  
	  if($member_info['data'][0]['status'] == 'pending'){
		 
		 $data = array('double_opt' => false); 
		 
		 $data['email'] = $member_info['data'][0]['email'];  
	     
	     $list_id = $member_info['data'][0]['list_id'] ? $member_info['data'][0]['list_id'] :  $list_id ;
	     
	     $resp = $mc->subscribe_user($data, $list_id );
	     
	     if( isset($resp['status']) && $resp['status'] == 'error'){
		   
		   $html =	 $resp['error'].'<p>Go to <a href="'.home_url().'">home</a><</p>';
		   
		   wp_die($html);
		 }
		 
		 include_once( list_saver_CLASSES_PATH . '/class.subscription.php');
  
         $subscription = new List_Saver_Subscription();
         
         $connection = List_Saver_Database::Connect();
         
         List_Saver_Database::InsertOrUpdate($subscription->table,array('sub_status' => 'a'),array('sub_email' => $member_info['data'][0]['email']) );
         
         $html = "<p><b>".$member_info['data'][0]['email']."</b></p>";
         $html .= '<p>Thank you for subscribed.<br>Go to <a href="'.home_url().'">home</a></p>';
         if( $settings['list_saver_thankyou_url'] ){
		  wp_redirect($settings['list_saver_thankyou_url']);
		  exit;	 
		 }
         wp_die($html);
 	     
	  }else if($member_info['data'][0]['status'] == 'subscribed'){
		
		$html = '<p><b>'. $member_info['data'][0]['email'].'</b></p><p>You have already subscribed.</p><p>Go to <a href="'.home_url().'">home</a></p>';  
		
		wp_die($html);
	  
	  }
	  		
	  
  }
     	
}


