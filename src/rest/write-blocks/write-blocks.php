<?php

namespace WPCOMVIP\BlockDataApi;

use WP_Error;
use WP_REST_Request;

defined( 'ABSPATH' ) || die();

class WriteBlocks {
	public static function init() {
		if ( ! self::is_write_enabled() ) {
			return;
		}

		add_action( 'rest_api_init', [ __CLASS__, 'register_rest_routes' ] );
	}

	public static function register_rest_routes() {
		register_rest_route( WPCOMVIP__BLOCK_DATA_API__REST_ROUTE, 'posts/(?P<id>[0-9]+)', [
			'methods'             => 'POST',
			'permission_callback' => function( WP_REST_Request $request ) {
				if ( $request->get_param( 'id' ) === null ) {
					return false;
				}

				$post_id = intval( $request->get_param( 'id' ) );
				return current_user_can( 'edit_post', $post_id );
			},
			'callback'            => [ __CLASS__, 'write_blocks' ],
			'args'                => [
				'id'     => [
					'required'          => true,
					'validate_callback' => function( $param ) {
						$post_id = intval( $param );
						$post    = get_post( $post_id );

						if ( empty( $post ) || empty( $post->ID ) ) {
							return false;
						} else {
							return true;
						}
					},
					'sanitize_callback' => function( $param ) {
						return intval( $param );
					},
				],
				'blocks' => [
					'required'          => true,
					'validate_callback' => function( $param ) {
						return self::validate_blocks( $param );
					},
				],
			],
		] );

		register_rest_route( WPCOMVIP__BLOCK_DATA_API__REST_ROUTE, 'posts/new', [
			'methods'             => 'POST',
			'permission_callback' => function( WP_REST_Request $request ) {
				return current_user_can( 'publish_posts' );
			},
			'callback'            => [ __CLASS__, 'write_blocks' ],
			'args'                => [
				'blocks' => [
					'required'          => true,
					'validate_callback' => function( $param ) {
						return self::validate_blocks( $param );
					},
				],
			],
		] );
	}

	public static function write_blocks( $params ) {
		$current_user_id = get_current_user_id();
		$post_id         = $params['id'] ?? 'new';
		$blocks          = $params['blocks'];

		// Because the nonce is used to verify user authentication, clear the global user and create a
		// nonce that can be verified while logged out.
		wp_set_current_user( 0 );
		$nonce_action = self::get_nonce_action( $post_id, $current_user_id );
		$write_nonce  = wp_create_nonce( $nonce_action );

		$response = wp_remote_post( WPCOMVIP__BLOCK_DATA_API__WRITE_MIDDLEWARE_URL, [
			'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'data_format' => 'body',
			'timeout'     => 10,
			'body'        => wp_json_encode([
				'blocks'  => $blocks,
				'postId'  => $post_id,
				'userId'  => $current_user_id,
				'nonce'   => $write_nonce,
				'authKey' => WPCOMVIP__BLOCK_DATA_API__WRITE_KEY,
			]),
		]);

		if ( is_wp_error( $response ) ) {
			$response_body = [ 'error' => $response->get_error_message() ];
		} else {
			$response_body = json_decode( $response['body'], /* associative */ true );
		}

		return $response_body;
	}

	public static function is_write_enabled() {
		return defined( 'WPCOMVIP__BLOCK_DATA_API__WRITE_KEY' ) && defined( 'WPCOMVIP__BLOCK_DATA_API__WRITE_MIDDLEWARE_URL' );
	}

	public static function get_nonce_action( $post_id, $current_user_id ) {
		return sprintf( 'block-data-write-post-%s-%d', $post_id, $current_user_id );
	}

	private static function validate_blocks( $blocks ) {
		return is_array( $blocks );
	}
}

WriteBlocks::init();
