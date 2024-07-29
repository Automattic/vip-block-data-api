<?php
/**
 * Class ContentParserTest
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

/**
 * Content parser tests that are not source-specific.
 */
class ContentParserTest extends RegistryTestCase {
	/* Multiple attributes */

	public function test_parse_multiple_attributes_from_block() {
		$this->register_block_with_attributes( 'test/captioned-image', [
			'caption' => [
				'type'     => 'string',
				'source'   => 'html',
				'selector' => 'figcaption',
			],
			'url'     => [
				'type'      => 'string',
				'source'    => 'attribute',
				'selector'  => 'img',
				'attribute' => 'src',
			],
		] );

		$html = '
			<!-- wp:test/captioned-image -->
			<div>
				<img src="/wp-content/uploads/test-image.png" />
				<figcaption>Test caption</figcaption>
			</div>
			<!-- /wp:test/captioned-image -->
		';

		$expected_blocks = [
			[
				'name'       => 'test/captioned-image',
				'attributes' => [
					'caption' => 'Test caption',
					'url'     => '/wp-content/uploads/test-image.png',
				],
			],
		];

		$content_parser = new ContentParser( $this->registry );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}

	/* Multiple blocks */

	public function test_parse_multiple_blocks() {
		$this->register_block_with_attributes( 'test/block1', [
			'content' => [
				'type'     => 'string',
				'source'   => 'html',
				'selector' => 'div.a',
			],
		] );

		$this->register_block_with_attributes( 'test/block2', [
			'url' => [
				'type'      => 'string',
				'source'    => 'attribute',
				'selector'  => 'img',
				'attribute' => 'src',
			],
		] );

		$html = '
			<!-- wp:test/block1 -->
			<div class="a">Block 1</div>
			<!-- /wp:test/block1 -->

			<!-- wp:test/block2 -->
			<img src="/image.jpg" />
			<!-- /wp:test/block2 -->
		';

		$expected_blocks = [
			[
				'name'       => 'test/block1',
				'attributes' => [
					'content' => 'Block 1',
				],
			],
			[
				'name'       => 'test/block2',
				'attributes' => [
					'url' => '/image.jpg',
				],
			],
		];

		$content_parser = new ContentParser( $this->registry );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}

	/* Default values and missing values */

	public function test_parse_block_missing_attributes_and_defaults() {
		$this->register_block_with_attributes( 'test/block-with-empty-attributes', [
			'attributeOneWithDefaultValueAndSource'      => [
				'type'      => 'string',
				'source'    => 'attribute',
				'selector'  => 'div',
				'attribute' => 'data-attr-one',
				'default'   => 'Default Attribute 1 Value',
			],
			'attributeTwoWithDefaultValueAndNoSource'    => [
				'type'      => 'string',
				'source'    => 'attribute',
				'selector'  => 'div',
				'attribute' => 'data-attr-two',
				'default'   => 'Default Attribute 2 Value',
			],
			'attributeThreeWithNoDefaultValueAndSource'  => [
				'type'      => 'string',
				'source'    => 'attribute',
				'selector'  => 'div',
				'attribute' => 'data-attr-three',
			],
			'attributeFourWithNoDefaultValueAndNoSource' => [
				'type'      => 'string',
				'source'    => 'attribute',
				'selector'  => 'div',
				'attribute' => 'data-attr-four',
			],
		] );

		$html = '
			<!-- wp:test/block-with-empty-attributes -->
			<div
				data-attr-one="Attribute 1 Value"
				data-attr-three="Attribute 3 Value"
			>Content</div>
			<!-- /wp:test/block-with-empty-attributes -->
		';

		$expected_blocks = [
			[
				'name'       => 'test/block-with-empty-attributes',
				'attributes' => [
					'attributeOneWithDefaultValueAndSource'   => 'Attribute 1 Value',
					'attributeTwoWithDefaultValueAndNoSource' => 'Default Attribute 2 Value',
					'attributeThreeWithNoDefaultValueAndSource' => 'Attribute 3 Value',
					// attributeFourWithNoDefaultValueAndNoSource has no default, not represented
				],
			],
		];

		$content_parser = new ContentParser( $this->registry );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}
}
