<?php
/**
 * Class SourceRawTest
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

/**
 * Test sourced attributes with the 'raw' type.
 */
class SourceRawTest extends RegistryTestCase {
	public function test_parse_raw_source() {
		$this->register_block_with_attributes( 'test/html', [
			'content' => [
				'type'   => 'string',
				'source' => 'raw',
			],
		] );

		$html = '
			<!-- wp:test/html -->
			<div style="border: 1px solid #999"><p>Custom HTML block</p></div>
			<!-- /wp:test/html -->';

		$expected_blocks = [
			[
				'name'       => 'test/html',
				'attributes' => [
					'content' => '<div style="border: 1px solid #999"><p>Custom HTML block</p></div>',
				],
			],
		];

		$content_parser = new ContentParser( $this->get_block_registry() );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}

	public function test_parse_raw_source__nested() {
		// Ensure that a raw source works properly when nested.
		$this->register_block_with_attributes( 'test/group', [] );

		$this->register_block_with_attributes( 'test/custom-html', [
			'content' => [
				'type'   => 'string',
				'source' => 'raw',
			],
		] );


		$html = '
			<!-- wp:test/group -->

			<!-- wp:test/custom-html -->
			<p>Custom HTML</p>
			<!-- /wp:test/custom-html -->

			<!-- /wp:test/group -->';

		$expected_blocks = [
			[
				'name'        => 'test/group',
				'innerBlocks' => [
					[
						'name'       => 'test/custom-html',
						'attributes' => [
							'content' => '<p>Custom HTML</p>',
						],
					],
				],
			],
		];

		$content_parser = new ContentParser( $this->get_block_registry() );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}

	public function test_parse_raw_source_multiple_top_level_nodes() {
		$this->register_block_with_attributes( 'test/html', [
			'content' => [
				'type'   => 'string',
				'source' => 'raw',
			],
		] );

		$html = '
			<!-- wp:test/html -->
			<p>Node 1</p><p>Node 2</p>
			<!-- /wp:test/html -->';

		$expected_blocks = [
			[
				'name'       => 'test/html',
				'attributes' => [
					'content' => '<p>Node 1</p><p>Node 2</p>',
				],
			],
		];

		$content_parser = new ContentParser( $this->get_block_registry() );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}

	public function test_parse_raw_source_multiple_top_level_nodes_with_whitespace() {
		$this->register_block_with_attributes( 'test/html', [
			'content' => [
				'type'   => 'string',
				'source' => 'raw',
			],
		] );

		$html = '
			<!-- wp:test/html -->
			<span class="highlight">This</span> <span>should</span> <span>retain</span>&nbsp;<span>inner</span>
<span>whitespace</span>
			<!-- /wp:test/html -->';

		$expected_blocks = [
			[
				'name'       => 'test/html',
				'attributes' => [
					'content' => "<span class=\"highlight\">This</span> <span>should</span> <span>retain</span>&nbsp;<span>inner</span>\n<span>whitespace</span>",
				],
			],
		];

		$content_parser = new ContentParser( $this->get_block_registry() );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}
}
