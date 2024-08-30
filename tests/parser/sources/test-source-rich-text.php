<?php
/**
 * Class SourceHtmlTest
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

/**
 * Test sourced attributes with the 'rich-text' type, added in WordPress 6.5:
 * https://github.com/WordPress/gutenberg/pull/43204
 */
class SourceRichTextTest extends RegistryTestCase {
	public function test_parse_rich_text_source() {
		$this->register_block_with_attributes( 'test/code', [
			'content' => [
				'type'     => 'rich-text',
				'source'   => 'rich-text',
				'selector' => 'code',
			],
		] );

		$html = '
		<!-- wp:test/code -->
		<pre class="wp-block-code"><code>This is a code block &lt;strong&gt;See!&lt;/strong&gt;</code></pre>
		<!-- /wp:test/code -->
		';

		$expected_blocks = [
			[
				'name'       => 'test/code',
				'attributes' => [
					'content' => 'This is a code block &lt;strong&gt;See!&lt;/strong&gt;',
				],
			],
		];

		$content_parser = new ContentParser( $this->get_block_registry() );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}

	public function test_parse_rich_text_source__with_formatting() {
		$this->register_block_with_attributes( 'test/captioned-image', [
			'caption' => [
				'type'     => 'rich-text',
				'source'   => 'rich-text',
				'selector' => 'figcaption',
			],
		] );

		$html = '
		<!-- wp:test/captioned-image -->
		<figure>
			<img src="http://example.com/image.jpg" />
			<figcaption><strong>RICH</strong> text caption.</figcaption>
		</figure>
		<!-- /wp:test/captioned-image -->
		';

		$expected_blocks = [
			[
				'name'       => 'test/captioned-image',
				'attributes' => [
					'caption' => '<strong>RICH</strong> text caption.',
				],
			],
		];

		$content_parser = new ContentParser( $this->get_block_registry() );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}

	public function test_parse_rich_text_source__with_default_value() {
		$this->register_block_with_attributes( 'test/image', [
			'caption' => [
				'type'     => 'rich-text',
				'source'   => 'rich-text',
				'selector' => 'figcaption',
				'default'  => 'Default rich-text caption',
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
					'caption' => 'Default rich-text caption',
				],
			],
		];

		$content_parser = new ContentParser( $this->get_block_registry() );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}

	public function test_parse_rich_text_source__with_default_value_with_formatting() {
		$this->register_block_with_attributes( 'test/image', [
			'caption' => [
				'type'     => 'rich-text',
				'source'   => 'rich-text',
				'selector' => 'figcaption',
				'default'  => 'Default <em>rich-text</em> caption',
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
					'caption' => 'Default <em>rich-text</em> caption',
				],
			],
		];

		$content_parser = new ContentParser( $this->get_block_registry() );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}
}
