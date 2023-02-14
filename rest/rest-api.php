<?php

namespace WPCOMVIP\GutenbergContentApi;

defined( 'ABSPATH' ) || die();

class RestApi {
	public static function init() {
		add_action( 'rest_api_init', [ __CLASS__, 'register_rest_routes' ] );
	}

	public static function register_rest_routes() {
		register_rest_route( WPCOMVIP__CONTENT_API__REST_ROUTE, 'posts/(?P<id>[0-9]+)/blocks', [
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => [ __CLASS__, 'get_block_content' ],
			'args'                => [
				'id' => [
					'validate_callback' => function( $param ) {
						return 'publish' === get_post_status( intval( $param ) );
					},
					'sanitize_callback' => function( $param ) {
						return intval( $param );
					},
				],
			],
		] );
	}

	public static function get_block_content( $params ) {
		$post_id = $params['id'];
		$post    = get_post( $post_id );

		$meta_source_function = function() use ( $post_id ) {
			return $post_id;
		};

		$content_parser = new ContentParser();

		add_filter( 'vip_content_api__meta_source_post_id', $meta_source_function );
		$result = $content_parser->post_content_to_blocks( $post->post_content );
		remove_filter( 'vip_content_api__meta_source_post_id', $meta_source_function );

		return $result;
	}
}

RestApi::init();
