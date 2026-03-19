<?php
/**
 * Created by Netivo for wp-core-wc-product-endpoints
 * User: manveru
 * Date: 19.03.2026
 * Time: 16:19
 *
 */

namespace Netivo\Module\WooCommerce\ProductEndpoints;

use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

class Rewrite {

	public function __construct() {

		add_action( 'init', [ $this, 'register_shop_endpoints' ], 1 );
		add_filter( 'query_vars', [ $this, 'register_query_vars' ] );

		add_filter( 'pre_get_posts', [ $this, 'modify_shop_query' ], 100, 1 );
	}

	public function register_shop_endpoints(): void {
		$permalinks = wc_get_permalink_structure();

		foreach ( Module::get_config_array() as $key => $value ) {
			$endpoint_slug = esc_attr( get_option( 'netivo_' . $key . '_slug', $value['default_slug'] ) );

			add_rewrite_rule( $endpoint_slug . '/' . $permalinks['category_rewrite_slug'] . '/(.+?)/page/([0-9]{1,})/?$',
				'index.php?product_cat=$matches[1]&nt_products=' . $key . '&paged=$matches[2]',
				'top' );
			add_rewrite_rule( $endpoint_slug . '/' . $permalinks['category_rewrite_slug'] . '/(.+?)/?$',
				'index.php?product_cat=$matches[1]&nt_products=' . $key,
				'top' );
			add_rewrite_rule( $endpoint_slug . '/page/([0-9]{1,})/?$',
				'index.php?post_type=product&nt_products=' . $key . '&paged=$matches[1]',
				'top' );
			add_rewrite_rule( $endpoint_slug . '/?$',
				'index.php?post_type=product&nt_products=' . $key,
				'top' );
		}
	}

	public function register_query_vars( array $vars ): array {
		$vars[] = 'nt_products';

		return $vars;
	}

	public function modify_shop_query( WP_Query $query ): WP_Query {
		if ( $query->is_main_query() && ! is_admin() && ( is_post_type_archive( 'product' ) ) ) {
			$var = get_query_var( 'nt_products' );

			if ( ! empty( $var ) ) {
				if ( array_key_exists( $var, Module::get_config_array() ) ) {
					$conf = Module::get_config_array()[ $var ];
					if ( ! empty( $conf['type'] ) ) {
						switch ( $conf['type'] ) {
							case 'promotions' :
								return $this->modify_query_promotions( $query );
							case 'bestsellers':
								return $this->modify_query_bestsellers( $query );
							case 'custom':
								if ( ! is_callable( $conf['custom_endpoint'] ) ) {
									return $conf['custom_endpoint']( $query );
								}
								break;
						}
					}
				}
			}
		}

		return $query;
	}

	protected function modify_query_promotions( WP_Query $query ): WP_Query {
		$query->set( 'post_type', 'product' );
		$meta_query = array(
			'relation' => 'AND',
			array(
				'key'     => '_sale_price',
				'value'   => 0,
				'compare' => '>',
				'type'    => 'numeric'
			)
		);
		$query->set( 'meta_query', $meta_query );

		return $query;
	}

	protected function modify_query_bestsellers( WP_Query $query ): WP_Query {
		$query->set( 'post_type', 'product' );
		$meta_query = array(
			'relation' => 'AND',
			array(
				'key'     => '_total_sales',
				'value'   => 0,
				'compare' => '>',
				'type'    => 'numeric'
			)
		);
		$query->set( 'meta_query', $meta_query );

		return $query;
	}

}