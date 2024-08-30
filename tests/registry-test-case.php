<?php

namespace WPCOMVIP\BlockDataApi;

use WP_Block_Type_Registry;
use WP_Block_Bindings_Registry;
use WP_UnitTestCase;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

/**
 * Sample test case.
 */
class RegistryTestCase extends WP_UnitTestCase {
	use ArraySubsetAsserts;

	protected function tearDown(): void {
		// Unregister non-core blocks.
		$block_registry = WP_Block_Type_Registry::get_instance();
		foreach ( $block_registry->get_all_registered() as $block_type ) {
			if ( 'core/' === substr( $block_type->name, 0, 5 ) ) {
				continue;
			}

			$block_registry->unregister( $block_type->name );
		}

		if ( class_exists( 'WP_Block_Bindings_Registry' ) ) {
			// Unregister non-core block bindings.
			$block_bindings_registry = WP_Block_Bindings_Registry::get_instance();
			foreach ( $block_bindings_registry->get_all_registered() as $source ) {
				if ( 'core/' === substr( $source->name, 0, 5 ) ) {
					continue;
				}

				$block_bindings_registry->unregister( $source->name );
			}
		}

		parent::tearDown();
	}

	/* Helper methods */

	protected function get_block_registry(): WP_Block_Type_Registry {
		return WP_Block_Type_Registry::get_instance();
	}

	protected function register_block_with_attributes( string $block_name, array $attributes, array $additional_args = [] ): void {
		$block_type_args = array_merge( [
			'apiVersion' => 2,
			'attributes' => $attributes,
		], $additional_args );

		$this->get_block_registry()->register( $block_name, $block_type_args );
	}

	protected function register_block_bindings_source( string $source, array $args ): void {
		WP_Block_Bindings_Registry::get_instance()->register( $source, $args );
	}
}
