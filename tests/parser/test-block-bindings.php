<?php
/**
 * Class BlockBindingsTest
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

use WP_Block;

/**
 * Test parsing blocks with block binding.
 */
class BlockBindingsTest extends RegistryTestCase {
	protected function setUp(): void {
		parent::setUp();

		$this->ensure_core_blocks_are_registered();
	}

	/* Single paragraph block binding */

	public function test_single_paragraph_block_binding() {

		$this->register_block_bindings_source(
			'test/block-binding',
			[
				'label'              => 'Test paragraph block binding',
				'get_value_callback' => static function ( array $args, WP_Block $block ) {
					return sprintf( 'Block binding for %s with arg foo=%s', $block->name, $args['foo'] );
				},
			]
		);

		$html = '
			<!-- wp:core/paragraph {"metadata":{"bindings":{"content":{"source":"test/block-binding","args":{"foo":"bar"}}}}} -->
			<p>Fallback content</p>
			<!-- /wp:core/paragraph -->
		';

		$expected_blocks = [
			[
				'name'       => 'core/paragraph',
				'attributes' => [
					'content' => 'Block binding for core/paragraph with arg foo=bar',
				],
			],
		];

		$content_parser = new ContentParser( $this->get_block_registry() );
		$blocks         = $content_parser->parse( $html );

		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], false, wp_json_encode( $blocks['blocks'] ) );
	}

	/* Nested paragraph block binding with context */

	public function test_nested_paragraph_block_binding_with_custom_context() {
		$this->register_block_bindings_source(
			'test/block-binding-with-custom-context', [
				'label'              => 'Test paragraph block binding with custom context',
				'get_value_callback' => static function ( array $args, WP_Block $block ) {
					return sprintf( 'Block binding for %s with arg foo=%s and context fizz=%s', $block->name, $args['foo'], $block->context['my-context/fizz'] ?? 'missing' );
				},
				'uses_context'       => [ 'my-context/fizz' ],
			]
		);

		$this->register_block_with_attributes(
			'test/context-provider',
			[
				'fizz' => [
					'type' => 'string',
				],
			],
			[
				'provides_context' => [
					'my-context/fizz' => 'fizz',
				],
			]
		);

		$html = '
		<!-- wp:test/context-provider {"fizz":"buzz"} -->
		<!-- wp:core/paragraph {"metadata":{"bindings":{"content":{"source":"test/block-binding-with-custom-context","args":{"foo":"bar"}}}}} -->
		<p>Fallback content</p>
		<!-- /wp:core/paragraph -->
		<!-- /wp:test/context-provider -->
		';

		$expected_blocks = [
			[
				'name'        => 'test/context-provider',
				'attributes'  => [
					'fizz' => 'buzz',
				],
				'innerBlocks' => [
					[
						'name'       => 'core/paragraph',
						'attributes' => [
							'content' => 'Block binding for core/paragraph with arg foo=bar and context fizz=buzz',
						],
					],
				],
			],
		];

		$content_parser = new ContentParser( $this->get_block_registry() );
		$blocks         = $content_parser->parse( $html );

		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], false, wp_json_encode( $blocks['blocks'] ) );
	}
}
