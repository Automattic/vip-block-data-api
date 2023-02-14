<?php
/**
 * Class SourceMetaTest
 *
 * @package vip-content-api
 */

namespace WPCOMVIP\ContentApi;

/**
 * Test sourced attributes with the 'html' type:
 * https://developer.wordpress.org/block-editor/reference-guides/block-api/block-attributes/#html-source
 */
class SourceMetaTest extends RegistryTestCase {
	public function test_parse_meta_source() {
		$post_id = $this->factory->post->create();
		update_post_meta( $post_id, 'test_meta_key', 'test_meta_value' );

		$this->register_block_with_attributes( 'test/block-with-meta', [
			'test_meta_attribute' => [
				'type'   => 'string',
				'source' => 'meta',
				'meta'   => 'test_meta_key',
			],
		] );

		$html = '<!-- wp:test/block-with-meta /-->';

		$expected_blocks = [
			[
				'name'       => 'test/block-with-meta',
				'attributes' => [
					'test_meta_attribute' => 'test_meta_value',
				],
			],
		];

		$meta_source_function = function() use ( $post_id ) {
			return $post_id;
		};

		add_filter( 'vip_content_api__meta_source_post_id', $meta_source_function );
		$content_parser = new ContentParser();
		$blocks         = $content_parser->post_content_to_blocks( $html, $this->registry );
		remove_filter( 'vip_content_api__meta_source_post_id', $meta_source_function );

		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}

	public function test_parse_meta_source__with_default_value() {
		$post_id = $this->factory->post->create();

		$this->register_block_with_attributes( 'test/block-with-missing-meta', [
			'test_meta_attribute' => [
				'type'    => 'string',
				'source'  => 'meta',
				'meta'    => 'missing_meta_key',
				'default' => 'default_value',
			],
		] );

		$html = '<!-- wp:test/block-with-missing-meta /-->';

		$expected_blocks = [
			[
				'name'       => 'test/block-with-missing-meta',
				'attributes' => [
					'test_meta_attribute' => 'default_value',
				],
			],
		];

		$meta_source_function = function() use ( $post_id ) {
			return $post_id;
		};

		add_filter( 'vip_content_api__meta_source_post_id', $meta_source_function );
		$content_parser = new ContentParser();
		$blocks         = $content_parser->post_content_to_blocks( $html, $this->registry );
		remove_filter( 'vip_content_api__meta_source_post_id', $meta_source_function );

		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}
}
