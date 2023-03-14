<?php
/**
 * Class NodeSourceTest
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

/**
 * Test sourced attributes with the deprecated 'node' type:
 * https://github.com/WordPress/gutenberg/pull/44265
 */
class NodeSourceTest extends RegistryTestCase {
	public function test_parse_node__with_object_value() {
		$this->register_block_with_attributes( 'test/custom-block', [
			'description' => [
				'type'     => 'object',
				'source'   => 'node',
				'selector' => '.description p',
			],
		] );

		$html = '
			<!-- wp:test/custom-block -->
			<div class="description">
				<p>Description text</p>
			</div>
			<!-- /wp:test/custom-block -->
		';

		$expected_blocks = [
			[
				'name'       => 'test/custom-block',
				'attributes' => [
					'description' => [
						'type'     => 'p',
						'children' => [
							'Description text',
						],
					],
				],
			],
		];

		$content_parser = new ContentParser( $this->registry );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}
}
