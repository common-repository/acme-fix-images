<?php // phpcs:ignore Class file names should be based on the class name with "class-" prepended.
/**
 * The parent class of all api class of this plugin.
 *
 * @link       https://acmethemes.com/
 * @since      1.0.0
 *
 * @package    Acme_Fix_Images
 * @subpackage Acme_Fix_Images/includes/api
 */

/**
 * The common variables and methods of api of the plugin.
 *
 * Define namespace, vresion and other common properties and methods.
 *
 * @package    Acme_Fix_Images
 * @subpackage Acme_Fix_Images/includes/api
 * @author     codersantosh <codersantosh@gmail.com>
 */
if ( ! class_exists( 'Acme_Fix_Images_Api' ) ) {

	/**
	 * Acme_Fix_Images_Api
	 *
	 * @package Acme_Fix_Images
	 * @since 1.0.0
	 */
	class Acme_Fix_Images_Api extends WP_Rest_Controller {

		/**
		 * Rest route namespace.
		 *
		 * @var Acme_Fix_Images_Api
		 */
		public $namespace = 'acme-fix-images/';

		/**
		 * Rest route version.
		 *
		 * @var Acme_Fix_Images_Api
		 */
		public $version = 'v1';

		/**
		 * Whether the controller supports batching.
		 *
		 * @since 1.0.0
		 * @var array
		 */
		protected $allow_batch = array( 'v1' => true );

		/**
		 * Table name.
		 *
		 * @var string
		 */
		public $type;

		/**
		 * Constructor
		 *
		 * @since    1.0.0
		 */
		public function __construct() {}

		/**
		 * Initialize the class and set up actions.
		 *
		 * @access public
		 * @return void
		 */
		public function run() {
			/*Custom Rest Routes*/
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}


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

				$instance = new self();
			}

			// Always return the instance.
			return $instance;
		}

		/**
		 * Throw error on object clone
		 *
		 * The whole idea of the singleton design pattern is that there is a single
		 * object therefore, we don't want the object to be cloned.
		 *
		 * @access public
		 * @return void
		 * @since 1.0.0
		 */
		public function __clone() {
			// Cloning instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'acme-fix-images' ), '1.0.0' );
		}

		/**
		 * Disable unserializing of the class
		 *
		 * @access public
		 * @return void
		 * @since 1.0.0
		 */
		public function __wakeup() {
			// Unserializing instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'acme-fix-images' ), '1.0.0' );
		}
	}
}
