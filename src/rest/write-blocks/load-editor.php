<?php

namespace WPCOMVIP\BlockDataApi;

defined( 'ABSPATH' ) || die();

use WP_REST_Request;

class LoadEditor {
	public static function init() {
		add_action( 'rest_api_init', [ __CLASS__, 'register_rest_routes' ] );
	}

	public static function register_rest_routes() {
		if ( ! WriteBlocks::is_write_enabled() ) {
			return;
		}

		register_rest_route( WPCOMVIP__BLOCK_DATA_API__REST_ROUTE, 'editor/auth', [
			'methods'             => 'POST',
			'permission_callback' => [ __CLASS__, 'permission_callback' ],
			'callback'            => [ __CLASS__, 'auth_editor' ],
			'args'                => [
				'user_id'  => [
					'required'          => true,
					'validate_callback' => function( $param ) {
						$user = get_user_by( 'id', intval( $param ) );
						return false !== $user;
					},
					'sanitize_callback' => function( $param ) {
						return intval( $param );
					},
				],
				'post_id'  => [
					'required'          => true,
					'validate_callback' => function( $param ) {
						if ( 'new' === $param ) {
							return true;
						}

						$post_id = intval( $param );
						$post    = get_post( $post_id );

						if ( empty( $post ) || empty( $post->ID ) ) {
							return false;
						} else {
							return true;
						}
					},
					'sanitize_callback' => function( $param ) {
						return 'new' === $param ? $param : intval( $param );
					},
				],
				'nonce'    => [
					'required'          => true,
					'validate_callback' => function( $param ) {
						// Validated by permission_callback, as it uses external parameter 'post_id' to verify nonce.
						return true;
					},
					'sanitize_callback' => function( $param ) {
						return strval( $param );
					},
				],
				'auth_key' => [
					'required'          => true,
					'validate_callback' => function( $param ) {
						return defined( 'WPCOMVIP__BLOCK_DATA_API__WRITE_KEY' ) && WPCOMVIP__BLOCK_DATA_API__WRITE_KEY === $param;
					},
					'sanitize_callback' => function( $param ) {
						return strval( $param );
					},
				],
			],
		] );
	}

	public static function permission_callback( WP_REST_Request $request ) {
		$user_id = $request->get_param( 'user_id' );
		$post_id = $request->get_param( 'post_id' );
		$nonce   = $request->get_param( 'nonce' );

		if ( empty( $user_id ) || empty( $post_id ) || empty( $nonce ) ) {
			return false;
		}

		$nonce_action = WriteBlocks::get_nonce_action( $post_id, $user_id );
		$nonce_result = wp_verify_nonce( $nonce, $nonce_action );

		if ( false === $nonce_result ) {
			return false;
		} else {
			return true;
		}
	}

	public static function auth_editor( WP_REST_Request $request ) {
		$user_id = $request->get_param( 'user_id' );
		$user    = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			return [ 'error' => 'Unable to identify editor user' ];
		}

		wp_set_current_user( $user->ID, $user->user_login );
		wp_set_auth_cookie( $user->ID );
		do_action( 'wp_login', $user->user_login, $user );

		wp_safe_redirect( admin_url() );
		exit;
	}
}

LoadEditor::init();
