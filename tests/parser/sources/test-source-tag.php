<?php
/**
 * Class SourceTagTest
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

/**
 * Test sourced attributes with the 'tag' type.
 */
class SourceTagTest extends RegistryTestCase {
	public function test_parse_tag_source() {
		$this->register_block_with_attributes( 'test/header', [
			'header-tag' => [
				'type'     => 'string',
				'source'   => 'tag',
				'selector' => 'h1,h2,h3',
			],
		] );

		$html = '
			<!-- wp:test/header -->
			<h1>Article title</h1>
			<!-- /wp:test/header -->
		';

		$expected_blocks = [
			[
				'name'       => 'test/header',
				'attributes' => [
					'header-tag' => 'h1',
				],
			],
		];

		$content_parser = new ContentParser( $this->get_block_registry() );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}

	public function test_parse_tag_source__in_query() {
		$this->register_block_with_attributes( 'test/headers', [
			'header-tags' => [
				'type'     => 'array',
				'source'   => 'query',
				'selector' => 'h1,h2,h3',
				'query'    => [
					'tag-name' => [
						'type'   => 'string',
						'source' => 'tag',
					],
				],
			],
		] );

		$html = '
			<!-- wp:test/headers -->
			<h2>Article subtitle</h2>
			<h3>Subsection title</h3>
			<!-- /wp:test/headers -->
		';

		$expected_blocks = [
			[
				'name'       => 'test/headers',
				'attributes' => [
					'header-tags' => [
						[
							'tag-name' => 'h2',
						],
						[
							'tag-name' => 'h3',
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

	public function test_parse_tag_source__with_default_value() {
		$this->register_block_with_attributes( 'test/cell', [
			'cell-tag' => [
				'type'     => 'string',
				'source'   => 'tag',
				'selector' => 'th,td',
				'default'  => 'td',
			],
		] );

		$html = '<!-- wp:test/cell /-->';

		$expected_blocks = [
			[
				'name'       => 'test/cell',
				'attributes' => [
					'cell-tag' => 'td',
				],
			],
		];

		$content_parser = new ContentParser( $this->get_block_registry() );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}
}
