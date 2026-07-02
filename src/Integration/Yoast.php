<?php
/**
 * Created by Netivo for wp-core-wc-product-endpoints
 * User: manveru
 * Date: 20.03.2026
 * Time: 11:28
 *
 */

namespace Netivo\Module\WooCommerce\ProductEndpoints\Integration;

use Netivo\Module\WooCommerce\ProductEndpoints\Module;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

/**
 * Integration class for Yoast SEO.
 *
 * Ensures custom titles, descriptions, canonical URLs, and rel links (next/prev)
 * are handled correctly according to Yoast SEO's filters when a custom product endpoint is requested.
 */
class Yoast {

	/**
	 * Yoast constructor.
	 *
	 * Registers filters for Yoast SEO title, description, canonical URL, and next/prev relation links.
	 */
	public function __construct() {
		add_filter( 'wpseo_title', [ $this, 'change_title' ] );
		add_filter( 'wpseo_metadesc', [ $this, 'change_description' ] );
		add_filter( 'wpseo_canonical', [ $this, 'change_canonical' ] );
		add_filter( 'wpseo_next_rel_link', [ $this, 'change_next_rel_link' ] );
		add_filter( 'wpseo_prev_rel_link', [ $this, 'change_prev_rel_link' ] );
	}

	/**
	 * Customizes the SEO title outputted by Yoast SEO.
	 *
	 * Replaces default variables inside Yoast SEO templates with the custom endpoint's title,
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
					if ( class_exists( 'WPSEO_Options' ) ) {
						$template = \WPSEO_Options::get( 'title-tax-product_cat' );
					}
				} elseif ( array_key_exists( 'page_title', $conf ) ) {
					$custom_title = $conf['page_title'];
					if ( class_exists( 'WPSEO_Options' ) ) {
						$template = \WPSEO_Options::get( 'title-product' );
					}
				}

				if ( ! empty( $template ) && function_exists( 'wpseo_replace_vars' ) ) {
					// Zamieniamy zmienne tytułowe na nasz customowy tytuł w szablonie
					$replacements = [ '%%term_title%%', '%%category%%', '%%title%%' ];
					$template     = str_replace( $replacements, $custom_title, $template );

					return wpseo_replace_vars( $template, get_queried_object() );
				}

				if ( ! empty( $custom_title ) ) {
					return $custom_title;
				}
			}
		}

		return $title;
	}

	/**
	 * Clears the SEO description outputted by Yoast SEO.
	 *
	 * Clears the description for custom endpoints since description is also cleared on the archive page.
	 *
	 * @param string $description Original description.
	 * @return string Emptied description if on custom endpoint, otherwise the original description.
	 */
	public function change_description( string $description ): string {
		$var = get_query_var( 'nt_products' );
		if ( ! empty( $var ) ) {
			$config = Module::get_config_array();
			if ( array_key_exists( $var, $config ) ) {
				// Można tu dodać dedykowane pole w configu jeśli zajdzie potrzeba
				// Na razie zwracamy pusty opis lub domyślny, bo WooCommerce description i tak jest czyszczone w Archive.php
				return '';
			}
		}

		return $description;
	}

	/**
	 * Modifies the canonical URL outputted by Yoast SEO.
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
	 * Modifies the rel="next" link outputted by Yoast SEO.
	 *
	 * Generates a paged-aware custom endpoint next rel link if there are more pages.
	 *
	 * @param mixed $next Original rel="next" tag.
	 * @return string Custom endpoint next link tag if on custom endpoint, otherwise original tag.
	 */
	public function change_next_rel_link( $next ): string {
		$var = get_query_var( 'nt_products' );
		if ( ! empty( $var ) ) {
			global $wp_query;

			$paged    = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
			$max_page = $wp_query->max_num_pages;

			if ( $paged < $max_page ) {
				return '<link rel="next" href="' . $this->get_custom_endpoint_url( $var, $paged + 1 ) . '" />';
			}

			return '';
		}

		return $next;
	}

	/**
	 * Modifies the rel="prev" link outputted by Yoast SEO.
	 *
	 * Generates a paged-aware custom endpoint prev rel link if not on the first page.
	 *
	 * @param mixed $prev Original rel="prev" tag.
	 * @return string Custom endpoint prev link tag if on custom endpoint, otherwise original tag.
	 */
	public function change_prev_rel_link( $prev ): string {
		$var = get_query_var( 'nt_products' );
		if ( ! empty( $var ) ) {
			$paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;

			if ( $paged > 1 ) {
				return '<link rel="prev" href="' . $this->get_custom_endpoint_url( $var, $paged - 1 ) . '" />';
			}

			return '';
		}

		return $prev;
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