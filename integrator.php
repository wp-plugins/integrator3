<?php
/**
 * Integrator
 * 
 * @package    Integrator 3.0 - Wordpress Package
 * @copyright  2009 - 2012 Go Higher Information Services.  All rights reserved.
 * @license    ${p.PROJECT_LICENSE}
 * @version    3.0.12 ( $Id: integrator.php 164 2012-12-18 14:57:42Z steven_gohigher $ )
 * @author     Go Higher Information Services
 * @since      3.0.0
 * 
 * @desc       This is the primary plugin file for the Wordpress extension of the Integrator
 *  
 */
/*
Plugin Name: Integrator
Plugin URI: https://www.gohigheris.com/
Description: The Wordpress extension of the Integrator application
Author: Go Higher Information Services
Author URI: https://www.gohigheris.com/
Version: 3.0.12
*/

define( 'INTEGRATOR_VERSION', "3.0.12" );

/**
 * Integrator Class
 * @version		3.0.12
 *  
 * @since		3.0.0
 * @author		Steven
 */
class Integrator
{
	private $cookiename			= null;
	private $cookievalue		= null;
	
	public	$sendback			= false;
	
	/**
	 * Constructor method
	 * @access		public
	 * @version		3.0.12
	 * 
	 * @since		3.0.0
	 */
	public function Integrator()
	{
		// Grab include files
		$this->_includes();
		
		// Register a new post type for the Wordpress instance
		register_post_type( 'intlink', array(
			'labels' => 
				array(
						'name'				=> __( 'Integrator' ),
						'singular_name'		=> __( 'Integrator Link' ),
						'add_new'			=> __( 'Add New' ),
						'add_new_item'		=> __( 'Add New Link' ),
						'edit_item'			=> __( 'Edit Link' ),
						'new_item'			=> __( 'New Link' ),
						'view_item'			=> __( 'View Link' ),
						'search_items'		=> __( 'Search Links' ),
						'not_found'			=> __( 'No links found' ),
						'all_items'			=> __( 'Integrator Links' ),
						'name_admin_bar'	=> __( 'Integrator' )
				),
			'public' => true,
			'show_ui' => true,
			'_builtin' => false,
			'capability_type' => 'post',
			'hierarchical' => false,
			'rewrite' => false,
			'query_var' => false,
			'supports' => array('title', 'intlink' )
			)
		);
		
		// Add hooks
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'admin_jscript' ) );
		
		// If we aren't active then don't try doing redirect / user functions
		if (! $this->_is_active() ) return;
		
		add_action( 'template_redirect', array( &$this, 'site_intlink_redirect' ), 0, 0 );
		add_action( 'set_logged_in_cookie', array( &$this, 'site_logged_in_cookie' ), 10, 1 );
		add_action( 'login_form_logout', array( &$this, 'site_logout' ), 10, 0 );
		add_filter( 'login_redirect', array( &$this, 'site_login' ), 10, 3 );
		
		// User Validation Hooks
		add_action( 'user_profile_update_errors', array( &$this, 'site_user_validation' ), 0, 3 );
		add_action( 'wpmu_validate_user_signup', array( &$this, 'site_multi_user_validation' ), 0, 1 );
		
		// User Create / Edit / Delete Hooks
		add_action( 'user_register', array( &$this, 'site_user_create' ), 0, 1 );
		add_action( 'profile_update', array( &$this, 'site_user_update' ), 0, 2 );
		add_action( 'delete_user', array( &$this, 'site_user_predelete' ), 0, 1 );
		add_action( 'deleted_user', array( &$this, 'site_user_delete' ), 0, 1 );
		
		//load_plugin_textdomain( 'integrator', false, basename( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'integrator' . DIRECTORY_SEPARATOR . 'languages' );
	}
	
	
	/**
	 * Initializes in admin
	 * @access		public
	 * @version		3.0.12
	 * 
	 * @since		3.0.0
	 */
	public function admin_init()
	{
		add_action( 'wp_insert_post', array( &$this, 'admin_intlink_insert' ), 10, 2 );
		add_meta_box( 'intlink-meta', 'Integrated URL', array( &$this, 'admin_init_box_intlink' ), 'intlink', 'normal', 'low' );
	}
	
	
	/**
	 * Create the meta box for the Integrated Link admin area
	 * @access		public
	 * @version		3.0.12
	 * 
	 * @since		3.0.0
	 */
	public function admin_init_box_intlink()
	{
		global $post;
		
		// Grab relevant data for posting
		$data	= array(
					'_a'	=> get_post_meta( $post->ID, '_a', true ),
					'page'	=> get_post_meta( $post->ID, 'page', true ),
					'vars'	=> get_post_meta( $post->ID, 'vars', true )
		);
		
		// Grab cnxn and page options
		$cnxnoptns		= $this->api->get_wrapped_cnxns();
		$pageoptns		= $this->admin_get_cnxnpages();
		
		include( dirname(__FILE__) . '/integrator3/box.php' );
	}
	
	
	/**
	 * Handles the Integrated Link field in admin area and sets it to meta data
	 * @access		public
	 * @version		3.0.12
	 * @param		integer		- $post_id: the post id passed by admin area
	 * @param		array		- $post: contains description of the post
	 * 
	 * @since		3.0.0
	 */
	public function admin_intlink_insert($post_id, $post = null)
	{
		if ($post->post_type == "intlink") {
			
			// Loop through the POST data
			foreach ( array( '_a', 'page', 'vars' ) as $key ) {
				
				$value = @$_POST[$key];
				
				if ( empty( $value ) && $value != '0' ) {
					delete_post_meta($post_id, $key);
					continue;
				}
				
				// If value is a string it should be unique
				if (! is_array( $value ) ) {
					// Update meta
					if (! update_post_meta( $post_id, $key, $value ) ) {
						// Or add the meta data
						add_post_meta( $post_id, $key, $value );
					}
				}
				else {
					// If passed along is an array, we should remove all previous data
					delete_post_meta( $post_id, $key );
					
					// Loop through the array adding new values to the post meta as different entries with the same name
					foreach ( $value as $entry )
						add_post_meta( $post_id, $key, $entry );
				}
			}
		}
	}
	
	
	/**
	 * Place holder for adding javascript to the admin header
	 * @access		public
	 * @version		3.0.12
	 * 
	 * @since		3.0.0
	 */
	public function admin_jscript()
	{
		//wp_enqueue_script( 'box_onload', plugins_url('/integrator/js/inclusion.js', __FILE__) );
	}
	
	
	/**
	 * Generates the menu item
	 * @access		public
	 * @version		3.0.12
	 * 
	 * @since		3.0.0
	 */
	public function admin_menu()
	{
		add_options_page('Integrator Settings', 'Integrator', 'manage_options', 'int_menu', array( &$this, 'admin_options' ) );
	}
	
	
	/**
	 * Renders and handles the options page for the Integrator
	 * @access		public
	 * @version		3.0.12
	 * 
	 * @since		3.0.0
	 */
	public function admin_options()
	{
		if (! current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		
		$integrator_url			= get_option( 'integrator_url' );
		$integrator_apiusername	= get_option( 'integrator_apiusername' );
		$integrator_apipassword	= get_option( 'integrator_apipassword' );
		$integrator_apisecret	= get_option( 'integrator_apisecret' );
		$integrator_cnxnid		= get_option( 'integrator_cnxnid' );
		
		if ( isset( $_POST['integratorUpdate'] ) ) {
			$integrator_url			= $_POST['integrator_url'];
			$integrator_apiusername	= $_POST['integrator_apiusername'];
			$integrator_apipassword	= $_POST['integrator_apipassword'];
			$integrator_apisecret	= $_POST['integrator_apisecret'];
			$integrator_cnxnid		= $_POST['integrator_cnxnid'];
			
			update_option( 'integrator_url', $integrator_url );
			update_option( 'integrator_apiusername', $integrator_apiusername );
			update_option( 'integrator_apipassword', $integrator_apipassword );
			update_option( 'integrator_apisecret', $integrator_apisecret );
			update_option( 'integrator_cnxnid', $integrator_cnxnid );
		}
		
		$status = $this->_get_status( true );
		
		if ( $status === true ) {
			$integrator_cnxnid = $this->_update_settings( true );
		}
		
		include( dirname(__FILE__) . '/integrator3/options.php' );
		
		//$item = false;
		//echo "<pre>" . print_r(IntXMLRPC::get_menutree( $item ),1)."</pre>";
		
	}
	
	
	/**
	 * Method to grab the cnxn pages from the Integrator
	 * @access		public
	 * @version		3.0.12
	 * 
	 * @return		array
	 * @since		3.0.0
	 */
	public function admin_get_cnxnpages()
	{
		static $pages	= null;
		
		if ( $pages == null ) {
			$items	=   $this->api->get_allcnxn_pages();
			foreach ( $items as $item ) {
				$txt	= array();
				asort( $item['pages'] );
				foreach( $item['pages'] as $val => $page ) {
					$txt[]	= $page . '|' . $val;
				}
				$pages .= 'pages[' . $item['cnxn_id'] . '] = ["' . implode( '", "', $txt ) . '"]
';
			}
		}
		return $pages;
	}
	
	
	/**
	 * Method to get a route from the Integrator
	 * @access		public
	 * @version		3.0.12
	 * 
	 * @return		result of API call
	 * @since		3.0.0
	 */
	public function site_get_route()
	{
		global $post;
		
		$data	= array(	'cnxn_id'	=> get_post_meta( $post->ID, '_a', true ),
							'page'		=> get_post_meta( $post->ID, 'page', true ),
							'vars'		=> get_post_meta( $post->ID, 'vars', true )
		);
		
		return $this->api->get_route( $data );
	}
	
	
	/**
	 * Intercepts calls for the intlink post type and redirects
	 * @access		public
	 * @version		3.0.12
	 * 
	 * @since		3.0.0
	 */
	public function site_intlink_redirect()
	{
		global $wp, $wp_query;
		
		$found = false;
		if ( isset( $_GET['intlink'] ) )
			$found = true;
		
		if ( isset( $wp->query_vars['post_type'] ) )
			if ( $wp->query_vars['post_type'] == 'intlink' )
				$found = true;
		
		// The intlink post_type is not found so return
		if (! $found ) return;
		
		$wp_query->is_404 = false;
		
		// If we have posts (links)
		if ( have_posts() ) {
			
			// Check and see if we are being called by the Integrator for wrapping
			if ( isset( $_GET['override'] ) )  {
				
				// Try to find the appropriate template file
				if ( file_exists( TEMPLATEPATH . '/intlink.php' ) ) {
					include(TEMPLATEPATH . '/intlink.php');
					die();
				}
				// Default to the plugin file
				else if ( file_exists( dirname( __FILE__ ) . '/integrator3/intlink.php' ) ) {
					include( dirname( __FILE__ ) . '/integrator3/intlink.php');
					die();
				}
				// Can't find it?  404 then
				else {
					$wp_query->is_404 = true;
				}
			}
			else {
				// We must be wanting to link to the appropriate place
				$data = $this->site_get_route();
				
				if (! $data['result'] ) {
					include( dirname( __FILE__ ) . '/integrator3/intlinkerror.php');
					die();
				}
				
				wp_redirect( $data['route'], 302 );
				exit;
			}
			
		}
		// No links, so we must 404
		else {
			
			if ( isset( $_GET['override'] ) ) {
				// Try to find the appropriate template file
				if ( file_exists( TEMPLATEPATH . '/intlink.php' ) ) {
					include(TEMPLATEPATH . '/intlink.php');
					die();
				}
				// Default to the plugin file
				else if ( file_exists( dirname( __FILE__ ) . '/integrator3/intlink.php' ) ) {
					include( dirname( __FILE__ ) . '/integrator3/intlink.php');
					die();
				}
				// Can't find it?  404 then
				else {
					$wp_query->is_404 = true;
				}
			}
			var_dump( $wp ); var_dump( $wp_query ) ; die();
			$wp_query->is_404 = true;
		}
		
	}
	
	
	/**
	 * Grabs the logged in cookies when passed through the set_logged_in_cookie action in WP
	 * @access		public
	 * @version		3.0.12
	 * @param		string		- $cookie: the value of the set cookie
	 * 
	 * @since		3.0.0
	 */
	public function site_logged_in_cookie( $cookie )
	{
		$this->cookiename	= base64_encode( LOGGED_IN_COOKIE );
		$this->cookievalue	= base64_encode( $cookie );
	}
	
	
	/**
	 * Action handler for logging into the site
	 * @access		public
	 * @version		3.0.12
	 * @param		string		- $redirect_to: the redirect url
	 * @param		bool		- $isset: if the $redirect has been sent back (login started)
	 * @param 		object		- $user: user object or error
	 * 
	 * @return		string
	 * @since		3.0.0
	 */
	public function site_login( $redirect_to, $isset, $user )
	{
		if ( defined( 'INTEGRATOR_API' ) ) return;
		
		$url = get_option( 'integrator_url' );
		$uri = new IntUri( $url );
		
		if ( empty( $isset ) ) {
			$uri->setPath( rtrim( $uri->getPath(), '/' ) . '/index.php/login/index/' . get_option( 'integrator_cnxnid' ) );
			$uri->setVar( 'wp', true );
			return $uri->toString();
		}
		
		// See if we successfully logged in
		$success = is_wp_error( $user ) ? false : true;
		
		// If we are redirecting to the admin and there is an error, send back to wp login
		if ( ( $redirect_to == admin_url() ) && (! $success ) ) return $redirect_to;
		
		// If we are originating from WP and we fail, send back to WP login
		$ruri = new IntUri( $isset );
		if ( ( $ruri->getVar( 'wp', false ) ) && (! $success ) ) return $redirect_to;
		
		// Intercept super admins and let them off here
		if ( is_super_admin( $user->ID ) ) return admin_url();
		
		$uri->setScheme( 'http' . ( is_ssl() ? 's' : '' ) );
		$uri->setPath( rtrim( $uri->getPath(), '/' ) . '/index.php/login/index/' . get_option( 'integrator_cnxnid' ) . ( $success ? "/{$this->cookievalue}/{$this->cookiename}" : '' ) );
		
		$fields	= array(	'log'	=> sanitize_user( $_POST['log'] ),
							'pwd'	=> $_POST['pwd'],
							'remember'	=> (! empty( $_POST['rememberme'] ) ? true : false )
		);
		
		IntHelper::form_redirect( $uri->toString(), $fields );
	}
	
	
	/**
	 * Action handler for logging out and redirecting to Integrator
	 * @access		public
	 * @version		3.0.12
	 * 
	 * @since		3.0.0s
	 */
	public function site_logout()
	{
		if ( defined( 'INTEGRATOR_API' ) ) return;
		
		// No need to send super admins everywhere
		if ( is_super_admin( get_current_user_id() ) ) return;
		
		// Perform logout
		wp_logout();
		
		// Create redirect URL
		$url = get_option( 'integrator_url' );
		$uri = new IntUri( $url );
		$uri->setScheme( 'http' . ( is_ssl() ? 's' : '' ) );
		$uri->setPath( rtrim( $uri->getPath(), '/' ) . '/logout/index/' . get_option( 'integrator_cnxnid' ) );
		$redirect_to	= $uri->toString();
		
		wp_safe_redirect( $redirect_to );
		exit;
	}
	
	
	public function site_multi_user_validation( $result )
	{
		/* NOT TESTED!
		 * $result contains the following, and must be returned back:
		 * array('user_name' => $user_name, 'orig_username' => $orig_username, 'user_email' => $user_email, 'errors' => $errors);
		 * 	'user_name' == string
		 *  'orig_username' == user_name before manipulation
		 *  'user_email' == string
		 *  'errors' == WP_Error object ( $errors->add( error, msg ) )
		 *
		 *  Called in wp-includes/ms-functions.php line 605
		 */
		
		if ( defined( 'INTEGRATOR_API' ) ) return;
		return $result;
	}
	
	
	public function site_user_create( $user_id )
	{
		if ( defined( 'INTEGRATOR_API' ) ) return;
		
		$post	= IntHelper::find_user( $user_id );
		
		$result	= $this->api->user_create( $post );
		
		if ( $result == 'true' ) {
			return;
		}
		
		// can't return error
		return; // ??
	}
	
	
	/**
	 * Perform actual deletion
	 * @access		public
	 * @version		3.0.12
	 * @param		integer		- $user_id: the user id of the user we just deleted in WP
	 * 
	 * @since		3.0.0
	 */
	public function site_user_delete( $user_id )
	{
		if ( defined( 'INTEGRATOR_API' ) ) return;
		
		$user	= $this->get( 'user_to_remove', array() );
		
		$result	= $this->api->user_remove( $user );
		
		if ( $result == 'true' ) {
			return;
		}
		
		// No way to do error generation
		return;
	}
	
	
	/**
	 * Grab a copy of the user before we delete them
	 * @access		public
	 * @version		3.0.12
	 * @param		integer		- $user_id: contains the user id of the user to delete
	 * 
	 * @since		3.0.0
	 */
	public function site_user_predelete( $user_id )
	{
		if ( defined( 'INTEGRATOR_API' ) ) return;
		
		$this->set( 'user_to_remove', IntHelper::find_user( $user_id ) );
	}
	
	
	/**
	 * Update user information on Integrator
	 * @access		public
	 * @version		3.0.12
	 * @param 		integer		- $user_id: id of user
	 * @param		array		- $post: the original data of user
	 * 
	 * @since		3.0.0
	 */
	public function site_user_update( $user_id, $post )
	{
		if ( defined( 'INTEGRATOR_API' ) ) return;
		
		$update	= IntHelper::find_user( $user_id );
		$post	= (array) $post;
		$post['update'] = $update;
		
		$result	= $this->api->user_update( $post );
		
		if ( $result == 'true' ) {
			return;
		}
		
		// can't return error?
		return;
	}
	
	
	/**
	 * Validates user information prior to saving
	 * @access		public
	 * @version		3.0.12
	 * @param		object		- $errors: (reference) WP_Error object
	 * @param		boolean		- $update: if this is an update then true
	 * @param		object		- $user: (reference) WP_User object of updated info
	 * 
	 * @since		3.0.0
	 */
	public function site_user_validation( &$errors, $update, &$user )
	{
		if ( defined( 'INTEGRATOR_API' ) ) return;
		
		// Make a copy of the user object
		$newuser = (array) $user;
		
		// If update then build current user with update info
		if ( $update ) {
			$post = IntHelper::find_user( $user->ID );
			
			foreach ( $newuser as $k => $v ) {
				if (! isset( $post[$k] ) ) continue;
				if ( $post[$k] != $v ) continue;
				unset( $newuser[$k] );
			}
			
			$post['update'] = $newuser;
		}
		else {
			$post = (array) $user;
		}
		
		// Determine task
		$task	= 'user_validation_on_' . ( $update ? 'update' : 'create' );
		
		// Call API
		$result = $this->api->$task( $post );
		
		// If we are valid then send back
		if ( $result == 'true' ) {
			return;
		}
		
		// If not, set the error and send back
		$errors->add( 'user_validation', $result );
		
		return;
	}
	
	
	/**
	 * Permits getting of variables
	 * @access		public
	 * @version		3.0.12
	 * @param		string		- $var: the name of the variable to get
	 * @param		mixed		- $default: the default value if not set
	 * 
	 * @return		mixed value of variable or default if not set
	 * @since		3.0.0
	 */
	public function get( $var, $default = null )
	{
		return (! isset( $this->$var ) ? $default : $this->$var );
	}
	
	
	/**
	 * Permits setting of variables
	 * @access		public
	 * @version		3.0.12
	 * @param		string		- $var: the name of the variable to set
	 * @param		mixed		- $value: the value to set
	 * 
	 * @return		mixed previous value or new value if not previously set
	 * @since		3.0.0
	 */
	public function set( $var, $value )
	{
		$prev = $this->get( $var, $value );
		$this->$var = $value;
		return $prev;
	}
	
	
	/**
	 * **********************************************************************
	 * PRIVATE METHODS BELOW
	 * **********************************************************************
	 */
	
	
	/**
	 * Easy wrapper for pinging Integrator
	 * @access		private
	 * @version		3.0.12
	 * @param		boolean		- $updated: if we are updating settings, set to true to grab from POST
	 * 
	 * @return		boolean true or string on error
	 * @since		3.0.0
	 */
	private function _get_status( $updated = false )
	{
		return ( ( $message = $this->api->ping( $updated ) ) === true ? true : $message );
	}
	
	
	/**
	 * Convenient file includer
	 * @access		private
	 * @version		3.0.12
	 * 
	 * @since		3.0.0
	 */
	private function _includes()
	{
		$path	= dirname(__FILE__) . DIRECTORY_SEPARATOR . 'integrator3' . DIRECTORY_SEPARATOR;
		
		// Files must be in global to specific order - uri, curl, api... etc
		$files	= array( 'uri' => false, 'curl' => true, 'api' => true, 'helper' => false );
		$error	= false;
		
		foreach ( $files as $file => $declare ) {
			$filename = $path . $file . '.php';
			if (! file_exists( $filename ) ) {
				$error = $file;
				break;
			}
			else {
				
				include_once( $filename );
				
				if ( $declare ) {
					$classname		= 'Int' . ucfirst( $file );
					$this->$file	= new $classname();
				}
			}
		}
	}
	
	
	/**
	 * Method to check to see if we are active or not
	 * @access		private
	 * @version		3.0.12
	 * 
	 * @return		boolean
	 * @since		3.0.7
	 */
	private function _is_active()
	{
		$check	= array( 'url', 'apiusername', 'apipassword', 'apisecret', 'cnxnid' );
		$active	= true;
		
		foreach ( $check as $item ) {
			$value = get_option( 'integrator_' . $item );
			if ( empty( $value ) ) {
				$active = false;
				break;
			}
		}
		
		return (bool) $active;
	}
	
	
	/**
	 * Easy wrapper for updating settings on the Integrator
	 * @access		private
	 * @version		3.0.12
	 * @param		boolean		- $updated: if we are updating settings, set to true to grab from POST
	 * 
	 * @return		boolean true or string on error
	 * @since		3.0.0
	 */
	private function _update_settings( $updated = false )
	{
		$response = $this->api->update_settings( $updated );
		
		if ( $response['result'] != 'success' ) {
			return $response['data'];
		}
		else {
			$data	= $response['data'];
		}
		
		if ( $data['cnxnid'] ) update_option( 'integrator_cnxnid', $data['cnxnid'] );
		
		return ( $updated ? $data['cnxnid'] : true );
	}
}



/**
 * Integrator XMLRPC Handler
 * @version		3.0.12
 * 
 * @since		3.0.0
 * @author		Steven
 */
class IntXMLRPC
{
	
	/**
	 * Takes a set of credentials and authenticates them against WP
	 * @access		public
	 * @version		3.0.12
	 * @param		array		- $args: the arguments passed via XMLRPC Server
	 * 
	 * @return		array
	 * @since		3.0.0
	 */
	public function authenticate( &$args )
	{
		extract( $args );
		
		// We must log in first
		if (! self::login( $credentials ) ) {
			global $wp_xmlrpc_server;
			return false;
		}
		
		extract( $data );
		
		$user = wp_authenticate( $username, $password );
		
		if ( is_wp_error($user) ) {
			return false;
		}
		
		return true;
	}
	
	
	/**
	 * Returns this version of the Integrator
	 * @access		public
	 * @version		3.0.12
	 * @param		array		- $args: the arguments passed via XMLRPC Server
	 * 
	 * @return		array
	 * @since		3.0.1 (0.1)
	 */
	public function get_info( &$args )
	{
		return array( 'cnxns|wp' => INTEGRATOR_VERSION );
	}
	
	
	/**
	 * Gets the Wordpress menu tree
	 * @access		public
	 * @version		3.0.12
	 * @param		array of arguments passed via XML-RPC
	 * 
	 * @return		array of menu items
	 * @since		3.0.0
	 */
	public function get_menutree( &$args )
	{
		extract( $args );
		
		// We must log in first
		if (! self::login( $credentials ) ) {
			global $wp_xmlrpc_server;
			return false;
		}
		
		$data		= array();
		
		$menus	= wp_get_nav_menus();
		//echo "<pre>".print_r($menus,1)."</pre><hr/><hr/>";
		foreach ( $menus as $menu ) {
			if (! isset( $data[$menu->name] ) ) $data[$menu->name] = array();
			
			$items 		= wp_get_nav_menu_items( $menu->term_id );
			$children	= array();
			
			if ( empty ( $items ) ) continue;
			
			foreach( $items as $item ) {
				$pt 	= $item->menu_item_parent;
				$list 	= @$children[$pt] ? $children[$pt] : array();
				
				array_push( $list, $item );
				$children[$pt] = $list;
			}
			
			$data[$menu->name] = IntHelper::tree_recurse( 0, '', array(), $children, 9999, 0, 0 );
			
		}
		
		//$data	= treerecurse( 0, '', array(), $children, 9999, 0, 0 );
		
		return $data;
	}
	
	
	/**
	 * Gets missing credentials for user
	 * @access		public
	 * @version		3.0.12
	 * @param		array of arguments passed via XML-RPC
	 * 
	 * @return		string or false on error
	 * @since		3.0.0
	 */
	public function get_missing_credentials( &$args )
	{
		extract( $args );
		
		// We must log in first
		if (! self::login( $credentials ) ) {
			global $wp_xmlrpc_server;
			return false;
		}
		
		extract( $data );
		
		$is_email	= IntHelper::is_email( $uservalue );
		$user		= ( $is_email ? get_user_by_email( $uservalue ) : get_userdatabylogin( $uservalue ) );
		
		if (! $user ) return false;
		else return ( $is_email ? $user->user_login : $user->user_email );
	}
	
	
	/**
	 * Log a user in first
	 * @access		public
	 * @version		3.0.12
	 * @param		array		- $args: the arguments passed via XMLRPC Server
	 * 
	 * @return		true on success array on error
	 * @since		3.0.0
	 */
	public function login( $credentials )
	{
		if (! is_array( $credentials ) ) return false;
		
		extract( $credentials );
		
		global $wp_xmlrpc_server;
		
		// Let's run a check to see if credentials are okay
		if ( !$user = $wp_xmlrpc_server->login( $apiusername, $apipassword ) ) {
			return false;
		}
		
		if (! user_can( $user, 'edit_users' ) ) {
			return 'API user permissions not set high enough';
		}
		
		// Generate signature - apisignature / apisalt
		$secret		= get_option( 'integrator_apisecret' );
		$signature	= base64_encode( hash_hmac( 'sha256', $apisalt, $secret, true ) );
		
		if ( $apisignature != $signature ) {
			return 'There is a problem with the secret / hash generated code.';
		}
		
		return true;
	}
	
	
	/**
	 * Ping interface
	 * @access		public
	 * @version		3.0.12
	 * @param		array		- $args: the arguments passed via XMLRPC Server
	 * 
	 * @return		string
	 * @since		3.0.0
	 */
	public function ping( &$args )
	{
		extract( $args );
		
		if ( isset( $data['login'] ) && $data['login'] == true ) {
			
			if ( ( $result = self::login( $credentials ) ) === false ) {
				global $wp_xmlrpc_server;
				return $wp_xmlrpc_server->error;
			}
			else if ( is_string( $result ) ) {
				return $result;
			}
		}
		
		return 'Pong';
	}
	
	
	/**
	 * Create a new user in WP
	 * @access		public
	 * @version		3.0.12
	 * @param		array		- $args: the arguments passed via XMLRPC Server
	 * 
	 * @return		string
	 * @since		3.0.0
	 */
	public function user_create( &$args )
	{
		extract( $args );
		
		// We must log in first
		if (! self::login( $credentials ) ) {
			return false;
		}
		
		$user		= wp_insert_user( $data );
		
		if ( is_wp_error( $user ) ) {
			return false;
		}
		
		return $user;
	}
	
	
	/**
	 * Find a user in WP
	 * @access		public
	 * @version		3.0.12
	 * @param		array		- $args: the arguments passed via XMLRPC Server
	 * 
	 * @return		string
	 * @since		3.0.0
	 */
	public function user_find( &$args )
	{
		extract( $args );
		
		// We must log in first
		if (! self::login( $credentials ) ) {
			return false;
		}
		
		$find = ( isset( $data['user_email'] ) ? $data['user_email'] : ( isset( $data['user_login'] ) ? $data['user_login'] : false ) );
		
		return ( $find === false ? false : IntHelper::find_user( $find ) );
	}
	
	
	/**
	 * Method to remove a user from WP
	 * @access		public
	 * @version		3.0.12
	 * @param		array		- $args: the arguments passed via XMLRPC Server
	 * 
	 * @return		true or string on error
	 * @since		3.0.0
	 */
	public function user_remove( &$args )
	{
		extract( $args );
		
		// We must log in first
		if (! self::login( $credentials ) ) {
			return false;
		}
		
		$find	= ( isset( $data['user_email'] ) ? $data['user_email'] : ( isset( $data['user_login'] ) ? $data['user_login'] : false ) );
		$user	= IntHelper::find_user( $find );
		
		if ( $user['user_email'] != $data['user_email'] ) return false;
		
		$del	= wp_delete_user( $user['ID'] );
		
		if ( is_wp_error( $del ) ) {
			return false;
		}
		
		return true;
	}
	
	
	/**
	 * Method to search for a user
	 * @access		public
	 * @version		3.0.12
	 * @param		array		- $args: the arguments passed via XMLRPC Server
	 * 
	 * @return		array or false on error
	 * @since		3.0.1 (0.1)
	 */
	public function user_search( &$args )
	{
		extract( $args );
		
		// We must log in first
		if (! self :: login( $credentials ) ) {
			return false;
		}
		
		return IntHelper :: user_search( $data['search'] );
	}
	
	
	/**
	 * Updates a user in WP
	 * @access		public
	 * @version		3.0.12
	 * @param		array		- $args: the arguments passed via XMLRPC Server
	 * 
	 * @return		string
	 * @since		3.0.0
	 */
	public function user_update( &$args )
	{
		extract( $args );
		
		// We must log in first
		if (! self::login( $credentials ) ) {
			return false;
		}
		
		$find = ( isset( $data['user_email'] ) ? $data['user_email'] : ( isset( $data['user_login'] ) ? $data['user_login'] : false ) );
		$user = IntHelper::find_user( $find );
		 
		if ( is_wp_error( $user ) ) {
			return false;
		}
		
		$data['update']['ID'] = $user['ID'];
		
		$updated	= wp_update_user( $data['update'] );
		
		if ( is_wp_error( $updated ) ) {
			return false;
		}
		
		if ( ( $data['update']['user_login'] != $user['user_login'] ) && isset( $data['update']['user_login'] ) ) {
			global $wpdb;
			$qry = "UPDATE {$wpdb->users} as u SET u.user_login = '{$wpdb->escape( $data['update']['user_login'] )}' WHERE u.user_login = '{$wpdb->escape( $user['user_login'] )}'";
			$err = $wpdb->query( $qry );
			
			if ( is_wp_error( $err ) ) {
				return false;
			}
		}
		
		return true;
	}
	
	
	/**
	 * Provides for new user validation
	 * @access		public
	 * @version		3.0.12
	 * @param		array		- $args: the arguments passed via XMLRPC Server
	 * 
	 * @return		true or string on error
	 * @since		3.0.0
	 */
	public function validate_on_create( &$args )
	{
		extract( $args );
		
		// We must log in first
		if (! self::login( $credentials ) ) {
			return false;
		}
		
		if ( username_exists( $data['user_login'] ) ) {
			return sprintf( 'The username `%s` is already in use!', $data['user_login'] );
		}
		
		if ( email_exists( $data['user_email'] ) ) {
			return sprintf( 'The email address `%s` is already in use!', $data['user_email'] );
		}
		
		return true;
	}
	
	
	/**
	 * Provides for existing user validation on changes
	 * @access		public
	 * @version		3.0.12
	 * @param		array		- $args: the arguments passed via XMLRPC Server
	 * 
	 * @return		true or string on error
	 * @since		3.0.0
	 */
	public function validate_on_update( &$args )
	{
		extract( $args );
		
		// We must log in first
		if (! self::login( $credentials ) ) {
			return false;
		}
		
		$update = $data['update'];
		
		if ( isset ( $data['user_email'] ) ) {
			if ( ( $data['user_email'] != $update['user_email'] ) && email_exists( $update['user_email'] ) ) {
				return sprintf( 'The email address `%s` is already in use!', $update['user_email'] );
			}
		}
		
		return true;
	}
	
	
}



/**
 * **********************************************************************
 * XML RPC FILTERS
 * We must add the xml-rpc filters here
 * **********************************************************************
 */

add_filter('xmlrpc_methods', 'integrator_xmlrpc');

function integrator_xmlrpc( $methods )
{
	$methods['integrator.authenticate']				= "intxml_auth";
	$methods['integrator.getinfo']					= "intxml_getinfo";
	$methods['integrator.getmenutree']				= "intxml_getmenutree";
	$methods['integrator.getmissingcredentials']	= "intxml_getmissingcredentials";
	$methods['integrator.ping']						= "intxml_ping";
	$methods['integrator.usercreate']				= "intxml_usercreate";
	$methods['integrator.userfind']					= "intxml_userfind";
	$methods['integrator.userremove']				= "intxml_userremove";
	$methods['integrator.usersearch']				= "intxml_usersearch";
	$methods['integrator.userupdate']				= "intxml_userupdate";
	$methods['integrator.validateoncreate']			= "intxml_validateoncreate";
	$methods['integrator.validateonupdate']			= "intxml_validateonupdate";
	return $methods;
}

function intxml_auth( $args ) {
	define( 'INTEGRATOR_API', true ); 
	return IntXMLRPC::authenticate( $args );
}

function intxml_getinfo( $args ) {
	define( 'INTEGRATOR_API', true );
	return IntXMLRPC::get_info( $args );
}

function intxml_getmenutree( $args ) {
	define( 'INTEGRATOR_API', true );
	return IntXMLRPC::get_menutree( $args );
}

function intxml_getmissingcredentials( $args ) {
	define( 'INTEGRATOR_API', true );
	return IntXMLRPC::get_missing_credentials( $args );
}

function intxml_ping( $args ) {
	define( 'INTEGRATOR_API', true );
	return IntXMLRPC::ping( $args );
}

function intxml_usercreate( $args ) {
	define( 'INTEGRATOR_API', true );
	return IntXMLRPC::user_create( $args );
}
function intxml_userfind( $args ) {
	define( 'INTEGRATOR_API', true );
	return IntXMLRPC::user_find( $args );
}

function intxml_userremove( $args ) {
	define( 'INTEGRATOR_API', true );
	return IntXMLRPC::user_remove( $args );
}
function intxml_usersearch( $args ) {
	define( 'INTEGRATOR_API', true );
	return IntXMLRPC::user_search( $args );
}

function intxml_userupdate( $args ) {
	define( 'INTEGRATOR_API', true );
	return IntXMLRPC::user_update( $args );
}

function intxml_validateoncreate( $args ) {
	define( 'INTEGRATOR_API', true );
	return IntXMLRPC::validate_on_create( $args );
}

function intxml_validateonupdate( $args ) {
	define( 'INTEGRATOR_API', true );
	return IntXMLRPC::validate_on_update( $args );
}


/**
 * **********************************************************************
 * INTEGRATOR INITIALIZATION
 * **********************************************************************
 */

// Add initialization function to init action hook
add_action("init", "Int3init");

/**
 * Integrator initilization
 * @access		public
 * @version		3.0.12
 * 
 * @since		3.0.0
 */
function Int3init()
{
	global $int;
	$int = new Integrator();
	
	if ( isset( $_GET['sendback'] ) ) $int->sendback = true;
}

?>