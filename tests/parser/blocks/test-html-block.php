<?php
/**
 * Tests for the core/html block.
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

/**
 * Core HTML block tests.
 */
class HtmlBlockTest extends RegistryTestCase {
	public function test_parse_registered_core_html_content() {
		$parser = new ContentParser( $this->get_block_registry() );
		$result = $parser->parse( '<!-- wp:html --><div>Custom HTML</div><!-- /wp:html -->' );

		$this->assertIsArray( $result );
		$this->assertSame( '<div>Custom HTML</div>', $result['blocks'][0]['attributes']['content'] );
	}

	public function test_parse_local_content_without_source() {
		$registry       = $this->get_block_registry();
		$original_block = $registry->get_registered( 'core/html' );
		$this->assertNotNull( $original_block );

		$registry->unregister( 'core/html' );
		$registry->register( 'core/html', [
			'attributes' => [
				'content' => [
					'type' => 'string',
					'role' => 'local',
				],
			],
		] );

		try {
			$html   = "<!-- wp:html -->\n<div>First</div><div>Second</div>\n<!-- /wp:html -->";
			$parser = new ContentParser( $registry );
			$result = $parser->parse( $html );

			$this->assertIsArray( $result, sprintf( 'Unexpected parser output: %s', wp_json_encode( $result ) ) );
			$this->assertSame( '<div>First</div><div>Second</div>', $result['blocks'][0]['attributes']['content'] );
		} finally {
			$registry->unregister( 'core/html' );
			$registry->register( $original_block );
		}
	}
}
