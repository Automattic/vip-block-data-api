<?php

namespace WPCOMVIP\BlockDataApi;

defined( 'ABSPATH' ) || die();

use WP_REST_Request;

class AuthenticateEditor {
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
				'secret_key' => [
					'required'          => true,
					'validate_callback' => function( $param ) {
						return defined( 'WPCOMVIP__BLOCK_DATA_API__WRITE_SECRET_KEY' ) && WPCOMVIP__BLOCK_DATA_API__WRITE_SECRET_KEY === $param;
					},
					'sanitize_callback' => function( $param ) {
						return strval( $param );
					},
				],
			],
		] );
	}

	public static function permission_callback( WP_REST_Request $request ) {
		// By default, don't allow any user to access the editor endpoint unless explicitly allowed via the
		// vip_block_data_api__write_editor_user_ids filter.
		$is_editor_authorized = in_array( get_current_user_id(), apply_filters( 'vip_block_data_api__write_editor_user_ids', [] ) );

		return $is_editor_authorized && current_user_can( 'edit_posts' );
	}

	public static function auth_editor( WP_REST_Request $request ) {
		$current_user = wp_get_current_user();

		wp_set_current_user( $current_user->ID, $current_user->user_login );
		wp_set_auth_cookie( $current_user->ID );
		do_action( 'wp_login', $current_user->user_login, $current_user );

		wp_safe_redirect( admin_url( 'post-new.php' ) );
		exit;
	}
}

AuthenticateEditor::init();
