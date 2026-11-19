<?php
/**
 * Synced pattern rendering regressions.
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

class PatternRenderingTest extends RegistryTestCase {
	private function create_pattern( $content ) {
		return self::factory()->post->create( [
			'post_type'    => 'wp_block',
			'post_status'  => 'publish',
			'post_content' => $content,
		] );
	}

	private function reference( $id ) {
		return sprintf( '<!-- wp:block {"ref":%d} /-->', $id );
	}

	public function test_core_pattern_children_are_reused_when_available() {
		$renders = 0;
		$this->register_block_with_attributes( 'test/counted', [], [
			'render_callback' => static function () use ( &$renders ) {
				++$renders;
				return '<p>Dynamic</p>';
			},
		] );
		$pattern = $this->create_pattern( '<!-- wp:group --><div class="wp-block-group"><!-- wp:test/counted /--></div><!-- /wp:group -->' );
		$result  = ( new ContentParser() )->parse( str_repeat( $this->reference( $pattern ), 2 ) );
		$this->assertIsArray( $result );
		$this->assertCount( 2, $result['blocks'] );
		// The 6.8 compatibility path still renders again to resolve sourced children.
		$this->assertSame( version_compare( $GLOBALS['wp_version'], '6.9', '<' ) ? 4 : 2, $renders );
		foreach ( $result['blocks'] as $block ) {
			$this->assertCount( 1, $block['innerBlocks'] );
			$this->assertSame( 'core/group', $block['innerBlocks'][0]['name'] );
			$this->assertSame( 'test/counted', $block['innerBlocks'][0]['innerBlocks'][0]['name'] );
		}
	}

	/** @dataProvider cycle_provider */
	public function test_pattern_cycles_terminate( $mutual ) {
		$first  = $this->create_pattern( '' );
		$second = $mutual ? $this->create_pattern( $this->reference( $first ) ) : $first;
		wp_update_post( [
			'ID'           => $first,
			'post_content' => $this->reference( $second ),
		] );
		$visits = 0;
		$guard  = static function ( $blocks ) use ( &$visits ) {
			if ( ++$visits > 8 ) {
				throw new \RuntimeException( 'Pattern traversal did not terminate' );
			}
			return $blocks;
		};
		add_filter( 'vip_block_data_api__sourced_block_inner_blocks', $guard );
		try {
			$result = ( new ContentParser() )->parse( $this->reference( $first ) );
			$this->assertIsArray( $result );
			$block = $result['blocks'][0];
			for ( $index = 0; $index < ( $mutual ? 2 : 1 ); ++$index ) {
				$this->assertCount( 1, $block['innerBlocks'] );
				$block = $block['innerBlocks'][0];
			}
			$this->assertArrayNotHasKey( 'innerBlocks', $block );
		} finally {
			remove_filter( 'vip_block_data_api__sourced_block_inner_blocks', $guard );
		}
	}

	public function cycle_provider() {
		return [
			'self'   => [ false ],
			'mutual' => [ true ],
		];
	}

	public function test_pattern_references_rejected_by_core_are_not_expanded() {
		$pattern = $this->create_pattern( '' );
		$ref     = sprintf( '"%dabc"', $pattern );
		wp_update_post( [
			'ID'           => $pattern,
			'post_content' => '<!-- wp:block {"ref":' . $ref . '} /-->',
		] );
		$visits = 0;
		$guard  = static function ( $blocks ) use ( &$visits ) {
			if ( ++$visits > 8 ) {
				throw new \RuntimeException( 'Invalid pattern reference did not terminate' );
			}
			return $blocks;
		};
		add_filter( 'vip_block_data_api__sourced_block_inner_blocks', $guard, 1 );
		try {
			$result = ( new ContentParser() )->parse( $this->reference( $pattern ) );
			$this->assertIsArray( $result );
			$this->assertCount( 1, $result['blocks'][0]['innerBlocks'] );
			$this->assertArrayNotHasKey( 'innerBlocks', $result['blocks'][0]['innerBlocks'][0] );
		} finally {
			remove_filter( 'vip_block_data_api__sourced_block_inner_blocks', $guard, 1 );
		}
	}

	public function test_numeric_string_pattern_reference_is_expanded() {
		$pattern = $this->create_pattern( '<!-- wp:paragraph --><p>Pattern content</p><!-- /wp:paragraph -->' );
		$result  = ( new ContentParser() )->parse( sprintf( '<!-- wp:block {"ref":"%d"} /-->', $pattern ) );
		$this->assertIsArray( $result );
		$this->assertSame( 'core/paragraph', $result['blocks'][0]['innerBlocks'][0]['name'] );
		$this->assertSame( 'Pattern content', $result['blocks'][0]['innerBlocks'][0]['attributes']['content'] );
	}

	public function test_reentrant_parser_does_not_capture_unrelated_blocks() {
		$other = $this->create_pattern( '<!-- wp:paragraph --><p>Unrelated</p><!-- /wp:paragraph -->' );
		$this->register_block_with_attributes( 'test/reentrant', [], [
			'render_callback' => function () use ( $other ) {
				$result = ( new ContentParser() )->parse( $this->reference( $other ) );
				$this->assertIsArray( $result );
				return '<p>Dynamic</p>';
			},
		] );
		$pattern = $this->create_pattern( '<!-- wp:test/reentrant /--><!-- wp:paragraph --><p>Expected</p><!-- /wp:paragraph -->' );
		$result  = ( new ContentParser() )->parse( $this->reference( $pattern ) );
		$this->assertSame( [ 'test/reentrant', 'core/paragraph' ], array_column( $result['blocks'][0]['innerBlocks'], 'name' ) );
	}

	public function test_blocks_generated_inside_dynamic_callbacks_are_not_pattern_children() {
		$this->register_block_with_attributes( 'test/generated', [], [
			'render_callback' => static function () {
				return do_blocks( '<!-- wp:paragraph --><p>Generated content</p><!-- /wp:paragraph -->' );
			},
		] );
		$pattern = $this->create_pattern( '<!-- wp:test/generated /-->' );
		$result  = ( new ContentParser() )->parse( $this->reference( $pattern ) );
		$this->assertSame( [ 'test/generated' ], array_column( $result['blocks'][0]['innerBlocks'], 'name' ) );
	}
}
