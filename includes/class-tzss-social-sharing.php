<?php
/***
 * Social Sharing Class
 *
 * The main class to display social sharing buttons
 *
 * @package ThemeZee Social Sharing
 */
 
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


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
		$instance = TZSS_Settings::instance();
		$this->settings = $instance->get_all();
		
		// Display Social Sharing Buttons on Content
		add_filter( 'the_content', array( $this, 'share_buttons_content' ) );

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
		if( true == $this->settings['locations']['above_content'] ) {
		
			$content = $this->display_share_buttons( 'above-content' ) . $content;
			
		}
		
		// Add Social Icons below Content
		if( true == $this->settings['locations']['below_content'] ) {
		
			$content .= $this->display_share_buttons( 'below-content' );
			
		}
		
		return $content;
		
	}
	
	/**
	 * Render social sharing buttons
	 *
	 * @access public
	 * @return array
	 */
	private function display_share_buttons( $location ) {
	
		// Return early if no buttons are there to be displayed
		if( count( $this->share_buttons() ) < 1 ) {
			return;
		}
		
		// Wrap social icons list
		$social_sharing = sprintf(
			'<div class="themezee-social-sharing tzss-%1$s %2$s tzss-clearfix">%3$s</div>',
			esc_attr( $location ), 
			esc_attr( $this->container_class() ), 
			$this->share_buttons_list()
		);
		
		return $social_sharing;
		
	}
	
	/**
	 * Get social sharing container class
	 *
	 * @access public
	 * @return array
	 */
	private function container_class() {
		
		$classes = '';
		
		if( true == $this->settings['labels'] ) {
		
			$classes = 'tzss-network-labels';
			
		}
		
		$columns = count( $this->share_buttons() );
		$classes .= ' tzss-' . $columns . '-columns';
		
		return $classes;
		
	}
	
	/**
	 * Display social sharing button list
	 *
	 * @access public
	 * @return array
	 */
	private function share_buttons_list() {
	
		// Get Sharing Buttons
		$buttons = $this->share_buttons();
		
		$list = '<ul class="tzss-share-buttons-list">';
		
		foreach( $buttons as $key => $button ) {
		
			$list .= '<li class="tzss-share-item">';
			$list .= '<span class="tzss-button tzss-' . $key . '"><a class="tzss-link" href="'. $button['url'] .'" target="_blank">' . $button['title'] . '</a></span>';
			$list .= '</li>';
		}
		
		$list .= '</ul>';
		
		return $list;
		
	}
	
	
	/**
	 * Return social sharing buttons
	 *
	 * @access public
	 * @return array
	 */
	private function share_buttons() {
	
		// Get current page URL 
		$page_url = get_permalink();
 
		// Get current page title
		$page_title = str_replace( ' ', '%20', get_the_title() );
	
		// Create Button Array
		$buttons = array();
		
		// Facebook Button
		if( true == $this->settings['networks']['facebook'] ) {
			
			$buttons['facebook'] = array(
				'url' => 'https://www.facebook.com/sharer/sharer.php?u=' . $page_url,
				'title' => 'Facebook',
			);
			
		}
		
		// Twitter Button
		if( true == $this->settings['networks']['twitter'] ) {
			
			$buttons['twitter'] = array(
				'url' => 'https://twitter.com/intent/tweet?text=' . $page_title . '&amp;url=' .$page_url,
				'title' => 'Twitter',
			);
			
		}
		
		// Google+ Button
		if( true == $this->settings['networks']['googleplus'] ) {
			
			$buttons['googleplus'] = array(
				'url' => 'https://plus.google.com/share?url=' . $page_url . '&amp;t=' .$page_title,
				'title' => 'Google+',
			);
			
		}
		
		// Buffer Button
		if( true == $this->settings['networks']['buffer'] ) {
			
			$buttons['buffer'] = array(
				'url' => 'https://bufferapp.com/add?url=' . $page_url . '&amp;text=' . $page_title,
				'title' => 'Buffer',
			);
			
		}
		
		// LinkedIn Button
		if( true == $this->settings['networks']['linkedin'] ) {
			
			$buttons['linkedin'] = array(
				'url' => 'https://www.linkedin.com/shareArticle?mini=true&amp;url=' . $page_url . '&amp;text='  . $page_title,
				'title' => 'LinkedIn',
			);
			
		}
		
		// Pinterest Button
		if( true == $this->settings['networks']['pinterest'] ) {
			
			$thumbnail = get_the_post_thumbnail();
			
			$buttons['pinterest'] = array(
				'url' => 'https://pinterest.com/pin/create/button/?url=' . $page_url . '&amp;media=' . $thumbnail[0] . '&amp;description=' . $page_title,
				'title' => 'Pinterest',
			);
			
		}
		
		// StumbleUpon Button
		if( true == $this->settings['networks']['stumbleupon'] ) {
			
			$thumbnail = get_the_post_thumbnail();
			
			$buttons['stumbleupon'] = array(
				'url' => 'http://www.stumbleupon.com/badge?url=' . $page_url . '&amp;title=' . $page_title,
				'title' => 'StumbleUpon',
			);
			
		}
		
		// Email Button
		if( true == $this->settings['networks']['email'] ) {
			
			$thumbnail = get_the_post_thumbnail();
			
			$buttons['email'] = array(
				'url' => 'mailto:?subject=' . $page_title . '&amp;body='. $page_url,
				'title' => 'Email',
			);
			
		}
		
		return $buttons;
		
	}
	
}