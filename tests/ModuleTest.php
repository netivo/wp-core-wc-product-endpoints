<?php

namespace Netivo\Module\WooCommerce\ProductEndpoints\Tests;

use Netivo\Module\WooCommerce\ProductEndpoints\Module;
use Brain\Monkey;

class ModuleTest extends TestCase {

	/**
	 * Reset the singleton instance before each test.
	 */
	protected function setUp(): void {
		parent::setUp();

		$reflection = new \ReflectionClass( Module::class );
		$instance   = $reflection->getProperty( 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );
	}

	/**
	 * Test singleton instance retrieval.
	 */
	public function test_get_instance(): void {
		$instance1 = Module::get_instance();
		$instance2 = Module::get_instance();

		$this->assertInstanceOf( Module::class, $instance1 );
		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test configuration loading.
	 */
	public function test_init_config(): void {
		Monkey\Functions\expect( 'get_stylesheet_directory' )
			->andReturn( __DIR__ . '/stubs' );

		$module = Module::get_instance();
		$config = $module->get_config();

		$this->assertArrayHasKey( 'promotions_test', $config );
		$this->assertArrayHasKey( 'bestsellers_test', $config );
		$this->assertArrayHasKey( 'custom_test', $config );
		$this->assertEquals( 'promotions-slug', $config['promotions_test']['default_slug'] );
	}

	/**
	 * Test static config array access.
	 */
	public function test_get_config_array(): void {
		Monkey\Functions\expect( 'get_stylesheet_directory' )
			->andReturn( __DIR__ . '/stubs' );

		$config = Module::get_config_array();

		$this->assertIsArray( $config );
		$this->assertArrayHasKey( 'promotions_test', $config );
	}

	/**
	 * Test bootstrap logic under non-admin request.
	 */
	public function test_init_non_admin(): void {
		$this->expectNotToPerformAssertions();

		Monkey\Functions\expect( 'is_admin' )
			->andReturn( false );

		Monkey\Functions\expect( 'get_stylesheet_directory' )
			->andReturn( __DIR__ . '/stubs' );

		// We expect hook registration for Rewrite, Archive, Yoast, and RankMath.
		Monkey\Actions\expectAdded( 'init' )->once();
		Monkey\Filters\expectAdded( 'query_vars' )->once();
		Monkey\Filters\expectAdded( 'pre_get_posts' )->once();

		Monkey\Filters\expectAdded( 'woocommerce_page_title' )->once();
		Monkey\Filters\expectAdded( 'woocommerce_taxonomy_archive_description_raw' )->once();
		Monkey\Filters\expectAdded( 'woocommerce_product_archive_description' )->once();
		Monkey\Filters\expectAdded( 'woocommerce_get_breadcrumb' )->once();

		Monkey\Filters\expectAdded( 'wpseo_title' )->once();
		Monkey\Filters\expectAdded( 'wpseo_metadesc' )->once();
		Monkey\Filters\expectAdded( 'wpseo_canonical' )->once();
		Monkey\Filters\expectAdded( 'wpseo_next_rel_link' )->once();
		Monkey\Filters\expectAdded( 'wpseo_prev_rel_link' )->once();

		Monkey\Filters\expectAdded( 'rank_math/frontend/title' )->once();
		Monkey\Filters\expectAdded( 'rank_math/frontend/description' )->once();
		Monkey\Filters\expectAdded( 'rank_math/frontend/canonical' )->once();

		// We expect no admin load-options action
		Monkey\Actions\expectAdded( 'load-options-permalink.php' )->never();

		Module::get_instance()->init();
	}

	/**
	 * Test bootstrap logic under admin request.
	 */
	public function test_init_admin(): void {
		$this->expectNotToPerformAssertions();

		Monkey\Functions\expect( 'is_admin' )
			->andReturn( true );

		Monkey\Functions\expect( 'get_stylesheet_directory' )
			->andReturn( __DIR__ . '/stubs' );

		// Expect permalink load actions
		Monkey\Actions\expectAdded( 'load-options-permalink.php' )->twice();

		Module::get_instance()->init();
	}
}
