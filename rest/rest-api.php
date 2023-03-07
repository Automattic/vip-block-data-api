<?php

namespace WPCOMVIP\ContentApi;

use Exception;
use WP_Error;

defined( 'ABSPATH' ) || die();

defined( 'WPCOMVIP__CONTENT_API__PARSE_TIME_ERROR_THRESHOLD_MS' ) || define( 'WPCOMVIP__CONTENT_API__PARSE_TIME_ERROR_THRESHOLD_MS', 1000 );

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

		Analytics::record_usage();

		$parse_time_start = microtime( true );

		try {
			$content_parser = new ContentParser();
			$parser_results = $content_parser->parse( $post->post_content, $post_id );
		} catch ( Exception $exception ) {
			$error_message = sprintf( 'Error parsing post ID %d: %s', $post_id, $exception );
			Analytics::record_error( $error_message );

			$exception_data     = '';
			$is_production_site = defined( 'VIP_GO_APP_ENVIRONMENT' ) && 'production' === VIP_GO_APP_ENVIRONMENT;

			if ( ! $is_production_site && true === WP_DEBUG ) {
				$exception_data = [
					'stack_trace' => explode( "\n", $exception->getTraceAsString() ),
				];
			}

			// Early return to skip parse time check
			return new WP_Error( 'vip-content-api-parser-error', $exception->getMessage(), $exception_data );
		}

		$parse_time    = microtime( true ) - $parse_time_start;
		$parse_time_ms = floor( $parse_time * 1000 );

		if ( $parse_time_ms > WPCOMVIP__CONTENT_API__PARSE_TIME_ERROR_THRESHOLD_MS ) {
			$error_message = sprintf( 'Parse time for post ID %d exceeded threshold: %dms', $post_id, $parse_time_ms );
			Analytics::record_error( $error_message );
		}

		return $parser_results;
	}
}

RestApi::init();
