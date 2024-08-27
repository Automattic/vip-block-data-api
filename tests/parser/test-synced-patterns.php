<?php
/**
 * Class SyncedPatternsTest
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

use WP_Block;

/**
 * Test parsing blocks with synced patterns.
 */
class SyncedPatternsTest extends RegistryTestCase {
	protected function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'resolve_pattern_blocks' ) ) {
			$this->markTestSkipped( 'This test suite requires resolve_pattern_blocks (WordPress 6.6 or higher).' );
		}
	}

	/* Simple synced pattern */

	public function test_simple_synced_pattern() {
		$this->register_block_with_attributes( 'test/custom-block', [
			'content' => [
				'type'               => 'rich-text',
				'source'             => 'rich-text',
				'selector'           => 'p',
				'__experimentalRole' => 'content',
			],
			'bing'    => [
				'type'      => 'string',
				'source'    => 'attribute',
				'selector'  => 'p',
				'attribute' => 'data-bing',
			],
		] );

		$synced_pattern_content = '
			<!-- wp:test/custom-block -->
			<p data-bing="bong">My synced pattern content</p>
			<!-- /wp:test/custom-block -->
		';

		$synced_pattern = $this->factory()->post->create_and_get( [
			'post_content' => $synced_pattern_content,
			'post_status'  => 'publish',
			'post_type'    => 'wp_block',
		] );

		$html = sprintf( '<!-- wp:block {"ref":%d} /-->', $synced_pattern->ID );

		$expected_blocks = [
			[
				'name'        => 'core/block',
				'attributes'  => [
					'ref' => $synced_pattern->ID,
				],
				'innerBlocks' => [
					[
						'name'       => 'test/custom-block',
						'attributes' => [
							'content' => 'My synced pattern content',
							'bing'    => 'bong',
						],
					],
				],
			],
		];

		$content_parser = new ContentParser( $this->get_block_registry() );
		$blocks         = $content_parser->parse( $html );

		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertEquals( $expected_blocks, $blocks['blocks'], sprintf( 'Blocks not equal: %s', wp_json_encode( $blocks['blocks'] ) ) );
	}

	/* Multiple synced patterns */

	public function test_multiple_synced_patterns() {
		$this->register_block_with_attributes( 'test/custom-block', [
			'content' => [
				'type'               => 'rich-text',
				'source'             => 'rich-text',
				'selector'           => 'p',
				'__experimentalRole' => 'content',
			],
			'bing'    => [
				'type'      => 'string',
				'source'    => 'attribute',
				'selector'  => 'p',
				'attribute' => 'data-bing',
			],
		] );

		$synced_pattern_content_1 = '
			<!-- wp:test/custom-block -->
			<p data-bing="bong">My first synced pattern content</p>
			<!-- /wp:test/custom-block -->
		';

		$synced_pattern_1 = $this->factory()->post->create_and_get( [
			'post_content' => $synced_pattern_content_1,
			'post_status'  => 'publish',
			'post_type'    => 'wp_block',
		] );

		$synced_pattern_content_2 = '
			<!-- wp:test/custom-block -->
			<p data-bing="bang">My second synced pattern content</p>
			<!-- /wp:test/custom-block -->
		';

		$synced_pattern_2 = $this->factory()->post->create_and_get( [
			'post_content' => $synced_pattern_content_2,
			'post_status'  => 'publish',
			'post_type'    => 'wp_block',
		] );

		$html = sprintf( '
		<!-- wp:block {"ref":%d} /-->
		<!-- wp:block {"ref":%d} /-->
		', $synced_pattern_1->ID, $synced_pattern_2->ID );

		$expected_blocks = [
			[
				'name'        => 'core/block',
				'attributes'  => [
					'ref' => $synced_pattern_1->ID,
				],
				'innerBlocks' => [
					[
						'name'       => 'test/custom-block',
						'attributes' => [
							'content' => 'My first synced pattern content',
							'bing'    => 'bong',
						],
					],
				],
			],
			[
				'name'        => 'core/block',
				'attributes'  => [
					'ref' => $synced_pattern_2->ID,
				],
				'innerBlocks' => [
					[
						'name'       => 'test/custom-block',
						'attributes' => [
							'content' => 'My second synced pattern content',
							'bing'    => 'bang',
						],
					],
				],
			],
		];

		$content_parser = new ContentParser( $this->get_block_registry() );
		$blocks         = $content_parser->parse( $html );

		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertEquals( $expected_blocks, $blocks['blocks'], sprintf( 'Blocks not equal: %s', wp_json_encode( $blocks['blocks'] ) ) );
	}

	/* Synced pattern with override */

	public function test_synced_pattern_with_override() {
		$synced_pattern_content = '
			<!-- wp:paragraph {"metadata":{"bindings":{"__default":{"source":"core/pattern-overrides"}},"name":"my-override"}} -->
			<p>Default content</p>
			<!-- /wp:paragraph -->
		';

		$synced_pattern = $this->factory()->post->create_and_get( [
			'post_content' => $synced_pattern_content,
			'post_status'  => 'publish',
			'post_type'    => 'wp_block',
		] );

		$html = sprintf( '
		<!-- wp:block {"ref":%d,"content":{"my-override":{"content":"Overridden content"}}} /-->
		', $synced_pattern->ID );

		$post = $this->factory()->post->create_and_get();

		$expected_blocks = [
			[
				'name'        => 'core/block',
				'attributes'  => [
					'ref' => $synced_pattern->ID,
				],
				'innerBlocks' => [
					[
						'name'       => 'core/paragraph',
						'attributes' => [
							'content'  => 'Overridden content', // Overridden by synced pattern override
							'metadata' => [
								'bindings' => [
									'__default' => [
										'source' => 'core/pattern-overrides',
									],
								],
								'name'     => 'my-override',
							],
						],
					],
				],
			],
		];

		$content_parser = new ContentParser( $this->get_block_registry() );
		$blocks         = $content_parser->parse( $html, $post->ID );

		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );

		// Block bindings are currently only supported for specific core blocks.
		// https://make.wordpress.org/core/2024/03/06/new-feature-the-block-bindings-api/
		//
		// Core block attributes can change, so we use assertArraySubset to avoid
		// brittle assertions.
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], false, wp_json_encode( $blocks['blocks'] ) );
		$this->assertEquals( 1, count( $blocks['blocks'] ), 'Too many blocks in result set' );
		$this->assertEquals( 1, count( $blocks['blocks'][0]['innerBlocks'] ), 'Too many inner blocks in synced pattern' );
	}

	/* Multiple nested synced patterns with block bindings -- FINAL BOSS! */

	public function test_multiple_nested_synced_patterns_with_block_bindings() {
		$this->register_block_with_attributes( 'test/custom-container', [
			'fizz' => [
				'type'      => 'string',
				'source'    => 'attribute',
				'selector'  => 'div',
				'attribute' => 'data-fizz',
			],
		] );

		$this->register_block_with_attributes( 'test/custom-block', [
			'content' => [
				'type'               => 'rich-text',
				'source'             => 'rich-text',
				'selector'           => 'p',
				'__experimentalRole' => 'content',
			],
			'bing'    => [
				'type'      => 'string',
				'source'    => 'attribute',
				'selector'  => 'p',
				'attribute' => 'data-bing',
			],
		] );

		$synced_pattern_content_1 = '
			<!-- wp:test/custom-block -->
			<p data-bing="bong">My first synced pattern content</p>
			<!-- /wp:test/custom-block -->

			<!-- wp:core/paragraph {"metadata":{"bindings":{"content":{"source":"test/synced-pattern-block-binding","args":{"foo":"bar"}}}}} -->
			<p>Fallback content</p>
			<!-- /wp:core/paragraph -->

			<!-- wp:paragraph {"metadata":{"bindings":{"__default":{"source":"core/pattern-overrides"}},"name":"my-override"}} -->
			<p>Default content</p>
			<!-- /wp:paragraph -->
		';

		$synced_pattern_1 = $this->factory()->post->create_and_get( [
			'post_content' => $synced_pattern_content_1,
			'post_status'  => 'publish',
			'post_type'    => 'wp_block',
		] );

		$synced_pattern_content_2 = sprintf( '
			<!-- wp:test/custom-block -->
			<p data-bing="bang">My second synced pattern content which contains the first</p>
			<!-- /wp:test/custom-block -->

			<!-- wp:block {"ref":%d} /-->

			<!-- wp:test/custom-block -->
			<p data-bing="bang">Another block to "wrap" the nested pattern</p>
			<!-- /wp:test/custom-block -->
		', $synced_pattern_1->ID );

		$synced_pattern_2 = $this->factory()->post->create_and_get( [
			'post_content' => $synced_pattern_content_2,
			'post_status'  => 'publish',
			'post_type'    => 'wp_block',
		] );

		// This uses the default post context. Custom block binding context is not
		// yet supported inside synced patterns.
		$this->register_block_bindings_source(
			'test/synced-pattern-block-binding',
			[
				'label'              => 'Block binding inside synced pattern',
				'get_value_callback' => static function ( array $args, WP_Block $block ) {
					return sprintf( 'Block binding for %s with arg foo=%s in %s %d', $block->name, $args['foo'], $block->context['postType'] ?? 'unknown', $block->context['postId'] ?? 'unknown' );
				},
				'uses_context'       => [ 'postId', 'postType' ],
			]
		);

		$html = sprintf( '
		<!-- wp:test/custom-container -->
		<div data-fizz="buzz">
		<!-- wp:block {"ref":%d} /-->
		</div>
		<!-- /wp:test/custom-container -->

		<!-- wp:test/custom-container -->
		<div data-fizz="buzz">
		<!-- wp:block {"ref":%d,"content":{"my-override":{"content":"Overridden content"}}} /-->
		</div>
		<!-- /wp:test/custom-container -->

		<!-- wp:test/custom-container -->
		<div data-fizz="bazz">
		<!-- wp:block {"ref":%d} /-->
		</div>
		<!-- /wp:test/custom-container -->
		', $synced_pattern_1->ID, $synced_pattern_1->ID, $synced_pattern_2->ID );

		$post = $this->factory()->post->create_and_get();

		$expected_blocks = [
			[
				'name'        => 'test/custom-container',
				'attributes'  => [
					'fizz' => 'buzz',
				],
				'innerBlocks' => [
					[
						'name'        => 'core/block',
						'attributes'  => [
							'ref' => $synced_pattern_1->ID,
						],
						'innerBlocks' => [
							[
								'name'       => 'test/custom-block',
								'attributes' => [
									'content' => 'My first synced pattern content',
									'bing'    => 'bong',
								],
							],
							[
								'name'       => 'core/paragraph',
								'attributes' => [
									'content'  => sprintf( 'Block binding for core/paragraph with arg foo=bar in post %d', $post->ID ),
									'metadata' => [
										'bindings' => [
											'content' => [
												'source' => 'test/synced-pattern-block-binding',
												'args'   => [ 'foo' => 'bar' ],
											],
										],
									],
								],
							],
							[
								'name'       => 'core/paragraph',
								'attributes' => [
									'content'  => 'Default content',
									'metadata' => [
										'bindings' => [
											'__default' => [
												'source' => 'core/pattern-overrides',
											],
										],
										'name'     => 'my-override',
									],
								],
							],
						],
					],
				],
			],
			[
				'name'        => 'test/custom-container',
				'attributes'  => [
					'fizz' => 'buzz',
				],
				'innerBlocks' => [
					[
						'name'        => 'core/block',
						'attributes'  => [
							'ref' => $synced_pattern_1->ID,
						],
						'innerBlocks' => [
							[
								'name'       => 'test/custom-block',
								'attributes' => [
									'content' => 'My first synced pattern content',
									'bing'    => 'bong',
								],
							],
							[
								'name'       => 'core/paragraph',
								'attributes' => [
									'content'  => sprintf( 'Block binding for core/paragraph with arg foo=bar in post %d', $post->ID ),
									'metadata' => [
										'bindings' => [
											'content' => [
												'source' => 'test/synced-pattern-block-binding',
												'args'   => [ 'foo' => 'bar' ],
											],
										],
									],
								],
							],
							[
								'name'       => 'core/paragraph',
								'attributes' => [
									'content'  => 'Overridden content', // Overridden by synced pattern override
									'metadata' => [
										'bindings' => [
											'__default' => [
												'source' => 'core/pattern-overrides',
											],
										],
										'name'     => 'my-override',
									],
								],
							],
						],
					],
				],
			],
			[
				'name'        => 'test/custom-container',
				'attributes'  => [
					'fizz' => 'bazz',
				],
				'innerBlocks' => [
					[
						'name'        => 'core/block',
						'attributes'  => [
							'ref' => $synced_pattern_2->ID,
						],
						'innerBlocks' => [
							[
								'name'       => 'test/custom-block',
								'attributes' => [
									'content' => 'My second synced pattern content which contains the first',
									'bing'    => 'bang',
								],
							],
							[
								'name'        => 'core/block',
								'attributes'  => [
									'ref' => $synced_pattern_1->ID,
								],
								'innerBlocks' => [
									[
										'name'       => 'test/custom-block',
										'attributes' => [
											'content' => 'My first synced pattern content',
											'bing'    => 'bong',
										],
									],
									[
										'name'       => 'core/paragraph',
										'attributes' => [
											'content'  => sprintf( 'Block binding for core/paragraph with arg foo=bar in post %d', $post->ID ),
											'metadata' => [
												'bindings' => [
													'content' => [
														'source' => 'test/synced-pattern-block-binding',
														'args'   => [ 'foo' => 'bar' ],
													],
												],
											],
										],
									],
									[
										'name'       => 'core/paragraph',
										'attributes' => [
											'content'  => 'Default content',
											'metadata' => [
												'bindings' => [
													'__default' => [
														'source' => 'core/pattern-overrides',
													],
												],
												'name'     => 'my-override',
											],
										],
									],
								],
							],
							[
								'name'       => 'test/custom-block',
								'attributes' => [
									'content' => 'Another block to "wrap" the nested pattern',
									'bing'    => 'bang',
								],
							],
						],
					],
				],
			],
		];

		$content_parser = new ContentParser( $this->get_block_registry() );
		$blocks         = $content_parser->parse( $html, $post->ID );

		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );

		// Block bindings are currently only supported for specific core blocks.
		// https://make.wordpress.org/core/2024/03/06/new-feature-the-block-bindings-api/
		//
		// Core block attributes can change, so we use assertArraySubset to avoid
		// brittle assertions.
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], false, wp_json_encode( $blocks['blocks'] ) );
		$this->assertEquals( 3, count( $blocks['blocks'] ), 'Too many blocks in result set' );

		// First synced pattern
		$this->assertEquals( 1, count( $blocks['blocks'][0]['innerBlocks'] ), 'Too many inner blocks in first container block' );
		$this->assertEquals( 3, count( $blocks['blocks'][0]['innerBlocks'][0]['innerBlocks'] ), 'Too many inner blocks in first synced pattern' );

		// First synced pattern, repeated (contains pattern override)
		$this->assertEquals( 1, count( $blocks['blocks'][1]['innerBlocks'] ), 'Too many inner blocks in first container block' );
		$this->assertEquals( 3, count( $blocks['blocks'][1]['innerBlocks'][0]['innerBlocks'] ), 'Too many inner blocks in first synced pattern' );

		// Second synced pattern
		$this->assertEquals( 1, count( $blocks['blocks'][2]['innerBlocks'] ), 'Too many inner blocks in second container block' );
		$this->assertEquals( 3, count( $blocks['blocks'][2]['innerBlocks'][0]['innerBlocks'] ), 'Too many inner blocks in second synced pattern' );
		$this->assertEquals( 3, count( $blocks['blocks'][2]['innerBlocks'][0]['innerBlocks'][1]['innerBlocks'] ), 'Too many inner blocks in nested pattern in second synced pattern' );
	}
}
