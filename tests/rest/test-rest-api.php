<?php
/**
 * Class RestApiTest
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

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
					'align'             => 'wide',
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

	private function get_post_id_with_content( $post_content, $post_status = 'publish' ) {
		return $this->factory()->post->create( [
			'post_title'   => 'Rest API Test Post',
			'post_type'    => 'post',
			'post_content' => $post_content,
			'post_status'  => $post_status,
		] );
	}
}
