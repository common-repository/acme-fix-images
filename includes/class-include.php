<?php // phpcs:ignore Class file names should be based on the class name with "class-" prepended.
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The common bothend functionality of the plugin.
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://acmethemes.com/
 * @since      1.0.0
 *
 * @package    Acme_Fix_Images
 * @subpackage Acme_Fix_Images/includes
 */

/**
 * The common bothend functionality of the plugin.
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @since      1.0.0
 * @package    Acme_Fix_Images
 * @subpackage Acme_Fix_Images/includes
 * @author     codersantosh <codersantosh@gmail.com>
 */
class Acme_Fix_Images_Include {

	/**
	 * Static property to store white label settings
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    settings All settings for this plugin.
	 */
	private static $white_label = null;

	/**
	 * Gets an instance of this object.
	 * Prevents duplicate instances which avoid artefacts and improves performance.
	 *
	 * @static
	 * @access public
	 * @return object
	 * @since 1.0.0
	 */
	public static function get_instance() {
		// Store the instance locally to avoid private static replication.
		static $instance = null;

		// Only run these methods if they haven't been ran previously.
		if ( null === $instance ) {
			/* Query only once */
			self::$white_label = acme_fix_images_get_white_label();

			$instance = new self();
		}

		// Always return the instance.
		return $instance;
	}

	/**
	 * Get options related to white label.
	 *
	 * @access public
	 * @return array|null
	 */
	public function get_white_label() {
		return self::$white_label;
	}

	/**
	 * Register scripts and styles
	 *
	 * @since    1.0.0
	 * @access   public
	 * @return void
	 */
	public function register_scripts_and_styles() {
		/* Atomic css */
		wp_register_style( 'atomic', ACME_FIX_IMAGES_URL . 'assets/library/atomic-css/atomic.min.css', array(), ACME_FIX_IMAGES_VERSION );
	}
}

if ( ! function_exists( 'acme_fix_images_include' ) ) {
	/**
	 * Return instance of  Acme_Fix_Images_Include class
	 *
	 * @since 1.0.0
	 *
	 * @return Acme_Fix_Images_Include
	 */
	function acme_fix_images_include() {
		return Acme_Fix_Images_Include::get_instance();
	}
}
