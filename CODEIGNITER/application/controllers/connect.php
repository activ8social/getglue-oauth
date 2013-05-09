<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once 'application/controllers/page.php';

class Connect extends Page {

	protected $data;

	public function __construct() {
		parent::__construct();

		/**
		 * ported from FXHTM
		 */
		//get request string
		parse_str(substr(strrchr($_SERVER['REQUEST_URI'], "?"), 1), $_GET);
		$this->load->helper('url');
		$this->load->library('session');
		$this->load->helper('cookie');

		$this->config->load('social');

		$glueData = $this->config->item('getglue');
		$this->data['consumer_key'] 	=	$glueData['oauth_consumer_key'];
		$this->data['consumer_secret'] 	= 	$glueData['oauth_consumer_secret'];
		$this->data['object_id'] 		=	$glueData['object_id'];
		$this->data['callback_url'] 	=	$glueData['callback_url'];
		$this->data['app'] 				=	$glueData['app'];
		$this->data['source'] 			=	$this->config->item('source');
	}

	/**
	 * Default controller action
	 */
	public function index() {

		$page_data = array();
		
		// add ad tag id
		$page_data['adTagId'] = $this->adTagId;

		// add the version number to the view - to hide/display certain items
		$page_data['version'] = $this->version;

		// add timezone data
		$page_data['timezone'] = $this->timezone;

		$page_data['page'] = "connect";
		$page_data['title'] = "FX Movie Channel | FX Networks";

		//tracking data
		$page_data['siteSection'] = "FXM";
		$page_data['subSection'] = "connect";

		$xml = simplexml_load_file('xml/menu.xml');
		$json = json_encode($xml);
		$menu = json_decode($json, TRUE);
		$page_data['nav'] = $menu;

		$page_data['user_agent'] = $this->agent->browser()." ".$this->agent->version();
		$page_data['is_ipad'] = $this->agent->is_mobile('ipad');

		$page_data['globalCurrTime'] = $this->globalCurrTime->format('M d, Y H:i:s');

		// set up views
		$page_data['header'] 		= $this->load->view('partial/_partial_header', $page_data, true);
		$page_data['top_banner_ad'] = $this->load->view('partial/_partial_top_banner_ad', $page_data, true);
		$page_data['fxm_header'] 	= $this->load->view('partial/_partial_fxm_header', $page_data, true);
		$page_data['footer'] 		= $this->load->view('partial/_partial_footer', $page_data, true);
		$page_data['tracking']		= $this->load->view('partial/_partial_tracking', $page_data, true);

		// instantiate necessary models
		$this->load->model('Social_model', 'objSocial');

		// get "Get Glue" stats
		$page_data['getglue_data'] = $this->objSocial->getGetGlueStats();

		// load the necessary helpers
		$this->load->helper('community');

		// get the feeds
		$feeds = getFeeds($this->objSocial);

		// get the comment feeds from all social networks (to build community section)
		$page_data['facebookFeed'] = $feeds['facebookFeed'];
		$page_data['twitterFeed']  = $feeds['twitterFeed'];
		$page_data['getglueFeed']  = $feeds['getglueFeed'];
		$page_data['allFeed']      = $feeds['allFeed'];

		// default the comment area to an empty string
		$page_data['social_comment'] = "";

		// load up the community partial view
		$page_data['fxm_community']	= $this->load->view('partial/_partial_fxm_community', $page_data, true);

		//load model and do any data processing that is required ...
		$page_data['content'] 		= $this->load->view('connect', $page_data, true);
		$this->load->view('main', $page_data);
	}

	/**
	* ported from FXHTM
	*/
	public function check_in($comment = '') {
		$params['objectId'] = $this->data['object_id'];
		$params['app'] = $this->data['app'];
		$params['source']  = $this->data['source'];
		$params['comment'] = $comment;

		if ($this->config->item('use_session')) {
			$user = $this->session->userData('glue_userID');
		} else {
			$user = get_cookie('gg_glue_userId');
		}

		if (isset($user) && $user != '') {
			$this->load->library('oauthlib',$this->data);

			$response = $this->oauthlib->api_call('http://api.getglue.com/v2/user/addCheckin', $params);

			$this->output->set_content_type('application/json')->set_output(str_replace("\\/", "/", json_encode($response)));
		}
	}

	/**
	 * ported from FXHTM
	 */
	public function getglue() {
		//callback for oauth popup window
		$this->load->view('getglue_auth_fallback');
	}

	/**
	* ported from FXHTM
	*/
	public function login(){
		//load Outh
		$this->load->library('oauthlib',$this->data);

		//get request token
		$token = $this->oauthlib->get_request_token();

		if (isset($token['oauth_token']) && $token['oauth_token'] != "") {

			if ($this->config->item('use_session')) {
				//store results to session
				$this->session->set_userdata('oauth_request_token', $token['oauth_token']);
				$this->session->set_userdata('oauth_request_token_secret',  $token['oauth_token_secret']);
			}
			else {

				$cookie = array(
					'name'   => 'oauth_request_token',
					'value'  => $token['oauth_token'],
					'expire' => '36000',
					'domain' => $this->config->item('domain'),
					'path'   => '/',
					'prefix' => 'gg_'
				);
				set_cookie($cookie);
				$cookie = array(
					'name'   => 'oauth_request_token_secret',
					'value'  => $token['oauth_token_secret'],
					'expire' => '36000',
					'domain' => $this->config->item('domain'),
					'path'   => '/',
					'prefix' => 'gg_'
				);
				set_cookie($cookie);
			}
			//get authorize token
			$request_link = $this->oauthlib->get_authorize_URL($token, (isset($_REQUEST['signed_request']) ? $_REQUEST['signed_request'] : null));
			$data['link'] = $request_link;
			header("Location: " . $request_link);
		}
		else {
			//GetGlue oauth error

			$this->load->view('getglue_auth_timeout');
		}
	}

	/**
	* ported from FXHTM
	*/
	public function get_glue_user() {
		$user_id = get_cookie('gg_glue_userId');
		$data = array('user_id' => $user_id,);

		$data['icon_url'] =  $this->get_glue_image_path . $user_id . "/avatar.png";
		$data['username'] = $user_id;
		$data['user_id'] = $user_id;

		//$data = array(''consumer_secret', 'callback_url', 'oauth_token_secret', 'oauth_token');
		$this->load->library('oauthlib', $this->data);

		$params = array('userId'=> $user_id);
		$url = "http://api.getglue.com/v2/user/profile";
		$response = $this->oauthlib->api_call($url . "?" . http_build_query($params), $params);

		if(isset($response['response']) && is_string($response['response']['profile']['displayName'])){
			$data['username'] = $response['response']['profile']['displayName'];
		} else {
			//oops!
		}

		//$this->session->userData('glue_userID')
		$this->output->set_content_type('application/json')->set_output(str_replace("\\/", "/", json_encode($data)));
	}

	/**
	* ported from FXHTM
	*/
	public function logout() {
		/*LOGOUT
		$this->load->library('oauthlib',$this->data);
		$logout_api_url = $this->oauthlib->api_call('http://api.getglue.com/v2/user/logout', NULL, false, true);
		$logout_api_url = str_replace("GET&http%3A%2F%2Fapi.getglue.com%2Fv2%2Fuser%2Flogout&", "http://api.getglue.com/v2/user/logout?", $logout_api_url);
		$cookie = array(
			'name'   => 'logout_api_url',
			'value'  => $logout_api_url,
			'expire' => '120',
			'domain' => $this->config->item('domain'),
			'path'   => '/',
			'prefix' => 'gg_'
		);
		set_cookie($cookie);
		*/
		$cookie = array(
			'name'   => 'glue_userId',
			'domain' => $this->config->item('domain'),
			'path'   => '/',
			'prefix' => 'gg_'
		);
		delete_cookie($cookie);
		$cookie = array(
			'name'   => 'oauth_access_token_secret',
			'domain' => $this->config->item('domain'),
			'path'   => '/',
			'prefix' => 'gg_'
		);
		delete_cookie($cookie);
		$cookie = array(
			'name'   => 'oauth_access_token',
			'domain' => $this->config->item('domain'),
			'path'   => '/',
			'prefix' => 'gg_'
		);
		if ($this->config->item('use_session')){
			$this->session->destroy();
		} else {
			$cookie = array(
				'name'   => 'oauth_request_token',
				'domain' => $this->config->item('domain'),
				'path'   => '/',
				'prefix' => 'gg_'
			);
			delete_cookie($cookie);
			$cookie = array(
				'name'   => 'oauth_request_token_secret',
				'domain' => $this->config->item('domain'),
				'path'   => '/',
				'prefix' => 'gg_'
			);
			delete_cookie($cookie);
		}
		$homepage = $this->config->item('source');

		if (isset($_REQUEST['signed_request']) && $_REQUEST['signed_request'] != 'false') {
			$homepage .= "?signed_request=".$_REQUEST['signed_request'];
		}
		header("Location: {$homepage}");
	}

	/**
	* ported from FXHTM
	*/
	public function auth() {
		if ($this->config->item('use_session')) {
			//session vars set before redirect were being lost in IE.  changed to Native Session
			//http://stackoverflow.com/questions/1703770/code-igniter-login-session-and-redirect-problem-in-internet-explorer
			//http://codeigniter.com/wiki/File:CI_1.5.1_with_Session.zip
			$get_glue_data = array(
				'glue_userID' =>				$this->session->userdata('glue_userID'),
				'oauth_request_token' =>		$this->session->userdata('oauth_request_token'),
				'oauth_request_token_secret' =>	$this->session->userdata('oauth_request_token_secret'),
				'oauth_access_token' =>			'',
				'oauth_access_token_secret' =>	'',
			);
		}
		else {
			$get_glue_data = array(
				'glue_userID' =>				get_cookie('gg_glue_userId'),
				'oauth_request_token' =>		get_cookie('gg_oauth_request_token'),
				'oauth_request_token_secret' =>	get_cookie('gg_oauth_request_token_secret'),
				'oauth_access_token' =>			'',
				'oauth_access_token_secret' =>	'',
			);
		}

		$this->data['oauth_token'] = $get_glue_data['oauth_request_token'];
		$this->data['oauth_token_secret'] = $get_glue_data['oauth_request_token_secret'];

		//load the library with the variables defined in the constructor
		$this->load->library('oauthlib', $this->data);

		$get_glue_data['oauth_verifier']     = '';// $_REQUEST['oauth_verifier'];

		/* Request access tokens - keep trying till ya get one! */
		//$tokens = false;
		//while($tokens == false) {
			$tokens = $this->oauthlib->get_access_token($get_glue_data['oauth_verifier']);
		//}
		/*Save the access tokens. Normally these would be saved in a database for future use. */
		$get_glue_data['oauth_access_token'] = $tokens['oauth_token'];
		$get_glue_data['oauth_access_token_secret'] = $tokens['oauth_token_secret'];
		$get_glue_data['glue_userID'] = $tokens['glue_userId'];

		if ($this->config->item('use_session')) $this->session->set_userdata($get_glue_data);

		$cookie = array(
			'name'   => 'glue_userId',
			'value'  => $get_glue_data['glue_userID'],
			'expire' => '15552000',
			'domain' => $this->config->item('domain'),
			'path'   => '/',
			'prefix' => 'gg_'
		);
		set_cookie($cookie);
		$cookie = array(
			'name'   => 'oauth_access_token_secret',
			'value'  => $get_glue_data['oauth_access_token_secret'],
			'expire' => '15552000',
			'domain' => $this->config->item('domain'),
			'path'   => '/',
			'prefix' => 'gg_'
		);
		set_cookie($cookie);
		$cookie = array(
			'name'   => 'oauth_access_token',
			'value'  => $get_glue_data['oauth_access_token'],
			'expire' => '15552000',
			'domain' => $this->config->item('domain'),
			'path'   => '/',
			'prefix' => 'gg_'
		);
		set_cookie($cookie);

		$homepage = $this->config->item('source');

		if (isset($_REQUEST['signed_request']) && $_REQUEST['signed_request'] != 'false') {
			$homepage .= "?signed_request=".$_REQUEST['signed_request'];
		}
		$glueData = $this->config->item('getglue');
		$fallback_if_popup = $glueData['get_glue_auth_fallback'];

		//echo $homepage; exit;

		if ($glueData['get_glue_oauth_in_popup']){
			header("Location: {$fallback_if_popup}");
		} else {
			header("Location: {$homepage}");
		}
	}
}