<?php
/**
 * Created by Netivo for wp-core-wc-product-endpoints
 * User: manveru
 * Date: 19.03.2026
 * Time: 12:37
 *
 */

namespace Netivo\Module\WooCommerce\ProductEndpoints;

use Netivo\Module\WooCommerce\ProductEndpoints\Integration\RankMath;
use Netivo\Module\WooCommerce\ProductEndpoints\Integration\Yoast;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

/**
 * Handles page titles, descriptions, and breadcrumbs for custom product archive endpoints.
 *
 * This class hooks into WooCommerce title, description, and breadcrumb filters to display
 * the customized information from the configuration, and boots SEO integrations.
 */
class Archive {

	/**
	 * Archive constructor.
	 *
	 * Registers WooCommerce filters for page titles, descriptions, and breadcrumbs,
	 * and conditionally instantiates Rank Math or Yoast SEO integrations if they are loaded.
	 */
	public function __construct() {
		add_filter( 'woocommerce_page_title', array( $this, 'change_woocommerce_page_title' ) );
		add_filter( 'woocommerce_taxonomy_archive_description_raw', array( $this, 'change_woocommerce_description' ) );
		add_filter( 'woocommerce_product_archive_description', array( $this, 'change_woocommerce_description' ) );
		add_filter( 'woocommerce_get_breadcrumb', [ $this, 'modify_breadcrumbs' ], 20 );

		// Integrate with Rank Math SEO if available
		if ( defined( 'RANK_MATH_VERSION' ) ) {
			new RankMath();
		}

		// Integrate with Yoast SEO if available
		if ( defined( 'WPSEO_VERSION' ) ) {
			new Yoast();
		}
	}

	/**
	 * Modifies the WooCommerce archive page title on custom product endpoint requests.
	 *
	 * Uses page_title_category if on a category archive, otherwise uses page_title.
	 *
	 * @param string $page_title Original WooCommerce page title.
	 * @return string Modified page title.
	 */
	public function change_woocommerce_page_title( string $page_title ): string {
		$var = get_query_var( 'nt_products' );
		if ( ! empty( $var ) ) {
			if ( array_key_exists( $var, Module::get_config_array() ) ) {
				$conf = Module::get_config_array()[ $var ];

				if ( is_product_category() && array_key_exists( 'page_title_category', $conf ) ) {
					$cat = get_queried_object();

					return sprintf( $conf['page_title_category'], $cat->name );
				} else {
					if ( array_key_exists( 'page_title', $conf ) ) {
						return $conf['page_title'];
					}
				}
			}
		}

		return $page_title;
	}

	/**
	 * Clears the WooCommerce product archive description on custom product endpoint requests.
	 *
	 * @param string $description Original WooCommerce archive description.
	 * @return string Emptied description if on a custom endpoint, otherwise the original description.
	 */
	public function change_woocommerce_description( string $description ): string {
		$var = get_query_var( 'nt_products' );

		if ( ! empty( $var ) ) {
			if ( array_key_exists( $var, Module::get_config_array() ) ) {
				return '';
			}
		}

		return $description;
	}

	/**
	 * Modifies the breadcrumbs array to inject the custom product endpoint.
	 *
	 * If viewing a product category archive, the custom endpoint crumb is inserted
	 * before the category crumbs. If paginated, the trailing pagination crumb is preserved.
	 *
	 * @param array $breadcrumbs Original WooCommerce breadcrumbs array.
	 * @return array Modified breadcrumbs array.
	 */
	public function modify_breadcrumbs( array $breadcrumbs ): array {
		$var = get_query_var( 'nt_products' );

		if ( ! empty( $var ) ) {
			$config = Module::get_config_array();
			if ( array_key_exists( $var, $config ) ) {
				$conf = $config[ $var ];

				if ( array_key_exists( 'breadcrumb_title', $conf ) ) {
					$title = $conf['breadcrumb_title'];
					$link  = $this->get_endpoint_url( $var );

					// Sprawdzamy czy mamy paginację na końcu
					$last_crumb = end( $breadcrumbs );
					$has_paged  = false;
					if ( get_query_var( 'paged' ) && strpos( $last_crumb[0], __( 'Page', 'woocommerce' ) ) !== false ) {
						$has_paged   = true;
						$paged_crumb = array_pop( $breadcrumbs );
					}

					if ( is_product_category() ) {
						// Szukamy gdzie zaczynają się kategorie. Zazwyczaj po Home i ewentualnie Shop.
						// W WC kategorie są dodawane przez add_crumbs_product_category.
						// Jeśli chcemy być przed kategoriami, musimy wiedzieć ile ich jest.
						$cat              = get_queried_object();
						$cat_crumbs_count = 1; // Sama kategoria
						$ancestors        = get_ancestors( $cat->term_id, 'product_cat' );
						$cat_crumbs_count += count( $ancestors );

						// Wstawiamy przed kategoriami
						$offset = count( $breadcrumbs ) - $cat_crumbs_count;
						if ( $offset < 0 ) {
							$offset = 0;
						}

						// Zmieniamy linki kategorii, żeby prowadziły do endpointu w danej kategorii
						for ( $i = $offset; $i < count( $breadcrumbs ); $i ++ ) {
							$term = get_term_by( 'name', $breadcrumbs[ $i ][0], 'product_cat' );
							if ( $term ) {
								$breadcrumbs[ $i ][1] = $this->get_endpoint_url( $var, $term->slug );
							}
						}

						array_splice( $breadcrumbs, $offset, 0, [ [ $title, $link ] ] );
					} else {
						// Dodajemy na końcu
						$breadcrumbs[] = [ $title, $link ];
					}

					// Przywracamy paginację jeśli była
					if ( $has_paged ) {
						$breadcrumbs[] = $paged_crumb;
					}
				}
			}
		}

		return $breadcrumbs;
	}

	/**
	 * Generates the canonical URL for a given product endpoint.
	 *
	 * Supports category-specific URL rewrites.
	 *
	 * @param string $var           Endpoint ID.
	 * @param string $category_slug Optional. The product category slug.
	 * @return string The absolute custom endpoint URL.
	 */
	protected function get_endpoint_url( string $var, string $category_slug = '' ): string {
		$config = Module::get_config_array();
		if ( ! array_key_exists( $var, $config ) ) {
			return '';
		}

		$conf          = $config[ $var ];
		$endpoint_slug = esc_attr( get_option( 'netivo_' . $var . '_slug', $conf['default_slug'] ) );
		$permalinks    = wc_get_permalink_structure();

		if ( ! empty( $category_slug ) ) {
			return home_url( sprintf( '%s/%s/%s/', $endpoint_slug, $permalinks['category_rewrite_slug'], $category_slug ) );
		}

		return home_url( $endpoint_slug . '/' );
	}
}