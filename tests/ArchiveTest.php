<?php

namespace Netivo\Module\WooCommerce\ProductEndpoints\Tests;

use Netivo\Module\WooCommerce\ProductEndpoints\Archive;
use Netivo\Module\WooCommerce\ProductEndpoints\Module;
use Brain\Monkey;

class ArchiveTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		// Set up Module stub config
		Monkey\Functions\expect( 'get_stylesheet_directory' )
			->andReturn( __DIR__ . '/stubs' );
	}

	/**
	 * Test hooks registration.
	 */
	public function test_construct_hooks(): void {
		$this->expectNotToPerformAssertions();

		Monkey\Filters\expectAdded( 'woocommerce_page_title' )->once();
		Monkey\Filters\expectAdded( 'woocommerce_taxonomy_archive_description_raw' )->once();
		Monkey\Filters\expectAdded( 'woocommerce_product_archive_description' )->once();
		Monkey\Filters\expectAdded( 'woocommerce_get_breadcrumb' )->once();

		new Archive();
	}

	/**
	 * Test page title when nt_products is empty.
	 */
	public function test_change_woocommerce_page_title_empty(): void {
		$archive = new Archive();

		Monkey\Functions\expect( 'get_query_var' )
			->with( 'nt_products' )
			->andReturn( '' );

		$this->assertEquals( 'Original Title', $archive->change_woocommerce_page_title( 'Original Title' ) );
	}

	/**
	 * Test page title on non-category custom endpoints.
	 */
	public function test_change_woocommerce_page_title_non_category(): void {
		$archive = new Archive();

		Monkey\Functions\expect( 'get_query_var' )
			->with( 'nt_products' )
			->andReturn( 'promotions_test' );
		Monkey\Functions\expect( 'is_product_category' )
			->andReturn( false );

		$this->assertEquals( 'Promotions Test', $archive->change_woocommerce_page_title( 'Original Title' ) );
	}

	/**
	 * Test page title on category custom endpoints.
	 */
	public function test_change_woocommerce_page_title_category(): void {
		$archive = new Archive();

		Monkey\Functions\expect( 'get_query_var' )
			->with( 'nt_products' )
			->andReturn( 'promotions_test' );
		Monkey\Functions\expect( 'is_product_category' )
			->andReturn( true );

		$catObj = new \stdClass();
		$catObj->name = 'Shoes';
		Monkey\Functions\expect( 'get_queried_object' )
			->andReturn( $catObj );

		$this->assertEquals( 'Promotions in Shoes', $archive->change_woocommerce_page_title( 'Original Title' ) );
	}

	/**
	 * Test page description when nt_products is empty.
	 */
	public function test_change_woocommerce_description_empty(): void {
		$archive = new Archive();

		Monkey\Functions\expect( 'get_query_var' )
			->with( 'nt_products' )
			->andReturn( '' );

		$this->assertEquals( 'Original Desc', $archive->change_woocommerce_description( 'Original Desc' ) );
	}

	/**
	 * Test page description on custom product endpoints.
	 */
	public function test_change_woocommerce_description_with_var(): void {
		$archive = new Archive();

		Monkey\Functions\expect( 'get_query_var' )
			->with( 'nt_products' )
			->andReturn( 'promotions_test' );

		$this->assertEquals( '', $archive->change_woocommerce_description( 'Original Desc' ) );
	}

	/**
	 * Test modify breadcrumbs when nt_products is empty.
	 */
	public function test_modify_breadcrumbs_empty(): void {
		$archive = new Archive();
		Monkey\Functions\expect( 'get_query_var' )
			->with( 'nt_products' )
			->andReturn( '' );

		$crumbs = [ [ 'Home', '/' ] ];
		$this->assertEquals( $crumbs, $archive->modify_breadcrumbs( $crumbs ) );
	}

	/**
	 * Test modify breadcrumbs for custom endpoint in non-category archives.
	 */
	public function test_modify_breadcrumbs_non_category(): void {
		$archive = new Archive();

		Monkey\Functions\expect( 'get_query_var' )
			->andReturnUsing( function( $var ) {
				if ( $var === 'nt_products' ) {
					return 'promotions_test';
				}
				if ( $var === 'paged' ) {
					return 0;
				}
				return null;
			} );

		Monkey\Functions\expect( 'is_product_category' )
			->andReturn( false );

		// Setup endpoint URL dependencies
		Monkey\Functions\expect( 'get_option' )
			->with( 'netivo_promotions_test_slug', 'promotions-slug' )
			->andReturn( 'promotions-slug' );
		Monkey\Functions\expect( 'esc_attr' )
			->andReturnFirstArg();
		Monkey\Functions\expect( 'wc_get_permalink_structure' )
			->andReturn( [ 'category_rewrite_slug' => 'product-category' ] );
		Monkey\Functions\expect( 'home_url' )
			->with( 'promotions-slug/' )
			->andReturn( 'https://example.com/promotions-slug/' );

		$crumbs = [ [ 'Home', 'https://example.com' ] ];
		$expected = [
			[ 'Home', 'https://example.com' ],
			[ 'Promotions Crumb', 'https://example.com/promotions-slug/' ],
		];

		$this->assertEquals( $expected, $archive->modify_breadcrumbs( $crumbs ) );
	}

	/**
	 * Test modify breadcrumbs in category archives.
	 */
	public function test_modify_breadcrumbs_category(): void {
		$archive = new Archive();

		Monkey\Functions\expect( 'get_query_var' )
			->andReturnUsing( function( $var ) {
				if ( $var === 'nt_products' ) {
					return 'promotions_test';
				}
				if ( $var === 'paged' ) {
					return 0;
				}
				return null;
			} );

		Monkey\Functions\expect( 'is_product_category' )
			->andReturn( true );

		$catObj = new \stdClass();
		$catObj->term_id = 45;
		$catObj->slug = 'shoes';
		Monkey\Functions\expect( 'get_queried_object' )
			->andReturn( $catObj );

		Monkey\Functions\expect( 'get_ancestors' )
			->with( 45, 'product_cat' )
			->andReturn( [] ); // No parent categories

		// Stub categories resolution inside modify_breadcrumbs loop
		$termObj = new \stdClass();
		$termObj->slug = 'shoes';
		Monkey\Functions\expect( 'get_term_by' )
			->with( 'name', 'Shoes', 'product_cat' )
			->andReturn( $termObj );

		// Setup endpoint URL helpers
		Monkey\Functions\expect( 'get_option' )
			->with( 'netivo_promotions_test_slug', 'promotions-slug' )
			->andReturn( 'promotions-slug' );
		Monkey\Functions\expect( 'esc_attr' )
			->andReturnFirstArg();
		Monkey\Functions\expect( 'wc_get_permalink_structure' )
			->andReturn( [ 'category_rewrite_slug' => 'product-category' ] );
		Monkey\Functions\expect( 'home_url' )
			->andReturnUsing( function( $path ) {
				return 'https://example.com/' . $path;
			} );

		$crumbs = [
			[ 'Home', 'https://example.com' ],
			[ 'Shoes', 'https://example.com/product-category/shoes/' ]
		];

		$result = $archive->modify_breadcrumbs( $crumbs );

		// The Category link should have changed to the custom endpoint path,
		// and the custom endpoint crumb should be prepended before the category crumb.
		$expected = [
			[ 'Home', 'https://example.com' ],
			[ 'Promotions Crumb', 'https://example.com/promotions-slug/' ],
			[ 'Shoes', 'https://example.com/promotions-slug/product-category/shoes/' ]
		];

		$this->assertEquals( $expected, $result );
	}
}
