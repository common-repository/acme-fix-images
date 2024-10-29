<?php // phpcs:ignore Class file names should be based on the class name with "class-" prepended.
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://acmethemes.com/
 * @since      1.0.0
 *
 * @package    Acme_Fix_Images
 * @subpackage Acme_Fix_Images/Admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Define and execute the hooks for overall functionalities of the plugin and add the admin end like loading resources and defining settings.
 *
 * @package    Acme_Fix_Images
 * @subpackage Acme_Fix_Images/Admin
 * @author     codersantosh <codersantosh@gmail.com>
 */
class Acme_Fix_Images_Admin {

	/**
	 * Menu info.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $menu_info    Admin menu information.
	 */
	private $menu_info;

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
	 * Add Admin Page Menu page.
	 *
	 * @access public
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menu() {

		$white_label     = acme_fix_images_include()->get_white_label();
		$this->menu_info = $white_label['admin_menu_page'];

		add_submenu_page(
			'themes.php',
			$this->menu_info['page_title'],
			$this->menu_info['menu_title'],
			'manage_options',
			$this->menu_info['menu_slug'],
			array( $this, 'add_setting_root_div' ),
			$this->menu_info['position'],
		);
	}

	/**
	 * Add Root Div For React.
	 *
	 * @access public
	 *
	 * @since    1.0.0
	 */
	public function add_setting_root_div() {
		echo '<div id="' . esc_attr( ACME_FIX_IMAGES_PLUGIN_NAME ) . '"></div>';
	}

	/**
	 * Register the CSS/JavaScript Resources for the admin area.
	 *
	 * @access public
	 * Use Condition to Load it Only When it is Necessary
	 *
	 * @since    1.0.0
	 */
	public function enqueue_resources() {

		$screen              = get_current_screen();
		$admin_scripts_bases = array( 'appearance_page_' . ACME_FIX_IMAGES_PLUGIN_NAME );
		if ( ! ( isset( $screen->base ) && in_array( $screen->base, $admin_scripts_bases, true ) ) ) {
			return;
		}

		/* Atomic CSS */
		wp_enqueue_style( 'atomic' );

		/*Scripts dependency files*/
		$deps_file = ACME_FIX_IMAGES_PATH . 'build/admin/admin.asset.php';

		/*Fallback dependency array*/
		$dependency = array();
		$version    = ACME_FIX_IMAGES_VERSION;

		/*Set dependency and version*/
		if ( file_exists( $deps_file ) ) {
			$deps_file  = require $deps_file;
			$dependency = $deps_file['dependencies'];
			$version    = $deps_file['version'];
		}

		wp_enqueue_script( ACME_FIX_IMAGES_PLUGIN_NAME, ACME_FIX_IMAGES_URL . 'build/admin/admin.js', $dependency, $version, true );

		wp_enqueue_style( 'google-fonts-open-sans', ACME_FIX_IMAGES_URL . 'assets/library/fonts/open-sans.css', '', $version );
		wp_enqueue_style( ACME_FIX_IMAGES_PLUGIN_NAME, ACME_FIX_IMAGES_URL . 'build/admin/admin.css', array( 'wp-components' ), $version );

		/* Localize */
		$localize = apply_filters(
			'acme_fix_images_admin_localize',
			array(
				'version'             => $version,
				'root_id'             => ACME_FIX_IMAGES_PLUGIN_NAME,
				'nonce'               => wp_create_nonce( 'wp_rest' ),
				'store'               => 'acme-fix-images',
				'rest_url'            => get_rest_url(),
				'base_url'            => menu_page_url( $this->menu_info['menu_slug'], false ),
				'ACME_FIX_IMAGES_URL' => ACME_FIX_IMAGES_URL,
				'white_label'         => acme_fix_images_include()->get_white_label(),
				'img_sizes'           => acme_fix_images_get_image_sizes(),
			)
		);

		wp_set_script_translations( ACME_FIX_IMAGES_PLUGIN_NAME, ACME_FIX_IMAGES_PLUGIN_NAME );
		wp_localize_script( ACME_FIX_IMAGES_PLUGIN_NAME, 'acmeFixImagesLocalize', $localize );
	}

	/**
	 * Get resize image schema
	 *
	 * @access public
	 *
	 * @since 1.0.0
	 *
	 * @return array image setting props.
	 */
	public function get_resize_image_schema() {

		$image_sizes           = acme_fix_images_get_image_sizes();
		$resize_img_properties = array();
		if ( ! empty( $image_sizes ) ) {
			foreach ( $image_sizes as $key => $value ) {
				$resize_img_properties[ $key ] = array(
					'type'       => 'object',
					'properties' => array(
						'on'   => array( 'type' => 'boolean' ),
						'crop' => array( 'type' => 'boolean' ),
					),
				);
			}
		}
		return array(
			'type'       => 'object',
			'properties' => $resize_img_properties,
		);
	}

	/**
	 * Get settings schema
	 * Schema: http://json-schema.org/draft-04/schema#
	 *
	 * @access public
	 *
	 * @since 1.0.0
	 *
	 * @return array settings schema for this plugin.
	 */
	public function get_settings_schema() {

		$setting_properties = apply_filters(
			'acme_fix_images_setting_properties',
			array(
				'action'    => array(
					'type' => 'string',
					'enum' => array(
						'pre',
						'regen',
					),
				),
				'imgType'   => array(
					'type' => 'string',
					'enum' => array(
						'all',
						'featured',
					),
				),
				'deleteOld' => array( 'type' => 'boolean' ),
				'postTypes' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
				'resizeImg' => $this->get_resize_image_schema(),
				'paged'     => array(
					'type' => 'integer',
				),
			),
		);

		return array(
			'type'       => 'object',
			'properties' => $setting_properties,
		);
	}

	/**
	 * Add  button to the media page
	 *
	 * @param array   $form_fields An array of attachment form fields.
	 * @param WP_Post $post        The WP_Post attachment object.
	 * @return array
	 */
	public function fix_image_single( $form_fields, $post ) {
		ob_start();
		?>
			<script>
				function acmeFixImageSetMessage(msg) {
					jQuery("#acme-fix-images-update-msg").html(msg);
					jQuery("#acme-fix-images-update-msg").show();
				}

				function acmeFixImageRegenerate() {
					jQuery("#acme_fix_images").prop("disabled", true);
					acmeFixImageSetMessage("<?php esc_html_e( 'Reading attachments...', 'acme-fix-images' ); ?>");
					jQuery.ajax({
						url: "<?php echo esc_url( get_rest_url() . 'acme-fix-images/v1/settings' ); ?>",
						type: "POST",
						beforeSend: function(xhr) {
							xhr.setRequestHeader('X-WP-Nonce', '<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>');
						},
						data: "action=regen&id=<?php echo absint( $post->ID ); ?>",
						success: function(result) {
							if (result != '-1') {
								acmeFixImageSetMessage("<?php esc_html_e( 'Done.', 'acme-fix-images' ); ?>");
							}
						},
						error: function(request, status, error) {
							acmeFixImageSetMessage("<?php esc_html_e( 'Error', 'acme-fix-images' ); ?>" + request.status);
						},
						complete: function() {
							jQuery("#acme_fix_images").prop("disabled", false);
						}
					});
				}
			</script>
			<input type='button' onclick='javascript:acmeFixImageRegenerate();' class='button' name='acme_fix_images' id='acme_fix_images' value='<?php esc_html_e( 'Fix Images', 'acme-fix-images' ); ?>'>
			<span id="acme-fix-images-update-msg" class="updated fade" style="clear:both;display:none;line-height:28px;padding-left:10px;"></span>
			<?php
			$html                           = ob_get_clean();
			$form_fields['acme-fix-images'] = array(
				'label' => __( 'Fix Images', 'acme-fix-images' ),
				'input' => 'html',
				'html'  => $html,
			);
			return $form_fields;
	}

	/**
	 * Add plugin menu items.
	 *
	 * @access public
	 *
	 * @since 1.0.0
	 * @param string[] $actions     An array of plugin action links. By default this can include
	 *                              'activate', 'deactivate', and 'delete'. With Multisite active
	 *                              this can also include 'network_active' and 'network_only' items.
	 * @param string   $plugin_file Path to the plugin file relative to the plugins directory.
	 * @param array    $plugin_data An array of plugin data. See get_plugin_data()
	 *                              and the {@see 'plugin_row_meta'} filter for the list
	 *                              of possible values.
	 * @param string   $context     The plugin context. By default this can include 'all',
	 *                              'active', 'inactive', 'recently_activated', 'upgrade',
	 *                              'mustuse', 'dropins', and 'search'.
	 * @return array settings schema for this plugin.
	 */
	public function add_plugin_links( $actions, $plugin_file, $plugin_data, $context ) {
		$actions[] = '<a href="' . esc_url( menu_page_url( $this->menu_info['menu_slug'], false ) ) . '">Settings</a>';
		return $actions;
	}
}

if ( ! function_exists( 'acme_fix_images_admin' ) ) {
	/**
	 * Return instance of  Acme_Fix_Images_Admin class
	 *
	 * @since 1.0.0
	 *
	 * @return Acme_Fix_Images_Admin
	 */
	function acme_fix_images_admin() {
		return Acme_Fix_Images_Admin::get_instance();
	}
}
