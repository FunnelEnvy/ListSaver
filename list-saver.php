<?php
/*
  Plugin Name: List Saver

  Description: Check if email subscribed or not.

  Version: 1.0.0

  Author: Sandeep Kumar

  Author URI: www.funnelenvy.com

 */

add_action('init', 'lc_manage_init');

include_once('configuration.php');



add_action( 'lc_mailchimp_event', 'lc_mailchimp_subscriber_event' );

function lc_mailchimp_subscriber_event($is_cron=true){
 
  include_once( LC_CLASSES_PATH . '/class.subscription.php');
  
  $subscription = new Subscription();

  $results = $subscription->Get(array(array('sub_status','=','p')));
 
  if( !$results )
  return;
  
  $mandrill_mail = false;
  
  include_once( LC_CLASSES_PATH . '/class.mailchimp.php');
	
  $settings=get_option('lc_settings');
  
  if( $settings['mandrill'] == 'yes'){
	
	include( dirname(__FILE__).'/classes/class.mandrill.php');  
	
	$mandrill = new LC_Mandrill(trim($settings['mandrll_api_key']));
	
	$resp = $mandrill->call("/users/ping", array());
	
	if($resp == 'PONG!')
	
	$mandrill_mail = true;
  
  }
    
  $api_key=$settings['lc_api_key'];
  
  $list_id=$settings['lc_list_id'];
  
  $interval_days=$settings['lc_days'];

  $mc = new LC_MailChimp($settings['lc_api_key']);

  $connection = Database::Connect();

  $admin_email =  get_option('admin_email');

  foreach( $results as $subscriber ){

   if(!$subscriber->sub_email)
   continue;
 
   // Check Interval Here
   
  
	
   if( $mc->is_subscribed_user($subscriber->sub_email,$list_id) ){
     
     Database::InsertOrUpdate($subscription->table,array('sub_status' => 'a'),array('sub_id' => $subscriber->sub_id) );
     
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

		 $custom_message =  stripslashes($settings['lc_confirm_email']);
		 
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
       
			$subject = $settings['lc_mail_subject'] ? stripslashes($settings['lc_mail_subject']) : "Confirm subscriber";
				    				    			   
			add_filter ("wp_mail_content_type", "lc_send_email_html");
			
			if($mandrill_mail){
				
			  if( $mandrill->send_mail($subscriber->sub_email, $subject, nl2br($custom_message) ) )
			  
			  Database::InsertOrUpdate($subscription->table,array('sub_email_sent' => 'true'),array('sub_id' => $subscriber->sub_id) );
	
				
			}else{
				
				if(wp_mail($subscriber->sub_email, $subject, nl2br($custom_message)) )
				  // Now Update email_sent to true
				Database::InsertOrUpdate($subscription->table,array('sub_email_sent' => 'true'),array('sub_id' => $subscriber->sub_id) );

				
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
function lc_manage_init(){
 	
	
	wp_enqueue_style( 'lc_manage_plugin_style1', plugins_url( '/css/bootstrap/css/bootstrap.css', __FILE__ ) );
	wp_enqueue_style( 'lc_manage_plugin_style3', plugins_url( '/css/list-checker.css', __FILE__ ) );
	wp_enqueue_script('jquery');
	wp_enqueue_script('lc_manage_plugin_scrpt', plugins_url( '/css/bootstrap/js/bootstrap.min.js', __FILE__ ));
	add_action('admin_menu', 'register_lc_manage_menu_page');
	

}


add_action( 'wp_enqueue_scripts', 'lc_admin_enqueue_scripts' );

function lc_admin_enqueue_scripts(){
	$settings = get_option('lc_settings');
	wp_enqueue_script( 'general', plugins_url('js/lc-script.js',__FILE__), array('jquery') );
	wp_localize_script( 'general', 'wtm', array('ajaxUrl' => admin_url('admin-ajax.php'), 'form_action_url' => $settings['lc_formaction_url'] ) );
}

/**
 * This function used to display List Chekcer menu in backend.
 */
function register_lc_manage_menu_page(){
	
	add_options_page('List Saver', 'List Saver', 'manage_options','lc_manage', 'lc_manage_display', '');

}

/**
 * This function used to display List Checker Dashboard.
 */

function lc_manage_display(){
	
	$tabs = array( 'lc_active_subscriptions' => 'Active Subscriptions','lc_pending_subscriptions' => 'Pending Subscriptions','lc_settings' => 'Settings' );
	
	echo '<div class="wrap">';
	
	echo '<h2 class="nav-tab-wrapper">';
	
	$current_tab = $_GET['tab'] ? $_GET['tab'] : 'lc_active_subscriptions'; 
	
	foreach($tabs as $tab => $tab_title ){
      
      $active_class = '';
      
      if( $current_tab == $tab )
      
      $active_class = 'nav-tab-active';
      
     echo '<a href="'.admin_url('admin.php?page=lc_manage&tab='.$tab).'" class="nav-tab '.$active_class.'">'.$tab_title.'</a>'; 		 
	
	}
	
	echo '</h2>';
	
	echo '<br>';
	
	switch( $current_tab ){
		
	  case 'lc_active_subscriptions'	:   lc_active_subscriptions(); break;	
	 
	  case 'lc_pending_subscriptions'	:   lc_pending_subscriptions(); break;

	  case 'lc_settings'	:   lc_settings(); break;
	  
	  
	  default : lc_active_subscriptions();
		
	}
	
	echo '</div>';
	
}


/**
 * This function used to view active subscriptions in backend.
 */
function lc_active_subscriptions(){
	
	include_once( LC_CLASSES_PATH . '/class.subscription.php');
	
	$tag = new Subscription();
	
	echo $tag->view_subscriptions("a");
	
}

/**
 * This function used to view pending subscriptions in backend.
 */
function lc_pending_subscriptions(){
	
	include_once( LC_CLASSES_PATH . '/class.subscription.php');
	
	$tag = new Subscription();
	
	echo $tag->view_subscriptions("p");
	
}


/**
 * This function used to settings in backend.
 */

function lc_settings()
{
	$lc_settings=get_option('lc_settings');
  
?>
<div class="wrap">  
<div id="icon-options-general" class="icon32"><br></div>
		
        <form class='form' role="form" method="post" action="options.php">  
        <?php wp_nonce_field('update-options') ?>
        <h2><?php _e( 'Mailchimp Settings', 'lc_language' ) ?></h2><br>
        <div class='form-group'> 
		<label>API KEY</label>
		<input type="textbox"  class="form-control" name="lc_settings[lc_api_key]" id="lc_api_key" value="<?php echo $lc_settings['lc_api_key']; ?>" />
		</div> 
		<div class='form-group'> 
		<label>LIST ID</label>
		<input type="textbox" class="form-control" name="lc_settings[lc_list_id]" id="lc_list_id" value="<?php echo $lc_settings['lc_list_id']; ?>" />
		</div> 
        <div class='form-group'> 
		<label>Form Action URL</label>
		<input type="textbox" class="form-control" name="lc_settings[lc_formaction_url]" id="lc_formaction_url" value="<?php echo $lc_settings['lc_formaction_url']; ?>" />
		</div>
		<div class='form-group'> 
		<label>Thank You Page URL</label>
		<input type="textbox" class="form-control" name="lc_settings[lc_thankyou_url]" id="lc_thankyou_url" value="<?php echo $lc_settings['lc_thankyou_url']; ?>" />
		</div> 	 		
		<div class='form-group'> 
		<label>Reminder Interval (Days)</label>
                <select name="lc_settings[lc_days]" id="lc_days" class="form-control" style="width:98px;">
		<?php for( $n = 1 ; $n<=30; $n++): ?>
                 <option value="<?php echo $n ?>" <?php selected( $lc_settings['lc_days'], $n ) ?>><?php echo $n ?></option>
                <?php endfor; ?> 
                </select>
                <p class="description">Set number of days for reminder email after the user signs up </p>
		</div>
		<div class='form-group'> 
		<label>Use Mandrill Email</label>
		 <select name="lc_settings[mandrill]" id="mandrill" class="form-control" style="width:70px;" onChange="if(this.value == 'yes') jQuery('#mandrill_options').show(); else jQuery('#mandrill_options').hide();">
		   <option value="no" <?php selected($lc_settings['mandrill'], 'no'); ?>>No</option>
		   <option value="yes" <?php selected($lc_settings['mandrill'], 'yes'); ?>>Yes</option>
		 </select>
		</div> 	
		<div class='form-group' id="mandrill_options" style="display:<?php if($lc_settings['mandrill'] == 'yes') echo "inline"; else echo "none"; ?>;"> 
		<label>Mandrill Api Key</label>
		 <input type="text" name="lc_settings[mandrll_api_key]" id="mandrill_api_key" value="<?php echo esc_attr($lc_settings['mandrll_api_key']) ?>" class="form-control">
		 </div> 
		<h2><?php _e( 'Email/Message Settings', 'lc_language' ) ?></h2><br>
		<div class='form-group'> 
		<label>Subject</label>
		<input type="text" class="form-control" style="width:500px;" name="lc_settings[lc_mail_subject]" id="lc_mail_subject" value="<?php echo stripslashes($lc_settings['lc_mail_subject']); ?>" />
		</div> 	 
		<div class='form-group'> 
		  <?php wp_editor( $lc_settings['lc_confirm_email'], 'lc_confirm_email', array('textarea_rows' => 10, 'textarea_name' => 'lc_settings[lc_confirm_email]')); ?>
		  <p>Available Tags: <b>{confirm_link}</b>, <b>{subscriber_email}</b> copy and paste message content body.</p>
		</div>
		<input type="hidden" name="action" value="update" />  
		<input type="hidden" name="page_options" value="lc_settings" />  
		<p class="submit">
		<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Save', 'lc_language' ) ?>">
		</p>
		</form>
 </div>
     	
<?php	
}
/**
 * This function used to show success/failure message in backend.
 */
function lc_manage_show_message($message, $errormsg = false){
	if(empty($message))
		return;
	if ($errormsg)
		echo '<div id="message" class="error">';
	else 
		echo '<div id="message" class="updated">';
	echo "<p><strong>$message</strong></p></div>";
}


register_activation_hook(__FILE__, 'lc_manage_plugin_activation');

register_deactivation_hook(__FILE__, 'lc_manage_plugin_deactivation'); 

/**
 * This function used to install required tables in database.
 */
function lc_manage_plugin_activation(){
	
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
  
  if ( ! wp_next_scheduled('lc_mailchimp_event') ) {
	 
	  wp_schedule_event( time(), 'daily', 'lc_mailchimp_event');
	
	}

}

/**
 * This function used to do required action on deactivation.
 */
function lc_manage_plugin_deactivation(){
	
	//remove cron job here
	
	wp_clear_scheduled_hook( 'lc_mailchimp_event' );

	
}

/**
 * This function used to clean up everything if plugin deleted.
 */

register_uninstall_hook( __FILE__, 'lc_plugin_clean_up' );

function lc_plugin_clean_up()
{
   
	//remove settings here
	delete_option('lc_settings');
	
	//cron job
	if ( wp_next_scheduled( 'lc_mailchimp_event' ) ) 
	{
      wp_clear_scheduled_hook( 'lc_mailchimp_event' );
    }
    
    //delete database
    global $wpdb;
	
	$sql = "DROP TABLE IF EXISTS ".TBL_EMAILS;
	$wpdb->query($sql);
	
} 

add_action( 'wp_ajax_lc_ajax_mailchimp_subscribe', 'lc_ajax_mailchimp_subscribe' );

add_action( 'wp_ajax_nopriv_lc_ajax_mailchimp_subscribe', 'lc_ajax_mailchimp_subscribe' );

function lc_ajax_mailchimp_subscribe(){
	
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


function lc_set_action_url($query_args){
  	
 return add_query_arg($query_args);

}

function lc_mailchimp_response_in_cron_run($dta){

 global $mailchimp_response;

 return $mailchimp_response;	

}

function lc_send_email_html(){
return "text/html";	
}

add_filter('rewrite_rules_array', 'lc_add_rewrite_rules_custom');

add_filter('query_vars', 'lc_query_vars');

function lc_query_vars($new_var) {
$new_var[] = "subscribe";
$new_var[] = "euid";
return $new_var;
}


function lc_add_rewrite_rules_custom($rules){
  
  $subscriber_rules = array('subscribe/([^/]*)/([^/]*)/?$'=>'index.php?&subscribe=$matches[1]&euid=$matches[2]');
  $rules = $subscriber_rules+$rules;
  return $rules;
}

add_action( 'template_redirect', 'lc_confirm_subscription' );

function lc_confirm_subscription(){
 
 global $wp_query;
  
    
  $subscribe =  $wp_query->query_vars['subscribe'] ? $wp_query->query_vars['subscribe'] : $_GET['subscribe'];
  
  if($subscribe == 'confirm'){
	  
	  $euid=  $wp_query->query_vars['euid'] ? $wp_query->query_vars['euid'] : $_GET['euid'];
	  
	  if( !$euid )
	  return;
	  
	   include_once( LC_CLASSES_PATH . '/class.mailchimp.php');
	  
	   $settings=get_option('lc_settings');
  
       $api_key=$settings['lc_api_key'];
  
       $list_id=$settings['lc_list_id'];

       $mc = new LC_MailChimp($settings['lc_api_key']);
       
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
		 
		 include_once( LC_CLASSES_PATH . '/class.subscription.php');
  
         $subscription = new Subscription();
         
         $connection = Database::Connect();
         
         Database::InsertOrUpdate($subscription->table,array('sub_status' => 'a'),array('sub_email' => $member_info['data'][0]['email']) );
         
         $html = "<p><b>".$member_info['data'][0]['email']."</b></p>";
         $html .= '<p>Thank you for subscribed.<br>Go to <a href="'.home_url().'">home</a></p>';
         if( $settings['lc_thankyou_url'] ){
		  wp_redirect($settings['lc_thankyou_url']);
		  exit;	 
		 }
         wp_die($html);
 	     
	  }else if($member_info['data'][0]['status'] == 'subscribed'){
		
		$html = '<p><b>'. $member_info['data'][0]['email'].'</b></p><p>You have already subscribed.</p><p>Go to <a href="'.home_url().'">home</a></p>';  
		
		wp_die($html);
	  
	  }
	  		
	  
  }
     	
}


