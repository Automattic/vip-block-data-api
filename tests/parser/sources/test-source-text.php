<?php
/**
 * Class SourceTextTest
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

/**
 * Test sourced attributes with the 'text' type:
 * https://developer.wordpress.org/block-editor/reference-guides/block-api/block-attributes/#text-source
 */
class SourceTextTest extends RegistryTestCase {
	public function test_parse_text_source() {
		$this->register_block_with_attributes( 'test/figure', [
			'content' => [
				'type'     => 'string',
				'source'   => 'text',
				'selector' => 'figcaption',
			],
		] );

		$html = '
			<!-- wp:test/figure -->
			<figure>
				<img src="/image.jpg" />
				<figcaption>The inner text of the figcaption element</figcaption>
			</figure>
			<!-- /wp:test/figure -->';

		$expected_blocks = [
			[
				'name'       => 'test/figure',
				'attributes' => [
					'content' => 'The inner text of the figcaption element',
				],
			],
		];

		$content_parser = new ContentParser( $this->get_block_registry() );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}

	public function test_parse_text_source__with_html_tags() {
		$this->register_block_with_attributes( 'test/figure', [
			'content' => [
				'type'     => 'string',
				'source'   => 'text',
				'selector' => 'figcaption',
			],
		] );

		$html = '
			<!-- wp:test/figure -->
			<figure>
				<img src="/image.jpg" />
				<figcaption>
					<strong>HTML tags</strong> should be <em>ignored</em> in text attributes
				</figcaption>
			</figure>
			<!-- /wp:test/figure -->';

		$expected_blocks = [
			[
				'name'       => 'test/figure',
				'attributes' => [
					'content' => 'HTML tags should be ignored in text attributes',
				],
			],
		];

		$content_parser = new ContentParser( $this->get_block_registry() );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}

	public function test_parse_text_source__with_default_value() {
		$this->register_block_with_attributes( 'test/figure', [
			'caption' => [
				'type'     => 'string',
				'source'   => 'text',
				'selector' => 'figcaption',
				'default'  => 'Default caption',
			],
		] );

		$html = '
			<!-- wp:test/figure -->
			<figure>
				<img src="/image.jpg" />
			</figure>
			<!-- /wp:test/figure -->';

		$expected_blocks = [
			[
				'name'       => 'test/figure',
				'attributes' => [
					'caption' => 'Default caption',
				],
			],
		];

		$content_parser = new ContentParser( $this->get_block_registry() );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}
}
