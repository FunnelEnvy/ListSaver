<?php

class List_Saver_Mandrill {
    
    public $apikey;
    public $ch;
    public $root = 'https://mandrillapp.com/api/1.0';
    public $debug = false;

    public static $error_map = array(
        "ValidationError" => "Mandrill_ValidationError",
        "Invalid_Key" => "Mandrill_Invalid_Key",
        "PaymentRequired" => "Mandrill_PaymentRequired",
        "Unknown_Subaccount" => "Mandrill_Unknown_Subaccount",
        "Unknown_Template" => "Mandrill_Unknown_Template",
        "ServiceUnavailable" => "Mandrill_ServiceUnavailable",
        "Unknown_Message" => "Mandrill_Unknown_Message",
        "Invalid_Tag_Name" => "Mandrill_Invalid_Tag_Name",
        "Invalid_Reject" => "Mandrill_Invalid_Reject",
        "Unknown_Sender" => "Mandrill_Unknown_Sender",
        "Unknown_Url" => "Mandrill_Unknown_Url",
        "Unknown_TrackingDomain" => "Mandrill_Unknown_TrackingDomain",
        "Invalid_Template" => "Mandrill_Invalid_Template",
        "Unknown_Webhook" => "Mandrill_Unknown_Webhook",
        "Unknown_InboundDomain" => "Mandrill_Unknown_InboundDomain",
        "Unknown_InboundRoute" => "Mandrill_Unknown_InboundRoute",
        "Unknown_Export" => "Mandrill_Unknown_Export",
        "IP_ProvisionLimit" => "Mandrill_IP_ProvisionLimit",
        "Unknown_Pool" => "Mandrill_Unknown_Pool",
        "NoSendingHistory" => "Mandrill_NoSendingHistory",
        "PoorReputation" => "Mandrill_PoorReputation",
        "Unknown_IP" => "Mandrill_Unknown_IP",
        "Invalid_EmptyDefaultPool" => "Mandrill_Invalid_EmptyDefaultPool",
        "Invalid_DeleteDefaultPool" => "Mandrill_Invalid_DeleteDefaultPool",
        "Invalid_DeleteNonEmptyPool" => "Mandrill_Invalid_DeleteNonEmptyPool",
        "Invalid_CustomDNS" => "Mandrill_Invalid_CustomDNS",
        "Invalid_CustomDNSPending" => "Mandrill_Invalid_CustomDNSPending",
        "Metadata_FieldLimit" => "Mandrill_Metadata_FieldLimit",
        "Unknown_MetadataField" => "Mandrill_Unknown_MetadataField"
    );

    public function __construct($apikey=null) {
        
        if(!$apikey) throw new Mandrill_Error('You must provide a Mandrill API key');
        
        $this->apikey = $apikey;

        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_USERAGENT, 'Mandrill-PHP/1.0.52');
        curl_setopt($this->ch, CURLOPT_POST, true);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->ch, CURLOPT_HEADER, false);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 600);

        $this->root = rtrim($this->root, '/') . '/';

        
    }

    public function __destruct() {
        
        curl_close($this->ch);
   
    }
    
    public function send_mail( $to, $subject, $message , $from_email, $from_name ){
	
	 $message = array(
        'html' => $message,
        'subject' => $subject,
        'from_email' => $from_email,
        'from_name' => $from_name,
        'to' => array(
            array(
                'email' => $to,
                'type' => 'to'
            )
        ),
        'tags' => null,
        'important' => false,
        'track_opens' => true,
        'track_clicks' => true,
        'auto_text' => null,
        'auto_html' => null,
        'inline_css' => null,
        'url_strip_qs' => null,
        'preserve_recipients' => null,
        'view_content_link' => null,
        'tracking_domain' => null,
        'signing_domain' => null,
        'return_path_domain' => null,
        'merge' => false,
       );	
       
       $response = $this->call("/messages/send", array('message' =>  $message, 'async' => false, 'ip_pool' => null, 'send_at' => null));
	   
	   if(!  is_wp_error($response) ){

	    if( $response[0]['status'] == 'sent' || $response[0]['status'] == 'queued')
	    return true;
		   
	   }
	   
	   return false;
	
	}

    public function call($url, $params) {
        $params['key'] = $this->apikey;
        $params = json_encode($params);
        $ch = $this->ch;

        curl_setopt($ch, CURLOPT_URL, $this->root . $url . '.json');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_VERBOSE, $this->debug);

        $start = microtime(true);
        $this->log('Call to ' . $this->root . $url . '.json: ' . $params);
        if($this->debug) {
            $curl_buffer = fopen('php://memory', 'w+');
            curl_setopt($ch, CURLOPT_STDERR, $curl_buffer);
        }

        $response_body = curl_exec($ch);
        $info = curl_getinfo($ch);
        $time = microtime(true) - $start;
        if($this->debug) {
            rewind($curl_buffer);
            $this->log(stream_get_contents($curl_buffer));
            fclose($curl_buffer);
        }
        $this->log('Completed in ' . number_format($time * 1000, 2) . 'ms');
        $this->log('Got response: ' . $response_body);

        if(curl_error($ch)) {
            throw new WP_Error("API call to $url failed: " . curl_error($ch));
        }
        $result = json_decode($response_body, true);
        if($result === null) throw new WP_Error('We were unable to decode the JSON response from the Mandrill API: ' . $response_body);
        
        
        return $result;
    }

  

    public function castError($result) {
        if($result['status'] !== 'error' || !$result['name']) throw new Mandrill_Error('We received an unexpected error: ' . json_encode($result));

        $class = (isset(self::$error_map[$result['name']])) ? self::$error_map[$result['name']] : 'Mandrill_Error';
        return new $class($result['message'], $result['code']);
    }

    public function log($msg) {
        if($this->debug) error_log($msg);
    }
}


