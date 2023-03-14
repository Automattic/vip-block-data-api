<?php
/**
 * Class SourceHtmlTest
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

/**
 * Test sourced attributes with the 'html' type:
 * https://developer.wordpress.org/block-editor/reference-guides/block-api/block-attributes/#html-source
 */
class SourceHtmlTest extends RegistryTestCase {
	public function test_parse_html_source() {
		$this->register_block_with_attributes( 'test/paragraph', [
			'content' => [
				'type'     => 'string',
				'source'   => 'html',
				'selector' => 'p',
			],
		] );

		$html = '
			<!-- wp:test/paragraph -->
			<p>Test paragraph <strong>with HTML</strong></p>
			<!-- /wp:test/paragraph -->
		';

		$expected_blocks = [
			[
				'name'       => 'test/paragraph',
				'attributes' => [
					'content' => 'Test paragraph <strong>with HTML</strong>',
				],
			],
		];

		$content_parser = new ContentParser( $this->registry );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}

	public function test_parse_html_source__with_multiline_selector() {
		$this->register_block_with_attributes( 'test/quote', [
			'content' => [
				'type'      => 'string',
				'source'    => 'html',
				'selector'  => 'blockquote',
				'multiline' => 'p',
			],
		] );

		$html = '
			<!-- wp:test/quote -->
			<div>
				<blockquote>
					<p>Line 1</p>
					<p>Line 2</p>
				</blockquote>
			</div>
			<!-- /wp:test/quote -->';

		$expected_blocks = [
			[
				'name'       => 'test/quote',
				'attributes' => [
					'content' => '<p>Line 1</p><p>Line 2</p>',
				],
			],
		];

		$content_parser = new ContentParser( $this->registry );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}

	public function test_parse_html_source__with_default_value() {
		$this->register_block_with_attributes( 'test/image', [
			'caption' => [
				'type'     => 'string',
				'source'   => 'html',
				'selector' => 'figcaption',
				'default'  => 'Default image caption',
			],
		] );

		$html = '
			<!-- wp:test/image -->
			<img src="/test.jpg" />
			<!-- /wp:test/image -->
		';

		$expected_blocks = [
			[
				'name'       => 'test/image',
				'attributes' => [
					'caption' => 'Default image caption',
				],
			],
		];

		$content_parser = new ContentParser( $this->registry );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}
}
