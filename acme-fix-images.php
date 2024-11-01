<?php
/**
 * The plugin main file.
 *
 * @link              https://acmethemes.com/
 * @since             1.0.0
 * @package           Acme_Fix_Images
 *
 * Plugin Name:       Acme Fix Images - Regenerate Thumbnails
 * Description:       Fix image sizes after you have changed image sizes from Media Settings.
 * Version:           2.0.3
 * Author:            acmethemes
 * Author URI:        https://acmethemes.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       acme-fix-images
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin path.
 * Current plugin url.
 * Current plugin version.
 * Current plugin name.
 * Current plugin option name.
 */
define( 'ACME_FIX_IMAGES_PATH', plugin_dir_path( __FILE__ ) );
define( 'ACME_FIX_IMAGES_URL', plugin_dir_url( __FILE__ ) );
define( 'ACME_FIX_IMAGES_VERSION', '2.0.3' );
define( 'ACME_FIX_IMAGES_PLUGIN_NAME', 'acme-fix-images' );
define( 'ACME_FIX_IMAGES_OPTION_NAME', 'acme_fix_images_options' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class--activator.php
 */
function activate_acme_fix_images() {

	require_once ACME_FIX_IMAGES_PATH . 'includes/class-activator.php';
	Acme_Fix_Images_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-deactivator.php
 */
function deactivate_acme_fix_images() {
	require_once ACME_FIX_IMAGES_PATH . 'includes/class-deactivator.php';
	Acme_Fix_Images_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_acme_fix_images' );
register_deactivation_hook( __FILE__, 'deactivate_acme_fix_images' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require ACME_FIX_IMAGES_PATH . 'includes/main.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_acme_fix_images() {

	$plugin = new Acme_Fix_Images();
	$plugin->run();
}
run_acme_fix_images();
