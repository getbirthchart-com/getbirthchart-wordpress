<?php
/**
 * Settings page markup.
 *
 * @package GetBirthChart
 *
 * @var bool   $updated
 * @var string $masked
 * @var array{default_type: string, theme: string} $settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$getbirthchart_api_key_error = isset( $_GET['error'] ) && 'api_key' === $_GET['error']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<div class="wrap getbirthchart-settings">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php if ( $updated ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'getbirthchart' ); ?></p></div>
	<?php endif; ?>

	<?php if ( $getbirthchart_api_key_error ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'Enter a valid GetBirthChart API key.', 'getbirthchart' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" autocomplete="off">
		<input type="hidden" name="action" value="<?php echo esc_attr( GetBirthChart_Admin::SAVE_ACTION ); ?>" />
		<?php wp_nonce_field( GetBirthChart_Admin::NONCE_SAVE ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="getbirthchart-api-key"><?php esc_html_e( 'API key', 'getbirthchart' ); ?></label>
				</th>
				<td>
					<?php if ( '' !== $masked ) : ?>
						<p class="getbirthchart-masked-key">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: masked API key */
									__( 'Current key: %s', 'getbirthchart' ),
									$masked
								)
							);
							?>
						</p>
					<?php endif; ?>
					<input
						type="password"
						class="regular-text"
						id="getbirthchart-api-key"
						name="getbirthchart_api_key"
						value=""
						autocomplete="new-password"
						placeholder="<?php echo esc_attr( '' !== $masked ? __( 'Enter a new key to replace the stored key', 'getbirthchart' ) : 'gbc_live_…' ); ?>"
					/>
					<p class="description">
						<?php
						echo wp_kses(
							sprintf(
								/* translators: %s: developers URL */
								__( 'Create a key at <a href="%s">getbirthchart.com/developers</a>. The full key is never shown again after you save it.', 'getbirthchart' ),
								esc_url( 'https://getbirthchart.com/developers/' )
							),
							array( 'a' => array( 'href' => array() ) )
						);
						?>
					</p>
					<?php if ( '' !== $masked ) : ?>
						<label>
							<input type="checkbox" name="getbirthchart_remove_api_key" value="1" />
							<?php esc_html_e( 'Remove the stored API key', 'getbirthchart' ); ?>
						</label>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'API status', 'getbirthchart' ); ?></th>
				<td>
					<button type="button" class="button" id="getbirthchart-test-connection">
						<?php esc_html_e( 'Test connection', 'getbirthchart' ); ?>
					</button>
					<p class="description" id="getbirthchart-test-result" aria-live="polite">
						<?php esc_html_e( 'Save a new key before testing. The test uses the stored key on the server and never sends it to the browser.', 'getbirthchart' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="getbirthchart-default-type"><?php esc_html_e( 'Default calculator type', 'getbirthchart' ); ?></label>
				</th>
				<td>
					<select id="getbirthchart-default-type" name="getbirthchart_default_type">
						<?php foreach ( GetBirthChart_Validator::TYPES as $getbirthchart_type ) : ?>
							<option value="<?php echo esc_attr( $getbirthchart_type ); ?>" <?php selected( $settings['default_type'], $getbirthchart_type ); ?>>
								<?php echo esc_html( GetBirthChart_Shortcodes::type_label( $getbirthchart_type ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="getbirthchart-theme"><?php esc_html_e( 'Default theme', 'getbirthchart' ); ?></label>
				</th>
				<td>
					<select id="getbirthchart-theme" name="getbirthchart_theme">
						<option value="inherit" <?php selected( $settings['theme'], 'inherit' ); ?>><?php esc_html_e( 'Inherit theme styles', 'getbirthchart' ); ?></option>
						<option value="light" <?php selected( $settings['theme'], 'light' ); ?>><?php esc_html_e( 'Light', 'getbirthchart' ); ?></option>
					</select>
				</td>
			</tr>
		</table>
		<?php submit_button( __( 'Save settings', 'getbirthchart' ) ); ?>
	</form>

	<h2><?php esc_html_e( 'Shortcodes', 'getbirthchart' ); ?></h2>
	<p><?php esc_html_e( 'Embed a calculator in a page or post:', 'getbirthchart' ); ?></p>
	<ul>
		<li><code>[getbirthchart]</code></li>
		<li><code>[getbirthchart type="moon-sign"]</code></li>
		<li><code>[getbirthchart type="rising-sign"]</code></li>
		<li><code>[getbirthchart type="big-three"]</code></li>
	</ul>
</div>
