<?php
/***
 * TZSS Settings Class
 *
 * Registers all plugin settings with the WordPress Settings API.
 * Handles license key activation with the ThemeZee Store API.
 *
 * @link https://codex.wordpress.org/Settings_API
 * @package ThemeZee Social Sharing
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


// Use class to avoid namespace collisions
if ( ! class_exists('TZSS_Settings') ) :

class TZSS_Settings {
	/** Singleton *************************************************************/

	/**
	 * @var instance The one true TZSS_Settings instance
	 */
	private static $instance;
	
	/**
	 * @var options Plugin options array
	 */
	private $options;
	
	/**
     * Creates or returns an instance of this class.
     *
     * @return TZSS_Settings A single instance of this class.
     */
	public static function instance() {
 
        if ( null == self::$instance ) {
            self::$instance = new self;
        }
 
        return self::$instance;
    }
	
	/**
	 * Plugin Setup
	 *
	 * @return void
	*/
	public function __construct() {

		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'activate_license' ) );
		add_action( 'admin_init', array( $this, 'deactivate_license' ) );
		add_action( 'admin_init', array( $this, 'check_license' ) );
		
		// Merge Plugin Options Array from Database with Default Settings Array
		$this->options = wp_parse_args( 
			
			// Get saved theme options from WP database
			get_option( 'tzss_settings' , array() ), 
			
			// Merge with Default Settings if setting was not saved yet
			$this->default_settings()
			
		);
	}

	/**
	 * Get the value of a specific setting
	 *
	 * @return mixed
	*/
	public function get( $key, $default = false ) {
		$value = ! empty( $this->options[ $key ] ) ? $this->options[ $key ] : $default;
		return $value;
	}

	/**
	 * Get all settings
	 *
	 * @return array
	*/
	public function get_all() {
		return $this->options;
	}
	
	/**
	 * Retrieve default settings
	 *
	 * @return array
	*/
	public function default_settings() {

		$default_settings = array();

		foreach ( $this->get_registered_settings() as $key => $option ) :
		
			if ( $option[ 'type' ] == 'multicheck' ) :
			
				foreach ( $option[ 'options' ] as $index => $value ) :
				
					$default_settings[$key][$index] = isset( $option['default'] ) ? $option['default'] : false;
				
				endforeach;
			
			else :
				
				$default_settings[$key] =  isset( $option['default'] ) ? $option['default'] : false;
				
			endif;
		
		endforeach;
		
		return $default_settings;
	}

	/**
	 * Register all settings sections and fields
	 *
	 * @return void
	*/
	function register_settings() {

		// Make sure that options exist in database
		if ( false == get_option( 'tzss_settings' ) ) {
			add_option( 'tzss_settings' );
		}
		
		// Add Sections
		add_settings_section( 'tzss_settings_widgets', esc_html__( 'General', 'themezee-social-sharing' ), array( $this, 'general_section_intro' ), 'tzss_settings' );
		add_settings_section( 'tzss_settings_example_one', __('Example 1', 'themezee-social-sharing' ), '__return_false', 'tzss_settings' );
		add_settings_section( 'tzss_settings_example_two', __('Example 2', 'themezee-social-sharing' ), '__return_false', 'tzss_settings' );
		add_settings_section( 'tzss_settings_license', esc_html__( 'License', 'themezee-social-sharing' ), array( $this, 'license_section_intro' ), 'tzss_settings' );
		
		// Add Settings
		foreach ( $this->get_registered_settings() as $key => $option ) :

			$name = isset( $option['name'] ) ? $option['name'] : '';
			$section = isset( $option['section'] ) ? $option['section'] : 'widgets';
			
			add_settings_field(
				'tzss_settings[' . $key . ']',
				$name,
				is_callable( array( $this, $option[ 'type' ] . '_callback' ) ) ? array( $this, $option[ 'type' ] . '_callback' ) : array( $this, 'missing_callback' ),
				'tzss_settings',
				'tzss_settings_' . $section,
				array(
					'id'      => $key,
					'name'    => isset( $option['name'] ) ? $option['name'] : null,
					'desc'    => ! empty( $option['desc'] ) ? $option['desc'] : '',
					'size'    => isset( $option['size'] ) ? $option['size'] : null,
					'max'     => isset( $option['max'] ) ? $option['max'] : null,
					'min'     => isset( $option['min'] ) ? $option['min'] : null,
					'step'    => isset( $option['step'] ) ? $option['step'] : null,
					'options' => isset( $option['options'] ) ? $option['options'] : '',
					'default'     => isset( $option['default'] ) ? $option['default'] : ''
				)
			);
			
		endforeach;

		// Creates our settings in the options table
		register_setting( 'tzss_settings', 'tzss_settings', array( $this, 'sanitize_settings' ) );
	}

	
	/**
	 * General Section Intro
	 *
	 * @return void
	*/
	function general_section_intro() {
		esc_html_e( 'Configure the Social Sharing.', 'themezee-social-sharing');
	}
	
	
	/**
	 * License Section Intro
	 *
	 * @return void
	*/
	function license_section_intro() {
		printf( __( 'Please enter your license key. An active license key is needed for automatic plugin updates and <a href="%s" target="_blank">support</a>.', 'themezee-social-sharing' ), 'http://themezee.com/support/' );

	}
	
	
	/**
	 * Sanitize the Plugin Settings
	 *
	 * @return array
	*/
	function sanitize_settings( $input = array() ) {

		if ( empty( $_POST['_wp_http_referer'] ) ) {
			return $input;
		}

		$saved    = get_option( 'tzss_settings', array() );
		if( ! is_array( $saved ) ) {
			$saved = array();
		}
		
		$settings = $this->get_registered_settings();
		$input = $input ? $input : array();
		
		// Loop through each setting being saved and pass it through a sanitization filter
		foreach ( $input as $key => $value ) :

			// Get the setting type (checkbox, select, etc)
			$type = isset( $settings[ $key ][ 'type' ] ) ? $settings[ $key ][ 'type' ] : false;
			
			// Sanitize user input based on setting type
			if ( $type == 'text' or $type == 'license' ) :
				
				$input[ $key ] = sanitize_text_field( $value );
			
			elseif ( $type == 'radio' or $type == 'select' ) :
				
				$available_options = array_keys( $settings[ $key ][ 'options' ] );
				$input[ $key ] = in_array( $value, $available_options, true ) ? $value : $settings[ $key ][ 'default' ];
							
			elseif ( $type == 'number' ) :
				
				$input[ $key ] = floatval( $value );
			
			elseif ( $type == 'textarea' ) :
				
				$input[ $key ] = esc_html( $value );
			
			elseif ( $type == 'textarea_html' ) :
				
				if ( current_user_can('unfiltered_html') ) :
					$input[ $key ] = $value;
				else :
					$input[ $key ] = wp_kses_post( $value );
				endif;
			
			elseif ( $type == 'checkbox' or $type == 'multicheck' ) :
				
				$input[ $key ] = $value; // Validate Checkboxes later
				
			else :
				
				// Default Sanitization
				$input[ $key ] = esc_html( $value );
				
			endif;

		endforeach;
		
		// Ensure a value is always passed for every checkbox
		if( ! empty( $settings ) ) :
			foreach ( $settings as $key => $setting ) :

				// Single checkbox
				if ( isset( $settings[ $key ][ 'type' ] ) && 'checkbox' == $settings[ $key ][ 'type' ] ) :
					$input[ $key ] = ! empty( $input[ $key ] );
				endif;

				// Multicheck list
				if ( isset( $settings[ $key ][ 'type' ] ) && 'multicheck' == $settings[ $key ][ 'type' ] ) :
					foreach ( $settings[ $key ][ 'options' ] as $index => $value ) :
						$input[ $key ][ $index ] = ! empty( $input[ $key ][ $index ] );
					endforeach;
				endif;
				
			endforeach;
		endif;

		return array_merge( $saved, $input );

	}

	/**
	 * Retrieve the array of plugin settings
	 *
	 * @return array
	*/
	function get_registered_settings() {

		$settings = array(
			'textfield' => array(
				'name' =>  __('Textfield', 'themezee-social-sharing'),
				'desc' => __('Displays a textfield setting. ', 'themezee-social-sharing'),
				'section' => 'example_one',
				'type' => 'text',
				'size' => 'regular' // Delete that line for normal text field
			),
			'textfield_small' => array(
				'name' =>  __('Textfield Small', 'themezee-social-sharing'),
				'desc' => __('Displays a small textfield setting. ', 'themezee-social-sharing'),
				'section' => 'example_one',
				'type' => 'text',
				'size' => 'small',
				'default' => ''
			),
			'textfield_large' => array(
				'name' =>  __('Textfield Large', 'themezee-social-sharing'),
				'desc' => __('Displays a large textfield setting. ', 'themezee-social-sharing'),
				'section' => 'example_one',
				'type' => 'text',
				'size' => 'large',
				'default' => ''
			),
			'radio_button' => array(
				'name' =>  __('Radio Buttons', 'themezee-social-sharing'),
				'desc' => __('Shows an example radio button control. ', 'themezee-social-sharing'),
				'section' => 'example_one',
				'type' => 'radio',
				'options' => array(	
					'radio_1' => __('Radio Setting 1', 'themezee-social-sharing'),	
					'radio_2' => __('Radio Setting 2', 'themezee-social-sharing'),	
					'radio_3' => __('Radio Setting 3', 'themezee-social-sharing')
				),
				'default' => 'radio_2'
			),
			'checkbox' => array(
				'name' =>  __('Checkbox', 'themezee-social-sharing'),
				'desc' => __('Displays an example checkbox (default = true). ', 'themezee-social-sharing'),
				'section' => 'example_one',
				'type' => 'checkbox',
				'default' => true
			),
			'checkbox_2' => array(
				'name' =>  __('Checkbox 2', 'themezee-social-sharing'),
				'desc' => __('Displays a second example checkbox (default = false). ', 'themezee-social-sharing'),
				'section' => 'example_one',
				'type' => 'checkbox',
				'default' => false
			),
			'textarea' => array(
				'name' =>  __('Textarea', 'themezee-social-sharing'),
				'desc' => __('Displays a textarea. ', 'themezee-social-sharing'),
				'section' => 'example_one',
				'type' => 'textarea',
				'size' => 'large',
				'default' => __('Default Text', 'themezee-social-sharing')	
			),
			'textarea_html' => array(
				'name' =>  __('Textarea HTML', 'themezee-social-sharing'),
				'desc' => __('Displays a HTML textarea. ', 'themezee-social-sharing'),
				'section' => 'example_one',
				'type' => 'textarea_html',
				'size' => 'large',
				'default' => __('Default HTML', 'themezee-social-sharing')	
			),
			'select_field' => array(
				'name' =>  __('Select Field', 'themezee-social-sharing'),
				'desc' => __('Shows an example select field control. ', 'themezee-social-sharing'),
				'section' => 'example_two',
				'type' => 'select',
				'options' => array(	
					'select_1' => __('Select Setting 1', 'themezee-social-sharing'),	
					'select_2' => __('Select Setting 2', 'themezee-social-sharing'),	
					'select_3' => __('Select Setting 3', 'themezee-social-sharing')
				),
				'default' => 'select_3'
			),
			'multicheck' => array(
				'name' => __( 'Multi Checkbox', 'themezee-social-sharing' ),
				'desc' => __( 'Select multiple checkboxes.', 'themezee-social-sharing' ),
				'section' => 'example_two',
				'type' => 'multicheck',
				'options' => array(	
					'check_1' => __('Checkbox Setting 1', 'themezee-social-sharing'),	
					'check_2' => __('Checkbox Setting 2', 'themezee-social-sharing'),	
					'check_3' => __('Checkbox Setting 3', 'themezee-social-sharing')
				),
				'default' => true
			),
			'number' => array(
				'name' =>  __('Number', 'themezee-social-sharing'),
				'desc' => __('Example number setting', 'themezee-social-sharing'),
				'section' => 'example_two',
				'type' => 'number',
				'max' => 80,
				'min' => 3,
				'step' => 0.5,
				'default' => 4
			),
			'upload' => array(
				'name' =>  __('File Upload', 'themezee-social-sharing'),
				'desc' => __('Example uploader', 'themezee-social-sharing'),
				'section' => 'example_two',
				'type' => 'upload',
				'default' => ''
			),
			'missing' => array(
				'name' =>  __('Missing Callback', 'themezee-social-sharing'),
				'desc' => __('No Setting exists for that type', 'themezee-social-sharing'),
				'section' => 'example_two',
				'type' => 'blablub',
				'default' => ''
			),
			'editor' => array(
				'name' =>  __('Editor', 'themezee-social-sharing'),
				'section' => 'example_two',
				'type' => 'rich_editor',
				'default' => ''
			),
			'license_key' => array(
				'name' => __( 'License Key', 'themezee-social-sharing' ),
				'section' => 'license',
				'type' => 'license',
				'default' => ''
			)
		);

		return apply_filters( 'tzss_settings', $settings );
	}

	
	/**
	 * Checkbox Callback
	 *
	 * Renders checkboxes.
	 *
	 * @param array $args Arguments passed by the setting
	 * @global $this->options Array of all the ThemeZee Social Sharing Options
	 * @return void
	 */
	function checkbox_callback( $args ) {

		$checked = isset($this->options[$args['id']]) ? checked(1, $this->options[$args['id']], false) : '';
		$html = '<input type="checkbox" id="tzss_settings[' . $args['id'] . ']" name="tzss_settings[' . $args['id'] . ']" value="1" ' . $checked . '/>';
		$html .= '<label for="tzss_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

		echo $html;
	}

	
	/**
	 * Multicheck Callback
	 *
	 * Renders multiple checkboxes.
	 *
	 * @param array $args Arguments passed by the setting
	 * @global $this->options Array of all the ThemeZee Social Sharing Options
	 * @return void
	 */
	function multicheck_callback( $args ) {

		if ( ! empty( $args['options'] ) ) :
			foreach( $args['options'] as $key => $option ) {
				$checked = isset($this->options[$args['id']][$key]) ? checked(1, $this->options[$args['id']][$key], false) : '';
				echo '<input name="tzss_settings[' . $args['id'] . '][' . $key . ']" id="tzss_settings[' . $args['id'] . '][' . $key . ']" type="checkbox" value="1" ' . $checked . '/>&nbsp;';
				echo '<label for="tzss_settings[' . $args['id'] . '][' . $key . ']">' . $option . '</label><br/>';
			}
		endif;
		echo '<p class="description">' . $args['desc'] . '</p>';
	}
	
	
	/**
	 * Text Callback
	 *
	 * Renders text fields.
	 *
	 * @param array $args Arguments passed by the setting
	 * @global $this->options Array of all the ThemeZee Social Sharing Options
	 * @return void
	 */
	function text_callback( $args ) {

		if ( isset( $this->options[ $args['id'] ] ) )
			$value = $this->options[ $args['id'] ];
		else
			$value = isset( $args['default'] ) ? $args['default'] : '';

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="text" class="' . $size . '-text" id="tzss_settings[' . $args['id'] . ']" name="tzss_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
		$html .= '<p class="description">'  . $args['desc'] . '</p>';

		echo $html;
	}
	
	
	/**
	 * Radio Callback
	 *
	 * Renders radio boxes.
	 *
	 * @param array $args Arguments passed by the setting
	 * @global $this->options Array of all the ThemeZee Social Sharing Options
	 * @return void
	 */
	function radio_callback( $args ) {

		if ( ! empty( $args['options'] ) ):
			foreach ( $args['options'] as $key => $option ) :
				$checked = false;

				if ( isset( $this->options[ $args['id'] ] ) && $this->options[ $args['id'] ] == $key )
					$checked = true;
				elseif( isset( $args['default'] ) && $args['default'] == $key && ! isset( $this->options[ $args['id'] ] ) )
					$checked = true;

				echo '<input name="tzss_settings[' . $args['id'] . ']"" id="tzss_settings[' . $args['id'] . '][' . $key . ']" type="radio" value="' . $key . '" ' . checked(true, $checked, false) . '/>&nbsp;';
				echo '<label for="tzss_settings[' . $args['id'] . '][' . $key . ']">' . $option . '</label><br/>';
			endforeach;
		endif;
		echo '<p class="description">' . $args['desc'] . '</p>';
	}


	/**
	 * License Callback
	 *
	 * Renders license key fields.
	 *
	 * @param array $args Arguments passed by the setting
	 * @global $this->options Array of all the ThemeZee Social Sharing Options
	 * @return void
	 */
	function license_callback( $args ) {

		if ( isset( $this->options[ $args['id'] ] ) )
			$value = $this->options[ $args['id'] ];
		else
			$value = isset( $args['default'] ) ? $args['default'] : '';

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="text" class="' . $size . '-text" id="tzss_settings[' . $args['id'] . ']" name="tzss_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/><br/><br/>';
		$license_status = $this->get( 'license_status' );
		$license_key = ! empty( $value ) ? $value : false;

		if( 'valid' === $license_status && ! empty( $license_key ) ) {
			$html .= '<input type="submit" class="button" name="tzss_deactivate_license" value="' . esc_attr__( 'Deactivate License', 'themezee-social-sharing' ) . '"/>';
			$html .= '<span style="display: inline-block; padding: 5px; color: green;">&nbsp;' . esc_html__( 'Your license is valid!', 'themezee-social-sharing' ) . '</span>';
		} elseif( 'expired' === $license_status && ! empty( $license_key ) ) {
			$renewal_url = esc_url( add_query_arg( array( 'edd_license_key' => $license_key, 'download_id' => TZSS_PRODUCT_ID ), 'https://themezee.com/checkout' ) );
			$html .= '<a href="' . esc_url( $renewal_url ) . '" class="button-primary">' . esc_html__( 'Renew Your License', 'themezee-social-sharing' ) . '</a>';
			$html .= '<br/><span style="display: inline-block; padding: 5px; color: red;">&nbsp;' . esc_html__( 'Your license has expired, renew today to continue getting updates and support!', 'themezee-social-sharing' ) . '</span>';
		} elseif( 'invalid' === $license_status && ! empty( $license_key ) ) {
			$html .= '<input type="submit" class="button" name="tzss_activate_license" value="' . esc_attr__( 'Activate License', 'themezee-social-sharing' ) . '"/>';
			$html .= '<span style="display: inline-block; padding: 5px; color: red;">&nbsp;' . esc_html__( 'Your license is invalid!', 'themezee-social-sharing' ) . '</span>';
		} else {
			$html .= '<input type="submit" class="button" name="tzss_activate_license" value="' . esc_attr__( 'Activate License', 'themezee-social-sharing' ) . '"/>';
		}

		$html .= '<p class="description">'  . $args['desc'] . '</p>';

		echo $html;
	}

	
	/**
	 * Number Callback
	 *
	 * Renders number fields.
	 *
	 * @param array $args Arguments passed by the setting
	 * @global $this->options Array of all the ThemeZee Social Sharing Options
	 * @return void
	 */
	function number_callback( $args ) {

		if ( isset( $this->options[ $args['id'] ] ) )
			$value = $this->options[ $args['id'] ];
		else
			$value = isset( $args['default'] ) ? $args['default'] : '';

		$max  = isset( $args['max'] ) ? $args['max'] : 999999;
		$min  = isset( $args['min'] ) ? $args['min'] : 0;
		$step = isset( $args['step'] ) ? $args['step'] : 1;

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="number" step="' . esc_attr( $step ) . '" max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" class="' . $size . '-text" id="tzss_settings[' . $args['id'] . ']" name="tzss_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
		$html .= '<p class="description">'  . $args['desc'] . '</p>';

		echo $html;
	}

	
	/**
	 * Textarea Callback
	 *
	 * Renders textarea fields.
	 *
	 * @param array $args Arguments passed by the setting
	 * @global $this->options Array of all the ThemeZee Social Sharing Options
	 * @return void
	 */
	function textarea_callback( $args ) {

		if ( isset( $this->options[ $args['id'] ] ) )
			$value = $this->options[ $args['id'] ];
		else
			$value = isset( $args['default'] ) ? $args['default'] : '';

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<textarea class="' . $size . '-text" cols="20" rows="5" id="tzss_settings_' . $args['id'] . '" name="tzss_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
		$html .= '<p class="description">'  . $args['desc'] . '</p>';

		echo $html;
	}
	
	
	/**
	 * Textarea HTML Callback
	 *
	 * Renders textarea fields which allow HTML code.
	 *
	 * @param array $args Arguments passed by the setting
	 * @global $this->options Array of all the ThemeZee Social Sharing Options
	 * @return void
	 */
	function textarea_html_callback( $args ) {

		if ( isset( $this->options[ $args['id'] ] ) )
			$value = $this->options[ $args['id'] ];
		else
			$value = isset( $args['default'] ) ? $args['default'] : '';

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<textarea class="' . $size . '-text" cols="20" rows="5" id="tzss_settings_' . $args['id'] . '" name="tzss_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
		$html .= '<p class="description">'  . $args['desc'] . '</p>';

		echo $html;
	}


	/**
	 * Missing Callback
	 *
	 * If a function is missing for settings callbacks alert the user.
	 *
	 * @param array $args Arguments passed by the setting
	 * @return void
	 */
	function missing_callback($args) {
		printf( __( 'The callback function used for the <strong>%s</strong> setting is missing.', 'themezee-social-sharing' ), $args['id'] );
	}

	/**
	 * Select Callback
	 *
	 * Renders select fields.
	 *
	 * @param array $args Arguments passed by the setting
	 * @global $this->options Array of all the ThemeZee Social Sharing Options
	 * @return void
	 */
	function select_callback($args) {

		if ( isset( $this->options[ $args['id'] ] ) )
			$value = $this->options[ $args['id'] ];
		else
			$value = isset( $args['default'] ) ? $args['default'] : '';

		$html = '<select id="tzss_settings[' . $args['id'] . ']" name="tzss_settings[' . $args['id'] . ']"/>';

		foreach ( $args['options'] as $option => $name ) :
			$selected = selected( $option, $value, false );
			$html .= '<option value="' . $option . '" ' . $selected . '>' . $name . '</option>';
		endforeach;

		$html .= '</select>';
		$html .= '<p class="description">'  . $args['desc'] . '</p>';

		echo $html;
	}
	

	/**
	 * Activate license key
	 *
	 * @return void
	*/
	public function activate_license() {
		
		if( ! isset( $_POST['tzss_settings'] ) )
			return;

		if( ! isset( $_POST['tzss_activate_license'] ) )
			return;

		if( ! isset( $_POST['tzss_settings']['license_key'] ) )
			return;

		// retrieve the license from the database
		$status  = $this->get( 'license_status' );
		$license = trim( $_POST['tzss_settings']['license_key'] );

		if( 'valid' == $status )
			return; // license already activated and valid

		// data to send in our API request
		$api_params = array(
			'edd_action'=> 'activate_license',
			'license' 	=> $license,
			'item_name' => urlencode( TZSS_NAME ),
			'item_id'   => TZSS_PRODUCT_ID,
			'url'       => home_url()
		);
		
		// Call the custom API.
		$response = wp_remote_post( TZSS_STORE_API_URL, array( 'timeout' => 35, 'sslverify' => true, 'body' => $api_params ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) )
			return false;

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		$options = $this->get_all();

		$options['license_status'] = $license_data->license;

		update_option( 'tzss_settings', $options );

		delete_transient( 'tzss_license_check' );

	}
	
	/**
	 * Deactivate license key
	 *
	 * @return void
	*/
	public function deactivate_license() {

		if( ! isset( $_POST['tzss_settings'] ) )
			return;

		if( ! isset( $_POST['tzss_deactivate_license'] ) )
			return;

		if( ! isset( $_POST['tzss_settings']['license_key'] ) )
			return;

		// retrieve the license from the database
		$license = trim( $_POST['tzss_settings']['license_key'] );

		// data to send in our API request
		$api_params = array(
			'edd_action'=> 'deactivate_license',
			'license' 	=> $license,
			'item_name' => urlencode( TZSS_NAME ),
			'url'       => home_url()
		);
		
		// Call the custom API.
		$response = wp_remote_post( TZSS_STORE_API_URL, array( 'timeout' => 35, 'sslverify' => true, 'body' => $api_params ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) )
			return false;

		$options = $this->get_all();

		$options['license_status'] = 0;

		update_option( 'tzss_settings', $options );

		delete_transient( 'tzss_license_check' );

	}

	/**
	 * Check license key
	 *
	 * @return void
	*/
	public function check_license() {

		if( ! empty( $_POST['tzss_settings'] ) ) {
			return; // Don't fire when saving settings
		}

		$status = get_transient( 'tzss_license_check' );

		// Run the license check a maximum of once per day
		if( false === $status ) {

			// data to send in our API request
			$api_params = array(
				'edd_action'=> 'check_license',
				'license' 	=> $this->get( 'license_key' ),
				'item_name' => urlencode( TZSS_NAME ),
				'url'       => home_url()
			);
			
			// Call the custom API.
			$response = wp_remote_post( TZSS_STORE_API_URL, array( 'timeout' => 25, 'sslverify' => true, 'body' => $api_params ) );

			// make sure the response came back okay
			if ( is_wp_error( $response ) )
				return false;

			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			$options = $this->get_all();

			$options['license_status'] = $license_data->license;

			update_option( 'tzss_settings', $options );

			set_transient( 'tzss_license_check', $license_data->license, DAY_IN_SECONDS );

			$status = $license_data->license;

		}

		return $status;

	}
	
	/**
	 * Retrieve license status
	 *
	 * @return bool
	*/
	public function is_license_valid() {
		return $this->check_license() == 'valid';
	}

}

// Run Setting Class
TZSS_Settings::instance();

endif;