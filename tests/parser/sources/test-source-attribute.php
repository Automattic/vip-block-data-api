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

		$content_parser = new ContentParser( $this->get_block_registry() );
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

		$content_parser = new ContentParser( $this->get_block_registry() );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}

	public function test_parse_attribute_source__with_asterisk_selector() {
		$this->register_block_with_attributes( 'test/image', [
			'anchor' => [
				'type'      => 'string',
				'source'    => 'attribute',
				'selector'  => '*',
				'attribute' => 'id',
			],
		] );

		$html = '
			<!-- wp:test/image -->
			<img src="/image.jpg" id="anchor123" />
			<!-- /wp:test/image -->
		';

		$expected_blocks = [
			[
				'name'       => 'test/image',
				'attributes' => [
					'anchor' => 'anchor123',
				],
			],
		];

		$content_parser = new ContentParser( $this->get_block_registry() );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}

	public function test_parse_boolean_attribute_source_with_true_default() {
		$this->register_block_with_attributes( 'test/video', [
			'controls' => [
				'type'      => 'boolean',
				'source'    => 'attribute',
				'selector'  => 'video',
				'attribute' => 'controls',
				'default'   => true,
			],
		] );

		$content_parser  = new ContentParser( $this->get_block_registry() );
		$without         = $content_parser->parse( '<!-- wp:test/video --><video src="/video.mp4"></video><!-- /wp:test/video -->' );
		$with            = $content_parser->parse( '<!-- wp:test/video --><video controls src="/video.mp4"></video><!-- /wp:test/video -->' );
		$with_false_text = $content_parser->parse( '<!-- wp:test/video --><video controls="false" src="/video.mp4"></video><!-- /wp:test/video -->' );
		$missing_video   = $content_parser->parse( '<!-- wp:test/video --><p>No video</p><!-- /wp:test/video -->' );

		$this->assertIsArray( $without );
		$this->assertIsArray( $with );
		$this->assertIsArray( $with_false_text );
		$this->assertIsArray( $missing_video );
		$this->assertFalse( $without['blocks'][0]['attributes']['controls'] );
		$this->assertTrue( $with['blocks'][0]['attributes']['controls'] );
		$this->assertTrue( $with_false_text['blocks'][0]['attributes']['controls'] );
		$this->assertTrue( $missing_video['blocks'][0]['attributes']['controls'] );
	}
}
