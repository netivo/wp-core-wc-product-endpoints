<?php
/**
 * Created by Netivo for wp-core-wc-product-endpoints
 *
 */

namespace Netivo\Module\WooCommerce\ProductEndpoints\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

/**
 * Admin dashboard integration coordinator.
 *
 * Bootstraps the administration-specific components, such as setting up the custom permalink settings.
 */
class Admin {

	/**
	 * Admin constructor.
	 *
	 * Initializes the Permalinks settings class.
	 */
	public function __construct() {
		new Permalinks();
	}

}
