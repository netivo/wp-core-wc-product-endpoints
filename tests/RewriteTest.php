<?php

namespace Netivo\Module\WooCommerce\ProductEndpoints\Tests;

use Netivo\Module\WooCommerce\ProductEndpoints\Rewrite;
use Netivo\Module\WooCommerce\ProductEndpoints\Module;
use Brain\Monkey;
use WP_Query;

class RewriteTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		Monkey\Functions\expect( 'get_stylesheet_directory' )
			->andReturn( __DIR__ . '/stubs' );
	}

	/**
	 * Test hook registration.
	 */
	public function test_construct_hooks(): void {
		$this->expectNotToPerformAssertions();

		Monkey\Actions\expectAdded( 'init' )->once();
		Monkey\Filters\expectAdded( 'query_vars' )->once();
		Monkey\Filters\expectAdded( 'pre_get_posts' )->once();

		new Rewrite();
	}

	/**
	 * Test register_shop_endpoints.
	 */
	public function test_register_shop_endpoints(): void {
		$this->expectNotToPerformAssertions();

		$rewrite = new Rewrite();

		Monkey\Functions\expect( 'wc_get_permalink_structure' )
			->andReturn( [ 'category_rewrite_slug' => 'product-category' ] );

		// We have 3 endpoints in our stub: promotions_test, bestsellers_test, custom_test.
		// Each endpoint adds 4 rules, so 12 rewrite rule calls in total.
		Monkey\Functions\expect( 'get_option' )
			->andReturnUsing( function( $option, $default ) {
				return $default;
			} );

		Monkey\Functions\expect( 'esc_attr' )
			->andReturnFirstArg();

		Monkey\Functions\expect( 'add_rewrite_rule' )
			->times( 12 );

		$rewrite->register_shop_endpoints();
	}

	/**
	 * Test query var registration.
	 */
	public function test_register_query_vars(): void {
		$rewrite = new Rewrite();
		$vars = [ 'other_var' ];

		$result = $rewrite->register_query_vars( $vars );
		$this->assertContains( 'nt_products', $result );
	}

	/**
	 * Test modify_shop_query ignores non-main query or admin query.
	 */
	public function test_modify_shop_query_ignores_queries(): void {
		$rewrite = new Rewrite();

		// 1. Not main query
		$query = $this->createMock( WP_Query::class );
		$query->method( 'is_main_query' )->willReturn( false );
		$this->assertSame( $query, $rewrite->modify_shop_query( $query ) );

		// 2. Is admin
		Monkey\Functions\expect( 'is_admin' )->andReturn( true );
		$query2 = $this->createMock( WP_Query::class );
		$query2->method( 'is_main_query' )->willReturn( true );
		$this->assertSame( $query2, $rewrite->modify_shop_query( $query2 ) );
	}

	/**
	 * Test modify_shop_query for promotions endpoint.
	 */
	public function test_modify_shop_query_promotions(): void {
		$rewrite = new Rewrite();

		Monkey\Functions\expect( 'is_admin' )->andReturn( false );
		Monkey\Functions\expect( 'is_post_type_archive' )
			->with( 'product' )
			->andReturn( true );
		Monkey\Functions\expect( 'get_query_var' )
			->with( 'nt_products' )
			->andReturn( 'promotions_test' );

		$query_mock = $this->getMockBuilder( WP_Query::class )
			->onlyMethods( [ 'is_main_query', 'set' ] )
			->getMock();

		$query_mock->method( 'is_main_query' )->willReturn( true );

		// We expect set('post_type', 'product') and set('meta_query', ...)
		$query_mock->expects( $this->exactly( 2 ) )
			->method( 'set' )
			->willReturnCallback( function( $key, $value ) {
				if ( $key === 'post_type' ) {
					$this->assertEquals( 'product', $value );
				} elseif ( $key === 'meta_query' ) {
					$this->assertEquals( 'AND', $value['relation'] );
					$this->assertEquals( '_sale_price', $value[0]['key'] );
				}
			} );

		$rewrite->modify_shop_query( $query_mock );
	}

	/**
	 * Test modify_shop_query for bestsellers endpoint.
	 */
	public function test_modify_shop_query_bestsellers(): void {
		$rewrite = new Rewrite();

		Monkey\Functions\expect( 'is_admin' )->andReturn( false );
		Monkey\Functions\expect( 'is_post_type_archive' )
			->with( 'product' )
			->andReturn( true );
		Monkey\Functions\expect( 'get_query_var' )
			->with( 'nt_products' )
			->andReturn( 'bestsellers_test' );

		$query_mock = $this->getMockBuilder( WP_Query::class )
			->onlyMethods( [ 'is_main_query', 'set' ] )
			->getMock();

		$query_mock->method( 'is_main_query' )->willReturn( true );

		$query_mock->expects( $this->exactly( 2 ) )
			->method( 'set' )
			->willReturnCallback( function( $key, $value ) {
				if ( $key === 'post_type' ) {
					$this->assertEquals( 'product', $value );
				} elseif ( $key === 'meta_query' ) {
					$this->assertEquals( 'AND', $value['relation'] );
					$this->assertEquals( '_total_sales', $value[0]['key'] );
				}
			} );

		$rewrite->modify_shop_query( $query_mock );
	}
}
