<?php
/**
 * Class UnregisteredBlockTest
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

/**
 * Content parser tests for client-side blocks.
 */
class UnregisteredBlockTest extends RegistryTestCase {
	public function test_parse_unregistered_block() {
		$html = '
			<!-- wp:test/unknown-block {"delimiter-attribute": "delimiter-value"} -->
			<p>Unknown block content</p>
			<!-- /wp:test/unknown-block -->
		';

		$expected_blocks = [
			[
				'name'       => 'test/unknown-block',
				'attributes' => [
					'delimiter-attribute' => 'delimiter-value',
				],
			],
		];

		$expected_warnings = [
			'Block type "test/unknown-block" is not server-side registered. Sourced block attributes will not be available.',
		];

		$content_parser = new ContentParser( $this->get_block_registry() );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArrayHasKey( 'warnings', $blocks, sprintf( 'Expected parser to have warnings, none received: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
		$this->assertEqualSets( $expected_warnings, $blocks['warnings'] );
	}
}
