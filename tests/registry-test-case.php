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

	protected function setUp(): void {
		parent::setUp();

		$this->registry = new WP_Block_Type_Registry();
	}

	/* Helper methods */

	protected function register_block_with_attributes( $block_name, $attributes ) {
		$this->registry->register( $block_name, [
			'apiVersion' => 2,
			'attributes' => $attributes,
		] );
	}
}
