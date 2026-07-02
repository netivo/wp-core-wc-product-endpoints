<?php

namespace {
	// Ensure ABSPATH is defined before loading any code that checks it
	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', sys_get_temp_dir() );
	}

	// Composer autoload
	$autoload = __DIR__ . '/../vendor/autoload.php';
	if ( file_exists( $autoload ) ) {
		require_once $autoload;
	}

	// Provide a minimal WP_Query stub compatible with the Module class usage in tests
	if ( ! class_exists( 'WP_Query' ) ) {
		class WP_Query {
			public array $query_vars = [];

			public function __construct( array $args = [] ) {
				$this->query_vars = $args;
			}

			public function is_tax( string $taxonomy ): bool {
				return (bool) ( $this->query_vars[ 'is_tax_' . $taxonomy ] ?? false );
			}

			public function is_main_query(): bool {
				return true;
			}

			public function get( string $key ) {
				return $this->query_vars[ $key ] ?? null;
			}

			public function set( string $key, $value ): void {
				$this->query_vars[ $key ] = $value;
			}

			public function parse_tax_query( array $qv ): void {
				$this->query_vars = array_merge( $this->query_vars, $qv );
			}

			public function parse_query(): void {
				// no-op for tests
			}
		}
	}
}

// Base TestCase helper to initialize Brain Monkey
namespace Netivo\Module\WooCommerce\ProductEndpoints\Tests {

	use Brain\Monkey;
	use PHPUnit\Framework\TestCase as PhpUnitTestCase;

	abstract class TestCase extends PhpUnitTestCase {
		protected function setUp(): void {
			parent::setUp();
			Monkey\setUp();
			Monkey\Functions\when( '__' )->returnArg( 1 );
			Monkey\Functions\when( 'esc_html__' )->returnArg( 1 );
			Monkey\Functions\when( '_x' )->returnArg( 1 );
			Monkey\Functions\when( 'get_stylesheet_directory' )->justReturn( __DIR__ . '/stubs' );

			// Reset Module singleton
			if ( class_exists( 'Netivo\Module\WooCommerce\ProductEndpoints\Module' ) ) {
				$reflection = new \ReflectionClass( 'Netivo\Module\WooCommerce\ProductEndpoints\Module' );
				$instance   = $reflection->getProperty( 'instance' );
				$instance->setAccessible( true );
				$instance->setValue( null, null );
			}
		}

		protected function tearDown(): void {
			Monkey\tearDown();
			parent::tearDown();
		}
	}
}
