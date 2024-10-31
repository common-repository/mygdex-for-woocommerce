<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.gdexpress.com
 * @since             1.0.0
 * @package           Gdex
 *
 * @wordpress-plugin
 * Plugin Name:       GDEX for Woocommerce
 * Plugin URI:        https://www.gdexpress.com
 * Description:       WooCommerce integration for GDEX.
 * Version:           1.2.1
 * Author:            GDEX
 * Author URI:        https://www.gdexpress.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       gdex
 * Domain Path:       /languages
 *
 * WC requires at least: 4.0.0
 * WC tested up to: 8.7
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'GDEX_VERSION', '1.2.1' );
define( 'GDEX_API_URL', 'https://myopenapi.gdexpress.com/api/MyGDex/' );
define( 'GDEX_API_SUBSCRIPTION_KEY', 'f5fa5f396469493fb5ffbfbb9a50f33b' );
define( 'GDEX_TESTING_API_URL', 'https://myopenapi.gdexpress.com/test/api/MyGDex/' );
define( 'GDEX_TESTING_API_SUBSCRIPTION_KEY', '9a0f31b83fcf4318b63f039c4e16459e' );
define( 'GDEX_TIMEZONE', 'Asia/Kuala_Lumpur' );
define( 'GDEX_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'GDEX_TESTING', false );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-gdex-activator.php
 */
function activate_gdex() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-gdex-activator.php';
	Gdex_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-gdex-deactivator.php
 */
function deactivate_gdex() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-gdex-deactivator.php';
	Gdex_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_gdex' );
register_deactivation_hook( __FILE__, 'deactivate_gdex' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-gdex.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_gdex() {
	$plugin = new Gdex();
	$plugin->run();
}

run_gdex();