<?php
/**
 * Integrator
 * Wordpress - Curl Utility File
 * 
 * @package    Integrator 3.0 - Wordpress Package
 * @copyright  2009 - 2012 Go Higher Information Services.  All rights reserved.
 * @license    ${p.PROJECT_LICENSE}
 * @version    3.0.19 ( $Id: curl.php 144 2012-11-29 17:19:42Z steven_gohigher $ )
 * @author     Go Higher Information Services
 * @since      3.0.0
 * 
 * @desc       This file is the curl handler for the Integrator
 * 
 */

/*-- Security Protocols --*/
//defined('WHMCS') OR exit('No direct script access allowed');
/*-- Security Protocols --*/

/**
 * IntCurl class object
 * @version		3.0.19
 * 
 * @since		3.0.0
 * @author		Steven
 */
class IntCurl
{
	/**
	 * Contains the curl response for debugging
	 * @access		private
	 * @var			string
	 * @since		3.0.0
	 */
	private	$response		= '';
	
	/**
	 * Contains the curl handler for a session
	 * @access		private
	 * @var			object
	 * @since		3.0.0
	 */
	private	$session		= null;
	
	/**
	 * The url of the session
	 * @access		private
	 * @var			string
	 * @since		3.0.0
	 */
	private	$url			= null;
	
	/**
	 * The options set to the curl_setopt array
	 * @access		private
	 * @var			array
	 * @since		3.0.0
	 */
	private	$options		= array();
	
	/**
	 * The extra HTTP headers to set
	 * @access 		private
	 * @var			array
	 * @since		3.0.0
	 */
	private	$headers		= array();
	
	/**
	 * The error code returned by the curl handler
	 * @access		public
	 * @var			integer
	 * @since		3.0.0
	 */
	public	$error_code		= null;
	
	/**
	 * The error message returned by the curl handler
	 * @access		public
	 * @var			string
	 * @since		3.0.0
	 */
	public	$error_string	= null;
	
	/**
	 * The information array from the curl handler request
	 * @access		public
	 * @var			array
	 * @since		3.0.0
	 */
	public	$info			= array();
	
	/**
	 * The response stored for debugging purposes
	 * @access		public
	 * @var			string
	 * @since		3.0.0
	 */
	public	$debugresponse	= null;
	
	/**
	 * The method used for the curl call
	 * @access		public
	 * @var			string
	 * @since		3.0.0
	 */
	public	$debugmethod	= null;
	
	/**
	 * The posted variables to the curl call
	 * @access		public
	 * @var			array
	 * @since		3.0.0
	 */
	public	$debugpost		= array();
	
	
	/**
	 * Constructor
	 * @access		public
	 * @version		3.0.19
	 * @param		string		- $url: if set contains a URL to create the CURL instance with
	 * 
	 * @since		3.0.0
	 */
	public function __construct($url = '')
	{
		if ( ! $this->is_enabled()) {
			exit( 'cURL Class - PHP was not built with cURL enabled. Rebuild PHP with --with-curl to use cURL.');
		}
		
		$url AND $this->create($url);
	}
	
	
	/**
	 * Call constructor - when a method is called
	 * @access		public
	 * @version		3.0.19
	 * @param 		string		- $method: the name of the method being called
	 * @param 		array		- $arguments: the arguments passed to the method
	 * 
	 * @return		redirect to appropriate method
	 * @since		3.0.0
	 */
	public function __call($method, $arguments)
	{
		if (in_array($method, array('simple_get', 'simple_post', 'simple_put', 'simple_delete')))
		{
			// Take off the "simple_" and past get/post/put/delete to _simple_call
			$verb = str_replace('simple_', '', $method);
			array_unshift($arguments, $verb);
			return call_user_func_array(array($this, '_simple_call'), $arguments);
		}
	}
	
	
	/**
	 * **********************************************************************
	 * SIMPLE METHODS
	 * Used for quick and easy curl calls with a single line.
	 * **********************************************************************
	 */
	
	
	/**
	 * Simple call method
	 * @access		public
	 * @version		3.0.19
	 * @param		string		- $method: the call method to perform
	 * @param		string		- $url: the url to call with curl
	 * @param		array		- $params: any variables / parameters to post/get with
	 * @param		array		- $options: any options to set to the curl handler
	 * 
	 * @return		result of execution
	 * @since		3.0.0
	 */
	public function _simple_call($method, $url, $params = array(), $options = array())
	{
		// Get acts differently, as it doesnt accept parameters in the same way
		if ($method === 'get') {
			// If a URL is provided, create new session
			$this->create($url.($params ? '?'.http_build_query($params) : ''));
		}
		else {
			// If a URL is provided, create new session
			$this->create($url);
			$this->{$method}($params, $options );
		}
		
		return $this->execute();
	}
	
	
	/**
	 * Simple FTP get
	 * @access		public
	 * @version		3.0.19
	 * @param		string		- $url: the url to call for
	 * @param		string		- $file_path: the remote file path to get
	 * @param		string		- $username: the username to use for logging into the FTP server
	 * @param		string		- $password: the password to use for logging into the FTP server
	 * 
	 * @return		result of the ftp execution
	 * @since		3.0.0
	 */
	public function simple_ftp_get($url, $file_path, $username = '', $password = '')
	{
		// If there is no ftp:// or any protocol entered, add ftp://
		if ( ! preg_match('!^(ftp|sftp)://! i', $url)) {
			$url = 'ftp://' . $url;
		}
		
		// Use an FTP login
		if ($username != '') {
			$auth_string = $username;
			
			if ($password != '') {
				$auth_string .= ':' . $password;
			}
			
			// Add the user auth string after the protocol
			$url = str_replace('://', '://' . $auth_string . '@', $url);
		}
		
		// Add the filepath
		$url .= $file_path;
		
		$this->option(CURLOPT_BINARYTRANSFER, TRUE);
		$this->option(CURLOPT_VERBOSE, TRUE);
		
		return $this->execute();
	}
	
	
	/**
	 * **********************************************************************
	 * ADVANCED METHODS
	 * Can be used to create more complex requests.
	 * **********************************************************************
	 */
	
	
	/**
	 * Sets the required items for a post using curl
	 * @access		public
	 * @version		3.0.19
	 * @param		varies		- $params: the parameters to post
	 * @param		array		- $options: the options to use for curl handler
	 * 
	 * @since		3.0.0
	 */
	public function post($params = array(), $options = array())
	{
		$this->debugpost	= $params;
		
		// If its an array (instead of a query string) then format it correctly
		if (is_array($params)) {
			$params = http_build_query($params, NULL, '&');
		}
		
		// Add in the specific options provided
		$this->options($options);
		$this->http_method('post');
		
		$this->option(CURLOPT_POST, TRUE);
		$this->option(CURLOPT_POSTFIELDS, $params);
	}
	

	/**
	 * Sets the required items for a put using curl
	 * @access		public
	 * @version		3.0.19
	 * @param		varies		- $params: the parameters to put
	 * @param		array		- $options: the options to use for curl handler
	 * 
	 * @since		3.0.0
	 */
	public function put($params = array(), $options = array())
	{
		// If its an array (instead of a query string) then format it correctly
		if (is_array($params)) {
			$params = http_build_query($params, NULL, '&');
		}
		
		// Add in the specific options provided
		$this->options($options);
		
		$this->http_method('put');
		$this->option(CURLOPT_POSTFIELDS, $params);
		
		$this->option(CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override: PUT'));
	}
	
	
	/**
	 * Performs a delete through the curl handler
	 * @access		public
	 * @version		3.0.19
	 * @param		varies		- $params: the parameters to use for execution
	 * @param		array		- $options: the options for the curl handler
	 * 
	 * @since		3.0.0
	 */
	public function delete($params, $options = array())
	{
		// If its an array (instead of a query string) then format it correctly
		if (is_array($params)) {
			$params = http_build_query($params, NULL, '&');
		}
		
		// Add in the specific options provided
		$this->options($options);
		$this->http_method('delete');
		$this->option(CURLOPT_POSTFIELDS, $params);
	}
	
	
	/**
	 * Sets the cookeis for the curl handler
	 * @access		public
	 * @version		3.0.19
	 * @param		varies		- $params: the cookie parameters to use
	 * 
	 * @return		instance of this object
	 * @since		3.0.0
	 */
	public function set_cookies($params = array())
	{
		if (is_array($params)) {
			$params = http_build_query($params, NULL, '&');
		}
		
		$this->option(CURLOPT_COOKIE, $params);
		return $this;
	}
	
	
	/**
	 * Tests to see if there are errors
	 * @access		public
	 * @version		3.0.19
	 * 
	 * @return		string of error message or false on no error
	 * @since		3.0.0
	 */
	public function has_errors()
	{
		return ( $this->error_code != NULL ? $this->error_string : false );
	}
	
	
	/**
	 * Sets an http header
	 * @access		public
	 * @version		3.0.19
	 * @param		string		- $header: the header type to use
	 * @param		string		- $content: the content to set the header to
	 * 
	 * @since		3.0.0
	 */
	public function http_header($header, $content = NULL)
	{
		$this->headers[] = $content ? $header . ': ' . $content : $header;
	}
	
	
	/**
	 * Sets the http method
	 * @access		public
	 * @version		3.0.19
	 * @param		string		- $method: the setting to use
	 * 
	 * @return		instance of this object
	 * @since		3.0.0
	 */
	public function http_method($method)
	{
		$this->options[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
		return $this;
	}
	
	
	/**
	 * Create http login option fields
	 * @access		public
	 * @version		3.0.19
	 * @param		string		- $username: the username to use
	 * @param		string		- $password: the password to use
	 * @param		string		- $type: the authentication type to set
	 * 
	 * @return		instance of this object
	 * @since		3.0.0
	 */
	public function http_login($username = '', $password = '', $type = 'any')
	{
		$this->option(CURLOPT_HTTPAUTH, constant('CURLAUTH_' . strtoupper($type)));
		$this->option(CURLOPT_USERPWD, $username . ':' . $password);
		return $this;
	}
	
	
	/**
	 * Sets proxy options for the curl handler
	 * @access		public
	 * @version		3.0.19
	 * @param		string		- $url: the url to use as the proxy server
	 * @param		integer		- $port: the port on the proxy server to connect through
	 * 
	 * @return		instance of this object
	 * @since		3.0.0
	 */
	public function proxy($url = '', $port = 80)
	{
		$this->option(CURLOPT_HTTPPROXYTUNNEL, TRUE);
		$this->option(CURLOPT_PROXY, $url . ':' . $port);
		return $this;
	}
	
	
	/**
	 * Sets the login for a proxy server for the curl handler
	 * @access		public
	 * @version		3.0.19
	 * @param		string		- $username: the username to use
	 * @param		string		- $password: the password to use
	 * 
	 * @return		instance of this object
	 * @since		3.0.0
	 */
	public function proxy_login($username = '', $password = '')
	{
		$this->option(CURLOPT_PROXYUSERPWD, $username . ':' . $password);
		return $this;
	}
	
	
	/**
	 * Sets the required items for an ssl connection
	 * @access		public
	 * @version		3.0.19
	 * @param		boolean		- $verify_peer: true to verify peer
	 * @param		integer		- $verify_host: the verify host integer setting for the curl handler
	 * @param		string		- $path_to_cert: the path to the certificate if set
	 * 
	 * @return		instance of this object
	 * @since		3.0.0
	 */
	public function ssl($verify_peer = TRUE, $verify_host = 2, $path_to_cert = NULL) {
		if ($verify_peer)
		{
			$this->option(CURLOPT_SSL_VERIFYPEER, TRUE);
			$this->option(CURLOPT_SSL_VERIFYHOST, $verify_host);
			$this->option(CURLOPT_CAINFO, $path_to_cert);
		}
		else {
			$this->option(CURLOPT_SSL_VERIFYPEER, FALSE);
		}
		
		return $this;
	}
	
	
	/**
	 * Sets an array of options to the curl handler options
	 * @access		public
	 * @version		3.0.19
	 * @param		array		- $options: array to set
	 * 
	 * @return		instance of this object
	 * @since		3.0.0
	 */
	public function options($options = array())
	{
		// Merge options in with the rest - done as array_merge() does not overwrite numeric keys
		foreach ($options as $option_code => $option_value) {
			$this->option($option_code, $option_value);
		}
		
		// Catch null / invalid sessions
		if ( $this->session == null ) return $this;
		
		// Set all options provided
		curl_setopt_array($this->session, $this->options);
		
		return $this;
	}
	
	
	/**
	 * Sets an individual option code and value
	 * @access		public
	 * @version		3.0.19
	 * @param		string		- $code: the curl option to set
	 * @param		varies		- $value: the value to set the code to
	 * 
	 * @return		instance of this object
	 * @since		3.0.0
	 */
	public function option($code, $value)
	{
		if (is_string($code) && !is_numeric($code)) {
			$code = constant('CURLOPT_' . strtoupper($code));
		}
		
		$this->options[$code] = $value;
		return $this;
	}
	
	
	/**
	 * Creates a new curl handler session and resets common items
	 * @access		public
	 * @version		3.0.19
	 * @param		string		- $url: the url to use for the curl handler
	 * 
	 * @return		instance of this object
	 * @since		3.0.0
	 */
	public function create($url)
	{
		// If no a protocol in URL, assume its a CI link
		if ( ! preg_match('!^\w+://! i', $url)) {
			return false;
		}
		
		$this->debugresponse	= null;
		$this->debugmethod		= null;
		$this->debugpost		= null;
		$this->error_code		= NULL;
		$this->error_string		= '';
		$this->url				= $url;
		$this->session			= curl_init($this->url);
		
		// If we are using an SSL URL - set the appropriate options
		if ( preg_match( '!^https://! i', $url ) ) {
			$this->ssl();
		}
		
		return $this;
	}
	
	
	/**
	 * Executes the actual curl request
	 * @access		public
	 * @version		3.0.19
	 * 
	 * @return		response or false on error
	 * @since		3.0.0
	 */
	public function execute()
	{
		// Set two default options, and merge any extra ones in
		if ( ! isset($this->options[CURLOPT_TIMEOUT])) {
			$this->options[CURLOPT_TIMEOUT] = 30;
		}
		
		if ( ! isset($this->options[CURLOPT_RETURNTRANSFER])) {
			$this->options[CURLOPT_RETURNTRANSFER] = TRUE;
		}
		if ( ! isset($this->options[CURLOPT_FAILONERROR])) {
			$this->options[CURLOPT_FAILONERROR] = TRUE;
		}
		
		if ( ! ini_get('safe_mode') && !ini_get('open_basedir')) {
			if ( ! isset($this->options[CURLOPT_FOLLOWLOCATION])) {
				$this->options[CURLOPT_FOLLOWLOCATION] = TRUE;
			}
		}
		
		if ( ! empty($this->headers)) {
			$this->options(CURLOPT_HTTPHEADER, $this->headers);
		}
		
		// Null session - can't execute these
		if ( $this->session == null ) {
			$this->error_code = "0";
			$this->error_string = "The curl handler was invalid and could not be used to execute a request.";
			$this->set_defaults();
			return false;
		}
		
		$this->options();
		
		// Added output buffering
		ob_start();
			// Execute the request & and hide all output
			$this->response = curl_exec($this->session);
			$this->info = curl_getinfo($this->session);
		ob_end_clean();
		
		// Request failed
		if ($this->response === FALSE) {
			$this->error_code = curl_errno($this->session);
			$this->error_string = curl_error($this->session);
			
			curl_close($this->session);
			$this->set_defaults();
			
			return FALSE;
		}
		
		// Request successful
		else {
			curl_close($this->session);
			$this->debugresponse	= $this->response;
			$this->debugmethod		= $this->options[CURLOPT_CUSTOMREQUEST];
			$this->debugpost		= ( $this->debugmethod == "POST" ? $this->debugpost : array() );
			$response = $this->response;
			$this->set_defaults();
			return $response;
		}
	}
	
	
	/**
	 * Checks to see if curl is actually enabled
	 * @access		public
	 * @version		3.0.19
	 * 
	 * @return		boolean true if curl is enabled
	 * @since		3.0.0
	 */
	public function is_enabled()
	{
		return function_exists('curl_init');
	}
	
	
	/**
	 * Outputs the debugging screen
	 * @access		public
	 * @version		3.0.19
	 * 
	 * @return		string containing debug information
	 * @since		3.0.0
	 */
	public function debug()
	{
		echo "=============================================<br/>\n";
		echo "<h2>CURL Test</h2>\n";
		echo "=============================================<br/>\n";
		echo "<h3>Response</h3>\n";
		echo "<code>" . nl2br(htmlentities($this->debugresponse)) . "</code><br/>\n\n";

		if ($this->error_string)
		{
			echo "=============================================<br/>\n";
			echo "<h3>Errors</h3>";
			echo "<strong>Code:</strong> " . $this->error_code . "<br/>\n";
			echo "<strong>Message:</strong> " . $this->error_string . "<br/>\n";
		}

		echo "=============================================<br/>\n";
		echo "<h3>Info</h3>";
		echo "<pre>";
		print_r($this->info);
		echo "</pre>";
		
		if ($this->debugmethod == "POST" )
		{
			echo "=============================================<br/>\n";
			echo "<h3>Posted Variables</h3>";
			echo "<pre>";
			print_r($this->debugpost);
			echo "</pre>";
		}
	}
	
	
	/**
	 * Assembles an array of request options for debugging
	 * @access		public
	 * @version		3.0.19
	 * 
	 * @return		array
	 * @since		3.0.0
	 */
	public function debug_request()
	{
		return array( 'url' => $this->url );
	}
	
	
	/**
	 * Sets the default values when new sessions are created
	 * @access		private
	 * @version		3.0.19
	 * 
	 * @since		3.0.0
	 */
	private function set_defaults()
	{
		$this->response = '';
		$this->headers = array();
		$this->options = array();
		$this->session = NULL;
	}

}