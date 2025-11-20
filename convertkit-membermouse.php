<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://www.kit.com
 * @since             1.0.0
 * @package           ConvertKit_MM
 *
 * @wordpress-plugin
 * Plugin Name:       Kit (formerly ConvertKit) for MemberMouse
 * Plugin URI:        http://www.kit.com
 * Description:       This plugin integrates Kit with MemberMouse.
 * Version:           1.3.5
 * Author:            Kit
 * Author URI:        https://kit.com
 * License:           GPLv3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       convertkit-mm
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define ConverKit Plugin paths and version number.
define( 'CONVERTKIT_MM_NAME', 'convertkit-mm' ); // Used for settings.
define( 'CONVERTKIT_MM_FILE', plugin_basename( __FILE__ ) );
define( 'CONVERTKIT_MM_URL', plugin_dir_url( __FILE__ ) );
define( 'CONVERTKIT_MM_PATH', plugin_dir_path( __FILE__ ) );
define( 'CONVERTKIT_MM_VERSION', '1.3.5' );
define( 'CONVERTKIT_MM_OAUTH_CLIENT_ID', 'U4aHnnj_QgRrZOdtWUJ6vtpulZSloLKn-7e551T-Exw' );
define( 'CONVERTKIT_MM_OAUTH_CLIENT_REDIRECT_URI', 'https://app.kit.com/wordpress/redirect' );

// Load shared classes, if they have not been included by another ConvertKit Plugin.
if ( ! trait_exists( 'ConvertKit_API_Traits' ) ) {
	require_once CONVERTKIT_MM_PATH . '/vendor/convertkit/convertkit-wordpress-libraries/src/class-convertkit-api-traits.php';
}
if ( ! class_exists( 'ConvertKit_API_V4' ) ) {
	require_once CONVERTKIT_MM_PATH . '/vendor/convertkit/convertkit-wordpress-libraries/src/class-convertkit-api-v4.php';
}
if ( ! class_exists( 'ConvertKit_Resource_V4' ) ) {
	require_once CONVERTKIT_MM_PATH . '/vendor/convertkit/convertkit-wordpress-libraries/src/class-convertkit-resource-v4.php';
}
if ( ! class_exists( 'ConvertKit_Log' ) ) {
	require_once CONVERTKIT_MM_PATH . '/vendor/convertkit/convertkit-wordpress-libraries/src/class-convertkit-log.php';
}

// Load plugin files.
require CONVERTKIT_MM_PATH . 'includes/class-convertkit-mm-actions.php';
require CONVERTKIT_MM_PATH . 'includes/class-convertkit-mm-api.php';
require CONVERTKIT_MM_PATH . 'includes/class-convertkit-mm-resource.php';
require CONVERTKIT_MM_PATH . 'includes/class-convertkit-mm-resource-custom-fields.php';
require CONVERTKIT_MM_PATH . 'includes/class-convertkit-mm-resource-tags.php';
require CONVERTKIT_MM_PATH . 'includes/class-convertkit-mm-settings.php';
require CONVERTKIT_MM_PATH . 'includes/class-convertkit-mm.php';
require CONVERTKIT_MM_PATH . 'includes/convertkit-mm-functions.php';
require CONVERTKIT_MM_PATH . 'includes/cron-functions.php';
require CONVERTKIT_MM_PATH . 'admin/class-convertkit-mm-admin.php';

/**
 * Main function to return Plugin instance.
 *
 * @since   1.2.0
 */
function ConvertKit_MM() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName

	return ConvertKit_MM::get_instance();

}

// Finally, initialize the Plugin.
ConvertKit_MM();
