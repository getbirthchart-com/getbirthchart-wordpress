<?php
/**
 * Admin settings page.
 *
 * @package GetBirthChart
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings → GetBirthChart.
 */
class GetBirthChart_Admin {

	public const PAGE_SLUG   = 'getbirthchart';
	public const SAVE_ACTION = 'getbirthchart_save_settings';
	public const TEST_ACTION = 'getbirthchart_test_connection';
	public const NONCE_SAVE  = 'getbirthchart_save_settings';
	public const NONCE_TEST  = 'getbirthchart_test_connection';

	/**
	 * Register admin hooks.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_' . self::SAVE_ACTION, array( $this, 'handle_save' ) );
		add_action( 'wp_ajax_' . self::TEST_ACTION, array( $this, 'handle_test_connection' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_' . GETBIRTHCHART_PLUGIN_BASENAME, array( $this, 'action_links' ) );
	}

	/**
	 * Settings link on the plugins list.
	 *
	 * @param array<int, string> $links Links.
	 * @return array<int, string>
	 */
	public function action_links( array $links ): array {
		$url = admin_url( 'options-general.php?page=' . self::PAGE_SLUG );
		array_unshift(
			$links,
			'<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'getbirthchart' ) . '</a>'
		);
		return $links;
	}

	/**
	 * Add Settings → GetBirthChart.
	 */
	public function register_menu(): void {
		add_options_page(
			__( 'GetBirthChart', 'getbirthchart' ),
			__( 'GetBirthChart', 'getbirthchart' ),
			GetBirthChart_Settings::CAPABILITY,
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue admin CSS/JS on the settings screen only.
	 *
	 * @param string $hook Current admin hook.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}
		wp_enqueue_style(
			'getbirthchart-admin',
			GETBIRTHCHART_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			GETBIRTHCHART_VERSION
		);
		wp_enqueue_script(
			'getbirthchart-admin',
			GETBIRTHCHART_PLUGIN_URL . 'assets/js/admin.js',
			array(),
			GETBIRTHCHART_VERSION,
			true
		);
		wp_localize_script(
			'getbirthchart-admin',
			'getbirthchartAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( self::NONCE_TEST ),
				'action'  => self::TEST_ACTION,
				'i18n'    => array(
					'testing' => __( 'Testing…', 'getbirthchart' ),
					'test'    => __( 'Test connection', 'getbirthchart' ),
				),
			)
		);
	}

	/**
	 * Render the settings view.
	 */
	public function render_page(): void {
		if ( ! GetBirthChart_Settings::current_user_can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to manage GetBirthChart settings.', 'getbirthchart' ), '', array( 'response' => 403 ) );
		}
		$settings = GetBirthChart_Settings::get_settings();
		$masked   = GetBirthChart_Settings::masked_api_key();
		$updated  = isset( $_GET['updated'] ) && '1' === $_GET['updated']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		include GETBIRTHCHART_PLUGIN_DIR . 'admin/views/settings-page.php';
	}

	/**
	 * Save settings. Requires manage_options and a nonce.
	 */
	public function handle_save(): void {
		if ( ! GetBirthChart_Settings::current_user_can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to manage GetBirthChart settings.', 'getbirthchart' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::NONCE_SAVE );

		$raw_key = '';
		if ( isset( $_POST['getbirthchart_api_key'] ) && is_string( $_POST['getbirthchart_api_key'] ) ) {
			// Secret characters must not be mutated by sanitize_text_field(); validate via sanitize_api_key().
			$raw_key = wp_unslash( $_POST['getbirthchart_api_key'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}
		$raw_key = trim( $raw_key );
		if ( '' !== $raw_key ) {
			$sanitized = GetBirthChart_Validator::sanitize_api_key( $raw_key );
			if ( is_wp_error( $sanitized ) ) {
				wp_safe_redirect(
					add_query_arg(
						array(
							'page'  => self::PAGE_SLUG,
							'error' => 'api_key',
						),
						admin_url( 'options-general.php' )
					)
				);
				exit;
			}
			GetBirthChart_Settings::set_api_key( $sanitized );
		} elseif ( isset( $_POST['getbirthchart_remove_api_key'] ) ) {
			GetBirthChart_Settings::delete_api_key();
		}

		$type  = isset( $_POST['getbirthchart_default_type'] ) && is_string( $_POST['getbirthchart_default_type'] )
			? sanitize_text_field( wp_unslash( $_POST['getbirthchart_default_type'] ) )
			: 'birth-chart';
		$theme = isset( $_POST['getbirthchart_theme'] ) && is_string( $_POST['getbirthchart_theme'] )
			? sanitize_text_field( wp_unslash( $_POST['getbirthchart_theme'] ) )
			: 'inherit';
		GetBirthChart_Settings::update_settings(
			array(
				'default_type' => $type,
				'theme'        => $theme,
			)
		);

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::PAGE_SLUG,
					'updated' => '1',
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	/**
	 * AJAX connection test. Uses the stored key only.
	 */
	public function handle_test_connection(): void {
		if ( ! GetBirthChart_Settings::current_user_can_manage() ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have permission to manage GetBirthChart settings.', 'getbirthchart' ) ),
				403
			);
		}
		check_ajax_referer( self::NONCE_TEST, 'nonce' );

		$client  = new GetBirthChart_Api_Client();
		$result  = $client->test_connection();
		$payload = array(
			'status'  => $result['status'],
			'message' => GetBirthChart_Api_Client::connection_status_label( $result['status'] ),
		);
		if ( '' !== $result['request_id'] ) {
			$payload['request_id'] = $result['request_id'];
		}
		wp_send_json_success( $payload );
	}
}
