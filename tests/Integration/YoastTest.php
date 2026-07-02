<?php

namespace Netivo\Module\WooCommerce\ProductEndpoints\Tests\Integration;

use Netivo\Module\WooCommerce\ProductEndpoints\Integration\Yoast;
use Netivo\Module\WooCommerce\ProductEndpoints\Tests\TestCase;
use Brain\Monkey;

// Define a stub for Yoast SEO Options class
if ( ! class_exists( '\WPSEO_Options' ) ) {
	class WPSEOOptionsStub {
		public static array $options = [];

		public static function get( string $key ) {
			return self::$options[ $key ] ?? '';
		}
	}
	class_alias( WPSEOOptionsStub::class, '\WPSEO_Options' );
}

class YoastTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		Monkey\Functions\expect( 'get_stylesheet_directory' )
			->andReturn( __DIR__ . '/../stubs' );
	}

	/**
	 * Test hooks registration.
	 */
	public function test_construct_hooks(): void {
		$this->expectNotToPerformAssertions();

		Monkey\Filters\expectAdded( 'wpseo_title' )->once();
		Monkey\Filters\expectAdded( 'wpseo_metadesc' )->once();
		Monkey\Filters\expectAdded( 'wpseo_canonical' )->once();
		Monkey\Filters\expectAdded( 'wpseo_next_rel_link' )->once();
		Monkey\Filters\expectAdded( 'wpseo_prev_rel_link' )->once();

		new Yoast();
	}

	/**
	 * Test change_title returns original if query var is empty.
	 */
	public function test_change_title_empty(): void {
		$integration = new Yoast();

		Monkey\Functions\expect( 'get_query_var' )
			->with( 'nt_products' )
			->andReturn( '' );

		$this->assertEquals( 'Original Title', $integration->change_title( 'Original Title' ) );
	}

	/**
	 * Test change_title returns modified title if query var is set.
	 */
	public function test_change_title_with_var(): void {
		$integration = new Yoast();

		Monkey\Functions\expect( 'get_query_var' )
			->with( 'nt_products' )
			->andReturn( 'promotions_test' );
		Monkey\Functions\expect( 'is_product_category' )
			->andReturn( false );

		\WPSEO_Options::$options['title-product'] = '%%title%% - My Site';

		Monkey\Functions\expect( 'get_queried_object' )
			->andReturn( new \stdClass() );

		// Mock global function wpseo_replace_vars
		Monkey\Functions\expect( 'wpseo_replace_vars' )
			->once()
			->andReturnUsing( function( $template ) {
				return str_replace( '%%title%%', 'Promotions Test', $template );
			} );

		$this->assertEquals( 'Promotions Test - My Site', $integration->change_title( 'Original Title' ) );
	}

	/**
	 * Test change_description returns original if query var is empty.
	 */
	public function test_change_description_empty(): void {
		$integration = new Yoast();

		Monkey\Functions\expect( 'get_query_var' )
			->with( 'nt_products' )
			->andReturn( '' );

		$this->assertEquals( 'Original Desc', $integration->change_description( 'Original Desc' ) );
	}

	/**
	 * Test change_description returns empty if query var is set.
	 */
	public function test_change_description_with_var(): void {
		$integration = new Yoast();

		Monkey\Functions\expect( 'get_query_var' )
			->with( 'nt_products' )
			->andReturn( 'promotions_test' );

		$this->assertEquals( '', $integration->change_description( 'Original Desc' ) );
	}

	/**
	 * Test change_canonical.
	 */
	public function test_change_canonical(): void {
		$integration = new Yoast();

		Monkey\Functions\expect( 'get_query_var' )
			->andReturnUsing( function( $var ) {
				if ( $var === 'nt_products' ) {
					return 'promotions_test';
				}
				if ( $var === 'paged' ) {
					return 1;
				}
				return null;
			} );

		Monkey\Functions\expect( 'is_product_category' )
			->andReturn( false );

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

		$this->assertEquals( 'https://example.com/promotions-slug/', $integration->change_canonical( 'original-canonical' ) );
	}

	/**
	 * Test change_next_rel_link.
	 */
	public function test_change_next_rel_link(): void {
		$integration = new Yoast();

		Monkey\Functions\expect( 'get_query_var' )
			->andReturnUsing( function( $var ) {
				if ( $var === 'nt_products' ) {
					return 'promotions_test';
				}
				if ( $var === 'paged' ) {
					return 2;
				}
				return null;
			} );

		// Setup global $wp_query mock inside test
		global $wp_query;
		$wp_query_backup = $wp_query;
		$wp_query = new \stdClass();
		$wp_query->max_num_pages = 5;

		// Setup URL helpers
		Monkey\Functions\expect( 'is_product_category' )
			->andReturn( false );
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
		Monkey\Functions\expect( 'user_trailingslashit' )
			->andReturnFirstArg();

		// Since page is 2 and max is 5, next page is 3
		$this->assertEquals(
			'<link rel="next" href="https://example.com/promotions-slug/page/3" />',
			$integration->change_next_rel_link( 'original-next' )
		);

		// Restore backup
		$wp_query = $wp_query_backup;
	}

	/**
	 * Test change_prev_rel_link.
	 */
	public function test_change_prev_rel_link(): void {
		$integration = new Yoast();

		Monkey\Functions\expect( 'get_query_var' )
			->andReturnUsing( function( $var ) {
				if ( $var === 'nt_products' ) {
					return 'promotions_test';
				}
				if ( $var === 'paged' ) {
					return 3;
				}
				return null;
			} );

		// Setup URL helpers
		Monkey\Functions\expect( 'is_product_category' )
			->andReturn( false );
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
		Monkey\Functions\expect( 'user_trailingslashit' )
			->andReturnFirstArg();

		// Since page is 3, prev page is 2
		$this->assertEquals(
			'<link rel="prev" href="https://example.com/promotions-slug/page/2" />',
			$integration->change_prev_rel_link( 'original-prev' )
		);
	}
}
