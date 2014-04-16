<?php 

class List_Saver_MailChimp
{
	private $api_key;
	private $api_endpoint = 'https://<dc>.api.mailchimp.com/2.0';
	private $verify_ssl   = false;

	/**
	 * Create a new instance
	 * @param string $api_key Your MailChimp API key
	 */
	function __construct($api_key)
	{
		$this->api_key = $api_key;
		list(, $datacentre) = explode('-', $this->api_key);
		$this->api_endpoint = str_replace('<dc>', $datacentre, $this->api_endpoint);
	}

        
        function is_subscribed_user( $email , $list_id ){
        
           $response = $this->call('lists/member-info', array('id' => $list_id, 'emails'  => array( array('email' => $email) ) ) ); 
           
           if( isset($response['data']) )
           {
             if( $response['data'][0]['status'] == 'subscribed' )
             return true;
             else 
             return false;   
           }  
         
          return  false;
        
       }

        function subscribe_user( $data, $list_id ){
         
          $merge_data = array();
          if( isset($data['first_name']))
          $merge_data['FNAME'] =  $data['first_name'];
          
           if(isset($data['last_name']))
          $merge_data['LNAME'] = $data['last_name'];
          
           if(isset($data['double_opt']))
           $opt = $data['double_opt'];
           else
           $opt = true;
          
            $data = array(
			'id' => $list_id,
			'email' => array( 'email' => $data['email']),
			'merge_vars' => $merge_data,
			'email_type' => 'html',
			'double_optin' => $opt,
			'update_existing' => false,
			'replace_interests' => true,
			'send_welcome' => false
		);

         $response = $this->call('lists/subscribe', $data );
           
         return $response;
         
         if( ! isset($response->error) || $response['status'] !== 'error'){
          return true;
         }
         else{
           
           if($result->code == 214)  
           return 'already_subscribed'; 
				
	   return 'error';
         }  
         
         return 'error';

        }


	/**
	 * Call an API method. Every request needs the API key, so that is added automatically -- you don't need to pass it in.
	 * @param  string $method The API method to call, e.g. 'lists/list'
	 * @param  array  $args   An array of arguments to pass to the method. Will be json-encoded for you.
	 * @return array          Associative array of json decoded API response.
	 */
	public function call($method, $args=array())
	{
		return $this->_raw_request($method, $args);
	}


	/**
	 * Performs the underlying HTTP request. Not very exciting
	 * @param  string $method The API method to be called
	 * @param  array  $args   Assoc array of parameters to be passed
	 * @return array          Assoc array of decoded result
	 */
	private function _raw_request($method, $args=array())
	{      
		$args['apikey'] = $this->api_key;

		$url = $this->api_endpoint.'/'.$method.'.json';
		
		if( function_exists('wp_remote_post') ){
			
			$response = wp_remote_post($url, array( 
			 'body' => $args,
			 'timeout' => 20,
			 'headers' => array('Accept-Encoding' => ''),
			 'sslverify' => false
			) ); 
			
		if( is_wp_error($response) ) {
			
			return false;
			
		 }else{
			
			$result = wp_remote_retrieve_body($response);
		 } 	
			
		}
        else
		if (function_exists('curl_init') && function_exists('curl_setopt')){
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0');		
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verify_ssl);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($args));
			$result = curl_exec($ch);
			curl_close($ch);
		}else{
			$json_data = json_encode($args);
			$result = file_get_contents($url, null, stream_context_create(array(
			    'http' => array(
			        'protocol_version' => 1.1,
			        'user_agent'       => 'PHP-MCAPI/2.0',
			        'method'           => 'POST',
			        'header'           => "Content-type: application/json\r\n".
			                              "Connection: close\r\n" .
			                              "Content-length: " . strlen($json_data) . "\r\n",
			        'content'          => $json_data,
			    ),
			)));
		}

		return $result ? json_decode($result, true) : false;
	}

}
