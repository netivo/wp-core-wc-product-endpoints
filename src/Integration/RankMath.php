<?php
/**
 * Created by Netivo for wp-core-wc-product-endpoints
 * User: manveru
 * Date: 20.03.2026
 * Time: 12:15
 *
 */

namespace Netivo\Module\WooCommerce\ProductEndpoints\Integration;

use Netivo\Module\WooCommerce\ProductEndpoints\Module;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

class RankMath {

	public function __construct() {
		add_filter( 'rank_math/frontend/title', [ $this, 'change_title' ] );
		add_filter( 'rank_math/frontend/description', [ $this, 'change_description' ] );
		add_filter( 'rank_math/frontend/canonical', [ $this, 'change_canonical' ] );
	}

	public function change_title( string $title ): string {
		$var = get_query_var( 'nt_products' );
		if ( ! empty( $var ) ) {
			$config = Module::get_config_array();
			if ( array_key_exists( $var, $config ) ) {
				$conf = $config[ $var ];

				$template     = '';
				$custom_title = '';

				if ( is_product_category() && array_key_exists( 'page_title_category', $conf ) ) {
					$cat          = get_queried_object();
					$custom_title = sprintf( $conf['page_title_category'], $cat->name );
					// Pobranie szablonu z RankMath dla kategorii produktów
					if ( class_exists( '\RankMath\Helper' ) ) {
						$template = \RankMath\Helper::get_settings( 'titles.tax_product_cat_title' );
					}
				} elseif ( array_key_exists( 'page_title', $conf ) ) {
					$custom_title = $conf['page_title'];
					// Pobranie szablonu z RankMath dla archiwum produktów
					if ( class_exists( '\RankMath\Helper' ) ) {
						$template = \RankMath\Helper::get_settings( 'titles.pt_product_archive_title' );
					}
				}

				if ( ! empty( $template ) && class_exists( '\RankMath\Helper' ) ) {
					// Zamieniamy zmienne tytułowe na nasz customowy tytuł w szablonie RankMath
					// RankMath używa %title%, %term% itp.
					$replacements = [ '%title%', '%term%', '%name%' ];
					$template     = str_replace( $replacements, $custom_title, $template );

					// Używamy RankMath Helper do zamiany zmiennych (np. %sep%, %sitename%)
					if ( method_exists( '\RankMath\Helper', 'replace_vars' ) ) {
						return \RankMath\Helper::replace_vars( $template, get_queried_object() );
					}
				}

				if ( ! empty( $custom_title ) ) {
					return $custom_title;
				}
			}
		}

		return $title;
	}

	public function change_description( string $description ): string {
		$var = get_query_var( 'nt_products' );
		if ( ! empty( $var ) ) {
			$config = Module::get_config_array();
			if ( array_key_exists( $var, $config ) ) {
				return '';
			}
		}

		return $description;
	}

	public function change_canonical( string $canonical ): string {
		$var = get_query_var( 'nt_products' );
		if ( ! empty( $var ) ) {
			$paged = get_query_var( 'paged' );

			return $this->get_custom_endpoint_url( $var, $paged );
		}

		return $canonical;
	}

	protected function get_custom_endpoint_url( string $var, int $paged = 0 ): string {
		$config = Module::get_config_array();
		if ( ! array_key_exists( $var, $config ) ) {
			return '';
		}

		$conf          = $config[ $var ];
		$endpoint_slug = esc_attr( get_option( 'netivo_' . $var . '_slug', $conf['default_slug'] ) );
		$permalinks    = wc_get_permalink_structure();

		if ( is_product_category() ) {
			$cat = get_queried_object();
			$url = home_url( sprintf( '%s/%s/%s/', $endpoint_slug, $permalinks['category_rewrite_slug'], $cat->slug ) );
		} else {
			$url = home_url( $endpoint_slug . '/' );
		}

		if ( $paged > 1 ) {
			$url = user_trailingslashit( $url . 'page/' . $paged );
		}

		return $url;
	}

}
