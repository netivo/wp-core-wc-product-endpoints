<?php
/**
 * Created by Netivo for wp-core-wc-product-endpoints
 *
 */

namespace Netivo\Module\WooCommerce\ProductEndpoints\Admin;

use Netivo\Module\WooCommerce\ProductEndpoints\Module;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

/**
 * Manages custom product endpoint settings on the WordPress Permalink settings page.
 *
 * This class registers a new section under Settings -> Permalinks where administrators
 * can customize URL slugs for each configured product endpoint. It also handles validating
 * and saving changes, and flushes rewrite rules when the configuration updates.
 */
class Permalinks {

	/**
	 * Nonce action string for security verification when saving settings.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'netivo_product_endpoints_permalinks';

	/**
	 * Nonce field name for security verification when saving settings.
	 *
	 * @var string
	 */
	const NONCE_FIELD = '_netivo_product_endpoints_nonce';

	/**
	 * Permalinks constructor.
	 *
	 * Binds Settings registration and settings save actions on the permalinks settings load.
	 */
	public function __construct() {
		// Priority 20 so this is registered after WooCommerce's own permalink field (priority 10),
		// making it render right after the WooCommerce bases on Settings -> Permalinks.
		add_action( 'load-options-permalink.php', [ $this, 'add_settings' ], 20 );
		add_action( 'load-options-permalink.php', [ $this, 'settings_save' ], 20 );
	}

	/**
	 * Registers settings fields on the WordPress permalinks page.
	 *
	 * Adds the "Product endpoints" field under the optional section.
	 *
	 * @return void
	 */
	public function add_settings(): void {
		if ( empty( Module::get_config_array() ) ) {
			return;
		}

		add_settings_field(
			'netivo_product_endpoints_slugs',
			__( 'Product endpoints', 'netivo' ),
			[ $this, 'render_settings_field' ],
			'permalink',
			'optional'
		);
	}

	/**
	 * Renders HTML inputs for all configured product endpoints in the WordPress Permalink settings page.
	 *
	 * Outputs form inputs pre-populated with current settings values or placeholders.
	 *
	 * @return void
	 */
	public function render_settings_field(): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );
		?>
		<table class="form-table netivo-product-endpoints-permalinks">
			<tbody>
			<?php foreach ( Module::get_config_array() as $key => $conf ) : ?>
				<tr>
					<th scope="row">
						<label for="netivo_endpoint_<?php echo esc_attr( $key ); ?>">
							<?php echo esc_html( $conf['page_title'] ?? $key ); ?>
						</label>
					</th>
					<td>
						<input
							name="netivo_product_endpoints[<?php echo esc_attr( $key ); ?>]"
							id="netivo_endpoint_<?php echo esc_attr( $key ); ?>"
							type="text"
							class="regular-text code"
							value="<?php echo esc_attr( get_option( 'netivo_' . $key . '_slug', $conf['default_slug'] ?? '' ) ); ?>"
							placeholder="<?php echo esc_attr( $conf['default_slug'] ?? '' ); ?>"
						/>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Processes, validates, and saves custom product endpoint slug updates from the options page.
	 *
	 * Verifies nonces, sanitizes slugs, updates database options, and flushes rewrite rules if any slugs changed.
	 *
	 * @return void
	 */
	public function settings_save(): void {
		if ( empty( $_POST['netivo_product_endpoints'] ) || ! is_array( $_POST['netivo_product_endpoints'] ) ) {
			return;
		}

		check_admin_referer( self::NONCE_ACTION, self::NONCE_FIELD );

		$config  = Module::get_config_array();
		$slugs   = wp_unslash( $_POST['netivo_product_endpoints'] );
		$changed = false;

		foreach ( $config as $key => $conf ) {
			if ( ! isset( $slugs[ $key ] ) ) {
				continue;
			}

			$slug    = sanitize_title( $slugs[ $key ] );
			$default = $conf['default_slug'] ?? '';
			$option  = 'netivo_' . $key . '_slug';

			if ( '' === $slug || $slug === $default ) {
				$changed = delete_option( $option ) || $changed;
			} else {
				$changed = update_option( $option, $slug ) || $changed;
			}
		}

		if ( $changed ) {
			flush_rewrite_rules();
		}
	}

}
