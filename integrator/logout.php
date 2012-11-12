<?php

require( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php' );
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