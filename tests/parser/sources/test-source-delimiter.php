<?php
/**
 * Class DelimiterSourceTests
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

/**
 * Ensure attributes in the block delimiter are sourced correctly.
 */
class DelimiterSourceTest extends RegistryTestCase {
	public function test_parse_block_delimiter_attributes() {
		$this->register_block_with_attributes( 'test/custom-block', [
			'data-1' => [
				'type' => 'string',
			],
			'data-2' => [
				'type' => 'number',
			],
			'data-3' => [
				'type'    => 'string',
				'default' => 'default-data-3-value',
			],
			'data-4' => [
				'type' => 'number',
			],
		] );

		$html = '
			<!-- wp:test/custom-block {"data-1":"data-1-value","data-2":123} -->
			<div>Custom block content here</div>
			<!-- /wp:test/custom-block -->
		';

		$expected_blocks = [
			[
				'name'       => 'test/custom-block',
				'attributes' => [
					'data-1' => 'data-1-value',
					'data-2' => 123,
					'data-3' => 'default-data-3-value',
				],
			],
		];

		$content_parser = new ContentParser( $this->get_block_registry() );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}

	public function test_parse_block_delimiter_attributes__are_overridden_by_sourced_attributes() {
		$this->register_block_with_attributes( 'test/paragraph', [
			'content' => [
				'type'     => 'string',
				'source'   => 'html',
				'selector' => 'p',
			],
		] );

		$html = '
			<!-- wp:test/paragraph {"content":"this should be ignored"} -->
			<p>Test content</p>
			<!-- /wp:test/paragraph -->
		';

		$expected_blocks = [
			[
				'name'       => 'test/paragraph',
				'attributes' => [
					'content' => 'Test content',
				],
			],
		];

		$content_parser = new ContentParser( $this->get_block_registry() );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}
}
