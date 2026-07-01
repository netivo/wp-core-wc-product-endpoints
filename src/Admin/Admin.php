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

class Admin {

	public function __construct() {
		new Permalinks();
	}

}
