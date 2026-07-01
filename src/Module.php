<?php
/**
 * Created by Netivo for wp-core-wc-product-endpoints
 * User: manveru
 * Date: 19.03.2026
 * Time: 11:50
 *
 */

namespace Netivo\Module\WooCommerce\ProductEndpoints;

use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

class Module {

	protected static ?self $instance = null;

	protected array $config = [];

	/**
	 * Retrieves the singleton instance of the class. If the instance does not already exist, it initializes a new instance.
	 *
	 * @return self The singleton instance of the class.
	 */
	public static function get_instance(): self {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function get_config_array(): array {
		return self::get_instance()->get_config();
	}

	protected function __construct() {
		$this->init_config();
	}

	public function init(): void {
		new Rewrite();
		new Archive();
		new Integration\Yoast();
		new Integration\RankMath();

		if ( is_admin() ) {
			new Admin\Admin();
		}
	}

	public function init_config(): void {
		if ( file_exists( get_stylesheet_directory() . "/config/product-endpoints.config.php" ) ) {
			$this->config = include get_stylesheet_directory() . "/config/product-endpoints.config.php";
		}
	}

	public function get_config(): array {
		return $this->config;
	}

	protected function __clone() {
	}


}