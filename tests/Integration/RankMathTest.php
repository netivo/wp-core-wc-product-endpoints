<?php

namespace Netivo\Module\WooCommerce\ProductEndpoints\Tests\Integration;

use Netivo\Module\WooCommerce\ProductEndpoints\Integration\RankMath;
use Netivo\Module\WooCommerce\ProductEndpoints\Tests\TestCase;
use Brain\Monkey;

// Define a stub for RankMath Helper to test template replacement path
if ( ! class_exists( '\RankMath\Helper' ) ) {
	class RankMathHelperStub {
		public static array $settings = [];

		public static function get_settings( string $key ) {
			return self::$settings[ $key ] ?? '';
		}

		public static function replace_vars( string $template, $object ): string {
			return str_replace( '%sep% %sitename%', '- My Site', $template );
		}
	}
	class_alias( RankMathHelperStub::class, '\RankMath\Helper' );
}

class RankMathTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		Monkey\Functions\expect( 'get_stylesheet_directory' )
			->andReturn( __DIR__ . '/../stubs' );
	}

	/**
	 * Test hook registration.
	 */
	public function test_construct_hooks(): void {
		$this->expectNotToPerformAssertions();

		Monkey\Filters\expectAdded( 'rank_math/frontend/title' )->once();
		Monkey\Filters\expectAdded( 'rank_math/frontend/description' )->once();
		Monkey\Filters\expectAdded( 'rank_math/frontend/canonical' )->once();

		new RankMath();
	}

	/**
	 * Test change_title returns original if query var is empty.
	 */
	public function test_change_title_empty(): void {
		$integration = new RankMath();

		Monkey\Functions\expect( 'get_query_var' )
			->with( 'nt_products' )
			->andReturn( '' );

		$this->assertEquals( 'Original Title', $integration->change_title( 'Original Title' ) );
	}

	/**
	 * Test change_title returns modified title if query var is set.
	 */
	public function test_change_title_with_var(): void {
		$integration = new RankMath();

		Monkey\Functions\expect( 'get_query_var' )
			->with( 'nt_products' )
			->andReturn( 'promotions_test' );
		Monkey\Functions\expect( 'is_product_category' )
			->andReturn( false );

		\RankMath\Helper::$settings['titles.pt_product_archive_title'] = '%title% %sep% %sitename%';

		Monkey\Functions\expect( 'get_queried_object' )
			->andReturn( new \stdClass() );

		$this->assertEquals( 'Promotions Test - My Site', $integration->change_title( 'Original Title' ) );
	}

	/**
	 * Test change_description returns original if query var is empty.
	 */
	public function test_change_description_empty(): void {
		$integration = new RankMath();

		Monkey\Functions\expect( 'get_query_var' )
			->with( 'nt_products' )
			->andReturn( '' );

		$this->assertEquals( 'Original Desc', $integration->change_description( 'Original Desc' ) );
	}

	/**
	 * Test change_description returns empty if query var is set.
	 */
	public function test_change_description_with_var(): void {
		$integration = new RankMath();

		Monkey\Functions\expect( 'get_query_var' )
			->with( 'nt_products' )
			->andReturn( 'promotions_test' );

		$this->assertEquals( '', $integration->change_description( 'Original Desc' ) );
	}

	/**
	 * Test change_canonical.
	 */
	public function test_change_canonical(): void {
		$integration = new RankMath();

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

		$this->assertEquals( 'https://example.com/promotions-slug/page/2', $integration->change_canonical( 'original-canonical' ) );
	}
}
