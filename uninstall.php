<?php
/**
 * Uninstall handler.
 *
 * Removes plugin-owned settings only. Visitor birth data is never stored,
 * so there is no submissions table to delete.
 *
 * Deactivating the plugin does not run this file. Uninstall is the
 * explicit cleanup path and deletes the stored API key so a secret is
 * not left in the database after removal.
 *
 * @package GetBirthChart
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'getbirthchart_api_key' );
delete_option( 'getbirthchart_settings' );
