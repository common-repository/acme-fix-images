<?php
/**
 * Reusable functions.
 *
 * @package Acme_Fix_Images
 * @since 1.0.0
 * @author     codersantosh <codersantosh@gmail.com>
 */

if ( ! function_exists( 'acme_fix_images_file_system' ) ) {
	/**
	 *
	 * WordPress file system wrapper
	 *
	 * @since 1.0.0
	 *
	 * @return string|WP_Error directory path or WP_Error object if no permission
	 */
	function acme_fix_images_file_system() {
		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'file.php';
		}

		WP_Filesystem();
		return $wp_filesystem;
	}
}

if ( ! function_exists( 'acme_fix_images_default_options' ) ) :
	/**
	 * Get the Plugin Default Options.
	 *
	 * @since 1.0.0
	 * @return array Default Options
	 * @author     codersantosh <codersantosh@gmail.com>
	 */
	function acme_fix_images_default_options() {
		$default_options = array(
			'action'    => 'pre',
			'imgType'   => 'all',
			'deleteOld' => false,
			'postTypes' => array( 'post', 'page' ),
			'resizeImg' => array(),
			'paged'     => 1,
		);

		$image_sizes = acme_fix_images_get_image_sizes();
		if ( ! empty( $image_sizes ) ) {
			$default_options['resizeImg'] = array();

			foreach ( $image_sizes as $key => $value ) {
				$default_options['resizeImg'][ $key ]['on']   = true;
				$default_options['resizeImg'][ $key ]['crop'] = rest_sanitize_boolean( $value['crop'] );
			}
		}

		return apply_filters( 'acme_fix_images_default_options', $default_options );
	}
endif;

if ( ! function_exists( 'acme_fix_images_get_image_sizes' ) ) :

	/**
	 * Get image sizes.
	 *
	 * @since Acme Fix Images 1.0.0
	 *
	 * @return array
	 */
	function acme_fix_images_get_image_sizes() {
		global $_wp_additional_image_sizes;

		$sizes = array();
		foreach ( get_intermediate_image_sizes() as $s ) {
			$sizes[ $s ] = array(
				'name'   => '',
				'width'  => '',
				'height' => '',
				'crop'   => false,
			);

			/* Read theme added sizes or fall back to default sizes set in options... */
			$sizes[ $s ]['name'] = $s;

			if ( isset( $_wp_additional_image_sizes[ $s ]['width'] ) ) {
				$sizes[ $s ]['width'] = intval( $_wp_additional_image_sizes[ $s ]['width'] );
			} else {
				$sizes[ $s ]['width'] = get_option( "{$s}_size_w" );
			}

			if ( isset( $_wp_additional_image_sizes[ $s ]['height'] ) ) {
				$sizes[ $s ]['height'] = intval( $_wp_additional_image_sizes[ $s ]['height'] );
			} else {
				$sizes[ $s ]['height'] = get_option( "{$s}_size_h" );
			}

			if ( isset( $_wp_additional_image_sizes[ $s ]['crop'] ) ) {
				$sizes[ $s ]['crop'] = intval( $_wp_additional_image_sizes[ $s ]['crop'] );
			} else {
				$sizes[ $s ]['crop'] = get_option( "{$s}_crop" );
			}
		}

		return apply_filters( 'acme_fix_images_get_image_sizes', $sizes );
	}
endif;

if ( ! function_exists( 'acme_fix_images_wp_generate_attachment_metadata' ) ) :

	/**
	 * Generate post thumbnail attachment meta data.
	 * Copy of wp_generate_attachment_metadata
	 *
	 * @since 1.0.0
	 *
	 * @param int    $attachment_id Attachment Id to process.
	 * @param string $file Filepath of the Attached image.
	 * @param array  $allowed_sizes Allowed image sizes.
	 * @param array  $thumbnail_data Array of thumbnail crop data.
	 * @return array Message and Metadata for attachment.
	 */
	function acme_fix_images_wp_generate_attachment_metadata( $attachment_id, $file, $allowed_sizes = null, $thumbnail_data = array() ) {
		$attachment = get_post( $attachment_id );
		if ( ! function_exists( 'file_is_displayable_image' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		$msg      = array();
		$metadata = array();

		if ( preg_match( '!^image/!', get_post_mime_type( $attachment ) ) && file_is_displayable_image( $file ) ) {

			$imagesize = wp_getimagesize( $file );
			if ( ! empty( $imagesize ) ) {
				$metadata['width']          = $imagesize[0];
				$metadata['height']         = $imagesize[1];
				list( $uwidth, $uheight )   = wp_constrain_dimensions( $metadata['width'], $metadata['height'], 128, 96 );
				$metadata['hwstring_small'] = "height='$uheight' width='$uwidth'";

				// Make the file path relative to the upload dir.
				$metadata['file'] = _wp_relative_upload_path( $file );

				$sizes = acme_fix_images_get_image_sizes();

				foreach ( $sizes as $size => $size_data ) {
					if ( isset( $allowed_sizes ) && $allowed_sizes && is_array( $allowed_sizes ) && ! in_array( $size, $allowed_sizes, true ) ) {
						$msg[]             = esc_html__( 'Size not selected: ', 'acme-fix-images' ) . esc_html( $size );
						$intermediate_size = image_get_intermediate_size( $attachment_id, $size_data['name'] );

					} else {
						$msg[]             = esc_html__( 'Regenerated: ', 'acme-fix-images' ) . esc_html( $size );
						$intermediate_size = image_make_intermediate_size( $file, $size_data['width'], $size_data['height'], $thumbnail_data[ $size ]['crop'] );
					}

					if ( $intermediate_size ) {
						$metadata['sizes'][ $size ] = $intermediate_size;
					} else {
						$msg[] = esc_html__( 'Error/Unsupported: ', 'acme-fix-images' ) . esc_html( $size );
					}
				}

				// Fetch additional metadata from exif/iptc.
				$image_meta = wp_read_image_metadata( $file );
				if ( $image_meta ) {
					$metadata['image_meta'] = $image_meta;
				}
			} else {
				$msg[] = esc_html__( 'Error reading image size for attachment with id: ', 'acme-fix-images' ) . esc_html( $attachment_id );
			}
		} else {
			$msg[] = esc_html__( 'Attachment should be an image. ID: ', 'acme-fix-images' ) . esc_html( $attachment_id );
		}

		return apply_filters(
			'acme_fix_images_wp_generate_attachment_metadata',
			array(
				'msg'      => $msg,
				'metadata' => $metadata,
			),
			$attachment_id,
			$file,
			$allowed_sizes,
			$thumbnail_data
		);
	}
endif;

if ( ! function_exists( 'acme_fix_images_delete_old' ) ) :
	/**
	 * Delete old images.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $attachment_id Attachment Id to process.
	 * @param string $original_file_path Original file path.
	 * @return string message from the process.
	 */
	function acme_fix_images_delete_old( $attachment_id, $original_file_path ) {
		if ( ! function_exists( 'file_is_displayable_image' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$msg        = array();
		$attachment = get_post( $attachment_id );

		$filesystem = acme_fix_images_file_system();

		if ( preg_match( '!^image/!', get_post_mime_type( $attachment ) ) && file_is_displayable_image( $original_file_path ) ) {
			$attachment_meta = wp_get_attachment_metadata( $attachment_id );

			/* Deleting thumbnails */
			if ( isset( $attachment_meta['sizes'] ) && is_array( $attachment_meta['sizes'] ) ) {
				foreach ( $attachment_meta['sizes'] as $size_data ) {
					if ( empty( $size_data['file'] ) ) {
						continue;
					}
					$file_dir  = trailingslashit( dirname( $original_file_path ) );
					$file_path = $file_dir . wp_basename( $size_data['file'] );

					$did_process = apply_filters( 'acme_fix_images_delete_old', false, $file_path, $attachment_id );

					if ( $did_process ) {
						$msg[] = $did_process;
						continue;
					}

					if ( $file_path !== $original_file_path ) {
						if ( $filesystem->exists( $file_path ) ) {
							if ( $filesystem->delete( $file_path ) ) {
								$msg[] = esc_html__( 'Deleted: ', 'acme-fix-images' ) . sprintf( '%dx%d', $size_data['width'], $size_data['height'] );

							} else {
								$msg[] = esc_html__( 'Delete failed: ', 'acme-fix-images' ) . sprintf( '%dx%d', $size_data['width'], $size_data['height'] );
							}
						} else {
							$msg[] = esc_html__( 'Not exists: ', 'acme-fix-images' ) . sprintf( '%dx%d', $size_data['width'], $size_data['height'] );
						}
					} else {
						$msg[] = esc_html__( 'Original file cannot be deleted', 'acme-fix-images' );
					}
				}
			} else {
				$msg[] = esc_html__( 'Not any sizes to delete.', 'acme-fix-images' );
			}
		} else {
			$msg[] = esc_html__( 'Attachment should be image.', 'acme-fix-images' );
		}

		return apply_filters( 'acme_fix_images_delete_old', $msg, $attachment_id, $original_file_path );
	}
endif;

if ( ! function_exists( 'acme_fix_images_get_white_label' ) ) :
	/**
	 * Get white label options for this plugin.
	 *
	 * @since 1.0.0
	 * @param string $key optional option key.
	 * @return mixed All Options Array Or Options Value
	 * @author     codersantosh <codersantosh@gmail.com>
	 */
	function acme_fix_images_get_white_label( $key = '' ) {
		$options = apply_filters(
			'acme_fix_images_white_label',
			array(
				'admin_menu_page' => array(
					'page_title' => esc_html__( 'Acme Fix Images', 'acme-fix-images' ),
					'menu_title' => esc_html__( 'Acme Fix Images', 'acme-fix-images' ),
					'menu_slug'  => ACME_FIX_IMAGES_PLUGIN_NAME,
					'position'   => null,
				),
				'dashboard'       => array(
					'logo' => ACME_FIX_IMAGES_URL . 'assets/img/logo.png',
				),
			)
		);
		if ( ! empty( $key ) ) {
			return $options[ $key ];
		} else {
			return $options;
		}
	}
endif;
