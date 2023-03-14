<?php
/**
 * Class ChildrenSourceTest
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

/**
 * Test sourced attributes with the deprecated 'children' type:
 * https://github.com/WordPress/gutenberg/pull/44265
 */
class ChildrenSourceTest extends RegistryTestCase {
	public function test_parse_children__with_list_elements() {
		$this->register_block_with_attributes( 'test/custom-list-children', [
			'steps' => [
				'type'     => 'array',
				'source'   => 'children',
				'selector' => '.steps',
			],
		] );

		$html = '
			<!-- wp:test/custom-list-children -->
			<ul class="steps">
				<li>Step 1</li>
				<li>Step 2</li>
			</ul>
			<!-- /wp:test/custom-list-children -->
		';

		$expected_blocks = [
			[
				'name'       => 'test/custom-list-children',
				'attributes' => [
					'steps' => [
						[
							'type'     => 'li',
							'children' => [
								'Step 1',
							],
						],
						[
							'type'     => 'li',
							'children' => [
								'Step 2',
							],
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

	public function test_parse_children__with_single_child() {
		$this->register_block_with_attributes( 'test/custom-block-with-title', [
			'title' => [
				'type'     => 'array',
				'source'   => 'children',
				'selector' => 'h2',
			],
		] );

		$html = '
			<!-- wp:test/custom-block-with-title -->
			<div>
				<h2>Block title</h2>
			</div>
			<!-- /wp:test/custom-block-with-title -->
		';

		$expected_blocks = [
			[
				'name'       => 'test/custom-block-with-title',
				'attributes' => [
					'title' => [
						'Block title',
					],
				],
			],
		];

		$content_parser = new ContentParser( $this->registry );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}

	public function test_parse_children__with_mixed_nodes_and_text() {
		$this->register_block_with_attributes( 'test/custom-block', [
			'instructions' => [
				'type'     => 'array',
				'source'   => 'children',
				'selector' => '.instructions',
			],
		] );

		$html = '
			<!-- wp:test/custom-block -->
			<div>
				<div class="instructions">Preheat oven to <strong>200 degrees</strong></div>
			</div>
			<!-- /wp:test/custom-block -->
		';

		$expected_blocks = [
			[
				'name'       => 'test/custom-block',
				'attributes' => [
					'instructions' => [
						'Preheat oven to',
						[
							'type'     => 'strong',
							'children' => [
								'200 degrees',
							],
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

	public function test_parse_children__with_default_value() {
		$this->register_block_with_attributes( 'test/custom-block', [
			'unused-value' => [
				'type'     => 'array',
				'default'  => [],
				'source'   => 'children',
				'selector' => '.unused-class',
			],
		] );

		$html = '
			<!-- wp:test/custom-block -->
			<p>Unrelated content</p>
			<!-- /wp:test/custom-block -->
		';

		$expected_blocks = [
			[
				'name'       => 'test/custom-block',
				'attributes' => [
					'unused-value' => [],
				],
			],
		];

		$content_parser = new ContentParser( $this->registry );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}
}
