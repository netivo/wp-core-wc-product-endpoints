<?php

namespace Netivo\Module\WooCommerce\ProductEndpoints\Tests\Integration;

use Netivo\Module\WooCommerce\ProductEndpoints\Integration\WidgetFilters;
use Netivo\Module\WooCommerce\ProductEndpoints\Tests\TestCase;
use Brain\Monkey;

class WidgetFiltersTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		Monkey\Functions\expect( 'get_stylesheet_directory' )
			->andReturn( __DIR__ . '/../stubs' );

		Monkey\Functions\when( 'is_wp_error' )->justReturn( false );
	}

	/**
	 * Test hooks registration.
	 */
	public function test_construct_hooks(): void {
		$this->expectNotToPerformAssertions();

		Monkey\Filters\expectAdded( 'netivo/widget/filters/categories' )->once();
		Monkey\Filters\expectAdded( 'netivo/widget/filters/parent' )->once();

		new WidgetFilters();
	}

	/**
	 * Test categories are left untouched when nt_products is empty.
	 */
	public function test_rewrite_category_links_empty_var(): void {
		$widget_filters = new WidgetFilters();

		Monkey\Functions\expect( 'get_query_var' )
			->with( 'nt_products' )
			->andReturn( '' );

		$categories = [
			[ 'id' => 5, 'link' => 'https://example.com/product-category/shoes/', 'subcategories' => [] ],
		];

		$this->assertEquals( $categories, $widget_filters->rewrite_category_links( $categories ) );
	}

	/**
	 * Test category, subcategory, and parent links are rewritten to the endpoint URL.
	 */
	public function test_rewrite_category_links_with_var(): void {
		$widget_filters = new WidgetFilters();

		Monkey\Functions\expect( 'get_query_var' )
			->with( 'nt_products' )
			->andReturn( 'promotions_test' );

		Monkey\Functions\expect( 'get_term' )
			->andReturnUsing( function ( $term_id, $taxonomy ) {
				$term       = new \stdClass();
				$term->slug = $term_id === 5 ? 'shoes' : 'boots';

				return $term;
			} );

		Monkey\Functions\expect( 'get_option' )
			->with( 'netivo_promotions_test_slug', 'promotions-slug' )
			->andReturn( 'promotions-slug' );
		Monkey\Functions\expect( 'esc_attr' )
			->andReturnFirstArg();
		Monkey\Functions\expect( 'wc_get_permalink_structure' )
			->andReturn( [ 'category_rewrite_slug' => 'product-category' ] );
		Monkey\Functions\expect( 'home_url' )
			->andReturnUsing( function ( $path ) {
				return 'https://example.com/' . $path;
			} );

		$categories = [
			[
				'id'             => 5,
				'link'           => 'https://example.com/product-category/shoes/',
				'subcategories'  => [
					[ 'id' => 6, 'link' => 'https://example.com/product-category/boots/' ],
				],
			],
		];

		$expected = [
			[
				'id'            => 5,
				'link'          => 'https://example.com/promotions-slug/product-category/shoes/',
				'subcategories' => [
					[ 'id' => 6, 'link' => 'https://example.com/promotions-slug/product-category/boots/' ],
				],
			],
		];

		$this->assertEquals( $expected, $widget_filters->rewrite_category_links( $categories ) );
	}

	/**
	 * Test unresolved terms fall back to the original link instead of breaking it.
	 */
	public function test_rewrite_category_links_unresolved_term_keeps_fallback(): void {
		$widget_filters = new WidgetFilters();

		Monkey\Functions\expect( 'get_query_var' )
			->with( 'nt_products' )
			->andReturn( 'promotions_test' );

		Monkey\Functions\expect( 'get_term' )
			->andReturn( null );

		$categories = [
			[ 'id' => 5, 'link' => 'https://example.com/product-category/shoes/', 'subcategories' => [] ],
		];

		$this->assertEquals( $categories, $widget_filters->rewrite_category_links( $categories ) );
	}

	/**
	 * Test the parent link is left untouched when there is no parent.
	 */
	public function test_rewrite_parent_link_empty_parent(): void {
		$widget_filters = new WidgetFilters();

		$this->assertNull( $widget_filters->rewrite_parent_link( null ) );
	}

	/**
	 * Test the parent link is left untouched when nt_products is empty.
	 */
	public function test_rewrite_parent_link_empty_var(): void {
		$widget_filters = new WidgetFilters();

		Monkey\Functions\expect( 'get_query_var' )
			->with( 'nt_products' )
			->andReturn( '' );

		$parent = [ 'id' => 3, 'link' => 'https://example.com/product-category/parent/', 'name' => 'Parent' ];

		$this->assertEquals( $parent, $widget_filters->rewrite_parent_link( $parent ) );
	}

	/**
	 * Test the parent link is rewritten to the endpoint URL.
	 */
	public function test_rewrite_parent_link_with_var(): void {
		$widget_filters = new WidgetFilters();

		Monkey\Functions\expect( 'get_query_var' )
			->with( 'nt_products' )
			->andReturn( 'promotions_test' );

		$term       = new \stdClass();
		$term->slug = 'parent-cat';
		Monkey\Functions\expect( 'get_term' )
			->with( 3, 'product_cat' )
			->andReturn( $term );

		Monkey\Functions\expect( 'get_option' )
			->with( 'netivo_promotions_test_slug', 'promotions-slug' )
			->andReturn( 'promotions-slug' );
		Monkey\Functions\expect( 'esc_attr' )
			->andReturnFirstArg();
		Monkey\Functions\expect( 'wc_get_permalink_structure' )
			->andReturn( [ 'category_rewrite_slug' => 'product-category' ] );
		Monkey\Functions\expect( 'home_url' )
			->andReturnUsing( function ( $path ) {
				return 'https://example.com/' . $path;
			} );

		$parent = [ 'id' => 3, 'link' => 'https://example.com/product-category/parent-cat/', 'name' => 'Parent' ];

		$expected = [ 'id' => 3, 'link' => 'https://example.com/promotions-slug/product-category/parent-cat/', 'name' => 'Parent' ];

		$this->assertEquals( $expected, $widget_filters->rewrite_parent_link( $parent ) );
	}
}
