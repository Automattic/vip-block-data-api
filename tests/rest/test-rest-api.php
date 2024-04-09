<?php
/**
 * Class RestApiTest
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

use Exception;
use WP_UnitTestCase;
use WP_REST_Server;
use WP_REST_Request;

/**
 * e2e tests to ensure that the REST API endpoint is available.
 */
class RestApiTest extends WP_UnitTestCase {
	private $server;

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

		parent::tearDown();
	}

	public function test_rest_api_returns_blocks_for_post() {
		$html = '
			<!-- wp:heading -->
			<h2>Heading 1</h2>
			<!-- /wp:heading -->

			<!-- wp:quote -->
			<blockquote class="wp-block-quote">
				<!-- wp:paragraph -->
				<p>Text in quote</p>
				<!-- /wp:paragraph -->
				<cite>~ Citation, 2023</cite>
			</blockquote>
			<!-- /wp:quote -->

			<!-- wp:separator -->
			<hr class="wp-block-separator has-alpha-channel-opacity"/>
			<!-- /wp:separator -->

			<!-- wp:media-text {"mediaId":6,"mediaLink":"https://gutenberg-block-data-api-test.go-vip.net/?attachment_id=6","mediaType":"image"} -->
			<div class="wp-block-media-text alignwide is-stacked-on-mobile">
				<figure class="wp-block-media-text__media">
					<img src="https://gutenberg-block-data-api-test.go-vip.net/wp-content/uploads/2023/01/4365xAanG8.jpg?w=1024" alt="" class="wp-image-6 size-full"/>
				</figure>

				<div class="wp-block-media-text__content">
					<!-- wp:paragraph {"placeholder":"Content…"} -->
					<p>Content on right side of media-text.</p>
					<!-- /wp:paragraph -->
				</div>
			</div>
			<!-- /wp:media-text -->
		';

		$post_id = $this->get_post_id_with_content( $html );

		$expected_blocks = [
			[
				'name'       => 'core/heading',
				'attributes' => [
					'content' => 'Heading 1',
					'level'   => 2,
				],
			],
			[
				'name'        => 'core/quote',
				'attributes'  => [
					'value'    => '',
					'citation' => '~ Citation, 2023',
				],
				'innerBlocks' => [
					[
						'name'       => 'core/paragraph',
						'attributes' => [
							'content' => 'Text in quote',
							'dropCap' => false,
						],
					],
				],
			],
			[
				'name'       => 'core/separator',
				'attributes' => [
					'opacity' => 'alpha-channel',
				],
			],
			[
				'name'        => 'core/media-text',
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
						'name'       => 'core/paragraph',
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
		$html = '
			<!-- wp:heading -->
			<h2>Heading 1</h2>
			<!-- /wp:heading -->

			<!-- wp:quote -->
			<blockquote class="wp-block-quote">
				<!-- wp:paragraph -->
				<p>Text in quote</p>
				<!-- /wp:paragraph -->
				<cite>~ Citation, 2023</cite>
			</blockquote>
			<!-- /wp:quote -->

			<!-- wp:separator -->
			<hr class="wp-block-separator has-alpha-channel-opacity"/>
			<!-- /wp:separator -->

			<!-- wp:media-text {"mediaId":6,"mediaLink":"https://gutenberg-block-data-api-test.go-vip.net/?attachment_id=6","mediaType":"image"} -->
			<div class="wp-block-media-text alignwide is-stacked-on-mobile">
				<figure class="wp-block-media-text__media">
					<img src="https://gutenberg-block-data-api-test.go-vip.net/wp-content/uploads/2023/01/4365xAanG8.jpg?w=1024" alt="" class="wp-image-6 size-full"/>
				</figure>

				<div class="wp-block-media-text__content">
					<!-- wp:paragraph {"placeholder":"Content…"} -->
					<p>Content on right side of media-text.</p>
					<!-- /wp:paragraph -->
				</div>
			</div>
			<!-- /wp:media-text -->
		';

		$post_id = $this->get_post_id_with_content( $html );

		$expected_blocks = [
			[
				'name'       => 'core/heading',
				'attributes' => [
					'content' => 'Heading 1',
					'level'   => 2,
				],
			],
			[
				'name'       => 'core/quote',
				'attributes' => [
					'value'    => '',
					'citation' => '~ Citation, 2023',
				],
			],
			[
				'name'       => 'core/media-text',
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
		$request->set_query_params( [ 'exclude' => 'core/paragraph,core/separator' ] );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$result = $response->get_data();
		$this->assertArrayHasKey( 'blocks', $result, sprintf( 'Unexpected REST output: %s', wp_json_encode( $result ) ) );
		$this->assertEquals( $expected_blocks, $result['blocks'], sprintf( 'Unexpected REST output: %s', wp_json_encode( $result['blocks'] ) ) );
		$this->assertArrayNotHasKey( 'warnings', $result );

		wp_delete_post( $post_id );
	}

	public function test_rest_api_only_returns_included_blocks_for_post() {
		$html = '
			<!-- wp:heading -->
			<h2>Heading 1</h2>
			<!-- /wp:heading -->

			<!-- wp:quote -->
			<blockquote class="wp-block-quote">
				<!-- wp:paragraph -->
				<p>Text in quote</p>
				<!-- /wp:paragraph -->
				<cite>~ Citation, 2023</cite>
			</blockquote>
			<!-- /wp:quote -->

			<!-- wp:separator -->
			<hr class="wp-block-separator has-alpha-channel-opacity"/>
			<!-- /wp:separator -->

			<!-- wp:media-text {"mediaId":6,"mediaLink":"https://gutenberg-block-data-api-test.go-vip.net/?attachment_id=6","mediaType":"image"} -->
			<div class="wp-block-media-text alignwide is-stacked-on-mobile">
				<figure class="wp-block-media-text__media">
					<img src="https://gutenberg-block-data-api-test.go-vip.net/wp-content/uploads/2023/01/4365xAanG8.jpg?w=1024" alt="" class="wp-image-6 size-full"/>
				</figure>

				<div class="wp-block-media-text__content">
					<!-- wp:paragraph {"placeholder":"Content…"} -->
					<p>Content on right side of media-text.</p>
					<!-- /wp:paragraph -->
				</div>
			</div>
			<!-- /wp:media-text -->
		';

		$post_id = $this->get_post_id_with_content( $html );

		$expected_blocks = [
			[
				'name'       => 'core/heading',
				'attributes' => [
					'content' => 'Heading 1',
					'level'   => 2,
				],
			],
		];

		$request = new WP_REST_Request( 'GET', sprintf( '/vip-block-data-api/v1/posts/%d/blocks', $post_id ) );
		$request->set_query_params( [ 'include' => 'core/heading' ] );

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

		$html = '
			<!-- wp:paragraph -->
			<p>Text in custom post type</p>
			<!-- /wp:paragraph -->
		';

		$post_id = $this->factory()->post->create( [
			'post_title'   => 'Custom published post',
			'post_type'    => $test_post_type->name,
			'post_content' => $html,
			'post_status'  => 'publish',
		] );

		$expected_blocks = [
			[
				'name'       => 'core/paragraph',
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

		$post_id = $this->factory()->post->create( [
			'post_title'   => 'Custom post type',
			'post_type'    => $test_post_type->name,
			'post_content' => '<!-- wp:paragraph --><p>Custom post type content</p><!-- /wp:paragraph -->',
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

		$post_id = $this->factory()->post->create( [
			'post_title'   => 'Custom post type',
			'post_type'    => $test_post_type->name,
			'post_content' => '<!-- wp:paragraph --><p>Custom post type content</p><!-- /wp:paragraph -->',
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
		$post_id = $this->factory()->post->create( [
			'post_title'   => 'Unpublished post',
			'post_type'    => 'post',
			'post_content' => '<!-- wp:paragraph --><p>Unpublished content</p><!-- /wp:paragraph -->',
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
		$post_id = $this->get_post_id_with_content( '<!-- wp:paragraph --><p>content</p><!-- /wp:paragraph -->' );

		// Ignore exception created by PHPUnit called when trigger_error() is called internally
		$this->convert_next_error_to_exception();
		$this->expectExceptionMessage( 'vip-block-data-api-invalid-params' );

		$request = new WP_REST_Request( 'GET', sprintf( '/vip-block-data-api/v1/posts/%d/blocks', $post_id ) );
		$request->set_query_params( [
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
			'exclude' => 'core/paragraph,core/separator',
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
		$post_id = $this->get_post_id_with_content( '<!-- wp:paragraph --><p>Content</p><!-- /wp:paragraph -->' );

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
}
