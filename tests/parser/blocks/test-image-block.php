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
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}

	public function test_parse_core_image_resized_keeps_metadata_and_resize_attributes() {
		global $wp_version;

		// A drag-to-resize in the editor stores width/height in the block
		// attributes. The type of these attributes changed in WordPress 6.3
		// from numbers (e.g. 300) to unit strings (e.g. "300px").
		// Test each shape against the version that produces it.
		if ( version_compare( $wp_version, '6.3', '<' ) ) {
			$this->assert_resize_attributes_preserved( 300, 169 );
		} else {
			$this->assert_resize_attributes_preserved( '300px', '169px' );
		}
	}

	/**
	 * Assert that a resized core/image keeps its full-size metadata dimensions
	 * as width/height while exposing the editor resize dimensions separately.
	 *
	 * @param int|string $resize_width  Editor-selected width stored in the block attributes.
	 * @param int|string $resize_height Editor-selected height stored in the block attributes.
	 */
	private function assert_resize_attributes_preserved( $resize_width, $resize_height ) {
		$attachment_id  = $this->factory()->attachment->create_upload_object( WPCOMVIP__BLOCK_DATA_API__TEST_DATA . '/blue.png' );
		$attachment_url = wp_get_attachment_url( $attachment_id );

		$image_attributes = [
			'id'     => $attachment_id,
			'width'  => $resize_width,
			'height' => $resize_height,
		];

		$html = '
			<!-- wp:image ' . wp_json_encode( $image_attributes ) . ' -->
			<figure class="wp-block-image">
				<img src="' . $attachment_url . '" />
			</figure>
			<!-- /wp:image -->
		';

		$expected_blocks = [
			[
				'name'       => 'core/image',
				'attributes' => [
					// Full-size dimensions from the attachment metadata are preserved.
					'width'         => 800,
					'height'        => 450,
					// Editor-selected resize dimensions are exposed separately.
					'resize-width'  => $resize_width,
					'resize-height' => $resize_height,
				],
			],
		];

		$content_parser = new ContentParser();
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}
}
