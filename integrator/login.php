<?php 

require( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php' );
require_once( dirname( __FILE__ ) . '/uri.php' );

$secure_cookie = '';
$interim_login = isset( $_REQUEST['interim-login'] );

// If the user wants ssl but the session is not ssl, force a secure cookie.
if (! empty( $_POST['log'] ) && !force_ssl_admin() ) {
	$user_name = sanitize_user( $_POST['log'] );
	if ( $user = get_userdatabylogin( $user_name ) ) {
		if ( get_user_option( 'use_ssl', $user->ID ) ) {
			$secure_cookie = true;
			force_ssl_admin( true );
		}
	}
}

// If the user was redirected to a secure login form from a non-secure admin page, and secure login is required but secure admin is not, then don't use a secure
// cookie and redirect back to the referring non-secure admin page.  This allows logins to always be POSTed over SSL while allowing the user to choose visiting
// the admin via http or https.
if ( !$secure_cookie && is_ssl() && force_ssl_login() && !force_ssl_admin() && ( 0 !== strpos($redirect_to, 'https') ) && ( 0 === strpos($redirect_to, 'http') ) )
	$secure_cookie = false;

// Create redirect URL
$url = get_option( 'integrator_url' );
$uri = new IntUri( $url );
$uri->setScheme( 'http' . ( is_ssl() ? 's' : '' ) );
$uri->setPath( rtrim( $uri->getPath(), '/' ) . '/index.php/login' );
$redirect_to	= $uri->toString();

// Perform login magic here
$int->sendback	= true;
$user		= wp_signon('', $secure_cookie);
$success	= is_wp_error( $user ) ? false : true;

$uri->setPath( rtrim( $uri->getPath(), '/' )  . ( $success ? '/succeed/' . $int->get( 'cookievalue' ) . '/' . $int->get( 'cookiename' ) : '/failed' ) );
$redirect_to = $uri->toString();

wp_safe_redirect($redirect_to);

?>