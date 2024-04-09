<?php
/**
 * Class ContentParserTest
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

/**
 * Content parser filter testing.
 */
class ParserFiltersTest extends RegistryTestCase {
	/* vip_block_data_api__allow_block */

	public function test_allow_block_filter_via_code() {
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

		$block_filter_function = function ( $is_block_included, $block_name ) {
			if ( 'test/block2' === $block_name ) {
				return false;
			} else {
				return true;
			}
		};

		add_filter( 'vip_block_data_api__allow_block', $block_filter_function, 10, 2 );
		$content_parser = new ContentParser( $this->registry );
		$blocks         = $content_parser->parse( $html );
		remove_filter( 'vip_block_data_api__allow_block', $block_filter_function, 10, 2 );

		$this->assertArrayNotHasKey( 'errors', $blocks );
		$this->assertNotEmpty( $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertEquals( $expected_blocks, $blocks['blocks'] );
	}

	/* vip_block_data_api__before_parse_post_content */

	public function test_before_parse_post_content_filter() {
		$this->register_block_with_attributes( 'test/valid-block', [
			'content' => [
				'type'     => 'rich-text',
				'source'   => 'rich-text',
				'selector' => 'code',
			],
		] );

		$html = '
			<!-- wp:test/invalid-block -->
			<code><strong>Block content</strong>!</code>
			<!-- /wp:test/invalid-block -->
		';

		$expected_blocks = [
			[
				'name'       => 'test/valid-block',
				'attributes' => [
					'content' => '<strong>Block content</strong>!',
				],
			],
		];

		$replace_post_content_filter = function ( $post_content ) {
			return str_replace( 'test/invalid-block', 'test/valid-block', $post_content );
		};

		add_filter( 'vip_block_data_api__before_parse_post_content', $replace_post_content_filter );
		$content_parser = new ContentParser( $this->registry );
		$blocks         = $content_parser->parse( $html );
		remove_filter( 'vip_block_data_api__before_parse_post_content', $replace_post_content_filter );

		$this->assertArrayNotHasKey( 'errors', $blocks );
		$this->assertNotEmpty( $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertEquals( $expected_blocks, $blocks['blocks'] );
	}

	/* vip_block_data_api__after_parse_blocks */

	public function test_after_parse_filter() {
		$this->register_block_with_attributes( 'test/paragraph', [
			'content' => [
				'type'     => 'rich-text',
				'source'   => 'rich-text',
				'selector' => 'p',
			],
		] );

		$html = '
			<!-- wp:test/paragraph -->
			<p>Paragaph text</p>
			<!-- /wp:test/paragraph -->
		';

		$expected_blocks = [
			[
				'name'       => 'test/paragraph',
				'attributes' => [
					'content' => 'Paragaph text',
				],
			],
		];

		$add_extra_data_filter = function ( $result ) {
			$result['my-key'] = 'my-value';

			return $result;
		};

		add_filter( 'vip_block_data_api__after_parse_blocks', $add_extra_data_filter );
		$content_parser = new ContentParser( $this->registry );
		$result         = $content_parser->parse( $html );
		remove_filter( 'vip_block_data_api__after_parse_blocks', $add_extra_data_filter );

		$this->assertArrayNotHasKey( 'errors', $result );
		$this->assertNotEmpty( $result, sprintf( 'Unexpected parser output: %s', wp_json_encode( $result ) ) );
		$this->assertArrayHasKey( 'blocks', $result, sprintf( 'Unexpected parser output: %s', wp_json_encode( $result ) ) );
		$this->assertEquals( $expected_blocks, $result['blocks'] );
		$this->assertEquals( 'my-value', $result['my-key'] );
	}
}
