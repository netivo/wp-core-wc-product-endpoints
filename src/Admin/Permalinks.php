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
	 * Adds one field per configured product endpoint under the optional section, plus a
	 * hidden field carrying the nonce.
	 *
	 * @return void
	 */
	public function add_settings(): void {
		$config = Module::get_config_array();

		if ( empty( $config ) ) {
			return;
		}

		add_settings_field(
			'netivo_product_endpoints_nonce',
			'',
			[ $this, 'render_nonce_field' ],
			'permalink',
			'optional'
		);

		foreach ( $config as $key => $conf ) {
			add_settings_field(
				'netivo_endpoint_' . $key,
				'Baza ' . ( $conf['page_title'] ?? $key ),
				[ $this, 'render_settings_field' ],
				'permalink',
				'optional',
				[
					'key'  => $key,
					'conf' => $conf,
				]
			);
		}
	}

	/**
	 * Renders the nonce field used to secure the permalink settings save.
	 *
	 * @return void
	 */
	public function render_nonce_field(): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );
	}

	/**
	 * Renders the HTML input for a single configured product endpoint on the WordPress Permalink settings page.
	 *
	 * Outputs a form input pre-populated with its current setting value or placeholder.
	 *
	 * @param array $args {
	 *     @type string $key  The endpoint id.
	 *     @type array  $conf The endpoint config entry.
	 * }
	 *
	 * @return void
	 */
	public function render_settings_field( array $args ): void {
		$key  = $args['key'];
		$conf = $args['conf'];
		?>
		<input
			name="netivo_product_endpoints[<?php echo esc_attr( $key ); ?>]"
			id="netivo_endpoint_<?php echo esc_attr( $key ); ?>"
			type="text"
			class="regular-text code"
			value="<?php echo esc_attr( get_option( 'netivo_' . $key . '_slug', $conf['default_slug'] ?? '' ) ); ?>"
			placeholder="<?php echo esc_attr( $conf['default_slug'] ?? '' ); ?>"
		/>
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
