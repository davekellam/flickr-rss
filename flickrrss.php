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

class flickrRSS {
	
	function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		// add_action( 'plugins_loaded', 'create_widget' );
	}

	function get_settings() {
		
		$settings = array(
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
		if (get_option('flickrRSS_settings'))
			$settings = array_merge($settings, get_option('flickrRSS_settings'));
		return $settings;
	}

	function get_rss( $settings ) {
		// Construct feed URL
		if ( $settings['type'] == 'user' ) { $rss_url = 'http://api.flickr.com/services/feeds/photos_public.gne?id=' . $settings['id'] . '&tags=' . $settings['tags'] . '&format=rss_200'; }
		elseif ( $settings['type'] == 'favorite' ) { $rss_url = 'http://api.flickr.com/services/feeds/photos_faves.gne?id=' . $settings['id'] . '&format=rss_200'; }
		elseif ( $settings['type'] == 'set' ) { $rss_url = 'http://api.flickr.com/services/feeds/photoset.gne?set=' . $settings['set'] . '&nsid=' . $settings['id'] . '&format=rss_200'; }
		elseif ( $settings['type'] == 'group' ) { $rss_url = 'http://api.flickr.com/services/feeds/groups_pool.gne?id=' . $settings['id'] . '&format=rss_200'; }
		elseif ( $settings['type'] == 'public' || $settings['type'] == 'community' ) { $rss_url = 'http://api.flickr.com/services/feeds/photos_public.gne?tags=' . $settings['tags'] . '&format=rss_200'; }
		else { 
			print '<strong>No "type" parameter has been setup. Check your flickrRSS Settings page, or provide the parameter as an argument.</strong>';
			die();
		}

		// Retrieve feed
		return fetch_feed( $rss_url );
	}

	function print_gallery( $settings ) {
	
		if ( ! is_array( $settings ) ) {
			return; // probably need better error stuff here
		}
	
		$settings = array_merge( $this->get_settings(), $settings );

		// fetch RSS feed
		$rss = $this->get_rss( $settings );

		// specifies number of pictures
		$num_items = $settings['num_items'];

		if ( ! is_wp_error( $rss ) ) : // Checks that the object is created correctly

			$maxitems = $rss->get_item_quantity( $num_items ); 

			// Build an array of all the items, starting with element 0 (first element).
		 	$items = $rss->get_items( 0, $maxitems );

		endif;

		// TODO: Construct object for output rather than echoing and store in transient
		echo stripslashes( $settings['before_list'] );

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
			echo $toprint;
		}
		echo stripslashes( $settings['after_list'] );
	}

	function add_settings_page() {
		if ( function_exists( 'add_options_page') ) {
			add_options_page( 'flickrRSS Settings', 'flickrRSS', 'manage_options', 'flickrrss-admin.php', array( &$this, 'create_settings_page' ) );
		}
	}

	function create_settings_page() {
		
		$settings = $this->get_settings();

		if ( isset( $_POST['save_flickrRSS_settings'] ) ) {
			
			foreach ( $settings as $name => $value ) {
				$settings[$name] = $_POST['flickrRSS_'.$name];
			}

			$settings['cache_sizes'] = array();
			
			foreach ( array("small", "square", "thumbnail", "medium", "large") as $size ) {
				if ( $_POST['flickrRSS_cache_'.$size] ) $settings['cache_sizes'][] = $size;
			}
			
			update_option( 'flickrRSS_settings', $settings );
			
			echo '<div class="updated"><p>flickrRSS settings saved!</p></div>';
		}

		if ( isset( $_POST['reset_flickrRSS_settings'] ) ) {
			delete_option( 'flickrRSS_settings' );
			echo '<div class="updated"><p>flickrRSS settings restored to default!</p></div>';
		}

		// add setting page 
		include ( 'flickrrss-admin.php' );

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
