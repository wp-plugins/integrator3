<?php

// ---- BEGIN INT-3
//		Wordpress 3.5.x upgrades plugins in a funny location
$possibles = array(
		dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php',
		dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ) . '/wp-load.php'
);

foreach ( $possibles as $possible ) {
	if ( file_exists( $possible ) ) {
		require ( $possible );
		break;
	}
}
// ---- END INT-3

require_once( dirname( __FILE__ ) . '/uri.php' );

wp_logout();

// Create redirect URL
$url = get_option( 'integrator_url' );
$uri = new IntUri( $url );
$uri->setScheme( 'http' . ( is_ssl() ? 's' : '' ) );
$uri->setPath( rtrim( $uri->getPath(), '/' ) . '/index.php/logout/complete' );
$redirect_to	= $uri->toString();

wp_safe_redirect( $redirect_to );

?>