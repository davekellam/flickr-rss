<?php
/*
Plugin Name: flickrRSS
Plugin URI: http://wordpress.org/extend/plugins/flickr-rss/
Description: Allows you to easily integrate Flickr photos into your site's templates.
Version: 6.0
License: GPL
Author: Dave Kellam
Author URI: http://eightface.com
*/

if ( ! class_exists( 'flickrRSS' ) ) :

define( 'FLICKRRSS_PATH', dirname( __FILE__ ) );

require_once( FLICKRRSS_PATH . '/flickrrss-settings.php' );
// require_once( FLICKRRSS_PATH . '/flickrrss-widget.php' );

class flickrRSS {
	
	function __construct() {
		// add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		// add_action( 'plugins_loaded', 'create_widget' );

		$this->settings = array(
			/*== Content params ==*/
			// The type of Flickr images that you want to show. Possible values: 'user', 'favorite', 'set', 'group', 'public'
			'type' => 'public',
			// Optional: To be used when type = 'user' or 'public', comma separated
			'tags' => '',
			// Optional: To be used when type = 'set' 
			'set' => '',
			// Optional: Your Group or User ID. To be used when type = 'user' or 'group'
			'id' => '',
		
			/*== Presentational params ==*/
			 // The number of thumbnails you want
			'num_items' => 4,
			 // the HTML to print before the list of images
			'before_list' => '',
			// the code to print out for each image. Meta tags available:
			// - %flickr_page%
			// - %title%
			// - %image_small%, %image_square%, %image_thumbnail%, %image_medium%, %image_large%
			'html' => '<a href="%flickr_page%" title="%title%"><img src="%image_square%" alt="%title%"/></a>',
			// the default title
			'default_title' => '', 
			// the HTML to print after the list of images
			'after_list' => ''
		);
	}

	function get_settings() {

		if ( get_option('flickrRSS_settings') )
			$settings = array_merge( $this->settings, get_option('flickrRSS_settings') );

		return $settings;
	}

	
	function get_rss( $type, $id, $tags, $set ) {

		// Construct feed URL
		if ( $type == 'user' ) 
			$url = 'http://api.flickr.com/services/feeds/photos_public.gne?id=' . $id . '&tags=' . $tags . '&format=rss_200';
		
		elseif ( $type == 'favorite' )
			$url = 'http://api.flickr.com/services/feeds/photos_faves.gne?id=' . $id . '&format=rss_200';
		
		elseif ( $type == 'set' ) 
			$url = 'http://api.flickr.com/services/feeds/photoset.gne?set=' . $set . '&nsid=' . $id . '&format=rss_200';
		
		elseif ( $type == 'group' ) 
			$url = 'http://api.flickr.com/services/feeds/groups_pool.gne?id=' . $id . '&format=rss_200';
		
		elseif ( $type == 'public' || $type == 'community' )
			$url = 'http://api.flickr.com/services/feeds/photos_public.gne?tags=' . $tags . '&format=rss_200';
		
		else { 
			return new WP_Error( 'feed_settings_error', __( "FlickrRSS has a configuration problem" ) );
		}

		// Retrieve feed
		return fetch_feed( $url );
	}

	function print_gallery( $settings ) {
	
		$settings = array_merge( $this->get_settings(), $settings );

		// Get settings
		$id = $settings['id'];
		$num_items = $settings['num_items'];
		$set = $settings['set'];
		$tags = $settings['tags'];
		$type = $settings['type'];

		// fetch RSS feed
		$rss = $this->get_rss( $type, $id, $tags, $set );		

		if ( ! is_wp_error( $rss ) ) {

			$maxitems = $rss->get_item_quantity( $num_items ); 

			// Build an array of all the items, starting with element 0 (first element).
			$items = $rss->get_items( 0, $maxitems );

		} else {

			echo $rss->get_error_message();
			return;

		}

		// TODO: Construct object for output rather than echoing and store in transient
		$html = esc_html( $settings['before_list'] );

		# builds html from array
		foreach ( $items as $item ) {
		
			if( ! preg_match('<img src="([^"]*)" [^/]*/>', $item->get_description(), $imgUrlMatches) ) {
				continue;
			}

			$baseurl = str_replace('_m.jpg', '', $imgUrlMatches[1]);
			$thumbnails = array(
				'small' => $baseurl . '_m.jpg',
				'square' => $baseurl . '_s.jpg',
				'thumbnail' => $baseurl . '_t.jpg',
				'medium' => $baseurl . '.jpg',
				'large' => $baseurl . '_b.jpg'
			);

			#check if there is an image title (for html validation purposes)
			if( $item->get_title() !== '' ) 
				$title = htmlspecialchars(stripslashes( $item->get_title() ) );
			else 
				$title = $settings['default_title'];
			$url = $item->get_permalink();
			$toprint = stripslashes($settings['html'] );
			$toprint = str_replace( "%flickr_page%", $url, $toprint );
			$toprint = str_replace( "%title%", $title, $toprint );
		
			foreach ( $thumbnails as $size => $thumbnail ) {
				$toprint = str_replace( "%image_" . $size . "%" , $thumbnail, $toprint );
			}
			$html .= $toprint;
		}
		$html .= esc_html( $settings['after_list'] );

		echo $html;
	}
}

endif;

$flickrRSS = new flickrRSS();

/**
 * Main function to call flickrRSS in your templates
 */
function get_flickrRSS( $settings ) {
	global $flickrRSS;

	$flickrRSS->print_gallery( $settings );
}
