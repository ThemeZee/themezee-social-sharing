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

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Use class to avoid namespace collisions
if ( ! class_exists( 'TZSS_Settings' ) ) :

	/**
	 * TZSS_Settings Class
	 */
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
				get_option( 'tzss_settings', array() ),
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

				if ( 'multicheck' === $option['type'] ) :

					foreach ( $option['options'] as $index => $value ) :

						$default_settings[ $key ][ $index ] = isset( $option['default'] ) ? $option['default'] : false;

					endforeach;

				else :

					$default_settings[ $key ] = isset( $option['default'] ) ? $option['default'] : false;

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
			add_settings_section( 'tzss_settings_general', esc_html__( 'General Options', 'themezee-social-sharing' ), '__return_false', 'tzss_settings' );
			add_settings_section( 'tzss_settings_license', esc_html__( 'License', 'themezee-social-sharing' ), array( $this, 'license_section_intro' ), 'tzss_settings' );

			// Add Settings
			foreach ( $this->get_registered_settings() as $key => $option ) :

				$name    = isset( $option['name'] ) ? $option['name'] : '';
				$section = isset( $option['section'] ) ? $option['section'] : 'widgets';

				add_settings_field(
					'tzss_settings[' . $key . ']',
					$name,
					is_callable( array( $this, $option['type'] . '_callback' ) ) ? array( $this, $option['type'] . '_callback' ) : array( $this, 'missing_callback' ),
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
						'default' => isset( $option['default'] ) ? $option['default'] : '',
					)
				);

			endforeach;

			// Creates our settings in the options table
			register_setting( 'tzss_settings', 'tzss_settings', array( $this, 'sanitize_settings' ) );
		}

		/**
		 * License Section Intro
		 *
		 * @return void
		*/
		function license_section_intro() {
			printf( __( 'Please activate your license in order to receive automatic plugin updates and <a href="%s" target="_blank">support</a>.', 'themezee-social-sharing' ), 'https://themezee.com/support/?utm_source=plugin-settings&utm_medium=textlink&utm_campaign=social-shargin&utm_content=support' );
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

			$saved = get_option( 'tzss_settings', array() );

			if ( ! is_array( $saved ) ) {
				$saved = array();
			}

			$settings = $this->get_registered_settings();
			$input    = $input ? $input : array();

			// Loop through each setting being saved and pass it through a sanitization filter
			foreach ( $input as $key => $value ) :

				// Get the setting type (checkbox, select, etc)
				$type = isset( $settings[ $key ]['type'] ) ? $settings[ $key ]['type'] : false;

				// Sanitize user input based on setting type
				if ( 'text' === $type or 'license' === $type ) :

					$input[ $key ] = sanitize_text_field( $value );

				elseif ( 'radio' === $type or 'select' === $type ) :

					$available_options = array_keys( $settings[ $key ]['options'] );
					$input[ $key ]     = in_array( $value, $available_options, true ) ? $value : $settings[ $key ]['default'];

				elseif ( 'number' === $type ) :

					$input[ $key ] = floatval( $value );

				elseif ( 'textarea' === $type ) :

					$input[ $key ] = esc_html( $value );

				elseif ( 'textarea_html' === $type ) :

					if ( current_user_can( 'unfiltered_html' ) ) :
						$input[ $key ] = $value;
					else :
						$input[ $key ] = wp_kses_post( $value );
					endif;

				elseif ( 'checkbox' === $type or 'multicheck' === $type ) :

					$input[ $key ] = $value; // Validate Checkboxes later

				else :

					// Default Sanitization
					$input[ $key ] = esc_html( $value );

				endif;

			endforeach;

			// Ensure a value is always passed for every checkbox
			if ( ! empty( $settings ) ) :
				foreach ( $settings as $key => $setting ) :

					// Single checkbox
					if ( isset( $settings[ $key ]['type'] ) && 'checkbox' == $settings[ $key ]['type'] ) :
						$input[ $key ] = ! empty( $input[ $key ] );
					endif;

					// Multicheck list
					if ( isset( $settings[ $key ]['type'] ) && 'multicheck' == $settings[ $key ]['type'] ) :
						foreach ( $settings[ $key ]['options'] as $index => $value ) :
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
				'networks' => array(
					'name'    => esc_html__( 'Social Networks', 'themezee-social-sharing' ),
					'section' => 'general',
					'type'    => 'multicheck',
					'options' => array(
						'facebook'  => esc_html__( 'Facebook', 'themezee-social-sharing' ),
						'twitter'   => esc_html__( 'Twitter', 'themezee-social-sharing' ),
						'whatsapp'  => esc_html__( 'WhatsApp', 'themezee-social-sharing' ),
						'telegram'  => esc_html__( 'Telegram', 'themezee-social-sharing' ),
						'buffer'    => esc_html__( 'Buffer', 'themezee-social-sharing' ),
						'pinterest' => esc_html__( 'Pinterest', 'themezee-social-sharing' ),
						'linkedin'  => esc_html__( 'LinkedIn', 'themezee-social-sharing' ),
						'xing'      => esc_html__( 'Xing', 'themezee-social-sharing' ),
						'email'     => esc_html__( 'Email', 'themezee-social-sharing' ),
					),
					'default' => false,
				),
				'locations' => array(
					'name'    => esc_html__( 'Choose Locations', 'themezee-social-sharing' ),
					'section' => 'general',
					'type'    => 'multicheck',
					'options' => array(
						'above_content' => esc_html__( 'Display share buttons above content', 'themezee-social-sharing' ),
						'below_content' => esc_html__( 'Display share buttons below content', 'themezee-social-sharing' ),
						'sidebar'       => esc_html__( 'Display share buttons in floating sidebar', 'themezee-social-sharing' ),
					),
					'default' => false,
				),
				'style' => array(
					'name'    => esc_html__( 'Button Style', 'themezee-social-sharing' ),
					'section' => 'general',
					'type'    => 'select',
					'options' => array(
						'icons'  => esc_html__( 'Display Icons', 'themezee-social-sharing' ),
						'labels' => esc_html__( 'Display Labels', 'themezee-social-sharing' ),
						'both'   => esc_html__( 'Display Icons and Labels', 'themezee-social-sharing' ),
					),
					'default' => 'icons',
				),
				'activate_license' => array(
					'name'    => esc_html__( 'Activate License', 'themezee-social-sharing' ),
					'section' => 'license',
					'type'    => 'license',
					'default' => '',
				),
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

			$checked = isset( $this->options[ $args['id'] ] ) ? checked( 1, $this->options[ $args['id'] ], false ) : '';

			$html  = '<input type="checkbox" id="tzss_settings[' . $args['id'] . ']" name="tzss_settings[' . $args['id'] . ']" value="1" ' . $checked . '/>';
			$html .= '<label for="tzss_settings[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

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
				foreach ( $args['options'] as $key => $option ) {
					$checked = isset( $this->options[ $args['id'] ][ $key ] ) ? checked( 1, $this->options[ $args['id'] ][ $key ], false ) : '';
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

			if ( isset( $this->options[ $args['id'] ] ) ) {
				$value = $this->options[ $args['id'] ];
			} else {
				$value = isset( $args['default'] ) ? $args['default'] : '';
			}

			$size  = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
			$html  = '<input type="text" class="' . $size . '-text" id="tzss_settings[' . $args['id'] . ']" name="tzss_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
			$html .= '<p class="description">' . $args['desc'] . '</p>';

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

			if ( ! empty( $args['options'] ) ) :
				foreach ( $args['options'] as $key => $option ) :
					$checked = false;

					if ( isset( $this->options[ $args['id'] ] ) && $this->options[ $args['id'] ] == $key ) {
						$checked = true;
					} elseif ( isset( $args['default'] ) && $args['default'] == $key && ! isset( $this->options[ $args['id'] ] ) ) {
						$checked = true;
					}

					echo '<input name="tzss_settings[' . $args['id'] . ']"" id="tzss_settings[' . $args['id'] . '][' . $key . ']" type="radio" value="' . $key . '" ' . checked( true, $checked, false ) . '/>&nbsp;';
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
			$html = '';

			$license_status = $this->get( 'license_status' );
			$license_key    = TZSS_LICENSE;

			if ( 'valid' === $license_status && ! empty( $license_key ) ) {
				$html .= '<input type="submit" class="button" name="tzss_deactivate_license" value="' . esc_attr__( 'Deactivate License', 'themezee-social-sharing' ) . '"/>';
				$html .= '<span style="display: inline-block; padding: 5px; color: green;">&nbsp;' . esc_html__( 'Your license is valid!', 'themezee-social-sharing' ) . '</span>';
			} elseif ( 'expired' === $license_status && ! empty( $license_key ) ) {
				$renewal_url = esc_url( add_query_arg(
					array(
						'edd_license_key' => $license_key,
						'download_id'     => TZSS_PRODUCT_ID,
					),
					'https://themezee.com/checkout'
				) );
				$html .= '<a href="' . esc_url( $renewal_url ) . '" class="button-primary">' . esc_html__( 'Renew Your License', 'themezee-social-sharing' ) . '</a>';
				$html .= '<br/><span style="display: inline-block; padding: 5px; color: red;">&nbsp;' . esc_html__( 'Your license has expired, renew today to continue getting updates and support!', 'themezee-social-sharing' ) . '</span>';
			} elseif ( 'invalid' === $license_status && ! empty( $license_key ) ) {
				$html .= '<input type="submit" class="button" name="tzss_activate_license" value="' . esc_attr__( 'Activate License', 'themezee-social-sharing' ) . '"/>';
				$html .= '<span style="display: inline-block; padding: 5px; color: red;">&nbsp;' . esc_html__( 'Your license is invalid!', 'themezee-social-sharing' ) . '</span>';
			} else {
				$html .= '<input type="submit" class="button" name="tzss_activate_license" value="' . esc_attr__( 'Activate License', 'themezee-social-sharing' ) . '"/>';
			}

			$html .= '<p class="description">' . $args['desc'] . '</p>';

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

			if ( isset( $this->options[ $args['id'] ] ) ) {
				$value = $this->options[ $args['id'] ];
			} else {
				$value = isset( $args['default'] ) ? $args['default'] : '';
			}

			$max  = isset( $args['max'] ) ? $args['max'] : 999999;
			$min  = isset( $args['min'] ) ? $args['min'] : 0;
			$step = isset( $args['step'] ) ? $args['step'] : 1;

			$size  = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
			$html  = '<input type="number" step="' . esc_attr( $step ) . '" max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" class="' . $size . '-text" id="tzss_settings[' . $args['id'] . ']" name="tzss_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
			$html .= '<p class="description">' . $args['desc'] . '</p>';

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

			if ( isset( $this->options[ $args['id'] ] ) ) {
				$value = $this->options[ $args['id'] ];
			} else {
				$value = isset( $args['default'] ) ? $args['default'] : '';
			}

			$size  = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
			$html  = '<textarea class="' . $size . '-text" cols="20" rows="5" id="tzss_settings_' . $args['id'] . '" name="tzss_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
			$html .= '<p class="description">' . $args['desc'] . '</p>';

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

			if ( isset( $this->options[ $args['id'] ] ) ) {
				$value = $this->options[ $args['id'] ];
			} else {
				$value = isset( $args['default'] ) ? $args['default'] : '';
			}

			$size  = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
			$html  = '<textarea class="' . $size . '-text" cols="20" rows="5" id="tzss_settings_' . $args['id'] . '" name="tzss_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
			$html .= '<p class="description">' . $args['desc'] . '</p>';

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
		function missing_callback( $args ) {
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
		function select_callback( $args ) {

			if ( isset( $this->options[ $args['id'] ] ) ) {
				$value = $this->options[ $args['id'] ];
			} else {
				$value = isset( $args['default'] ) ? $args['default'] : '';
			}

			$html = '<select id="tzss_settings[' . $args['id'] . ']" name="tzss_settings[' . $args['id'] . ']"/>';

			foreach ( $args['options'] as $option => $name ) :
				$selected = selected( $option, $value, false );
				$html .= '<option value="' . $option . '" ' . $selected . '>' . $name . '</option>';
			endforeach;

			$html .= '</select>';
			$html .= '<p class="description">' . $args['desc'] . '</p>';

			echo $html;
		}

		/**
		 * Activate license key
		 *
		 * @return void
		*/
		public function activate_license() {

			if ( ! isset( $_POST['tzss_settings'] ) ) {
				return;
			}

			if ( ! isset( $_POST['tzss_activate_license'] ) ) {
				return;
			}

			// retrieve the license from the database.
			$status = $this->get( 'license_status' );

			if ( 'valid' === $status ) {
				return; // license already activated and valid.
			}

			// data to send in our API request
			$api_params = array(
				'edd_action' => 'activate_license',
				'license'    => TZSS_LICENSE,
				'item_name'  => urlencode( TZSS_NAME ),
				'item_id'    => TZSS_PRODUCT_ID,
				'url'        => home_url(),
			);

			// Call the custom API.
			$response = wp_remote_post( TZSS_STORE_API_URL, array(
				'timeout'   => 35,
				'sslverify' => true,
				'body'      => $api_params,
			) );

			// make sure the response came back okay.
			if ( is_wp_error( $response ) ) {
				return false;
			}

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

			if ( ! isset( $_POST['tzss_settings'] ) ) {
				return;
			}

			if ( ! isset( $_POST['tzss_deactivate_license'] ) ) {
				return;
			}

			// Get Options.
			$options = $this->get_all();

			// Set License Status to inactive.
			$options['license_status'] = 'inactive';

			// Update Option.
			update_option( 'tzss_settings', $options );

			delete_transient( 'tzss_license_check' );
		}

		/**
		 * Check license key
		 *
		 * @return void
		*/
		public function check_license() {

			if ( ! empty( $_POST['tzss_settings'] ) ) {
				return; // Don't fire when saving settings.
			}

			$status = get_transient( 'tzss_license_check' );

			// Run the license check a maximum of once per day.
			if ( false === $status ) {

				$options = $this->get_all();

				if ( 'inactive' !== $options['license_status'] ) {

					// Data to send in our API request.
					$api_params = array(
						'edd_action' => 'check_license',
						'license'    => TZSS_LICENSE,
						'item_name'  => urlencode( TZSS_NAME ),
						'item_id'    => TZSS_PRODUCT_ID,
						'url'        => home_url(),
					);

					// Call the custom API.
					$response = wp_remote_post( TZSS_STORE_API_URL, array(
						'timeout'   => 25,
						'sslverify' => true,
						'body'      => $api_params,
					) );

					// Make sure the response came back okay.
					if ( is_wp_error( $response ) ) {
						return false;
					}

					$license_data = json_decode( wp_remote_retrieve_body( $response ) );

					$status = $license_data->license;

					// Update Options.
					$options['license_status'] = $status;
					update_option( 'tzss_settings', $options );

				} else {

					$status = 'inactive';

				}

				set_transient( 'tzss_license_check', $status, DAY_IN_SECONDS );
			}

			return $status;
		}
	}

	// Run Setting Class
	TZSS_Settings::instance();

endif;
