<?php
/**
 * Tests for resolved binding values and fallback behavior.
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

class BindingValuesTest extends RegistryTestCase {
	/** @dataProvider value_provider */
	public function test_core_binding_values_are_preserved( $value, $expected ) {
		$calls = 0;
		$this->register_block_bindings_source( 'test/value', [
			'label'              => 'Test value',
			'get_value_callback' => static function () use ( $value, &$calls ) {
				++$calls;
				return $value;
			},
		] );
		$html   = '<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"test/value"}}}} --><p>Saved fallback</p><!-- /wp:paragraph -->';
		$result = ( new ContentParser() )->parse( $html );
		$this->assertSame( $expected, $result['blocks'][0]['attributes']['content'] );
		$this->assertSame( 1, $calls );
	}

	public function value_provider() {
		return [
			'empty string'  => [ '', '' ],
			'string zero'   => [ '0', '0' ],
			'integer zero'  => [ 0, 0 ],
			'false'         => [ false, false ],
			'nonempty'      => [ 'Resolved', 'Resolved' ],
			'null fallback' => [ null, 'Saved fallback' ],
		];
	}

	public function test_resolved_value_matching_default_is_preserved() {
		$this->register_block_bindings_source( 'test/value', [
			'label'              => 'Empty alt',
			'get_value_callback' => static function () {
				return ''; },
		] );
		$html   = '<!-- wp:image {"metadata":{"bindings":{"alt":{"source":"test/value"}}}} --><img src="/image.png" alt="Saved fallback" /><!-- /wp:image -->';
		$result = ( new ContentParser() )->parse( $html );
		$this->assertSame( '', $result['blocks'][0]['attributes']['alt'] );
	}

	public function test_missing_source_does_not_preserve_attribute_default() {
		$html   = '<!-- wp:image {"metadata":{"bindings":{"alt":{"source":"test/missing"}}}} --><img src="/image.png" alt="Saved fallback" /><!-- /wp:image -->';
		$result = ( new ContentParser() )->parse( $html );
		$this->assertSame( 'Saved fallback', $result['blocks'][0]['attributes']['alt'] );
	}

	public function test_unsupported_block_still_sources_html() {
		$this->register_block_with_attributes( 'test/unsupported', [
			'content' => [
				'type'     => 'string',
				'source'   => 'html',
				'selector' => 'p',
				'default'  => '',
			],
		] );
		$this->register_block_bindings_source( 'test/value', [
			'label'              => 'Unused binding',
			'get_value_callback' => function () {
				$this->fail( 'Core should not resolve this binding' ); },
		] );
		$html   = '<!-- wp:test/unsupported {"metadata":{"bindings":{"content":{"source":"test/value"}}}} --><p>Saved fallback</p><!-- /wp:test/unsupported -->';
		$result = ( new ContentParser() )->parse( $html );
		$this->assertSame( 'Saved fallback', $result['blocks'][0]['attributes']['content'] );
	}

	public function test_empty_binding_inside_synced_pattern() {
		$this->register_block_bindings_source( 'test/value', [
			'label'              => 'Empty content',
			'get_value_callback' => static function () {
				return ''; },
		] );
		$pattern = self::factory()->post->create( [
			'post_type'    => 'wp_block',
			'post_status'  => 'publish',
			'post_content' => '<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"test/value"}}}} --><p>Saved fallback</p><!-- /wp:paragraph -->',
		] );
		$result  = ( new ContentParser() )->parse( sprintf( '<!-- wp:block {"ref":%d} /-->', $pattern ) );
		$this->assertSame( '', $result['blocks'][0]['innerBlocks'][0]['attributes']['content'] );
	}

	public function test_binding_filter_is_removed_after_a_throwing_source() {
		$this->register_block_bindings_source( 'test/value', [
			'label'              => 'Throwing source',
			'get_value_callback' => static function () {
				throw new \RuntimeException( 'Binding failed' ); },
		] );
		$before = $GLOBALS['wp_filter']['block_bindings_source_value']->callbacks[ PHP_INT_MAX ] ?? [];
		$result = ( new ContentParser() )->parse( '<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"test/value"}}}} --><p>Fallback</p><!-- /wp:paragraph -->' );
		$this->assertWPError( $result );
		$this->assertSame( $before, $GLOBALS['wp_filter']['block_bindings_source_value']->callbacks[ PHP_INT_MAX ] ?? [] );
	}
	/** @dataProvider filtered_value_provider */
	public function test_final_filtered_binding_value_controls_fallback( $filtered, $expected ) {
		$this->register_block_bindings_source( 'test/value', [
			'label'              => 'Filtered binding',
			'get_value_callback' => static function () {
				return 'Initial'; },
		] );
		$filter = static function () use ( $filtered ) {
			return $filtered;
		};
		add_filter( 'block_bindings_source_value', $filter, 20 );
		try {
			$result = ( new ContentParser() )->parse( '<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"test/value"}}}} --><p>Saved fallback</p><!-- /wp:paragraph -->' );
			$this->assertSame( $expected, $result['blocks'][0]['attributes']['content'] );
		} finally {
			remove_filter( 'block_bindings_source_value', $filter, 20 );
		}
	}

	public function filtered_value_provider() {
		return [
			'empty' => [ '', '' ],
			'null'  => [ null, 'Saved fallback' ],
		];
	}
}
