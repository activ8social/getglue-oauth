<?php
/**
 *
 * Here are the GetGlue Configs for you to set
 *
 */
$config['domain']  		= $_SERVER['HTTP_HOST'];
$config['root_path']	= '/'; // points to controller with getglue functionality
$config['source']		= 'http://'.$config['domain'].$config['root_path'];

$config['getglue']['oauth_consumer_key']     = 'YOUR CONSUMER KEY GOES HERE';
$config['getglue']['oauth_consumer_secret']  = "YOUR CONSUMER SECRET GOES HERE";
$config['getglue']['callback_url']           = $config['source']."connect/auth";
$config['getglue']['object_id']              = 'tv_shows/EXAMPLE';
$config['getglue']['app']                    = 'YOUR APPLICATION NAME HERE';
$config['getglue']['comment_count']          = 10;
$config['getglue']['oauth_in_popup']         = true;
$config['getglue']['get_glue_oauth_in_popup'] = true;

/*--------------------- ---*/
/*----DO NOT EDIT BELOW ---*/
/*--------------------- ---*/
$config['getglue']['site_url']           = "http://o.getglue.com/login";
$config['getglue']['authorize_url']      = "http://getglue.com/oauth/authorize";
$config['getglue']['request_token_url']  = "https://api.getglue.com/oauth/request_token";
$config['getglue']['access_token_url']   = "https://api.getglue.com/oauth/access_token";


?>