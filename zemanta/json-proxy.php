<?php
// PHP Proxy based on example for Yahoo! Web services.
//
// Author: Jason Levitt
// December 7th, 2005
//
//
// Adapted for Zemanta Wordpress plugin
// by Jure Cuhalev - <jure@zemanta.com>
//
// October 11th, 2007

$path = 'http://api.zemanta.com/services/rest/0.0/';

// Open the Curl session
$session = curl_init( $path );

// If it's a POST, put the POST data in the body
$postvars = '';
while ( $element = current( $_POST ) ) {
	$new_element = str_replace( '&', '%26', $element );
	$new_element = str_replace( ';', '%3B', $new_element );
	$postvars .= key( $_POST ).'='.$new_element.'&';
	next( $_POST );
}
curl_setopt ( $session, CURLOPT_POST, true );
curl_setopt ( $session, CURLOPT_POSTFIELDS, $postvars );

// Don't return HTTP headers. Do return the contents of the call
curl_setopt( $session, CURLOPT_HEADER, false );
curl_setopt( $session, CURLOPT_RETURNTRANSFER, true );

// Make the call
$json = curl_exec( $session );

// The web service returns JSON. Set the Content-Type appropriately
header("Content-Type: text/plain");

echo $json;
curl_close( $session );

?>
