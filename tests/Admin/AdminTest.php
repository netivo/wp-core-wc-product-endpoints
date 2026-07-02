<?php

namespace Netivo\Module\WooCommerce\ProductEndpoints\Tests\Admin;

use Netivo\Module\WooCommerce\ProductEndpoints\Admin\Admin;
use Netivo\Module\WooCommerce\ProductEndpoints\Tests\TestCase;
use Brain\Monkey;

class AdminTest extends TestCase {

	/**
	 * Test that instantiating Admin instantiates Permalinks and adds hooks.
	 */
	public function test_construct(): void {
		$this->expectNotToPerformAssertions();

		// Permalinks constructor registers load-options-permalink.php action twice
		Monkey\Actions\expectAdded( 'load-options-permalink.php' )->twice();

		new Admin();
	}
}
