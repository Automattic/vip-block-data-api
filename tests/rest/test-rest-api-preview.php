<?php
/**
 * Class RestApiPreviewTest
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

use WP_Application_Passwords;
use WP_REST_Server;
use WP_REST_Request;

/**
 * e2e tests to ensure that an external system (e.g. a headless frontend) can use
 * application password credentials to preview unpublished content.
 */
class RestApiPreviewTest extends RegistryTestCase {
	private $server;

	protected function setUp(): void {
		parent::setUp();

		$this->server = new WP_REST_Server();

		global $wp_rest_server;
		$wp_rest_server = $this->server;
		do_action( 'rest_api_init', $wp_rest_server );

		// Application passwords require HTTPS or a local environment by default.
		add_filter( 'wp_is_application_passwords_available', '__return_true' );

		// Application passwords are only accepted on API requests. REST_REQUEST is not
		// defined when dispatching requests directly in tests.
		add_filter( 'application_password_is_api_request', '__return_true' );

		$this->register_block_with_attributes( 'test/custom-paragraph', [
			'content' => [
				'type'               => 'rich-text',
				'source'             => 'rich-text',
				'selector'           => 'p',
				'__experimentalRole' => 'content',
			],
		] );
	}

	protected function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;

		// phpcs:ignore WordPressVIPMinimum.Variables.ServerVariables.BasicAuthentication -- Simulates an external client's credentials.
		unset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] );
		wp_set_current_user( 0 );

		remove_filter( 'wp_is_application_passwords_available', '__return_true' );
		remove_filter( 'application_password_is_api_request', '__return_true' );

		parent::tearDown();
	}

	/**
	 * @dataProvider unpublished_post_status_provider
	 */
	public function test_rest_api_returns_blocks_for_unpublished_post_with_application_password( $post_status ) {
		$editor_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
		$post_id   = $this->create_post( 'Unpublished content', $post_status );

		$this->authenticate_with_application_password( $editor_id );

		$response = $this->get_blocks( $post_id );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArraySubset( [
			'blocks' => [
				[
					'name'       => 'test/custom-paragraph',
					'attributes' => [
						'content' => 'Unpublished content',
					],
				],
			],
		], $response->get_data(), true );
	}

	public function unpublished_post_status_provider() {
		return [
			'draft'   => [ 'draft' ],
			'pending' => [ 'pending' ],
			'future'  => [ 'future' ],
			'private' => [ 'private' ],
		];
	}

	public function test_rest_api_returns_error_for_draft_post_with_insufficient_role() {
		$subscriber_id = $this->factory()->user->create( [ 'role' => 'subscriber' ] );
		$post_id       = $this->create_post( 'Unpublished content', 'draft' );

		$this->authenticate_with_application_password( $subscriber_id );

		$response = $this->get_blocks( $post_id );

		$this->assertEquals( 400, $response->get_status() );

		$result = $response->get_data();
		$this->assertArrayNotHasKey( 'blocks', $result );
		$this->assertEquals( 'rest_invalid_param', $result['code'] );
	}

	public function test_rest_api_returns_error_for_draft_post_with_invalid_application_password() {
		$editor_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
		$post_id   = $this->create_post( 'Unpublished content', 'draft' );

		WP_Application_Passwords::create_new_application_password( $editor_id, [ 'name' => 'Headless preview' ] );

		$this->set_basic_auth_credentials( get_userdata( $editor_id )->user_login, 'not-the-application-password' );

		$this->assertEquals( 0, get_current_user_id() );

		$response = $this->get_blocks( $post_id );

		$this->assertEquals( 400, $response->get_status() );

		$result = $response->get_data();
		$this->assertArrayNotHasKey( 'blocks', $result );
		$this->assertEquals( 'rest_invalid_param', $result['code'] );
	}

	public function test_rest_api_returns_error_for_draft_post_with_revoked_application_password() {
		$editor_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
		$post_id   = $this->create_post( 'Unpublished content', 'draft' );

		list( $password, $item ) = WP_Application_Passwords::create_new_application_password( $editor_id, [ 'name' => 'Headless preview' ] );
		WP_Application_Passwords::delete_application_password( $editor_id, $item['uuid'] );

		$this->set_basic_auth_credentials( get_userdata( $editor_id )->user_login, $password );

		$this->assertEquals( 0, get_current_user_id() );

		$response = $this->get_blocks( $post_id );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertArrayNotHasKey( 'blocks', $response->get_data() );
	}

	public function test_rest_api_returns_error_for_autosave_of_published_post_with_application_password() {
		$editor_id   = $this->factory()->user->create( [ 'role' => 'editor' ] );
		$post_id     = $this->create_post( 'Published content', 'publish', $editor_id );
		$autosave_id = $this->create_autosave( $post_id, 'Autosaved content', $editor_id );

		$this->authenticate_with_application_password( $editor_id );

		// Autosaves are not readable, even by a user who can edit the parent post.
		$response = $this->get_blocks( $autosave_id );

		$this->assertEquals( 400, $response->get_status() );

		$result = $response->get_data();
		$this->assertArrayNotHasKey( 'blocks', $result );
		$this->assertEquals( 'rest_invalid_param', $result['code'] );
	}

	public function test_rest_api_returns_error_for_autosave_of_published_post_without_authentication() {
		$editor_id   = $this->factory()->user->create( [ 'role' => 'editor' ] );
		$post_id     = $this->create_post( 'Published content', 'publish', $editor_id );
		$autosave_id = $this->create_autosave( $post_id, 'Autosaved content', $editor_id );

		$response = $this->get_blocks( $autosave_id );

		$this->assertEquals( 400, $response->get_status() );

		$result = $response->get_data();
		$this->assertArrayNotHasKey( 'blocks', $result );
		$this->assertEquals( 'rest_invalid_param', $result['code'] );
	}

	/**
	 * Create an application password for a user and authenticate the next request
	 * with it, the same way an external system would with HTTP Basic auth.
	 */
	private function authenticate_with_application_password( $user_id ) {
		list( $password ) = WP_Application_Passwords::create_new_application_password( $user_id, [ 'name' => 'Headless preview' ] );

		$this->set_basic_auth_credentials( get_userdata( $user_id )->user_login, $password );

		// Ensure authentication resolved via the application password.
		$this->assertEquals( $user_id, get_current_user_id() );
	}

	private function set_basic_auth_credentials( $username, $password ) {
		// phpcs:disable WordPressVIPMinimum.Variables.ServerVariables.BasicAuthentication -- Simulates an external client's credentials.
		$_SERVER['PHP_AUTH_USER'] = $username;
		$_SERVER['PHP_AUTH_PW']   = $password;
		// phpcs:enable WordPressVIPMinimum.Variables.ServerVariables.BasicAuthentication

		// Clear the current user so that the next call to wp_get_current_user()
		// re-runs the 'determine_current_user' filter, which validates the
		// application password.
		unset( $GLOBALS['current_user'] );
	}

	private function get_blocks( $post_id ) {
		$request = new WP_REST_Request( 'GET', sprintf( '/vip-block-data-api/v1/posts/%d/blocks', $post_id ) );
		return $this->server->dispatch( $request );
	}

	private function create_post( $content, $post_status, $post_author = 0 ) {
		$post_data = [
			'post_title'   => 'Rest API Preview Test Post',
			'post_type'    => 'post',
			'post_author'  => $post_author,
			'post_content' => sprintf( '<!-- wp:test/custom-paragraph --><p>%s</p><!-- /wp:test/custom-paragraph -->', $content ),
			'post_status'  => $post_status,
		];

		if ( 'future' === $post_status ) {
			$post_data['post_date'] = gmdate( 'Y-m-d H:i:s', strtotime( '+1 week' ) );
		}

		return $this->factory()->post->create( $post_data );
	}

	/**
	 * Unsaved edits to a published post are stored in an autosave revision. This is
	 * the content that WordPress shows when an editor previews the post.
	 */
	private function create_autosave( $post_id, $content, $post_author ) {
		$autosave_id = _wp_put_post_revision( [
			'ID'           => $post_id,
			'post_title'   => 'Rest API Preview Test Post',
			'post_content' => sprintf( '<!-- wp:test/custom-paragraph --><p>%s</p><!-- /wp:test/custom-paragraph -->', $content ),
			'post_author'  => $post_author,
		], true );

		$this->assertIsInt( $autosave_id );

		return $autosave_id;
	}
}
