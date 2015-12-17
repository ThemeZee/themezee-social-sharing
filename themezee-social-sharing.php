<?php
/*
Plugin Name: ThemeZee Boilerplate Addon
Plugin URI: http://themezee.com/plugins/boilerplate-addon/
Description: A collection of our most popular widgets, neatly bundled into a single plugin. The Plugin includes advanced widgets for Recent Posts, Recent Comments, Facebook Likebox, Tabbed Content, Social Icons and more.
Author: ThemeZee
Author URI: http://themezee.com/
Version: 1.0
Text Domain: themezee-boilerplate-addon
Domain Path: /languages/
License: GPL v3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

ThemeZee Boilerplate Addon
Copyright(C) 2015, ThemeZee.com - support@themezee.com

*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Use class to avoid namespace collisions
if ( ! class_exists( 'ThemeZee_Boilerplate_Addon' ) ) :


/**
 * Main ThemeZee_Boilerplate_Addon Class
 *
 * @package ThemeZee Boilerplate Addon
 */
class ThemeZee_Boilerplate_Addon {

	/**
	 * Call all Functions to setup the Plugin
	 *
	 * @uses ThemeZee_Boilerplate_Addon::constants() Setup the constants needed
	 * @uses ThemeZee_Boilerplate_Addon::includes() Include the required files
	 * @uses ThemeZee_Boilerplate_Addon::setup_actions() Setup the hooks and actions
	 * @return void
	 */
	static function setup() {
	
		// Setup Constants
		self::constants();
		
		// Setup Translation
		add_action( 'plugins_loaded', array( __CLASS__, 'translation' ) );
	
		// Include Files
		self::includes();
		
		// Setup Action Hooks
		self::setup_actions();
		
	}
	
	
	/**
	 * Setup plugin constants
	 *
	 * @return void
	 */
	static function constants() {
		
		// Define Plugin Name
		define( 'TZBA_NAME', 'ThemeZee Boilerplate Addon' );

		// Define Version Number
		define( 'TZBA_VERSION', '1.0' );
		
		// Define Plugin Name
		define( 'TZBA_PRODUCT_ID', 99999 );

		// Define Update API URL
		define( 'TZBA_STORE_API_URL', 'https://themezee.com' ); 

		// Plugin Folder Path
		define( 'TZBA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

		// Plugin Folder URL
		define( 'TZBA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

		// Plugin Root File
		define( 'TZBA_PLUGIN_FILE', __FILE__ );
		
	}
	
	
	/**
	 * Load Translation File
	 *
	 * @return void
	 */
	static function translation() {

		load_plugin_textdomain( 'themezee-boilerplate-addon', false, dirname( plugin_basename( TZBA_PLUGIN_FILE ) ) . '/languages/' );
		
	}
	
	
	/**
	 * Include required files
	 *
	 * @return void
	 */
	static function includes() {

		// Include Admin Classes
		require_once TZBA_PLUGIN_DIR . '/includes/admin/class-themezee-plugins-page.php';
		require_once TZBA_PLUGIN_DIR . '/includes/admin/class-tzba-plugin-updater.php';
		
		// Include Settings Classes
		require_once TZBA_PLUGIN_DIR . '/includes/settings/class-tzba-settings.php';
		require_once TZBA_PLUGIN_DIR . '/includes/settings/class-tzba-settings-page.php';
		
	}
	
	
	/**
	 * Setup Action Hooks
	 *
	 * @see https://codex.wordpress.org/Function_Reference/add_action WordPress Codex
	 * @return void
	 */
	static function setup_actions() {

		// Enqueue Frontend Widget Styles
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
		
		// Add Settings link to Plugin actions
		add_filter( 'plugin_action_links_' . plugin_basename( TZBA_PLUGIN_FILE ), array( __CLASS__, 'plugin_action_links' ) );
		
		// Add Boilerplate Addon Box to Add-on Overview Page
		add_action( 'themezee_plugins_overview_page', array( __CLASS__, 'plugin_overview_page' ) );
		
		// Add License Key admin notice
		add_action( 'admin_notices', array( __CLASS__, 'license_key_admin_notice' ) );
		
		// Add automatic plugin updater from ThemeZee Store API
		add_action( 'admin_init', array( __CLASS__, 'plugin_updater' ), 0 );
		
	}

	/**
	 * Enqueue Styles
	 *
	 * @return void
	 */
	static function enqueue_styles() {
		
		// Return early if theme handles styling
		if ( current_theme_supports( 'themezee-boilerplate-addon' ) ) :
			return;
		endif;
		
		// Enqueue Plugin Stylesheet
		wp_enqueue_style( 'themezee-boilerplate-addon', TZBA_PLUGIN_URL . 'assets/css/themezee-boilerplate-addon.css', array(), TZBA_VERSION );
		
	}
	
	/**
	 * Add Settings link to the plugin actions
	 *
	 * @return array $actions Plugin action links
	 */
	static function plugin_action_links( $actions ) {

		$settings_link = array( 'settings' => sprintf( '<a href="%s">%s</a>', admin_url( 'themes.php?page=themezee-plugins&tab=boilerplate' ), __( 'Settings', 'themezee-boilerplate-addon' ) ) );
		
		return array_merge( $settings_link, $actions );
	}
	
	/**
	 * Add widget bundle box to plugin overview admin page
	 *
	 * @return void
	 */
	static function plugin_overview_page() { 
	
		$plugin_data = get_plugin_data( __FILE__ );
		
		?>

		<dl>
			<dt>
				<h4><?php echo esc_html( $plugin_data['Name'] ); ?></h4>
				<span><?php printf( esc_html__( 'Version %s', 'themezee-boilerplate-addon' ),  esc_html( $plugin_data['Version'] ) ); ?></span>
			</dt>
			<dd>
				<p><?php echo wp_kses_post( $plugin_data['Description'] ); ?><br/></p>
				<a href="<?php echo admin_url( 'options-general.php?page=themezee-plugins&tab=boilerplate' ); ?>" class="button button-primary"><?php esc_html_e( 'Plugin Settings', 'themezee-boilerplate-addon' ); ?></a>&nbsp;
				<a href="<?php echo esc_url( 'http://themezee.com/docs/boilerplate-addon/' ); ?>" class="button button-secondary" target="_blank"><?php esc_html_e( 'View Documentation', 'themezee-boilerplate-addon' ); ?></a>
			</dd>
		</dl>
		
		<?php
	}
	
	/**
	 * Add license key admin notice
	 *
	 * @return void
	 */
	static function license_key_admin_notice() { 
	
		global $pagenow;
	
		// Display only on Plugins page
		if ( 'plugins.php' !== $pagenow  ) {
			return;
		}
		
		// Get Settings
		$options = TZBA_Settings::instance();
		
		if( '' == $options->get( 'license_key' ) ) : ?>
			
			<div class="updated">
				<p>
					<?php printf( __( 'Please enter your license key for the %1$s add-on in order to receive updates and support. <a href="%2$s">Enter License Key</a>', 'themezee-boilerplate-addon' ),
						TZBA_NAME,
						admin_url( 'themes.php?page=themezee-plugins&tab=boilerplate' ) ); 
					?>
				</p>
			</div>
			
		<?php
		endif;
	
	}
	
	/**
	 * Plugin Updater
	 *
	 * @return void
	 */
	static function plugin_updater() {

		if( ! is_admin() ) :
			return;
		endif;
		
		$options = TZBA_Settings::instance();

		if( $options->get( 'license_key' ) <> '' ) :
			
			$license_key = $options->get( 'license_key' );
			
			// setup the updater
			$tzba_updater = new TZBA_Plugin_Updater( TZBA_STORE_API_URL, __FILE__, array(
					'version' 	=> TZBA_VERSION,
					'license' 	=> $license_key,
					'item_name' => TZBA_NAME,
					'item_id'   => TZBA_PRODUCT_ID,
					'author' 	=> 'ThemeZee'
				)
			);
			
		endif;
		
	}
	
}

// Run Plugin
ThemeZee_Boilerplate_Addon::setup();

endif;