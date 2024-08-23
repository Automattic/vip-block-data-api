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

	protected function tearDown(): void {
		$block_registry = WP_Block_Type_Registry::get_instance();
		foreach ( $block_registry->get_all_registered() as $block ) {
			$block_registry->unregister( $block->name );
		}

		parent::tearDown();
	}

	/* Helper methods */

	protected function get_block_registry(): WP_Block_Type_Registry {
		return WP_Block_Type_Registry::get_instance();
	}

	protected function register_block_with_attributes( string $block_name, array $attributes ): void {
		$this->get_block_registry()->register( $block_name, [
			'apiVersion' => 2,
			'attributes' => $attributes,
		] );
	}
}
