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

/**
 * Integration class for Rank Math SEO.
 *
 * Ensures custom titles, descriptions, and canonical URLs are handled correctly
 * according to Rank Math's filters when a custom product endpoint is requested.
 */
class RankMath {

	/**
	 * RankMath constructor.
	 *
	 * Registers filters for Rank Math frontend title, description, and canonical URL.
	 */
	public function __construct() {
		add_filter( 'rank_math/frontend/title', [ $this, 'change_title' ] );
		add_filter( 'rank_math/frontend/description', [ $this, 'change_description' ] );
		add_filter( 'rank_math/frontend/canonical', [ $this, 'change_canonical' ] );
	}

	/**
	 * Customizes the SEO title outputted by Rank Math.
	 *
	 * Replaces default variables inside Rank Math templates with the custom endpoint's title,
	 * optionally incorporating category names if on category archives.
	 *
	 * @param string $title Original page title.
	 * @return string Modified SEO title.
	 */
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

	/**
	 * Clears the SEO description outputted by Rank Math.
	 *
	 * Clears the meta description for custom endpoints as the page layout differs from normal archives.
	 *
	 * @param string $description Original description.
	 * @return string Emptied description if on custom endpoint, otherwise the original description.
	 */
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

	/**
	 * Modifies the canonical URL outputted by Rank Math.
	 *
	 * Returns the custom endpoint URL instead of the default shop or category URL.
	 *
	 * @param string $canonical Original canonical URL.
	 * @return string Custom endpoint canonical URL if on custom endpoint, otherwise original canonical URL.
	 */
	public function change_canonical( string $canonical ): string {
		$var = get_query_var( 'nt_products' );
		if ( ! empty( $var ) ) {
			$paged = get_query_var( 'paged' );

			return $this->get_custom_endpoint_url( $var, $paged );
		}

		return $canonical;
	}

	/**
	 * Helper function to generate the custom endpoint URL.
	 *
	 * Generates a category/paged aware URL for custom endpoints.
	 *
	 * @param string $var   Endpoint query variable/ID.
	 * @param int    $paged Current page number (optional).
	 * @return string The absolute custom endpoint URL.
	 */
	protected function get_custom_endpoint_url( string $var, int $paged = 0 ): string {
		$category_slug = '';
		if ( is_product_category() ) {
			$cat           = get_queried_object();
			$category_slug = $cat->slug;
		}

		return Module::get_endpoint_url( $var, $category_slug, $paged );
	}

}
