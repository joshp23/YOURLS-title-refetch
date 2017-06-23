<?php
/*
Plugin Name: Title Refetch
Plugin URI: https://github.com/joshp23/YOURLS-title-refetch
Description: Refetch poorly defined titles
Version: 0.2.0
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

// Title Refetch on Stats Page Load (if sharebox is enabled)
yourls_add_filter( 'share_box_data', 'title_refetch_share' );
function title_refetch_share( $data ) {

	if( $data['title'] !== '' ) {

		if( in_array( yourls_get_protocol( $data['title'] ), array( 'http://', 'https://' ) ) ) {

			$data['title'] = yourls_get_remote_title( $data['longurl'] );

			$keyword = str_replace( YOURLS_SITE . '/' , '', $data['shorturl'] );
			yourls_edit_link_title( $keyword, $data['title'] );

			echo '<h3 style="color:green;">New Title: ' . $data['title'] . '</h3>';
		}
	}
	return $data;
}

// Mass Title Refetch Via Admin Page
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
	if( $i > 0 ) {
		 echo '<p style="color:green;">Total URL title updates: ' . $i . '</p>';
	} else {
		echo '<p style="color:green;">No URL title updates needed at this time.</p>';
	}
}
