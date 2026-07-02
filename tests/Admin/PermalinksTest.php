<?php

namespace Netivo\Module\WooCommerce\ProductEndpoints\Tests\Admin;

use Netivo\Module\WooCommerce\ProductEndpoints\Admin\Permalinks;
use Netivo\Module\WooCommerce\ProductEndpoints\Tests\TestCase;
use Netivo\Module\WooCommerce\ProductEndpoints\Module;
use Brain\Monkey;

class PermalinksTest extends TestCase {

	/**
	 * Test hooks registration.
	 */
	public function test_construct_hooks(): void {
		$this->expectNotToPerformAssertions();

		Monkey\Actions\expectAdded( 'load-options-permalink.php' )->twice();

		new Permalinks();
	}

	/**
	 * Test add_settings registration.
	 */
	public function test_add_settings(): void {
		$this->expectNotToPerformAssertions();

		$permalinks = new Permalinks();

		Monkey\Functions\expect( 'add_settings_field' )
			->once()
			->with(
				'netivo_product_endpoints_slugs',
				'Product endpoints',
				[ $permalinks, 'render_settings_field' ],
				'permalink',
				'optional'
			);

		$permalinks->add_settings();
	}

	/**
	 * Test rendering settings inputs field.
	 */
	public function test_render_settings_field(): void {
		$permalinks = new Permalinks();

		Monkey\Functions\expect( 'wp_nonce_field' )
			->once()
			->with( Permalinks::NONCE_ACTION, Permalinks::NONCE_FIELD );

		Monkey\Functions\expect( 'esc_attr' )
			->andReturnFirstArg();

		Monkey\Functions\expect( 'esc_html' )
			->andReturnFirstArg();

		Monkey\Functions\expect( 'get_option' )
			->andReturnUsing( function( $option, $default ) {
				return $default;
			} );

		ob_start();
		$permalinks->render_settings_field();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'netivo-product-endpoints-permalinks', $html );
		$this->assertStringContainsString( 'name="netivo_product_endpoints[promotions_test]"', $html );
		$this->assertStringContainsString( 'name="netivo_product_endpoints[bestsellers_test]"', $html );
		$this->assertStringContainsString( 'value="promotions-slug"', $html );
	}

	/**
	 * Test settings_save process.
	 */
	public function test_settings_save(): void {
		$this->expectNotToPerformAssertions();

		$permalinks = new Permalinks();

		$_POST['netivo_product_endpoints'] = [
			'promotions_test'  => 'new-promo-slug',
			'bestsellers_test' => 'bestsellers-slug', // default config slug is bestsellers-slug, so it should trigger deletion
		];

		Monkey\Functions\expect( 'check_admin_referer' )
			->once()
			->with( Permalinks::NONCE_ACTION, Permalinks::NONCE_FIELD );

		Monkey\Functions\expect( 'wp_unslash' )
			->andReturnFirstArg();

		Monkey\Functions\expect( 'sanitize_title' )
			->andReturnFirstArg();

		// We expect promotions_test to update since slug is new
		Monkey\Functions\expect( 'update_option' )
			->once()
			->with( 'netivo_promotions_test_slug', 'new-promo-slug' )
			->andReturn( true );

		// We expect bestsellers_test to delete since slug matches default slug
		Monkey\Functions\expect( 'delete_option' )
			->once()
			->with( 'netivo_bestsellers_test_slug' )
			->andReturn( true );

		// We expect rewrite rules to be flushed since options changed
		Monkey\Functions\expect( 'flush_rewrite_rules' )
			->once();

		$permalinks->settings_save();

		// Clean up global
		unset( $_POST['netivo_product_endpoints'] );
	}
}
