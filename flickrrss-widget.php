<?php

class Flickrrss_Posts_Widget extends WP_Widget {

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
}