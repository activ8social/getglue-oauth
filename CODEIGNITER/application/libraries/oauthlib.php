<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once(dirname(__FILE__).'/oauth/OAuth.php');

class oauthlib {
	 var $consumer;
	 var $token;
	 var $method;
	 var $http_status;
	 var $last_api_call;
	 var $callback;
	 var $timeout = 120;
	 var 	$api_request = 'oauth/request_token';
	 var 	$api_auth = 'http://getglue.com/oauth/authorize';
	 var 	$api_access = 'oauth/access_token';
	 var 	$api_url = 'http://api.getglue.com/';
	 var $ci;
	function __construct($data) 
	{
		
		$this->method = new OAuthSignatureMethod_HMAC_SHA1();
		$this->consumer = new OAuthConsumer($data['consumer_key'], $data['consumer_secret']);
		$this->callback = $data['callback_url'];
 
		
		if(!empty($data['oauth_token']) && !empty($data['oauth_token_secret']) && !empty($data['callback_url']))
		{
			$this->token = new OAuthConsumer($data['oauth_token'],$data['oauth_token_secret']);
 
 
		}
		else
		{
			$this->token = NULL;
		}
		
		$this->ci =& get_instance();
 		$this->ci->load->library('session');
 		$this->ci->load->library('xml');
 }

 
	 function debug_info()
		{
		 echo("Last API Call: ".$this->last_api_call."<br />\n");
		 echo("Response Code: ".$this->http_status."<br />\n");
		 }
		 
 
		 
	function get_request_token()
{
	 $args = array();
 
	 $request = OAuthRequest::from_consumer_and_token($this->consumer,
		 $this->token, 'GET',
		  $this->api_url.$this->api_request, $args);
 
	$request->set_parameter("oauth_callback", $this->callback);
	$request->sign_request($this->method, $this->consumer,$this->token);
	$request = $this->http($request->to_url());
 
	 parse_str($request,$token);
 
	 $this->token = new OAuthConsumer($token['oauth_token'],$token['oauth_token_secret'],$this->callback);
 
	 return $token;
 }

 function get_access_token($oauth_verifier)
	{
		$args = array();
 
		$request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, 'GET', $this->api_url.$this->api_access,$args);
		$request->set_parameter("oauth_verifier", $oauth_verifier);
		$request->sign_request($this->method, $this->consumer,$this->token);
		$request = $this->http($request->to_url());
 	
		parse_str($request,$token);
		//echo $oauth_verifier;
 
		//if ($token['oauth_problem']=='token_rejected') {
			//return false;
		//}
		$this->token = new OAuthConsumer($token['oauth_token'],$token['oauth_token_secret'],1);
	 
		return $token;
	}
	
	function api_call($api,$params=array(),$boolDebug=false,$returnUrl = false){
		$this->ci->load->helper('cookie');		
		if (!$boolDebug){
			//$session = $this->ci->session->all_userdata();
			$session = array(
				'glue_userID' =>		get_cookie('gg_glue_userId'),
				'oauth_request_token' =>	get_cookie('gg_oauth_request_token'),
				'oauth_request_token_secret' =>	get_cookie('gg_oauth_request_token_secret'),
				'oauth_access_token' =>		get_cookie('gg_oauth_access_token'),
				'oauth_access_token_secret' =>	get_cookie('gg_oauth_access_token_secret'),
			);
			
			$test_access_token = NULL;
			if (isset($session['oauth_access_token']) && !empty($session['oauth_access_token'])){
				$test_access_token = new OAuthConsumer($session['oauth_access_token'], $session['oauth_access_token_secret']);
		 		
			}
			
			$acc_req = OAuthRequest::from_consumer_and_token($this->consumer,  $test_access_token, "GET", $api, $params);
		    $acc_req->sign_request($this->method, $this->consumer,  $test_access_token);
	
			if ($returnUrl) return $acc_req->base_string;
	        $request = $this->http( $acc_req);
	  
			$response = json_decode(json_encode((array) simplexml_load_string($request)),1);
		} else {
			$xml='<?xml version="1.0" encoding="UTF-8"?><adaptiveblue><request><method>/user/addCheckin</method><params><app>ExampleApp</app><oauth_signature>rtAvE6cACic9T5yMWSRNqsDgEiQ=</oauth_signature><source>http://exampleglueapp.com/</source><oauth_nonce>e63a035e036f339edd2dd3336efe1ce2</oauth_nonce><oauth_version>1.0</oauth_version><objectId>tv_shows/psych</objectId><oauth_signature_method>HMAC-SHA1</oauth_signature_method><oauth_consumer_key>66e46233766a4e893b2ef183bc076910</oauth_consumer_key><oauth_token>ODzKkOOo3CoR2btA05T0eIYkk66aamFhPEWJx3Ot3UAi6fNRd"UDcMeENNKniZviz6LsbKy4Vb6cJ3TSC096ie_i0gRYsp6KVEpzaThhhMuqbesEoLwYDdcQ1ndiv3GoTZKC_cPK-1124731036</oauth_token><oauth_timestamp>1317045749</oauth_timestamp><tokenUser>moneygrabF</tokenUser></params><timestamp>2011-09-26T14:02:30Z</timestamp><computeTime>535</computeTime></request><response><interactions><interaction><objectKey>tv_shows/psych</objectKey><title>Psych</title><userId>moneygrabF</userId><displayName>Andy Mascaro</displayName><action>Checkin</action><source>http://exampleglueapp.com/</source><timestamp>2011-09-26T14:02:29Z</timestamp><numCheckins>1</numCheckins><verb>watching</verb><stickers><sticker><imageData><baseImageUrl>http://glueimg.s3.amazonaws.com/stickers/</baseImageUrl><sizes><size>medium</size><size>large</size><size>huge</size><size>stream</size></sizes><imageUrlSuffix>/getglue/ready_for_fall_tv.png</imageUrlSuffix></imageData><key>getglue/ready_for_fall_tv</key><name>Ready for Fall TV!</name><canonicalName>getglue/ready_for_fall_tv</canonicalName><description>You are ready for Fall TV! Thanks for gearing up by checking-in to a show today. Be sure to download the new GetGlue for iPhone to chat with friends and fans about your favorite shows: http://bit.ly/dzb4Ly .</description><group>GetGlue</group><limited>true</limited><startDate>2011-09-20T13:00:00Z</startDate><endDate>2011-09-28T04:00:00Z</endDate><awarded>1317045749946</awarded></sticker></stickers><points><gain><reason>Checkin</reason><value>2</value></gain></points><special><key>usa_network/2011-08-12T10:13:57Z</key><message>Get 10% off all merchandise!</message><awardType>merchandise_discount</awardType><title>10% off all Psych merchandise</title><level>check-in</level><instructions>Simply enter coupon code GETGLUE at checkout to receive 10% off from our friends at Shop the Shows.</instructions><description>For checking-in you\'ve earned 10% off anything in the Psych store.</description><link>http://www.nbcuniversalstore.com/psych/index.php?v=usa_psych&amp;ecid=NMA-5583&amp;pa=NMA-GLUE</link><isTimeLimited>false</isTimeLimited><linkTitle>Visit the store!</linkTitle><type>page</type></special><hint><action>checkin</action><sticker>Fan</sticker><more>4</more></hint></interaction></interactions></response></adaptiveblue>';
			$response = json_decode(json_encode((array) simplexml_load_string($xml)),1);
		}
		
		 return $response;
	}
 
	 function parse_request($string)
	 {
		 $args = explode("&", $string);
		 $args[] = explode("=", $args['0']);
		 $args[] = explode("=", $args['1']);
 
		 $token[$args['2']['0']] = $args['2']['1'];
		 $token[$args['3']['0']] = $args['3']['1'];
 
		 return $token;
	 }
 
	function parse_access($string)
	{
		$r = array();
 
		foreach(explode('&', $string) as $param)
		{
			$pair = explode('=', $param, 2);
			if(count($pair) != 2) continue;
			$r[urldecode($pair[0])] = urldecode($pair[1]);
		}
		return $r;
	}
 
	function get_authorize_URL($token, $signed_request) {
		if(is_array($token)) $token = $token['oauth_token'];
		$url = $this->api_auth."?oauth_token=" .$token.'&oauth_callback='.$this->callback;
		if (isset($signed_request)) $url .= "?signed_request=".$signed_request;
		return $url;
	}
 
	function http($url, $post_data = null)
	{
		$ch = curl_init();
 
		if(defined("CURL_CA_BUNDLE_PATH"))
		curl_setopt($ch, CURLOPT_CAINFO, CURL_CA_BUNDLE_PATH);
 
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
 
		if(isset($post_data))
		{
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		}
 
		$response = curl_exec($ch);
		$this->http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$this->last_api_call = $url;
		curl_close($ch);
 

		
		return $response;
	}
	
    function httpRequest($url, $auth_header, $method, $body = NULL) {
 
		if (!$method) {
			$method = "GET";
		};
 
		//echo $url. " " .$method. " " .$body;
 
		//echo $auth_header;
 
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array($auth_header)); // Set the headers.
 
		//echo $auth_header;
 
		if ($body) {
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
			curl_setopt($curl, CURLOPT_HTTPHEADER, array($auth_header, "Content-Type: text/xml;charset=utf-8"));  
 
 
		}
 
		$data = curl_exec($curl);
		echo curl_getinfo($curl, CURLINFO_HTTP_CODE);
		//if ($this->debug) {
			//echo "bla";
			echo $data . "\n";
		//}
 
		curl_close($curl);
 
		return $data; 
	}



}  
?>