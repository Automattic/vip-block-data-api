<?php
/**
 * Class ContentParserTest
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

/**
 * Content parser tests that are not source-specific.
 */
class ContentParserTest extends RegistryTestCase {
	/* Multiple attributes */

	public function test_block_filter_via_code() {
		$this->register_block_with_attributes( 'test/block1', [
			'content' => [
				'type'     => 'string',
				'source'   => 'html',
				'selector' => 'div.a',
			],
		] );

		$this->register_block_with_attributes( 'test/block2', [
			'url' => [
				'type'      => 'string',
				'source'    => 'attribute',
				'selector'  => 'img',
				'attribute' => 'src',
			],
		] );

		$html = '
			<!-- wp:test/block1 -->
			<div class="a">Block 1</div>
			<!-- /wp:test/block1 -->

			<!-- wp:test/block2 -->
			<img src="/image.jpg" />
			<!-- /wp:test/block2 -->
		';

		$expected_blocks = [
			[
				'name'       => 'test/block1',
				'attributes' => [
					'content' => 'Block 1',
				],
			],
		];

		$block_filter_function = function ( $is_block_included, $block_name, $block ) {
			if ( 'test/block2' === $block_name ) {
				return false;
			} else {
				return true;
			}
		};

		add_filter( 'vip_block_data_api__allow_block', $block_filter_function, 10, 3 );
		$content_parser = new ContentParser( $this->registry );
		$blocks         = $content_parser->parse( $html );
		remove_filter( 'vip_block_data_api__allow_block', $block_filter_function, 10, 3 );

		$this->assertArrayNotHasKey( 'errors', $blocks );
		$this->assertNotEmpty( $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertEquals( $expected_blocks, $blocks['blocks'] );
	}

	public function test_parse_multiple_attributes_from_block() {
		$this->register_block_with_attributes( 'test/captioned-image', [
			'caption' => [
				'type'     => 'string',
				'source'   => 'html',
				'selector' => 'figcaption',
			],
			'url'     => [
				'type'      => 'string',
				'source'    => 'attribute',
				'selector'  => 'img',
				'attribute' => 'src',
			],
		] );

		$html = '
			<!-- wp:test/captioned-image -->
			<div>
				<img src="/wp-content/uploads/test-image.png" />
				<figcaption>Test caption</figcaption>
			</div>
			<!-- /wp:test/captioned-image -->
		';

		$expected_blocks = [
			[
				'name'       => 'test/captioned-image',
				'attributes' => [
					'caption' => 'Test caption',
					'url'     => '/wp-content/uploads/test-image.png',
				],
			],
		];

		$content_parser = new ContentParser( $this->registry );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}

	/* Multiple blocks */

	public function test_parse_multiple_blocks() {
		$this->register_block_with_attributes( 'test/block1', [
			'content' => [
				'type'     => 'string',
				'source'   => 'html',
				'selector' => 'div.a',
			],
		] );

		$this->register_block_with_attributes( 'test/block2', [
			'url' => [
				'type'      => 'string',
				'source'    => 'attribute',
				'selector'  => 'img',
				'attribute' => 'src',
			],
		] );

		$html = '
			<!-- wp:test/block1 -->
			<div class="a">Block 1</div>
			<!-- /wp:test/block1 -->

			<!-- wp:test/block2 -->
			<img src="/image.jpg" />
			<!-- /wp:test/block2 -->
		';

		$expected_blocks = [
			[
				'name'       => 'test/block1',
				'attributes' => [
					'content' => 'Block 1',
				],
			],
			[
				'name'       => 'test/block2',
				'attributes' => [
					'url' => '/image.jpg',
				],
			],
		];

		$content_parser = new ContentParser( $this->registry );
		$blocks         = $content_parser->parse( $html );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}
}
