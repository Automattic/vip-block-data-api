<?php

namespace WPCOMVIP\BlockDataApi;

use WP_Block_Type_Registry;
use WP_UnitTestCase;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

/**
 * Sample test case.
 */
class RegistryTestCase extends WP_UnitTestCase {
	use ArraySubsetAsserts;

	protected $registry;
	protected $globally_registered_blocks = [];

	protected function setUp(): void {
		parent::setUp();

		$this->registry = new WP_Block_Type_Registry();
	}

	protected function tearDown(): void {
		foreach ( $this->globally_registered_blocks as $block_name ) {
			$this->unregister_global_block( $block_name );
		}

		parent::tearDown();
	}

	/* Helper methods */

	protected function register_block_with_attributes( $block_name, $attributes ) {
		$this->registry->register( $block_name, [
			'apiVersion' => 2,
			'attributes' => $attributes,
		] );
	}

	/* Global registrations */

	protected function register_global_block_with_attributes( $block_name, $attributes ) {
		// Use this function for mocking blocks definitions that need to persist across HTTP requests, like GraphQL tests.

		WP_Block_Type_Registry::get_instance()->register( $block_name, [
			'apiVersion' => 2,
			'attributes' => $attributes,
		] );

		$this->globally_registered_blocks[] = $block_name;
	}

	protected function unregister_global_block( $block_name ) {
		$registry = WP_Block_Type_Registry::get_instance();

		if ( $registry->is_registered( $block_name ) ) {
			$registry->unregister( $block_name );
		}
	}
}
