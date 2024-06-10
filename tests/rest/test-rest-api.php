<?php
/**
 * Class RestApiTest
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

use Exception;
use WP_Block_Type_Registry;
use WP_UnitTestCase;
use WP_REST_Server;
use WP_REST_Request;

/**
 * e2e tests to ensure that the REST API endpoint is available.
 */
class RestApiTest extends WP_UnitTestCase {
	private $server;
	private $globally_registered_blocks = [];

	protected function setUp(): void {
		parent::setUp();

		$this->server = new WP_REST_Server();

		global $wp_rest_server;
		$wp_rest_server = $this->server;
		do_action( 'rest_api_init', $wp_rest_server );
	}

	protected function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;

		foreach ( $this->globally_registered_blocks as $block_name ) {
			$this->unregister_global_block( $block_name );
		}

		parent::tearDown();
	}

	public function test_rest_api_returns_blocks_for_post() {
		$this->register_global_block_with_attributes( 'test/custom-heading', [
			'content' => [
				'type'               => 'rich-text',
				'source'             => 'rich-text',
				'selector'           => 'h1,h2,h3,h4,h5,h6',
				'__experimentalRole' => 'content',
			],
			'level'   => [
				'type'    => 'number',
				'default' => 2,
			],
		] );

		$this->register_global_block_with_attributes( 'test/custom-quote', [
			'value'    => [
				'type'               => 'string',
				'source'             => 'html',
				'selector'           => 'blockquote',
				'multiline'          => 'p',
				'default'            => '',
				'__experimentalRole' => 'content',
			],
			'citation' => [
				'type'               => 'rich-text',
				'source'             => 'rich-text',
				'selector'           => 'cite',
				'__experimentalRole' => 'content',
			],
		] );

		$this->register_global_block_with_attributes( 'test/custom-paragraph', [
			'content'     => [
				'type'               => 'rich-text',
				'source'             => 'rich-text',
				'selector'           => 'p',
				'__experimentalRole' => 'content',
			],
			'dropCap'     => [
				'type'    => 'boolean',
				'default' => false,
			],
			'placeholder' => [
				'type' => 'string',
			],
		] );

		$this->register_global_block_with_attributes( 'test/custom-separator', [
			'opacity' => [
				'type'    => 'string',
				'default' => 'alpha-channel',
			],
		] );

		$this->register_global_block_with_attributes( 'test/custom-media-text', [
			'align'             => [
				'type'    => 'string',
				'default' => 'none',
			],
			'mediaAlt'          => [
				'type'               => 'string',
				'source'             => 'attribute',
				'selector'           => 'figure img',
				'attribute'          => 'alt',
				'default'            => '',
				'__experimentalRole' => 'content',
			],
			'mediaPosition'     => [
				'type'    => 'string',
				'default' => 'left',
			],
			'mediaId'           => [
				'type'               => 'number',
				'__experimentalRole' => 'content',
			],
			'mediaUrl'          => [
				'type'               => 'string',
				'source'             => 'attribute',
				'selector'           => 'figure video,figure img',
				'attribute'          => 'src',
				'__experimentalRole' => 'content',
			],
			'mediaLink'         => [
				'type' => 'string',
			],
			'mediaType'         => [
				'type'               => 'string',
				'__experimentalRole' => 'content',
			],
			'mediaWidth'        => [
				'type'    => 'number',
				'default' => 50,
			],
			'isStackedOnMobile' => [
				'type'    => 'boolean',
				'default' => true,
			],
		] );

		$html = '
			<!-- wp:test/custom-heading -->
			<h2>Heading 1</h2>
			<!-- /wp:test/custom-heading -->

			<!-- wp:test/custom-quote -->
			<blockquote class="wp-block-quote">
				<!-- wp:test/custom-paragraph -->
				<p>Text in quote</p>
				<!-- /wp:test/custom-paragraph -->
				<cite>~ Citation, 2023</cite>
			</blockquote>
			<!-- /wp:test/custom-quote -->

			<!-- wp:test/custom-separator -->
			<hr class="wp-block-separator has-alpha-channel-opacity"/>
			<!-- /wp:test/custom-separator -->

			<!-- wp:test/custom-media-text {"mediaId":6,"mediaLink":"https://gutenberg-block-data-api-test.go-vip.net/?attachment_id=6","mediaType":"image"} -->
			<div class="wp-block-media-text alignwide is-stacked-on-mobile">
				<figure class="wp-block-media-text__media">
					<img src="https://gutenberg-block-data-api-test.go-vip.net/wp-content/uploads/2023/01/4365xAanG8.jpg?w=1024" alt="" class="wp-image-6 size-full"/>
				</figure>

				<div class="wp-block-media-text__content">
					<!-- wp:test/custom-paragraph {"placeholder":"Content…"} -->
					<p>Content on right side of media-text.</p>
					<!-- /wp:test/custom-paragraph -->
				</div>
			</div>
			<!-- /wp:test/custom-media-text -->
		';

		$post_id = $this->get_post_id_with_content( $html );

		$expected_blocks = [
			[
				'name'       => 'test/custom-heading',
				'attributes' => [
					'content' => 'Heading 1',
					'level'   => 2,
				],
			],
			[
				'name'        => 'test/custom-quote',
				'attributes'  => [
					'value'    => '',
					'citation' => '~ Citation, 2023',
				],
				'innerBlocks' => [
					[
						'name'       => 'test/custom-paragraph',
						'attributes' => [
							'content' => 'Text in quote',
							'dropCap' => false,
						],
					],
				],
			],
			[
				'name'       => 'test/custom-separator',
				'attributes' => [
					'opacity' => 'alpha-channel',
				],
			],
			[
				'name'        => 'test/custom-media-text',
				'attributes'  => [
					'mediaId'           => 6,
					'mediaLink'         => 'https://gutenberg-block-data-api-test.go-vip.net/?attachment_id=6',
					'mediaType'         => 'image',
					'align'             => 'none',
					'mediaAlt'          => '',
					'mediaPosition'     => 'left',
					'mediaUrl'          => 'https://gutenberg-block-data-api-test.go-vip.net/wp-content/uploads/2023/01/4365xAanG8.jpg?w=1024',
					'mediaWidth'        => 50,
					'isStackedOnMobile' => true,
				],
				'innerBlocks' => [
					[
						'name'       => 'test/custom-paragraph',
						'attributes' => [
							'placeholder' => 'Content…',
							'content'     => 'Content on right side of media-text.',
							'dropCap'     => false,
						],
					],
				],
			],
		];

		$request  = new WP_REST_Request( 'GET', sprintf( '/vip-block-data-api/v1/posts/%d/blocks', $post_id ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$result = $response->get_data();
		$this->assertArrayHasKey( 'blocks', $result, sprintf( 'Unexpected REST output: %s', wp_json_encode( $result ) ) );
		$this->assertEquals( $expected_blocks, $result['blocks'] );
		$this->assertArrayNotHasKey( 'warnings', $result );

		wp_delete_post( $post_id );
	}

	public function test_rest_api_does_not_return_excluded_blocks_for_post() {
		$this->register_global_block_with_attributes( 'test/custom-heading', [
			'content' => [
				'type'               => 'rich-text',
				'source'             => 'rich-text',
				'selector'           => 'h1,h2,h3,h4,h5,h6',
				'__experimentalRole' => 'content',
			],
			'level'   => [
				'type'    => 'number',
				'default' => 2,
			],
		] );

		$this->register_global_block_with_attributes( 'test/custom-quote', [
			'value'    => [
				'type'               => 'string',
				'source'             => 'html',
				'selector'           => 'blockquote',
				'multiline'          => 'p',
				'default'            => '',
				'__experimentalRole' => 'content',
			],
			'citation' => [
				'type'               => 'rich-text',
				'source'             => 'rich-text',
				'selector'           => 'cite',
				'__experimentalRole' => 'content',
			],
		] );

		$this->register_global_block_with_attributes( 'test/custom-paragraph', [
			'content'     => [
				'type'               => 'rich-text',
				'source'             => 'rich-text',
				'selector'           => 'p',
				'__experimentalRole' => 'content',
			],
			'dropCap'     => [
				'type'    => 'boolean',
				'default' => false,
			],
			'placeholder' => [
				'type' => 'string',
			],
		] );

		$this->register_global_block_with_attributes( 'test/custom-separator', [
			'opacity' => [
				'type'    => 'string',
				'default' => 'alpha-channel',
			],
		] );

		$this->register_global_block_with_attributes( 'test/custom-media-text', [
			'align'             => [
				'type'    => 'string',
				'default' => 'none',
			],
			'mediaAlt'          => [
				'type'               => 'string',
				'source'             => 'attribute',
				'selector'           => 'figure img',
				'attribute'          => 'alt',
				'default'            => '',
				'__experimentalRole' => 'content',
			],
			'mediaPosition'     => [
				'type'    => 'string',
				'default' => 'left',
			],
			'mediaId'           => [
				'type'               => 'number',
				'__experimentalRole' => 'content',
			],
			'mediaUrl'          => [
				'type'               => 'string',
				'source'             => 'attribute',
				'selector'           => 'figure video,figure img',
				'attribute'          => 'src',
				'__experimentalRole' => 'content',
			],
			'mediaLink'         => [
				'type' => 'string',
			],
			'mediaType'         => [
				'type'               => 'string',
				'__experimentalRole' => 'content',
			],
			'mediaWidth'        => [
				'type'    => 'number',
				'default' => 50,
			],
			'isStackedOnMobile' => [
				'type'    => 'boolean',
				'default' => true,
			],
		] );

		$html = '
			<!-- wp:test/custom-heading -->
			<h2>Heading 1</h2>
			<!-- /wp:test/custom-heading -->

			<!-- wp:test/custom-quote -->
			<blockquote class="wp-block-quote">
				<!-- wp:test/custom-paragraph -->
				<p>Text in quote</p>
				<!-- /wp:test/custom-paragraph -->
				<cite>~ Citation, 2023</cite>
			</blockquote>
			<!-- /wp:test/custom-quote -->

			<!-- wp:test/custom-separator -->
			<hr class="wp-block-separator has-alpha-channel-opacity"/>
			<!-- /wp:test/custom-separator -->

			<!-- wp:test/custom-media-text {"mediaId":6,"mediaLink":"https://gutenberg-block-data-api-test.go-vip.net/?attachment_id=6","mediaType":"image"} -->
			<div class="wp-block-media-text alignwide is-stacked-on-mobile">
				<figure class="wp-block-media-text__media">
					<img src="https://gutenberg-block-data-api-test.go-vip.net/wp-content/uploads/2023/01/4365xAanG8.jpg?w=1024" alt="" class="wp-image-6 size-full"/>
				</figure>

				<div class="wp-block-media-text__content">
					<!-- wp:test/custom-paragraph {"placeholder":"Content…"} -->
					<p>Content on right side of media-text.</p>
					<!-- /wp:test/custom-paragraph -->
				</div>
			</div>
			<!-- /wp:test/custom-media-text -->
		';

		$post_id = $this->get_post_id_with_content( $html );

		$expected_blocks = [
			[
				'name'       => 'test/custom-heading',
				'attributes' => [
					'content' => 'Heading 1',
					'level'   => 2,
				],
			],
			[
				'name'       => 'test/custom-quote',
				'attributes' => [
					'value'    => '',
					'citation' => '~ Citation, 2023',
				],
			],
			[
				'name'       => 'test/custom-media-text',
				'attributes' => [
					'mediaId'           => 6,
					'mediaLink'         => 'https://gutenberg-block-data-api-test.go-vip.net/?attachment_id=6',
					'mediaType'         => 'image',
					'align'             => 'none',
					'mediaAlt'          => '',
					'mediaPosition'     => 'left',
					'mediaUrl'          => 'https://gutenberg-block-data-api-test.go-vip.net/wp-content/uploads/2023/01/4365xAanG8.jpg?w=1024',
					'mediaWidth'        => 50,
					'isStackedOnMobile' => true,
				],
			],
		];

		$request = new WP_REST_Request( 'GET', sprintf( '/vip-block-data-api/v1/posts/%d/blocks', $post_id ) );
		// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
		$request->set_query_params( [ 'exclude' => 'test/custom-paragraph,test/custom-separator' ] );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$result = $response->get_data();
		$this->assertArrayHasKey( 'blocks', $result, sprintf( 'Unexpected REST output: %s', wp_json_encode( $result ) ) );
		$this->assertEquals( $expected_blocks, $result['blocks'], sprintf( 'Unexpected REST output: %s', wp_json_encode( $result['blocks'] ) ) );
		$this->assertArrayNotHasKey( 'warnings', $result );

		wp_delete_post( $post_id );
	}

	public function test_rest_api_only_returns_included_blocks_for_post() {
		$this->register_global_block_with_attributes( 'test/custom-heading', [
			'content' => [
				'type'               => 'rich-text',
				'source'             => 'rich-text',
				'selector'           => 'h1,h2,h3,h4,h5,h6',
				'__experimentalRole' => 'content',
			],
			'level'   => [
				'type'    => 'number',
				'default' => 2,
			],
		] );

		$this->register_global_block_with_attributes( 'test/custom-quote', [
			'value'    => [
				'type'               => 'string',
				'source'             => 'html',
				'selector'           => 'blockquote',
				'multiline'          => 'p',
				'default'            => '',
				'__experimentalRole' => 'content',
			],
			'citation' => [
				'type'               => 'rich-text',
				'source'             => 'rich-text',
				'selector'           => 'cite',
				'__experimentalRole' => 'content',
			],
		] );

		$this->register_global_block_with_attributes( 'test/custom-paragraph', [
			'content'     => [
				'type'               => 'rich-text',
				'source'             => 'rich-text',
				'selector'           => 'p',
				'__experimentalRole' => 'content',
			],
			'dropCap'     => [
				'type'    => 'boolean',
				'default' => false,
			],
			'placeholder' => [
				'type' => 'string',
			],
		] );

		$this->register_global_block_with_attributes( 'test/custom-separator', [
			'opacity' => [
				'type'    => 'string',
				'default' => 'alpha-channel',
			],
		] );

		$this->register_global_block_with_attributes( 'test/custom-media-text', [
			'align'             => [
				'type'    => 'string',
				'default' => 'none',
			],
			'mediaAlt'          => [
				'type'               => 'string',
				'source'             => 'attribute',
				'selector'           => 'figure img',
				'attribute'          => 'alt',
				'default'            => '',
				'__experimentalRole' => 'content',
			],
			'mediaPosition'     => [
				'type'    => 'string',
				'default' => 'left',
			],
			'mediaId'           => [
				'type'               => 'number',
				'__experimentalRole' => 'content',
			],
			'mediaUrl'          => [
				'type'               => 'string',
				'source'             => 'attribute',
				'selector'           => 'figure video,figure img',
				'attribute'          => 'src',
				'__experimentalRole' => 'content',
			],
			'mediaLink'         => [
				'type' => 'string',
			],
			'mediaType'         => [
				'type'               => 'string',
				'__experimentalRole' => 'content',
			],
			'mediaWidth'        => [
				'type'    => 'number',
				'default' => 50,
			],
			'isStackedOnMobile' => [
				'type'    => 'boolean',
				'default' => true,
			],
		] );

		$html = '
			<!-- wp:test/custom-heading -->
			<h2>Heading 1</h2>
			<!-- /wp:test/custom-heading -->

			<!-- wp:test/custom-quote -->
			<blockquote class="wp-block-quote">
				<!-- wp:test/custom-paragraph -->
				<p>Text in quote</p>
				<!-- /wp:test/custom-paragraph -->
				<cite>~ Citation, 2023</cite>
			</blockquote>
			<!-- /wp:test/custom-quote -->

			<!-- wp:test/custom-separator -->
			<hr class="wp-block-separator has-alpha-channel-opacity"/>
			<!-- /wp:test/custom-separator -->

			<!-- wp:test/custom-media-text {"mediaId":6,"mediaLink":"https://gutenberg-block-data-api-test.go-vip.net/?attachment_id=6","mediaType":"image"} -->
			<div class="wp-block-media-text alignwide is-stacked-on-mobile">
				<figure class="wp-block-media-text__media">
					<img src="https://gutenberg-block-data-api-test.go-vip.net/wp-content/uploads/2023/01/4365xAanG8.jpg?w=1024" alt="" class="wp-image-6 size-full"/>
				</figure>

				<div class="wp-block-media-text__content">
					<!-- wp:test/custom-paragraph {"placeholder":"Content…"} -->
					<p>Content on right side of media-text.</p>
					<!-- /wp:test/custom-paragraph -->
				</div>
			</div>
			<!-- /wp:test/custom-media-text -->
		';

		$post_id = $this->get_post_id_with_content( $html );

		$expected_blocks = [
			[
				'name'       => 'test/custom-heading',
				'attributes' => [
					'content' => 'Heading 1',
					'level'   => 2,
				],
			],
		];

		$request = new WP_REST_Request( 'GET', sprintf( '/vip-block-data-api/v1/posts/%d/blocks', $post_id ) );
		$request->set_query_params( [ 'include' => 'test/custom-heading' ] );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$result = $response->get_data();
		$this->assertArrayHasKey( 'blocks', $result, sprintf( 'Unexpected REST output: %s', wp_json_encode( $result ) ) );
		$this->assertEquals( $expected_blocks, $result['blocks'], sprintf( 'Unexpected REST output: %s', wp_json_encode( $result['blocks'] ) ) );
		$this->assertArrayNotHasKey( 'warnings', $result );

		wp_delete_post( $post_id );
	}

	public function test_rest_api_returns_blocks_for_custom_post_type() {
		$test_post_type = register_post_type( 'vip-test-post-type1', [
			'public'       => true,
			'show_in_rest' => true,
		]);

		$this->register_global_block_with_attributes( 'test/custom-paragraph', [
			'content' => [
				'type'               => 'rich-text',
				'source'             => 'rich-text',
				'selector'           => 'p',
				'__experimentalRole' => 'content',
			],
			'dropCap' => [
				'type'    => 'boolean',
				'default' => false,
			],
		] );

		$html = '
			<!-- wp:test/custom-paragraph -->
			<p>Text in custom post type</p>
			<!-- /wp:test/custom-paragraph -->
		';

		$post_id = $this->factory()->post->create( [
			'post_title'   => 'Custom published post',
			'post_type'    => $test_post_type->name,
			'post_content' => $html,
			'post_status'  => 'publish',
		] );

		$expected_blocks = [
			[
				'name'       => 'test/custom-paragraph',
				'attributes' => [
					'content' => 'Text in custom post type',
					'dropCap' => false,
				],
			],
		];

		$request  = new WP_REST_Request( 'GET', sprintf( '/vip-block-data-api/v1/posts/%d/blocks', $post_id ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$result = $response->get_data();
		$this->assertArrayHasKey( 'blocks', $result, sprintf( 'Unexpected REST output: %s', wp_json_encode( $result ) ) );
		$this->assertEquals( $expected_blocks, $result['blocks'] );
		$this->assertArrayNotHasKey( 'warnings', $result );

		wp_delete_post( $post_id );
		unregister_post_type( $test_post_type->name );
	}

	public function test_rest_api_returns_error_for_non_public_post_type() {
		$test_post_type = register_post_type( 'vip-test-post-type2', [
			'public' => false,
		]);

		$this->register_global_block_with_attributes( 'test/custom-paragraph', [
			'content' => [
				'type'               => 'rich-text',
				'source'             => 'rich-text',
				'selector'           => 'p',
				'__experimentalRole' => 'content',
			],
		] );

		$post_id = $this->factory()->post->create( [
			'post_title'   => 'Custom post type',
			'post_type'    => $test_post_type->name,
			'post_content' => '<!-- wp:test/custom-paragraph --><p>Custom post type content</p><!-- /wp:test/custom-paragraph -->',
			'post_status'  => 'publish',
		] );

		$request  = new WP_REST_Request( 'GET', sprintf( '/vip-block-data-api/v1/posts/%d/blocks', $post_id ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );

		$result = $response->get_data();
		$this->assertArrayNotHasKey( 'blocks', $result );
		$this->assertArrayHasKey( 'code', $result );
		$this->assertEquals( 'rest_invalid_param', $result['code'] );

		wp_delete_post( $post_id );
		unregister_post_type( $test_post_type->name );
	}

	public function test_rest_api_returns_error_for_non_rest_post_type() {
		$test_post_type = register_post_type( 'vip-test-post-type3', [
			'public'       => true,
			'show_in_rest' => false,
		]);

		$this->register_global_block_with_attributes( 'test/custom-paragraph', [
			'content' => [
				'type'               => 'rich-text',
				'source'             => 'rich-text',
				'selector'           => 'p',
				'__experimentalRole' => 'content',
			],
		] );

		$post_id = $this->factory()->post->create( [
			'post_title'   => 'Custom post type',
			'post_type'    => $test_post_type->name,
			'post_content' => '<!-- wp:test/custom-paragraph --><p>Custom post type content</p><!-- /wp:test/custom-paragraph -->',
			'post_status'  => 'publish',
		] );

		$request  = new WP_REST_Request( 'GET', sprintf( '/vip-block-data-api/v1/posts/%d/blocks', $post_id ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );

		$result = $response->get_data();
		$this->assertArrayNotHasKey( 'blocks', $result );
		$this->assertArrayHasKey( 'code', $result );
		$this->assertEquals( 'rest_invalid_param', $result['code'] );

		wp_delete_post( $post_id );
		unregister_post_type( $test_post_type->name );
	}

	public function test_rest_api_returns_error_for_unpublished_post() {
		$this->register_global_block_with_attributes( 'test/custom-paragraph', [
			'content' => [
				'type'               => 'rich-text',
				'source'             => 'rich-text',
				'selector'           => 'p',
				'__experimentalRole' => 'content',
			],
		] );

		$post_id = $this->factory()->post->create( [
			'post_title'   => 'Unpublished post',
			'post_type'    => 'post',
			'post_content' => '<!-- wp:test/custom-paragraph --><p>Unpublished content</p><!-- /wp:test/custom-paragraph -->',
			'post_status'  => 'draft',
		] );

		$request  = new WP_REST_Request( 'GET', sprintf( '/vip-block-data-api/v1/posts/%d/blocks', $post_id ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );

		$result = $response->get_data();
		$this->assertArrayNotHasKey( 'blocks', $result );
		$this->assertArrayHasKey( 'code', $result );
		$this->assertEquals( 'rest_invalid_param', $result['code'] );

		wp_delete_post( $post_id );
	}

	public function test_rest_api_returns_error_for_classic_content() {
		$post_id = $this->get_post_id_with_content( '<p>Classic editor content</p>' );

		// Ignore exception created by PHPUnit called when trigger_error() is called internally
		$this->convert_next_error_to_exception();
		$this->expectExceptionMessage( 'vip-block-data-api-no-blocks' );

		$request = new WP_REST_Request( 'GET', sprintf( '/vip-block-data-api/v1/posts/%d/blocks', $post_id ) );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );

		$result = $response->get_data();
		$this->assertArrayNotHasKey( 'blocks', $result );
		$this->assertArrayHasKey( 'code', $result );
		$this->assertEquals( 'vip-block-data-api-no-blocks', $result['code'] );

		wp_delete_post( $post_id );
	}

	public function test_rest_api_returns_error_for_include_and_exclude_filter() {
		$this->register_global_block_with_attributes( 'test/custom-paragraph', [
			'content' => [
				'type'               => 'rich-text',
				'source'             => 'rich-text',
				'selector'           => 'p',
				'__experimentalRole' => 'content',
			],
		] );

		$post_id = $this->get_post_id_with_content( '<!-- wp:test/custom-paragraph --><p>content</p><!-- /wp:test/custom-paragraph -->' );

		// Ignore exception created by PHPUnit called when trigger_error() is called internally
		$this->convert_next_error_to_exception();
		$this->expectExceptionMessage( 'vip-block-data-api-invalid-params' );

		$request = new WP_REST_Request( 'GET', sprintf( '/vip-block-data-api/v1/posts/%d/blocks', $post_id ) );
		$request->set_query_params( [
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
			'exclude' => 'test/custom-paragraph,core/separator',
			'include' => 'core/heading,core/quote,core/media-text',
		] );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );

		$result = $response->get_data();
		$this->assertArrayNotHasKey( 'blocks', $result );
		$this->assertArrayHasKey( 'code', $result );
		$this->assertEquals( 'vip-block-data-api-invalid-params', $result['code'] );

		wp_delete_post( $post_id );
	}

	public function test_rest_api_returns_error_for_unexpected_exception() {
		$this->register_global_block_with_attributes( 'test/custom-paragraph', [
			'content' => [
				'type'               => 'rich-text',
				'source'             => 'rich-text',
				'selector'           => 'p',
				'__experimentalRole' => 'content',
			],
		] );

		$post_id = $this->get_post_id_with_content( '<!-- wp:test/custom-paragraph --><p>Content</p><!-- /wp:test/custom-paragraph -->' );

		$exception_causing_parser_function = function () {
			throw new Exception( 'Exception in parser' );
		};

		// Ignore exception created by PHPUnit called when trigger_error() is called internally
		$this->convert_next_error_to_exception();
		$this->expectExceptionMessage( 'vip-block-data-api-parser-error' );

		add_filter( 'vip_block_data_api__sourced_block_result', $exception_causing_parser_function );
		$request  = new WP_REST_Request( 'GET', sprintf( '/vip-block-data-api/v1/posts/%d/blocks', $post_id ) );
		$response = $this->server->dispatch( $request );
		remove_filter( 'vip_block_data_api__sourced_block_result', $exception_causing_parser_function );

		$this->assertEquals( 500, $response->get_status() );

		$result = $response->get_data();
		$this->assertArrayNotHasKey( 'blocks', $result );
		$this->assertArrayHasKey( 'code', $result );
		$this->assertEquals( 'vip-block-data-api-parser-error', $result['code'] );

		wp_delete_post( $post_id );
	}

	private function get_post_id_with_content( $post_content, $post_status = 'publish' ) {
		return $this->factory()->post->create( [
			'post_title'   => 'Rest API Test Post',
			'post_type'    => 'post',
			'post_content' => $post_content,
			'post_status'  => $post_status,
		] );
	}

	private function convert_next_error_to_exception() {
		// See https://github.com/sebastianbergmann/phpunit/issues/5062
		// In PHPUnit 10, errors thrown in code can not be caught by expectException().
		// This method is now deprecated. Use this workaround to convert the next error
		// to an exception, which can be matched with expectExceptionMessage().

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Used for catching errors in tests.
		set_error_handler(
			static function ( int $errno, string $errstr ): never {
				restore_error_handler();
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Not necessary just for tests.
				throw new \Exception( $errstr, $errno );
			},
			E_USER_WARNING
		);
	}

	private function register_global_block_with_attributes( $block_name, $attributes ) {
		WP_Block_Type_Registry::get_instance()->register( $block_name, [
			'apiVersion' => 2,
			'attributes' => $attributes,
		] );

		$this->globally_registered_blocks[] = $block_name;
	}

	private function unregister_global_block( $block_name ) {
		$registry = WP_Block_Type_Registry::get_instance();

		if ( $registry->is_registered( $block_name ) ) {
			$registry->unregister( $block_name );
		}
	}
}
