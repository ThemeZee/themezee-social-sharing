<?php
/*
Plugin Name: ThemeZee Social Sharing
Plugin URI: https://themezee.com/plugins/social-sharing/
Description: Our simple and user friendly Social Sharing plugin helps you to integrate social sharing buttons for the most popular networks and increase the social reach of your website. Encouraging social sharing was never easier.
Author: ThemeZee
Author URI: https://themezee.com/
Version: 1.1
Text Domain: themezee-social-sharing
Domain Path: /languages/
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

ThemeZee Social Sharing
Copyright(C) 2019, ThemeZee.com - support@themezee.com

*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Use class to avoid namespace collisions
if ( ! class_exists( 'ThemeZee_Social_Sharing' ) ) :

	/**
	 * Main ThemeZee_Social_Sharing Class
	 *
	 * @package ThemeZee Social Sharing
	 */
	class ThemeZee_Social_Sharing {

		/**
		 * Call all Functions to setup the Plugin
		 *
		 * @uses ThemeZee_Social_Sharing::constants() Setup the constants needed
		 * @uses ThemeZee_Social_Sharing::includes() Include the required files
		 * @uses ThemeZee_Social_Sharing::setup_actions() Setup the hooks and actions
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

			// Run Social Sharing Class
			TZSS_Social_Sharing::instance();
		}

		/**
		 * Setup plugin constants
		 *
		 * @return void
		 */
		static function constants() {

			// Define Plugin Name
			define( 'TZSS_NAME', 'ThemeZee Social Sharing' );

			// Define Version Number
			define( 'TZSS_VERSION', '1.1' );

			// Define Plugin Name
			define( 'TZSS_PRODUCT_ID', 56016 );

			// Define Update API URL
			define( 'TZSS_STORE_API_URL', 'https://themezee.com' );

			// Define Plugin Name
			define( 'TZSS_LICENSE', '619a16aa9887cd4b28bd03eb4dd8fd14' );

			// Plugin Folder Path
			define( 'TZSS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

			// Plugin Folder URL
			define( 'TZSS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

			// Plugin Root File
			define( 'TZSS_PLUGIN_FILE', __FILE__ );
		}

		/**
		 * Load Translation File
		 *
		 * @return void
		 */
		static function translation() {
			load_plugin_textdomain( 'themezee-social-sharing', false, dirname( plugin_basename( TZSS_PLUGIN_FILE ) ) . '/languages/' );
		}

		/**
		 * Include required files
		 *
		 * @return void
		 */
		static function includes() {

			// Include Admin Classes
			require_once TZSS_PLUGIN_DIR . '/includes/admin/class-themezee-plugins-page.php';
			require_once TZSS_PLUGIN_DIR . '/includes/admin/class-tzss-plugin-updater.php';

			// Include Settings Classes
			require_once TZSS_PLUGIN_DIR . '/includes/settings/class-tzss-settings.php';
			require_once TZSS_PLUGIN_DIR . '/includes/settings/class-tzss-settings-page.php';

			// Include Social Sharing Class
			require_once TZSS_PLUGIN_DIR . '/includes/class-tzss-social-sharing.php';
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
			add_filter( 'plugin_action_links_' . plugin_basename( TZSS_PLUGIN_FILE ), array( __CLASS__, 'plugin_action_links' ) );

			// Add Social Sharing Box to Plugin Overview Page
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

			// Enqueue Plugin Stylesheet
			wp_enqueue_style( 'themezee-social-sharing', TZSS_PLUGIN_URL . 'assets/css/themezee-social-sharing.css', array(), TZSS_VERSION );

			// Enqueue Social Sharing JS
			wp_enqueue_script( 'themezee-social-sharing', TZSS_PLUGIN_URL . 'assets/js/themezee-social-sharing.js', array( 'jquery' ), TZSS_VERSION );
		}

		/**
		 * Add Settings link to the plugin actions
		 *
		 * @return array $actions Plugin action links
		 */
		static function plugin_action_links( $actions ) {

			$settings_link = array( 'settings' => sprintf( '<a href="%s">%s</a>', admin_url( 'options-general.php?page=themezee-plugins&tab=social' ), __( 'Settings', 'themezee-social-sharing' ) ) );

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
					<span><?php printf( esc_html__( 'Version %s', 'themezee-social-sharing' ), esc_html( $plugin_data['Version'] ) ); ?></span>
				</dt>
				<dd>
					<p><?php echo wp_kses_post( $plugin_data['Description'] ); ?><br/></p>
					<a href="<?php echo admin_url( 'options-general.php?page=themezee-plugins&tab=social' ); ?>" class="button button-primary"><?php esc_html_e( 'Plugin Settings', 'themezee-social-sharing' ); ?></a>&nbsp;
					<a href="<?php echo esc_url( 'https://themezee.com/docs/social-sharing-documentation/?utm_source=plugin-overview&utm_medium=button&utm_campaign=social-sharing&utm_content=documentation' ); ?>" class="button button-secondary" target="_blank"><?php esc_html_e( 'View Documentation', 'themezee-social-sharing' ); ?></a>
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

			// Display only on Plugins and Updates page
			if ( ! ( 'plugins.php' == $pagenow or 'update-core.php' == $pagenow ) ) {
				return;
			}

			// Get Settings
			$options = TZSS_Settings::instance();

			if ( 'valid' !== $options->get( 'license_status' ) ) :
				?>

				<div class="updated">
					<p>
						<?php
						printf( __( 'Please activate your license for the %1$s plugin in order to receive updates and support. <a href="%2$s">Activate License</a>', 'themezee-social-sharing' ),
							TZSS_NAME,
							admin_url( 'options-general.php?page=themezee-plugins&tab=social' )
						);
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

			if ( ! is_admin() ) :
				return;
			endif;

			$options = TZSS_Settings::instance();

			if ( 'valid' === $options->get( 'license_status' ) ) :

				// setup the updater
				$tzss_updater = new TZSS_Plugin_Updater( TZSS_STORE_API_URL, __FILE__, array(
					'version'   => TZSS_VERSION,
					'license'   => TZSS_LICENSE,
					'item_name' => TZSS_NAME,
					'item_id'   => TZSS_PRODUCT_ID,
					'author'    => 'ThemeZee',
				) );

			endif;
		}
	}

	// Run Plugin
	ThemeZee_Social_Sharing::setup();

endif;
