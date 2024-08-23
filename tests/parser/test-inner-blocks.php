<?php
/**
 * Class InnerBlocksTest
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

/**
 * Test parsing blocks that contain other blocks.
 */
class InnerBlocksTest extends RegistryTestCase {
	public function test_parse_inner_blocks__one_layer() {
		// Outer block
		$this->register_block_with_attributes( 'test/gallery', [
			'caption' => [
				'type'     => 'string',
				'source'   => 'html',
				'selector' => '.blocks-gallery-caption',
			],
		] );

		// Inner blocks
		$this->register_block_with_attributes( 'test/image', [
			'id'  => [
				'type' => 'number',
			],
			'url' => [
				'type'      => 'string',
				'source'    => 'attribute',
				'selector'  => 'img',
				'attribute' => 'src',
			],
			'alt' => [
				'type'      => 'string',
				'source'    => 'attribute',
				'selector'  => 'img',
				'attribute' => 'alt',
				'default'   => '',
			],
		] );

		$html = '
			<!-- wp:test/gallery -->
			<figure>
				<!-- wp:test/image {"id":48} -->
				<figure>
					<img src="/image1.jpg" alt="Image 1"/>
				</figure>
				<!-- /wp:test/image -->

				<!-- wp:test/image {"id":49} -->
				<figure>
					<img src="/image2.jpg" alt="Image 2"/>
				</figure>
				<!-- /wp:test/image -->

				<figcaption class="blocks-gallery-caption">Gallery caption</figcaption>
			</figure>
			<!-- /wp:test/gallery -->
		';

		$expected_blocks = [
			[
				'name'        => 'test/gallery',
				'attributes'  => [
					'caption' => 'Gallery caption',
				],
				'innerBlocks' => [
					[
						'name'       => 'test/image',
						'attributes' => [
							'id'  => 48,
							'url' => '/image1.jpg',
							'alt' => 'Image 1',
						],
					],
					[
						'name'       => 'test/image',
						'attributes' => [
							'id'  => 49,
							'url' => '/image2.jpg',
							'alt' => 'Image 2',
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

	public function test_parse_inner_blocks__two_layers() {
		// Outer media-text block
		$this->register_block_with_attributes( 'test/media-text', [
			'mediaId'  => [
				'type' => 'number',
			],
			'mediaUrl' => [
				'type'      => 'string',
				'source'    => 'attribute',
				'selector'  => 'figure video,figure img',
				'attribute' => 'src',
			],
		] );

		// Intermediate list block
		$this->register_block_with_attributes( 'test/list', [
			'ordered' => [
				'type'    => 'boolean',
				'default' => false,
			],
		] );

		// Inner list-item blocks
		$this->register_block_with_attributes( 'test/list-item', [
			'content' => [
				'type'     => 'string',
				'source'   => 'html',
				'selector' => 'li',
			],
		] );

		$html = '
			<!-- wp:test/media-text {"mediaId":68} -->
			<div class="wp-block-media-text">
				<figure class="wp-block-media-text__media">
					<img src="https://wpvip.com/image.png" alt="Media-text image" />
				</figure>

				<div class="wp-block-media-text__content">
					<!-- wp:test/list -->
					<ul>
						<!-- wp:test/list-item -->
						<li>List item 1</li>
						<!-- /wp:test/list-item -->

						<!-- wp:test/list-item -->
						<li>List item 2</li>
						<!-- /wp:test/list-item -->
					</ul>
					<!-- /wp:test/list -->
				</div>
			</div>
			<!-- /wp:test/media-text -->
		';

		$expected_blocks = [
			[
				'name'        => 'test/media-text',
				'attributes'  => [
					'mediaId'  => 68,
					'mediaUrl' => 'https://wpvip.com/image.png',
				],
				'innerBlocks' => [
					[
						'name'        => 'test/list',
						'attributes'  => [
							'ordered' => false,
						],
						'innerBlocks' => [
							[
								'name'       => 'test/list-item',
								'attributes' => [
									'content' => 'List item 1',
								],
							],
							[
								'name'       => 'test/list-item',
								'attributes' => [
									'content' => 'List item 2',
								],
							],
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
}
