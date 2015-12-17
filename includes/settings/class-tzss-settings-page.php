<?php
/***
 * TZBA Settings Page Class
 *
 * Adds a new tab on the themezee plugins page and displays the settings page.
 *
 * @package ThemeZee Boilerplate Addon
 */
 
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


// Use class to avoid namespace collisions
if ( ! class_exists('TZBA_Settings_Page') ) :

class TZBA_Settings_Page {

	/**
	 * Setup the Settings Page class
	 *
	 * @return void
	*/
	static function setup() {
		
		// Add settings page to plugin tabs
		add_filter( 'themezee_plugins_settings_tabs', array( __CLASS__, 'add_settings_page' ) );
		
		// Hook settings page to plugin page
		add_action( 'themezee_plugins_page_boilerplate', array( __CLASS__, 'display_settings_page' ) );
	}

	/**
	 * Add settings page to tabs list on themezee add-on page
	 *
	 * @return void
	*/
	static function add_settings_page($tabs) {
			
		// Add Boilerplate Settings Page to Tabs List
		$tabs['boilerplate']      = esc_html__( 'Boilerplate', 'themezee-boilerplate-addon' );
		
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
		
		<div id="tzba-settings" class="tzba-settings-wrap">
			
			<h1><?php esc_html_e( 'Boilerplate', 'themezee-boilerplate-addon' ); ?></h1>
			
			<form class="tzba-settings-form" method="post" action="options.php">
				<?php
					settings_fields('tzba_settings');
					do_settings_sections('tzba_settings');
					submit_button();
				?>
			</form>
			
		</div>
<?php
		echo ob_get_clean();
	}
	
}

// Run TZBA Settings Page Class
TZBA_Settings_Page::setup();

endif;