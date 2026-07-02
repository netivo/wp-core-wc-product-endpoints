<?php
/**
 * Created by Netivo for wp-core-wc-product-endpoints
 * Date: 02.07.2026
 *
 */

namespace Netivo\Module\WooCommerce\ProductEndpoints\Integration;

use Netivo\Module\WooCommerce\ProductEndpoints\Module;

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

/**
 * Integration class for the netivo/wc-widget-filters module's category filter widget.
 *
 * Keeps the category tree rendered by that widget (top-level categories, the active
 * branch's subcategories, and the "back to parent" link) pointing at the current
 * product endpoint instead of the plain category archive, matching the breadcrumb
 * behavior in Archive::modify_breadcrumbs().
 */
class WidgetFilters {

	/**
	 * WidgetFilters constructor.
	 *
	 * Registers filters for the category filter widget's category tree and parent link.
	 */
	public function __construct() {
		add_filter( 'netivo/widget/filters/categories', [ $this, 'rewrite_category_links' ] );
		add_filter( 'netivo/widget/filters/parent', [ $this, 'rewrite_parent_link' ] );
	}

	/**
	 * Rewrites category and subcategory links to the current product endpoint.
	 *
	 * @param array $categories Category data built by Widget\Filters::print_category_filter().
	 * @return array Modified category data.
	 */
	public function rewrite_category_links( array $categories ): array {
		$var = get_query_var( 'nt_products' );
		if ( empty( $var ) || ! array_key_exists( $var, Module::get_config_array() ) ) {
			return $categories;
		}

		foreach ( $categories as &$category ) {
			$category['link'] = $this->get_category_endpoint_url( $var, $category['id'], $category['link'] );

			foreach ( $category['subcategories'] as &$subcategory ) {
				$subcategory['link'] = $this->get_category_endpoint_url( $var, $subcategory['id'], $subcategory['link'] );
			}
			unset( $subcategory );
		}
		unset( $category );

		return $categories;
	}

	/**
	 * Rewrites the "back to parent category" link to the current product endpoint.
	 *
	 * @param array|null $parent Parent category data built by Widget\Filters::print_category_filter().
	 * @return array|null Modified parent category data.
	 */
	public function rewrite_parent_link( ?array $parent ): ?array {
		if ( empty( $parent ) ) {
			return $parent;
		}

		$var = get_query_var( 'nt_products' );
		if ( empty( $var ) || ! array_key_exists( $var, Module::get_config_array() ) ) {
			return $parent;
		}

		$parent['link'] = $this->get_category_endpoint_url( $var, $parent['id'], $parent['link'] );

		return $parent;
	}

	/**
	 * Resolves the endpoint-aware URL for a product category term id.
	 *
	 * @param string $var      Endpoint ID (the `nt_products` value).
	 * @param int    $term_id  Product category term id.
	 * @param string $fallback Link to return if the term can't be resolved.
	 * @return string The endpoint URL for the category, or the fallback link.
	 */
	protected function get_category_endpoint_url( string $var, int $term_id, string $fallback = '' ): string {
		$term = get_term( $term_id, 'product_cat' );
		if ( empty( $term ) || is_wp_error( $term ) ) {
			return $fallback;
		}

		return Module::get_endpoint_url( $var, $term->slug );
	}
}
