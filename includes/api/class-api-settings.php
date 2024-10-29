<?php // phpcs:ignore Class file names should be based on the class name with "class-" prepended.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class used to manage a plugin's settings functions via the REST API.
 *
 * @link       https://acmethemes.com/
 * @since      1.0.0
 *
 * @package    Acme_Fix_Images
 * @subpackage Acme_Fix_Images/Acme_Fix_Images_Api_Settings
 */

/**
 * Plugin's settings functions via the REST API.
 *
 * @package    Acme_Fix_Images
 * @subpackage Acme_Fix_Images/Acme_Fix_Images_Api_Settings
 * @author     codersantosh <codersantosh@gmail.com>
 *
 * @see Acme_Fix_Images_Api
 */

if ( ! class_exists( 'Acme_Fix_Images_Api_Settings' ) ) {

	/**
	 * Acme_Fix_Images_Api_Settings
	 *
	 * @package Acme_Fix_Images
	 * @since 1.0.0
	 */
	class Acme_Fix_Images_Api_Settings extends Acme_Fix_Images_Api {

		/**
		 * Post per page
		 *
		 * @var integer
		 */
		public $post_per_page = 100;

		/**
		 * Initialize the class and set up actions.
		 *
		 * @access public
		 * @return void
		 */
		public function run() {
			$this->type      = 'acme_fix_images_api_settings';
			$this->rest_base = 'settings';

			/*Custom Rest Routes*/
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}

		/**
		 * Register REST API route.
		 *
		 * @since    1.0.0
		 */
		public function register_routes() {
			$namespace = $this->namespace . $this->version;

			register_rest_route(
				$namespace,
				'/' . $this->rest_base,
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_item' ),
						'args'                => array(),
						'permission_callback' => array( $this, 'get_item_permissions_check' ),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'update_item' ),
						'permission_callback' => array( $this, 'get_item_permissions_check' ),
					),
					'schema' => array( $this, 'get_public_item_schema' ),
				)
			);
		}

		/**
		 * Checks if a given request has access to read and manage settings.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 * @return bool True if the request has read access for the item, otherwise false.
		 */
		public function get_item_permissions_check( $request ) {
			return apply_filters( 'acme_fix_images_has_api_permission', current_user_can( 'manage_options' ), $request );

		}

		/**
		 * Retrieves the image default options.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 * @return array|WP_Error Array on success, or WP_Error object on failure.
		 */
		public function get_item( $request ) {
			return acme_fix_images_default_options();
		}

		/**
		 * Retrieves the attachment ids.
		 * if all is imgType retrive all attachment
		 * otherwise retrive only featured images attachment
		 *
		 * @since 1.0.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 * @return array|WP_Error Array on success, or WP_Error object on failure.
		 */
		public function pre_regen( $request ) {
			$schema = acme_fix_images_admin()->get_settings_schema();

			$params = $request->get_params();

			$sanitized_options = rest_sanitize_value_from_schema( $params, $schema );
			if ( is_wp_error( $sanitized_options ) ) {
				return new WP_Error(
					'rest_invalid_stored_value',
					/* translators: %s: Property name. */
					sprintf( __( 'The %s property has an invalid stored value, and cannot be updated to null.', 'acme-fix-images' ), ACME_FIX_IMAGES_OPTION_NAME ),
					array( 'status' => 500 )
				);
			}

			$total_posts = 0;
			$max_pages   = 0;

			if ( 'all' === $sanitized_options['imgType'] ) {
				$args = array(
					'post_type'      => 'attachment',
					'posts_per_page' => $this->post_per_page,
					'paged'          => $sanitized_options['paged'],
					'post_status'    => 'any',
				);
				$args = apply_filters( 'acme_fix_images_attachment_args', $args, $request );

				$attachment_query = new WP_Query( $args );
				if ( $attachment_query->have_posts() ) {
					while ( $attachment_query->have_posts() ) {
						$attachment_query->the_post();
						$items[] = absint( get_the_ID() );
					}
				}
				$total_posts = $attachment_query->found_posts;

				wp_reset_postdata();

			} elseif ( 'featured' === $sanitized_options['imgType'] ) {
				$post_type_args = array(
					'post_type'      => $sanitized_options['postTypes'],
					'meta_key'       => '_thumbnail_id',//phpcs:ignore
					'posts_per_page' => $this->post_per_page,
					'paged'          => $sanitized_options['paged'],
				);

				$args = apply_filters( 'acme_fix_images_attachment_args', $post_type_args, $request );

				$post_query = new WP_Query( $args );
				if ( $post_query->have_posts() ) {
					while ( $post_query->have_posts() ) {
						$post_query->the_post();
						$items[] = absint( get_post_thumbnail_id( get_the_ID() ) );
					}
				}
				$total_posts = $post_query->found_posts;

				wp_reset_postdata();

			}
			$max_pages = ceil( $total_posts / $this->post_per_page );
			$response  = rest_ensure_response( $items );
			$response->header( 'X-WP-Total', (int) $total_posts );
			$response->header( 'X-WP-TotalPages', (int) $max_pages );

			return $response;
		}

		/**
		 * Return attachments ids for action is pre,
		 * when action is regen, regenerate thumbnails.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 * @return array|WP_Error|null Array on success, or error object on failure, or null if action no match.
		 */
		public function update_item( $request ) {
			$params = $request->get_params();

			if ( 'pre' === $params['action'] ) {
				/* return array of attachment ids */
				return $this->pre_regen( $request );
			} elseif ( 'regen' === $params['action'] ) {
				/*
				Get parameters id, resizeImg deleteOld, and thumbnails.
				action is already processed
				*/
				$id = absint( $params['id'] );
				if ( isset( $params['thumbnails'] ) ) {
					$thumbnails = $params['thumbnails'];
				} else {
					$thumbnails = array_keys( acme_fix_images_get_image_sizes() );
				}

				$thumbnails = array_map( 'sanitize_text_field', $thumbnails );

				if ( isset( $params['resizeImg'] ) ) {
					$resize_image = $params['resizeImg'];
				} else {
					$resize_image = array();

					foreach ( $thumbnails as $size ) {
						$resize_image[ $size ] = array(
							'on'   => true,
							'crop' => true,
						);
					}
				}

				$resize_image_schema = acme_fix_images_admin()->get_resize_image_schema();

				$sanitized_options = rest_sanitize_value_from_schema( $resize_image, $resize_image_schema );

				if ( is_wp_error( $sanitized_options ) ) {
					return new WP_Error(
						'rest_invalid_stored_value',
						/* translators: %s: Property name. */
						sprintf( __( 'The %s property has an invalid stored value, and cannot be updated to null.', 'acme-fix-images' ), ACME_FIX_IMAGES_OPTION_NAME ),
						array( 'status' => 500 )
					);
				}

				if ( $id && $thumbnails && $sanitized_options ) {
					$file_path = get_attached_file( $id );

					if ( false !== $file_path && file_exists( $file_path ) ) {
						set_time_limit( 150 );
						/* Delete old images */
						$deleted_msg = array();
						if ( isset( $params['deleteOld'] ) && $params['deleteOld'] ) {
							$deleted_msg = acme_fix_images_delete_old( $id, $file_path );
						}

						$get_metadata = acme_fix_images_wp_generate_attachment_metadata( $id, $file_path, $thumbnails, $sanitized_options );

						wp_update_attachment_metadata( $id, $get_metadata['metadata'] );

						return rest_ensure_response(
							array(
								'deleted_log' => $deleted_msg,
								'attachment'  => get_post( $id ),
								'created_log' => $get_metadata['msg'],
							)
						);
					}
				}
			}

			return null;
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
	}
}

/**
 * Return instance of  Acme_Fix_Images_Api_Settings class
 *
 * @since 1.0.0
 *
 * @return Acme_Fix_Images_Api_Settings
 */
function acme_fix_images_api_settings() {
	return Acme_Fix_Images_Api_Settings::get_instance();
}
acme_fix_images_api_settings()->run();
