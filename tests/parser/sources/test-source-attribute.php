<?php
/**
 * Class SourceAttributeTest
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

/**
 * Test sourced attributes with the 'attribute' type:
 * https://developer.wordpress.org/block-editor/reference-guides/block-api/block-attributes/#attribute-source
 */
class SourceAttributeTest extends RegistryTestCase {
	public function test_parse_attribute_source() {
		$this->register_block_with_attributes( 'test/image', [
			'url' => [
				'type'      => 'string',
				'source'    => 'attribute',
				'selector'  => 'img',
				'attribute' => 'src',
			],
		] );

		$html = '
			<!-- wp:test/image -->
			<img src="/image.jpg" />
			<!-- /wp:test/image -->
		';

		$expected_blocks = [
			[
				'name'       => 'test/image',
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

	public function test_parse_attribute_source__with_default_value() {
		$this->register_block_with_attributes( 'test/image', [
			'alt' => [
				'type'      => 'string',
				'source'    => 'attribute',
				'selector'  => 'img',
				'attribute' => 'alt',
				'default'   => 'Default alt text',
			],
		] );

		$html = '
			<!-- wp:test/image -->
			<img src="/image.jpg" />
			<!-- /wp:test/image -->
		';

		$expected_blocks = [
			[
				'name'       => 'test/image',
				'attributes' => [
					'alt' => 'Default alt text',
				],
			],
		];

		$content_parser = new ContentParser( $this->registry );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}
}
