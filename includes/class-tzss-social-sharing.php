<?php
/***
 * Social Sharing Class
 *
 * The main class to display social sharing buttons
 *
 * @package ThemeZee Social Sharing
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays Social Sharing Buttons
 *
 * @access public
 */
class TZSS_Social_Sharing {
	/** Singleton *************************************************************/

	/**
	 * @var instance The one true TZSS_Social_Sharing instance
	 */
	private static $instance;

	/**
	 * Settings for social sharing buttons
	 *
	 * @access public
	 * @var    array
	 */
	public $settings = array();

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @return TZSS_Social_Sharing A single instance of this class.
	 */
	public static function instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Sets up the related posts properties based on function parameters and user options.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		// Get Social Sharing Settings
		$instance       = TZSS_Settings::instance();
		$this->settings = $instance->get_all();

		// Display Social Sharing Buttons on Content
		add_filter( 'the_content', array( $this, 'share_buttons_content' ) );

		// Display Social Sharing Buttons as Sidebar
		add_action( 'wp_footer', array( $this, 'share_buttons_sidebar' ) );
	}

	/**
	 * Adds the social sharing buttons to post content
	 *
	 * @access public
	 * @return string
	 */
	public function share_buttons_content( $content ) {

		// Only display share buttons in single post and pages
		if ( ! ( is_singular() && is_main_query() ) ) {
			return $content;
		}

		// Add Social Icons above Content
		if ( true == $this->settings['locations']['above_content'] ) {
			$content = $this->display_share_buttons( 'above-content' ) . $content;
		}

		// Add Social Icons below Content
		if ( true == $this->settings['locations']['below_content'] ) {
			$content .= $this->display_share_buttons( 'below-content' );
		}

		return $content;
	}

	/**
	 * Adds the social sharing buttons to sidebar
	 *
	 * @access public
	 * @return string
	 */
	public function share_buttons_sidebar() {

		if ( ! ( is_singular() or is_archive() or is_home() ) ) {
			return;
		}

		if ( true == $this->settings['locations']['sidebar'] ) {

			echo $this->display_share_buttons( 'sidebar' );

		}
	}

	/**
	 * Render social sharing buttons
	 *
	 * @access public
	 * @return array
	 */
	private function display_share_buttons( $location ) {

		// Return early if no buttons are there to be displayed
		if ( count( $this->share_buttons() ) < 1 ) {
			return;
		}

		// Wrap social icons list
		$social_sharing = sprintf(
			'<div class="themezee-social-sharing %1$s">%2$s</div>',
			esc_attr( $this->container_class( $location ) ),
			$this->share_buttons_list( $location )
		);

		return $social_sharing;
	}

	/**
	 * Get social sharing container class
	 *
	 * @access public
	 * @return array
	 */
	private function container_class( $location ) {

		// Return early for Sidebar Location
		if ( 'sidebar' == $location ) {
			return 'tzss-sidebar tzss-socicons';
		}

		// Add Content Location Class
		$classes = 'tzss-content tzss-' . $location;

		// Add Styling Class
		if ( 'icons' == $this->settings['style'] ) {

			$classes .= ' tzss-style-icons tzss-socicons';

		} elseif ( 'labels' == $this->settings['style'] ) {

			$classes .= ' tzss-style-labels';

		} else {

			$classes .= ' tzss-style-icons-labels tzss-socicons';
		}

		// Add Number of Columns
		$columns  = count( $this->share_buttons() );
		$classes .= ' tzss-' . $columns . '-columns';

		return $classes;
	}

	/**
	 * Display social sharing button list
	 *
	 * @access public
	 * @return array
	 */
	private function share_buttons_list( $location ) {

		// Get Sharing Buttons
		$buttons = $this->share_buttons();

		$list = '<ul class="tzss-share-buttons-list">';

		foreach ( $buttons as $key => $button ) {

			$list .= '<li class="tzss-share-item">';
			$list .= '<span class="tzss-button tzss-' . $key . '"><a class="tzss-link" href="' . $button['url'] . '" target="_blank">';
			$list .= $this->display_social_icon( $location );
			$list .= $this->display_button_text( $location, $button['title'] );
			$list .= '</a></span>';
			$list .= '</li>';
		}

		$list .= '</ul>';

		return $list;
	}

	/**
	 * Display social icon
	 *
	 * @access public
	 * @return array
	 */
	private function display_social_icon( $location ) {

		// Return early if only labels are displayed.
		if ( 'labels' == $this->settings['style'] && 'sidebar' !== $location ) {
			return;
		}

		$icon = '<span class="tzss-icon"></span>';

		return $icon;
	}

	/**
	 * Display button text
	 *
	 * @access public
	 * @return array
	 */
	private function display_button_text( $location, $title ) {

		if ( 'icons' === $this->settings['style'] || 'sidebar' === $location ) {

			return '<span class="tzss-text screen-reader-text">' . $title . '</span>';

		} elseif ( 'labels' == $this->settings['style'] ) {

			return $title;

		} else {

			return '<span class="tzss-text">' . $title . '</span>';
		}
	}

	/**
	 * Return social sharing buttons
	 *
	 * @access public
	 * @return array
	 */
	private function share_buttons() {

		// Create Button Array
		$buttons = array();

		// Set Shared URL and Title
		if ( is_home() or is_archive() ) {

			// Set Home URL and Blog Title
			$page_url   = esc_url( home_url( '/' ) );
			$page_title = esc_html( get_bloginfo( 'name' ) );
			$media      = '';

		} elseif ( is_singular() ) {

			global $post;

			// Get current page URL.
			$page_url = esc_url( get_permalink( $post->ID ) );

			// Get current page title.
			$page_title = esc_html( str_replace( ' ', '%20', get_the_title( $post->ID ) ) );

			// Get current page image.
			$thumbnail = wp_get_attachment_url( get_post_thumbnail_id( $post->ID ) );
			$media     = $thumbnail ? '&amp;media=' . $thumbnail : '';

		} else {

			return array();
		}

		// Facebook Button.
		if ( isset( $this->settings['networks']['facebook'] ) && true === $this->settings['networks']['facebook'] ) {

			$buttons['facebook'] = array(
				'url'   => 'https://www.facebook.com/sharer/sharer.php?u=' . $page_url . '&amp;t=' . $page_title,
				'title' => 'Facebook',
			);
		}

		// Twitter Button.
		if ( isset( $this->settings['networks']['twitter'] ) && true === $this->settings['networks']['twitter'] ) {

			$buttons['twitter'] = array(
				'url'   => 'https://twitter.com/intent/tweet?text=' . $page_title . '&amp;url=' . $page_url,
				'title' => 'Twitter',
			);
		}

		// WhatsApp Button.
		if ( isset( $this->settings['networks']['whatsapp'] ) && true === $this->settings['networks']['whatsapp'] ) {

			$buttons['whatsapp'] = array(
				'url'   => 'https://wa.me/?text=' . $page_title . ': ' . $page_url,
				'title' => 'WhatsApp',
			);
		}

		// Telegram Button.
		if ( isset( $this->settings['networks']['telegram'] ) && true === $this->settings['networks']['telegram'] ) {

			$buttons['telegram'] = array(
				'url'   => 'https://telegram.me/share/url?url=' . $page_url . '&amp;text=' . $page_title,
				'title' => 'Telegram',
			);
		}

		// Buffer Button.
		if ( isset( $this->settings['networks']['buffer'] ) && true === $this->settings['networks']['buffer'] ) {

			$buttons['buffer'] = array(
				'url'   => 'https://bufferapp.com/add?url=' . $page_url . '&amp;text=' . $page_title,
				'title' => 'Buffer',
			);
		}

		// Pinterest Button.
		if ( isset( $this->settings['networks']['pinterest'] ) && true === $this->settings['networks']['pinterest'] ) {

			$buttons['pinterest'] = array(
				'url'   => 'https://pinterest.com/pin/create/button/?url=' . $page_url . $media . '&amp;description=' . $page_title,
				'title' => 'Pinterest',
			);
		}

		// LinkedIn Button.
		if ( isset( $this->settings['networks']['linkedin'] ) && true === $this->settings['networks']['linkedin'] ) {

			$buttons['linkedin'] = array(
				'url'   => 'https://www.linkedin.com/shareArticle?mini=true&url=' . $page_url . '&amp;title=' . $page_title,
				'title' => 'LinkedIn',
			);
		}

		// Xing Button.
		if ( isset( $this->settings['networks']['xing'] ) && true === $this->settings['networks']['xing'] ) {

			$buttons['xing'] = array(
				'url'   => 'https://www.xing.com/app/user?op=share;url=' . $page_url,
				'title' => 'Xing',
			);
		}

		// Email Button.
		if ( isset( $this->settings['networks']['email'] ) && true === $this->settings['networks']['email'] ) {

			$buttons['email'] = array(
				'url'   => 'mailto:?subject=' . $page_title . '&amp;body=' . $page_url,
				'title' => 'Email',
			);
		}

		return $buttons;
	}
}
