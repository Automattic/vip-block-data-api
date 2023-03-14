<?php
/**
 * Class ImageBlockTest
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

use WP_UnitTestCase;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

/**
 * Content parser tests for core/image blocks.
 * Uses built-in block registry.
 */
class ImageBlockTest extends WP_UnitTestCase {
	use ArraySubsetAsserts;

	public function test_parse_core_image_has_size_attributes() {
		$attachment_id  = $this->factory()->attachment->create_upload_object( WPCOMVIP__BLOCK_DATA_API__TEST_DATA . '/blue.png' );
		$attachment_url = wp_get_attachment_url( $attachment_id );

		$html = '
			<!-- wp:image {"id":' . $attachment_id . '} -->
			<figure class="wp-block-image">
				<img src="' . $attachment_url . '" />
			</figure>
			<!-- /wp:image -->
		';

		$expected_blocks = [
			[
				'name'       => 'core/image',
				'attributes' => [
					'width'  => 800,
					'height' => 450,
				],
			],
		];

		$content_parser = new ContentParser();
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $expected_blocks, true );
	}
}
