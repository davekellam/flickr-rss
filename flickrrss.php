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

if ( ! class_exists( 'flickrRSS' ) ) {

	class flickrRSS {
		
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
				// Do you want caching?
				'do_cache' => false,
				// The image sizes to cache locally. Possible values: 'square', 'thumbnail', 'small', 'medium' or 'large', provided within an array
				'cache_sizes' => array('square'),
				// Where images are saved (Server path)
				'cache_path' => '',
				// The URI associated to the cache path (web address)
				'cache_uri' => '',
			
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
				'default_title' => "Untitled Flickr photo", 
				// the HTML to print after the list of images
				'after_list' => ''
			);
			if (get_option('flickrRSS_settings'))
				$settings = array_merge($settings, get_option('flickrRSS_settings'));
			return $settings;
		}
	
		function get_rss( $settings ) {
			// Construct feed URL
			if ($settings['type'] == "user") { $rss_url = 'http://api.flickr.com/services/feeds/photos_public.gne?id=' . $settings['id'] . '&tags=' . $settings['tags'] . '&format=rss_200'; }
			elseif ($settings['type'] == "favorite") { $rss_url = 'http://api.flickr.com/services/feeds/photos_faves.gne?id=' . $settings['id'] . '&format=rss_200'; }
			elseif ($settings['type'] == "set") { $rss_url = 'http://api.flickr.com/services/feeds/photoset.gne?set=' . $settings['set'] . '&nsid=' . $settings['id'] . '&format=rss_200'; }
			elseif ($settings['type'] == "group") { $rss_url = 'http://api.flickr.com/services/feeds/groups_pool.gne?id=' . $settings['id'] . '&format=rss_200'; }
			elseif ($settings['type'] == "public" || $settings['type'] == "community") { $rss_url = 'http://api.flickr.com/services/feeds/photos_public.gne?tags=' . $settings['tags'] . '&format=rss_200'; }
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
			
				if(!preg_match('<img src="([^"]*)" [^/]*/>', $item->get_description(), $imgUrlMatches)) {
					continue;
				}
				$baseurl = str_replace("_m.jpg", "", $imgUrlMatches[1]);
				$thumbnails = array(
					'small' => $baseurl . "_m.jpg",
					'square' => $baseurl . "_s.jpg",
					'thumbnail' => $baseurl . "_t.jpg",
					'medium' => $baseurl . ".jpg",
					'large' => $baseurl . "_b.jpg"
				);
				#check if there is an image title (for html validation purposes)
				if( $item->get_title() !== '' ) 
					$title = htmlspecialchars(stripslashes( $item->get_title() ) );
				else 
					$title = $settings['default_title'];
				$url = $item->get_permalink();
				$toprint = stripslashes($settings['html']);
				$toprint = str_replace("%flickr_page%", $url, $toprint);
				$toprint = str_replace("%title%", $title, $toprint);
			
				$cachePath = trailingslashit($settings['cache_uri']);
				$fullPath = trailingslashit($settings['cache_path']);
			
				foreach ($thumbnails as $size => $thumbnail) {
					if (
							is_array($settings['cache_sizes']) && 
							in_array($size, $settings['cache_sizes']) && 
							$settings['do_cache'] == "true" && 
							$cachePath && 
							$fullPath && 
							strpos($settings['html'], "%image_".$size."%")
					) {
						$img_to_cache = $thumbnail;
						preg_match('<http://farm[0-9]{0,3}\.static.?flickr\.com/\d+?\/([^.]*)\.jpg>', $img_to_cache, $flickrSlugMatches);
						$flickrSlug = $flickrSlugMatches[1];
						if (!file_exists("$fullPath$flickrSlug.jpg") ) {   
							$localimage = fopen("$fullPath$flickrSlug.jpg", 'wb');
							$remoteimage = wp_remote_fopen($img_to_cache);
							$iscached = fwrite($localimage,$remoteimage);
							fclose($localimage);
						} else {
							$iscached = true;
						}
						if($iscached) $thumbnail = "$cachePath$flickrSlug.jpg";
					}
					$toprint = str_replace("%image_".$size."%", $thumbnail, $toprint);
				}
				echo $toprint;
			}
			echo stripslashes($settings['after_list']);
		}
	
		function create_widget() {
			if (!function_exists('wp_register_sidebar_widget')) return;
			function widget_flickrRSS($args) {
				extract($args);
				$options = get_option('widget_flickrRSS');
				$title = $options['title'];
				echo $before_widget . $before_title . $title . $after_title;
				get_flickrRSS();
				echo $after_widget;
			}
			function widget_flickrRSS_control() {
				$options = get_option('widget_flickrRSS');
				if ( $_POST['flickrRSS-submit'] ) {
					$options['title'] = strip_tags(stripslashes($_POST['flickrRSS-title']));
					update_option('widget_flickrRSS', $options);
				}
				$title = htmlspecialchars($options['title'], ENT_QUOTES);
				$settingspage = trailingslashit(get_option('siteurl')).'wp-admin/options-general.php?page='.basename(__FILE__);
				echo 
				'<p><label for="flickrRSS-title">Title:<input class="widefat" name="flickrRSS-title" type="text" value="'.$title.'" /></label></p>'.
				'<p>To control the other settings, please visit the <a href="'.$settingspage.'">flickrRSS Settings page</a>.</p>'.
				'<input type="hidden" id="flickrRSS-submit" name="flickrRSS-submit" value="1" />';
			}
			wp_register_sidebar_widget('flickrRSS', 'flickrRSS', 'widget_flickrRSS');
			wp_register_widget_control('flickrRSS', 'widget_flickrRSS_control', 'widget_flickrRSS_control');
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
					if ($_POST['flickrRSS_cache_'.$size]) $settings['cache_sizes'][] = $size;
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
}
$flickrRSS = new flickrRSS();
add_action( 'admin_menu', array( &$flickrRSS, 'add_settings_page' ) );
add_action( 'plugins_loaded', array( &$flickrRSS, 'create_widget' ) );

/**
 * Main function to call flickrRSS in your templates
 */
function get_flickrRSS( $settings ) {
	global $flickrRSS;

	$flickrRSS->print_gallery( $settings );
}
