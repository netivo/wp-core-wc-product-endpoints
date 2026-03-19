<?php
/**
 * Created by Netivo for wp-core-wc-product-endpoints
 * User: manveru
 * Date: 19.03.2026
 * Time: 12:37
 *
 */

namespace Netivo\Module\WooCommerce\ProductEndpoints;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

class Archive {


	public function __construct() {
		add_filter( 'woocommerce_page_title', array( $this, 'change_woocommerce_page_title' ) );
		add_filter( 'woocommerce_taxonomy_archive_description_raw', array( $this, 'change_woocommerce_description' ) );
		add_filter( 'woocommerce_product_archive_description', array( $this, 'change_woocommerce_description' ) );
	}

	public function change_archive_title( string $page_title ): string {
		$var = get_query_var( 'nt_products' );
		if ( is_product_category() ) {
			$cat = get_queried_object();

			if ( ! empty( $var ) ) {
				if ( $var == 'promotions' ) {
					return sprintf( __( 'Wyprzedaże w kategorii %s', 'netivo' ), $cat->name );
				}
			}
		} else {
			if ( ! empty( $var ) ) {
				if ( $var == 'promotions' ) {
					return __( 'Wyprzedaże', 'netivo' );
				}
			}
		}

		return $page_title;
	}

	public function change_woocommerce_description( string $description ): string {
		$var = get_query_var( 'nt_products' );
		
		if ( ! empty( $var ) ) {
			if ( $var == 'promotions' ) {
				return '';
			}
		}

		return $description;
	}
}