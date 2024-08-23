<?php

namespace WPCOMVIP\BlockDataApi;

use WP_Block_Type_Registry;
use WP_Block_Bindings_Registry;
use WP_UnitTestCase;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use function register_core_block_types_from_metadata;

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

		if ( class_exists( 'WP_Block_Bindings_Registry' ) ) {
			$block_bindings_registry = WP_Block_Bindings_Registry::get_instance();
			foreach ( $block_bindings_registry->get_all_registered() as $source ) {
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

	/**
	 * Register core static (not dynamic) blocks.
	 */
	protected function ensure_core_blocks_are_registered(): void {
		if ( empty( WP_Block_Type_Registry::get_instance()->get_all_registered() ) ) {
			register_core_block_types_from_metadata();
		}
	}
}
