<?php
/*
Plugin Name: Title Refetch
Plugin URI: https://github.com/joshp23/YOURLS-title-refetch
Description: Refetch poorly defined titles
Version: 1.2.2
Author: Josh Panter
Author URI: https://unfettered.net
*/

// No direct call
if( !defined( 'YOURLS_ABSPATH' ) ) die();

// Add the admin page
yourls_add_action( 'plugins_loaded', 'title_refetch_add_page' );
function title_refetch_add_page() {
        yourls_register_plugin_page( 'title_refetch', 'Title Refetch', 'title_refetch_do_page' );
}
function title_refetch_do_page() {

	// Check if a form was submitted
	if( isset( $_POST['title_refetch_batch_chk'] ) && ( $_POST['title_refetch_batch_chk'] == 'yes') ) {
		// Check nonce
		yourls_verify_nonce( 'title_refetch' );
		title_refetch_batch_do();
	}
	// Create nonce
	$nonce = yourls_create_nonce( 'title_refetch' );

	echo <<<HTML
	<div id="wrap">
		<form method="post">
			<h3>Mass Title Refetch</h3>
			<div style="padding-left: 10pt;">
				<p>Batch process your entire database at once and fetch a new title for any short url that is found to be missing one.</p>
				<p>This could be quite resource intensive and time consuming for larger databases. Alternatively, new titles are generated for urls tht need them whenever the sharebox is displayed on the stats page.</p>
				<div class="checkbox">
				  <label>
				    <input name="title_refetch_batch_chk" type="hidden" value="no" >
				    <input name="title_refetch_batch_chk" type="checkbox" value="yes" > Run?
				  </label>
				</div>
				<br>
			</div>
			<hr/>
			<input type="hidden" name="nonce" value="$nonce" />
			<p><input type="submit" value="Submit" /></p>
		</form>
	</div>

HTML;
}

// Add a Refetch Button to the Admin interface
yourls_add_filter( 'action_links', 'title_refetch_admin_button' );
function title_refetch_admin_button( $action_links, $keyword, $url, $ip, $clicks, $timestamp ) {

	$id = yourls_string2htmlid( $keyword ); // used as HTML #id
	$sig = yourls_auth_signature();
	$home = YOURLS_SITE;
	$jslink = "'$keyword','$sig','$id'";

	// Add the Refetch button to the action links list
	$action_links .= '<a href="javascript:void(0);" onclick="titleRefetch('. $jslink .');" id="trlink-'.$id.'" title="Title Refetch" class="button button_refetch">JS Refetch</a>';

 	return $action_links;
}

// Add the js/CSS to <head>
yourls_add_action( 'html_head', 'title_refetch_css' );
function title_refetch_css( $context ) {
	// expose what page we are on
	foreach($context as $k):
		// If we are on the index page, use this css code for the button
		if( $k == 'index' ):
			echo "\n<! --------------------------Title Refetch Start-------------------------- >\n";
			echo "<script src=\"". yourls_plugin_url( dirname( __FILE__ ) ). "/assets/refetch.js\" type=\"text/javascript\"></script>\n" ;
			echo "<link rel=\"stylesheet\" href=\"". yourls_plugin_url( dirname( __FILE__ ) ) . "/assets/refetch.css\" type=\"text/css\" />\n";
			echo "\n<! --------------------------Title Refetch End---------------------------- >\n";
		endif;
	endforeach;
}

// Mass Title Refetch
function title_refetch_batch_do() {
	global $ydb;
	$table = defined( 'YOURLS_DB_PREFIX' ) ? YOURLS_DB_PREFIX . 'url' : 'url';
	$records = $ydb->get_results("SELECT * FROM `$table` ORDER BY timestamp DESC");
	if($records) {
		$i = 0;
		foreach( $records as $record ) {
			$url = $record->url;
			$title = $record->title;
			$keyword = $record->keyword;
			if( in_array( yourls_get_protocol( $title ), array( 'http://', 'https://' ) ) ) {
				$title = yourls_get_remote_title( $url );
				yourls_edit_link_title( $keyword, $title );
			$i++;
			}
		}
	}
	if( yourls_is_API() ) {
		 return $i;
	} else {

		if( $i > 0 ) {
			 echo '<p style="color:green;">Total URL title updates: ' . $i . '</p>';
		} else {
			echo '<p style="color:green;">No URL title updates needed at this time.</p>';
		}
	}
}

// API addition to action=shorturl - this is the basic fix
yourls_add_action( 'post_add_new_link', 'title_refetch_api_add' );
function title_refetch_api_add( $data ) {

	if( yourls_is_API() ) {

		if ( isset( $_REQUEST['refetch'] ) && ( $_REQUEST['refetch'] == 'true') ) {

			if( in_array( yourls_get_protocol( $data[2] ), array( 'http://', 'https://' ) ) ) {

				$data[2] = yourls_get_remote_title( $data[0] );

				yourls_edit_link_title( $data[1], $data[2] );
			}
		}
	}
}
// API-Updates
yourls_add_filter( 'api_action_refetch', 'title_refetch_api' );
function title_refetch_api() {

	// We need a target for the refetch
	if( !isset( $_REQUEST['target'] ) ) {
		return array(
			'statusCode' => 400,
			'simple'     => "Need a 'target' parameter",
			'message'    => 'error: missing param',
		);	
	}
	
	// That target must be precise
	if( !in_array( $_REQUEST['target'], array( 'title', 'title-force', 'all' ) ) ) {
		return array(
			'statusCode' => 400,
			'simple'     => "Key: 'target' must match Value: 'title', 'title-force', or 'all'.",
			'message'    => 'error: missing param',
		);	
	}
	
	// Refetch Single Title
	if( $_REQUEST['target'] == 'title' || $_REQUEST['target'] == 'title-force') {

		// We need a short url to work with
		if( !isset( $_REQUEST['shorturl'] ) ) {
			return array(
				'statusCode' => 400,
				'simple'     => "Need a 'shorturl' parameter",
				'message'    => 'error: missing param',
			);	
		}
		
		$shorturl = $_REQUEST['shorturl'];
		$keyword = str_replace( YOURLS_SITE . '/' , '', $shorturl ); // accept either 'http://ozh.in/abc' or 'abc'

		$keyword = yourls_sanitize_string( $keyword );
		$url = yourls_get_keyword_longurl( $keyword );
		$title = yourls_get_keyword_title( $keyword );

		$target =  $_REQUEST['target'];

		$do = title_refetch_do( $url, $keyword, $title, $target );

		if( $do ) {
			switch ($do) {
					case 1: 
						$code	= 200;
						$simple = "Title refetched: unchanged.";
						$msg	= 'success: unchanged title refetch';
						break;
					case 2: 
						$code	= 200;
						$simple = "Title refetched: updated.";
						$msg	= 'success: updated title refetch';
						$title  = yourls_get_keyword_title( $keyword );
						break;
					case 3: 
						$code	= 200;
						$simple = "No refetch required.";
						$msg	= 'success: no refetch required';
						break;
				
					default:
						$code	= 200;
						$simple = "Title refetched.";
						$msg	= 'success: refetched';
			}

			return array(
				'statusCode' => $code,
				'simple'     => $simple,
				'message'    => $msg,
				'title'	     => $title
			);	
		} else {
			return array(
				'statusCode' => 500,
				'simple'     => 'Error: could not refetch title, not sure why :-/',
				'message'    => 'error: unknown error',
			);	
		}
	}
	
	// Refetch Entire DB
	if( $_REQUEST['target'] == 'all' ) {

		$auth = yourls_is_valid_user();
		if( $auth !== true ) {
			$format = ( isset($_REQUEST['format']) ? $_REQUEST['format'] : 'xml' );
			$callback = ( isset($_REQUEST['callback']) ? $_REQUEST['callback'] : '' );
			yourls_api_output( $format, array(
				'simple' => $auth,
				'message' => $auth,
				'errorCode' => 403,
				'callback' => $callback,
			) );
		}

		$do = title_refetch_batch_do();

		if( $do ) { 

			if( $do == 0 ) {
				$code	= 200;
				$simple = "No refetching required";
				$msg	= 'success: nothing refetched';
			} 

			elseif( $do > 0 ) {
				$code	= 200;
				$simple = $do . " titles refetched.";
				$msg	= 'success: refetched';
			}
			
			else {
				$code	= 200;
				$simple = "Titles checked and refetched.";
				$msg	= 'success: refetched';
			}

			return array(
				'statusCode' => $code,
				'simple'     => $simple,
				'message'    => $msg,
			);

		} else {
			return array(
				'statusCode' => 500,
				'simple'     => 'Error: could not refetch database titles, not sure why :-/',
				'message'    => 'error: unknown error',
			);	
		}
	}
}

// Title Refetch for API-Updates
function title_refetch_do( $url, $keyword, $title, $target ) {

	if( in_array( yourls_get_protocol( $title ), array( 'http://', 'https://' ) ) || $target == 'title-force' ) {

		$title_refetch = yourls_get_remote_title( $url );

		if( $title == $title_refetch ) { 

			$data = 1;

		} else {

			yourls_edit_link_title( $keyword, $title_refetch );
			$data = 2;
		}

	} else {

		$data = 3;
	}

	return $data;
}
