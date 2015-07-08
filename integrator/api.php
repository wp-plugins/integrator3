<?php
/**
 * Integrator 3
 * 
 * @package    Integrator 3
 * @copyright  2009-2013 Go Higher Information Services, LLC.  All rights reserved.
 * @license    GNU General Public License version 2, or later
 * @version    3.1.03 ( $Id: api.php 142 2012-11-28 02:28:39Z steven_gohigher $ )
 * @author     Go Higher Information Services, LLC
 * @since      3.0.0
 * 
 * @desc       This file is a wrapper for the API to handle calls to the Integrator
 *  
 */

/*-- Security Protocols --*/
//defined('_JEXEC') or exit('No direct script access allowed');
/*-- Security Protocols --*/

/*-- File Inclusions --*/
//jimport( 'joomla.application.component.helper' );
//require_once( "class.curl.php" );
/*-- File Inclusions --*/

/**
 * IntApi class object
 * @version		3.1.03
 * 
 * @since		3.0.0
 * @author		Steven
 */
class IntApi
{
	/**
	 * The default Integrator option variables
	 * @access		public
	 * @var			array
	 * @since		3.0.0
	 */
	public $apioptions	= array();
	
	/**
	 * The default Integrator Post variables
	 * @access		public
	 * @var			array
	 * @since		3.0.0
	 */
	public $apipost	= array();
	
	/**
	 * The Integrator URL for the API interface
	 * @access		public
	 * @var			string
	 * @since		3.0.0
	 */
	public $apiurl	= null;
	
	/**
	 * This connections identifier in the Integrator (if known)
	 * @access		public
	 * @var			string
	 * @since		3.0.0
	 */
	public $cnxnid	= null;
	
	/**
	 * Integrator Curl Object
	 * @access		public
	 * @var			object
	 * @since		3.0.0
	 */
	public $curl	= null;
	
	/**
	 * Errors stored here
	 * @access		public
	 * @since		3.0.0
	 * @var			array
	 */
	public $debug	= array();
	
	/**
	 * Integrator JParameters Object
	 * @access		public
	 * @var			object
	 * @since		3.0.0
	 */
	//public $params	= array();
	
	/**
	 * Catchall for undeclared data
	 * @access		public
	 * @var 		array
	 * @since		3.0.0
	 */
	private $data	= array();
	
	/**
	 * Constructor method
	 * @access		public
	 * @version		3.1.03
	 * 
	 * @since		3.0.0
	 */
	public function __construct()
	{
		global $int;
		$this->curl 	= & $int->curl;
		$this->apiurl	=   $this->_set_apiurl( trim( get_option( 'integrator_url' ) ) );
		
		// Build secret code
		$salt		= mt_rand();
		$secret		= get_option( 'integrator_apisecret' );
		$signature	= base64_encode( hash_hmac( 'sha256', $salt, $secret, true ) );
		
		// Default POST VARIABLES
		$post		= array	(	"apiusername"	=> get_option( 'integrator_apiusername' ),
								"apipassword"	=> get_option( 'integrator_apipassword' ),
								'apisignature'	=> $signature,
								'apisalt'		=> $salt
		);
		$this->apipost	= $post;
		
		// Default CURL OPTIONS
		$options	= array(	'POST'				=> true,
								'TIMEOUT'			=> 30,
								'RETURNTRANSFER'	=> true,
								'POSTFIELDS'		=> array(),
								'FOLLOWLOCATION'	=> false,
								'HEADER'			=> true,
								'HTTPHEADER'		=> array( 'Expect:' ),
								'MAXREDIRS'			=> 5,
								'SSL_VERIFYHOST'	=> false,
								'SSL_VERIFYPEER'	=> false
		);
		$this->apioptions = $options;
		
		$this->cnxnid	= get_option( 'integrator_cnxnid' );
	}
	
	
	/**
	 * Getter method
	 * @access		public
	 * @version		3.1.03
	 * @param		string		- $name: the name of the property trying to be gotten
	 * 
	 * @return		mixed value of property or null if not set
	 * @since		3.0.0 
	 */
	public function __get( $name )
	{
		return ( isset( $this->data[$name] ) ? $this->data[$name] : null );
	}
	
	
	/**
	 * Setter method
	 * @access		public
	 * @version		3.1.03
	 * @param		string		- $name: the name of the property to set
	 * @param		mixed		- $value: the value to set the property to
	 * 
	 * @since		3.0.0
	 */
	public function __set( $name, $value )
	{
		$this->data[$name] = $value;
	}
	
	
	/**
	 * Retrieves all the pages for all the connections
	 * @access		public
	 * @version		3.1.03
	 * 
	 * @return		result of api call
	 * @since		3.0.0
	 */
	public function get_allcnxn_pages()
	{
		$post['_c']	= $this->cnxnid;
		
		$url		= $this->apiurl . "get_allcnxn_pages/";
		$response	= $this->_call_api( $url, $post );
		
		return $response['data'];
	}
	
	
	/**
	 * Retrieves the correct route for a selected connection / page combination
	 * @access		public
	 * @version		3.1.03
	 * @param		array		- $post: contains the cnxn_id and page being requested
	 * 
	 * @return		string containing URL
	 * @since		3.0.0
	 */
	public function get_route( $post = array() )
	{
		$post['_c']	= $this->cnxnid;
		
		$url		= $this->apiurl . "get_route/";
		$response	= $this->_call_api( $url, $post );
		
		if ( $response['result'] == 'success' ) {
			return array( 'result' => true, 'route' => $response['data']['route'] );
		}
		
		return array( 'result' => false, 'message' => $response['data'] );
	}
	
	
	/**
	 * Retrieves connections requiring wrapping
	 * @access		public
	 * @version		3.1.03
	 * 
	 * @return		result of api call
	 * @since		3.0.0
	 */
	public function get_wrapped_cnxns()
	{
		$post['_c']	= $this->cnxnid;
		
		$url		= $this->apiurl . "get_wrapped_cnxns/";
		$response	= $this->_call_api( $url, $post );
		
		return $response['data'];
	}
	
	
	/**
	 * Pings the Integrator to ensure connection
	 * @access		public
	 * @version		3.1.03
	 * @param		boolean		- $updated: if true we should pull values from $_POST
	 * 
	 * @return		boolean true if connection successful, string returned error message otherwise
	 * @since		3.0.0
	 */
	public function ping( $updated = false )
	{
		// Ensure we have a URL to set
		if ( empty( $this->apiurl ) ) {
			return __( 'INT_ERROR01' );
		}
		
		$url	= $this->apiurl;
		$post	= array();
		
		if ( $updated && isset( $_POST['integrator_apiusername'] ) && isset( $_POST['integrator_apipassword'] ) && isset( $_POST['integrator_url'] ) && isset( $_POST['integrator_apisecret'] ) ) {
			$url	= $this->_set_apiurl( $_POST['integrator_url'] );
			
			$salt		= mt_rand();
			$secret		= $_POST['integrator_apisecret'];
			$signature	= base64_encode( hash_hmac( 'sha256', $salt, $secret, true ) );
			
			$post	= array (	'apiusername' 	=> $_POST['integrator_apiusername'],
								'apipassword' 	=> $_POST['integrator_apipassword'],
								'apisignature'	=> $signature,
								'apisalt'		=> $salt
			);
		}
		$url		= $url . "ping/";
		$response	= $this->_call_api( $url, $post );
		
		return ( $response['result'] == 'success' ? true : $response['data'] );
	}
	
	
	/**
	 * Updates the settings in the Integrator with the settings established in the component
	 * @access		public
	 * @version		3.1.03
	 * @param		boolean		- $updated: if true we should pull values from $_POST
	 * 
	 * @return		result of api call
	 * @since		3.0.0
	 */
	public function update_settings( $updated = false )
	{
		if (! empty( $this->cnxnid ) ) {
			$post['cnxnid']	= $this->cnxnid;
		}
		
		$uri	= new IntUri( get_option( 'siteurl' ) );
		$path	= array_reverse( explode( "/", $uri->getPath() ) );
		
		foreach( $path as $k => $p ) {
			if ( in_array( $p, array( "index.php", "administrator" ) ) ) unset( $path[$k] );
		}
		
		$path	= array_reverse( $path );
		
		$uri->setPath( "/" . implode( "/", $path ) );
		
		$post['cnxnurl']	= $uri->toString( array( "scheme", "host", "path" ) );
		$url				= $this->apiurl;
		
		if ( $updated && isset( $_POST['integrator_apiusername'] ) && isset( $_POST['integrator_apipassword'] ) && isset( $_POST['integrator_url'] ) ) {
			$url	= $this->_set_apiurl( $_POST['integrator_url'] );
			$post['apiusername'] = $_POST['integrator_apiusername'];
			$post['apipassword'] = $_POST['integrator_apipassword'];
		}
		$url		= $url . "update_settings/";
		
		$response	= $this->_call_api( $url, $post );
		
		return $response;
	}
	
	
	/**
	 * Create a new user throughout the Integrator
	 * @access		public
	 * @version		3.1.03
	 * @param		array		- $post: the user array to send
	 * 
	 * @return		string containing true or error message
	 * @since		3.0.0
	 */
	public function user_create( $post = array() )
	{
		if ( empty( $this->cnxnid ) ) die( 'NO' );
		
		$post		= (array) $post;
		$post['_c']	= $this->cnxnid;
		
		$url		= $this->apiurl . "user_create/";
		$response	= $this->_call_api( $url, $post );
		
		return $response['data'];
	}
	
	
	/**
	 * Calls the Integrator to remove a user
	 * @access		public
	 * @version		3.1.03
	 * @param		string		- $email: the email address of the user being deleted
	 * 
	 * @return		string containing true or error message
	 * @since		3.0.0
	 */
	public function user_remove( $user )
	{
		if ( empty( $this->cnxnid ) ) die( 'NO' );
		
		$post				= array();
		$post['user_email']	= $user['user_email'];
		$post['_c']			= $this->cnxnid;
		
		$url		= $this->apiurl . "user_remove/";
		$response	= $this->_call_api( $url, $post );
		
		return $response['data'];
	}
	
	
	/**
	 * Updates user information across the various Integrator connections
	 * @access		public
	 * @version		3.1.03
	 * @param		array		- $post: the user array to send
	 * 
	 * @return		string containing true or error message
	 * @since		3.0.0
	 */
	public function user_update( $post = array() )
	{
		if ( empty( $this->cnxnid ) ) die( 'NO' );
		
		$post		= (array) $post;
		$post['_c']	= $this->cnxnid;
		
		$url		= $this->apiurl . "user_update/";
		$response	= $this->_call_api( $url, $post );
		
		return $response['data'];
	}
	
	
	/**
	 * Validate user information prior to creation
	 * @access		public
	 * @version		3.1.03
	 * @param		array		- $post: the user array to send
	 * 
	 * @return		string containing true or error message
	 * @since		3.0.0
	 */
	public function user_validation_on_create( $post = array() )
	{
		if ( empty( $this->cnxnid ) ) die( 'NO' );
		
		$post		= (array) $post;
		$post['_c']	= $this->cnxnid;
		
		$url		= $this->apiurl . "user_validation_on_create/";
		
		if(! ($response	= $this->_call_api( $url, $post ) ) ) {
			return false;
		}
		
		return $response['data'];
	}
	
	
	/**
	 * Validates user information across the various Integrator connections
	 * @access		public
	 * @version		3.1.03
	 * @param		array		- $post: the user array to send
	 * 
	 * @return		string containing true or error message
	 * @since		3.0.0
	 */
	public function user_validation_on_update( $post = array() )
	{
		if ( empty( $this->cnxnid ) ) die( 'NO' );
		
		$post		= (array) $post;
		$post['_c']	= $this->cnxnid;
		
		$url		= $this->apiurl . "user_validation_on_update/";
		
		if(! ($response	= $this->_call_api( $url, $post ) ) ) {
			return false;
		}
		
		return $response['data'];
	}
	
	
	/**
	 * Wrapper for calling up the API interface
	 * @access		private
	 * @version		3.1.03
	 * @param		string		- $url: the url to connect to
	 * @param		array		- $post: any additional post variables to send
	 * @param 		array		- $options: any options to set
	 * 
	 * @return		json_decoded response
	 * @since		3.0.0
	 */
	private function _call_api( $url, $post = array(), $options = array() )
	{
		global $int;
		
		$post		= array_merge( $this->apipost, $post );
		$options	= array_merge( $this->apioptions, $options );
		
		$result		= $int->curl->simple_post( $url, $post, $options );
		//echo $url . '<pre>'.print_r($post, 1).print_r($result,1).'</pre><hr/>';
		if ( $result === false ) {
			// error trapping for debug purposes
			return array( 'result' => 'error', 'data' => $int->curl->has_errors() );
		}
		
		if ( $options['HEADER'] == TRUE ) {
			list( $header, $response ) = explode( "\r\n\r\n", $result, 2 );
		}
		else {
			$response = $result;
		}
		
		$return	= json_decode( $response, true );
		
		if (! isset( $return['result'] ) ) {
			$this->debug[] = $response;
			return false;
		}
		else {
			return $return;
		}
	}
	
	
	/**
	 * Uniform method to set the api url
	 * @access		private
	 * @version		3.1.03
	 * @param		string		- $url: the setting for the api url to use
	 * 
	 * @return		string containing corrected url
	 * @since		3.0.0
	 */
	private function _set_apiurl( $url )
	{
		if (! empty( $url ) ) {
			$uri = new IntUri( $url );
			$uri->setPath( rtrim( $uri->getPath(), "/" ) . "/index.php/api/" );
			return $uri->toString();
		}
		else {
			return $url;
		}
	}
	
}