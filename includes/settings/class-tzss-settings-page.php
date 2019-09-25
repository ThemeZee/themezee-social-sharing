<?php
/***
 * TZSS Settings Page Class
 *
 * Adds a new tab on the themezee plugins page and displays the settings page.
 *
 * @package ThemeZee Social Sharing
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Use class to avoid namespace collisions
if ( ! class_exists( 'TZSS_Settings_Page' ) ) :

	class TZSS_Settings_Page {

		/**
		 * Setup the Settings Page class
		 *
		 * @return void
		*/
		static function setup() {

			// Add settings page to plugin tabs
			add_filter( 'themezee_plugins_settings_tabs', array( __CLASS__, 'add_settings_page' ) );

			// Hook settings page to plugin page
			add_action( 'themezee_plugins_page_social', array( __CLASS__, 'display_settings_page' ) );
		}

		/**
		 * Add settings page to tabs list on themezee add-on page
		 *
		 * @return void
		*/
		static function add_settings_page( $tabs ) {

			// Add Social Sharing Settings Page to Tabs List
			$tabs['social'] = esc_html__( 'Social Sharing', 'themezee-social-sharing' );

			return $tabs;
		}

		/**
		 * Display settings page
		 *
		 * @return void
		*/
		static function display_settings_page() {
			ob_start();
			?>

			<div id="tzss-settings" class="tzss-settings-wrap">

				<h1><?php esc_html_e( 'Social Sharing', 'themezee-social-sharing' ); ?></h1>

				<form class="tzss-settings-form" method="post" action="options.php">
					<?php
						settings_fields( 'tzss_settings' );
						do_settings_sections( 'tzss_settings' );
						submit_button();
					?>
				</form>

			</div>

			<?php
			echo ob_get_clean();
		}
	}

	// Run TZSS Settings Page Class
	TZSS_Settings_Page::setup();

endif;
