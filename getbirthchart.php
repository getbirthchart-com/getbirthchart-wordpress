<?php
/**
 * Plugin Name: GetBirthChart – Birth Chart Calculators
 * Plugin URI: https://getbirthchart.com/developers
 * Description: Embed GetBirthChart-powered birth chart, Moon sign, Rising sign, and Big Three calculators. Requires a GetBirthChart API key; calculations run on the GetBirthChart API.
 * Version: 0.1.0
 * Author: GetBirthChart
 * Author URI: https://getbirthchart.com/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: getbirthchart
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 *
 * @package GetBirthChart
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GETBIRTHCHART_VERSION', '0.1.0' );
define( 'GETBIRTHCHART_PLUGIN_FILE', __FILE__ );
define( 'GETBIRTHCHART_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GETBIRTHCHART_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GETBIRTHCHART_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Authoritative public GetBirthChart API base.
 *
 * This is the documented developer API on getbirthchart.com, not the
 * internal calculation-engine host.
 */
define( 'GETBIRTHCHART_API_BASE_URL', 'https://getbirthchart.com/api' );

require_once GETBIRTHCHART_PLUGIN_DIR . 'includes/class-validator.php';
require_once GETBIRTHCHART_PLUGIN_DIR . 'includes/class-settings.php';
require_once GETBIRTHCHART_PLUGIN_DIR . 'includes/class-api-client.php';
require_once GETBIRTHCHART_PLUGIN_DIR . 'includes/class-result-mapper.php';
require_once GETBIRTHCHART_PLUGIN_DIR . 'includes/class-rate-limiter.php';
require_once GETBIRTHCHART_PLUGIN_DIR . 'includes/class-privacy.php';
require_once GETBIRTHCHART_PLUGIN_DIR . 'includes/class-assets.php';
require_once GETBIRTHCHART_PLUGIN_DIR . 'includes/class-shortcodes.php';
require_once GETBIRTHCHART_PLUGIN_DIR . 'includes/class-rest-controller.php';
require_once GETBIRTHCHART_PLUGIN_DIR . 'includes/class-plugin.php';
require_once GETBIRTHCHART_PLUGIN_DIR . 'admin/class-admin.php';
require_once GETBIRTHCHART_PLUGIN_DIR . 'public/class-public.php';

register_activation_hook( __FILE__, array( 'GetBirthChart_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'GetBirthChart_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'GetBirthChart_Plugin', 'instance' ) );
