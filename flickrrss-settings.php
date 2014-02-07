<?php

class FlickrrssSettings {
	/**
	 * Holds the values to be used in the fields callbacks
	 */
	private $options;

	/**
	 * Start up
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
	}

	/**
	 * Add options page
	 */
	public function add_plugin_page() {
		// This page will be under "Settings"
		add_options_page(
			'Settings Admin', 
			'FlickrRSS Settings', 
			'manage_options', 
			'flickrrss-settings', 
			array( $this, 'create_admin_page' )
		);
	}

	/**
	 * Options page callback
	 */
	public function create_admin_page() {
		// Set class property
		$this->options = get_option( 'flickrrss-settings' );
		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2>FlickrRSS Settings</h2>           
			<form method="post" action="options.php">
			<?php
				// This prints out all hidden setting fields
				settings_fields( 'flickrrss-settings-group' );
				do_settings_sections( 'flickrrss-admin' );
				submit_button(); 
			?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register and add settings
	 */
	public function page_init()
	{        
		register_setting(
			'flickrrss-settings-group', // Option group
			'flickrrss-settings', // Option name
			array( $this, 'sanitize' ) // Sanitize
		);

		add_settings_section(
			'flickrrss-display', // ID
			'My Custom Settings', // Title
			array( $this, 'print_section_info' ), // Callback
			'flickrrss-admin' // Page
		);

		add_settings_field(
			'id_number', // ID
			'ID Number', // Title 
			array( $this, 'id_number_callback' ), // Callback
			'flickrrss-admin', // Page
			'flickrrss-display' // Section           
		);

		add_settings_field(
			'title', 
			'Title', 
			array( $this, 'title_callback' ), 
			'flickrrss-admin', 
			'flickrrss-display'
		);

		add_settings_field(
			'num_images', 
			'Number of images', 
			array( $this, 'num_images_callback' ), 
			'flickrrss-admin', 
			'flickrrss-display',
			array (
	            //'label_for'   => 'label2', // makes the field name clickable,
	            'name'        => 'num_items_x', // value for 'name' attribute
	            //'value'       => esc_attr( $data['color'] ),
	            'options'     => array (
	                '1'  => '1',
	                '2'   => '2',
	                '3' => '3'
	            ),
	            //'option_name' => $option_name
	        )
		);
	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys
	 */
	public function sanitize( $input ) {
		$new_input = array();
		if( isset( $input['id_number'] ) )
			$new_input['id_number'] = absint( $input['id_number'] );

		if( isset( $input['title'] ) )
			$new_input['title'] = sanitize_text_field( $input['title'] );

		return $new_input;
	}

	/** 
	 * Print the Section text
	 */
	public function print_section_info() {
		print 'Enter your settings below:';
	}

	/** 
	 * Get the settings option array and print one of its values
	 */
	public function id_number_callback() {
		printf(
			'<input type="text" id="id_number" name="flickrrss-settings[id_number]" value="%s" />',
			isset( $this->options['id_number'] ) ? esc_attr( $this->options['id_number']) : ''
		);
	}

	/** 
	 * Get the settings option array and print one of its values
	 */
	public function title_callback() {
		printf(
			'<input type="text" id="title" name="flickrrss-settings[title]" value="%s" />',
			isset( $this->options['title'] ) ? esc_attr( $this->options['title']) : ''
		);
	}

	/**
	 * Get the number of images that should appear
	 */
	public function num_images_callback() {

		$num_items = isset( $this->options['num_items'] ) ? esc_attr( $this->options['num_items']) : '';

		$html = '<select name="flickrrss-settings[num_items]" id="num_items">';
		
		for ( $counter = 1; $counter <= 20; $counter++ ) { 
			$html .= '<option '; 
			
			$html .= isset( $this->options['num_items'] ) ? selected( $this->options['num_items'], $counter, false ) : '';

			$html .= 'value=' . $counter . '>' . $counter;
			$html .= '</option>';	
		}

		$html .= '</select>';

		echo $html;
	}
}

if( is_admin() )
	$my_settings_page = new FlickrrssSettings();
