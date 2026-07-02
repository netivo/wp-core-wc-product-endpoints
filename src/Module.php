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

/**
 * The main module bootstrap class (singleton).
 *
 * This class coordinates the initialization of rewrite rules, archive overrides,
 * admin settings, and SEO integrations for WooCommerce product endpoints.
 */
class Module {

	/**
	 * The singleton instance of the class.
	 *
	 * @var self|null
	 */
	protected static ?self $instance = null;

	/**
	 * The configuration array loaded from the parent theme.
	 *
	 * @var array
	 */
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

	/**
	 * Retrieves the module configuration array.
	 *
	 * @return array The configuration array.
	 */
	public static function get_config_array(): array {
		return self::get_instance()->get_config();
	}

	/**
	 * Module constructor.
	 *
	 * Initializes the configuration by loading product endpoints definitions.
	 */
	protected function __construct() {
		$this->init_config();
	}

	/**
	 * Bootstraps the module.
	 *
	 * Instantiates rewrite rules, archive hooks, and SEO integrations.
	 * Also instantiates the admin dashboard if the current request is an admin request.
	 *
	 * @return void
	 */
	public function init(): void {
		new Rewrite();
		new Archive();
		new Integration\Yoast();
		new Integration\RankMath();

		if ( is_admin() ) {
			new Admin\Admin();
		}
	}

	/**
	 * Loads the product endpoints configuration from the parent theme.
	 *
	 * Looks for a configuration file at `config/product-endpoints.config.php`
	 * in the parent theme/stylesheet directory.
	 *
	 * @return void
	 */
	public function init_config(): void {
		if ( file_exists( get_stylesheet_directory() . "/config/product-endpoints.config.php" ) ) {
			$this->config = include get_stylesheet_directory() . "/config/product-endpoints.config.php";
		}
	}

	/**
	 * Gets the loaded configuration array.
	 *
	 * @return array The configuration array.
	 */
	public function get_config(): array {
		return $this->config;
	}

	/**
	 * Cloning is forbidden for singletons.
	 */
	protected function __clone() {
	}


}