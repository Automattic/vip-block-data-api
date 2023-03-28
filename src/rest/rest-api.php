<?php

namespace WPCOMVIP\BlockDataApi;

use Error;
use Exception;
use WP_Error;

defined( 'ABSPATH' ) || die();

defined( 'WPCOMVIP__BLOCK_DATA_API__PARSE_TIME_ERROR_MS' ) || define( 'WPCOMVIP__BLOCK_DATA_API__PARSE_TIME_ERROR_MS', 500 );

class RestApi {
	public static function init() {
		add_action( 'rest_api_init', [ __CLASS__, 'register_rest_routes' ] );
	}

	public static function register_rest_routes() {
		register_rest_route( WPCOMVIP__BLOCK_DATA_API__REST_ROUTE, 'posts/(?P<id>[0-9]+)/blocks', [
			'methods'             => 'GET',
			'permission_callback' => [ __CLASS__, 'permission_callback' ],
			'callback'            => [ __CLASS__, 'get_block_content' ],
			'args'                => [
				'id' => [
					'validate_callback' => function( $param ) {
						$post_id  = intval( $param );
						$is_valid = 'publish' === get_post_status( $post_id );

						/**
						 * Validates a post can be queried via the block data API REST endpoint.
						 * Return false to disable access to a post.
						 *
						 * @param boolean $is_valid Whether the post ID is valid for querying. Defaults to true when post status is 'publish'.
						 * @param int $post_id The queried post ID.
						 */
						return apply_filters( 'vip_block_data_api__rest_validate_post_id', $is_valid, $post_id );
					},
					'sanitize_callback' => function( $param ) {
						return intval( $param );
					},
				],
			],
		] );
	}

	public static function permission_callback() {
		/**
		 * Validates a request can access the block data API. This filter can be used to limit access to
		 * authenticated users.
		 * Return false to disable access.
		 *
		 * @param boolean $is_permitted Whether the request is permitted. Defaults to true.
		 */
		return apply_filters( 'vip_block_data_api__rest_permission_callback', true );
	}

	public static function get_block_content( $params ) {
		$post_id = $params['id'];
		$post    = get_post( $post_id );

		Analytics::record_usage();

		$parser_error     = false;
		$parse_time_start = microtime( true );

		try {
			$content_parser = new ContentParser();
			$parser_results = $content_parser->parse( $post->post_content, $post_id );
		} catch ( Exception $exception ) {
			$parser_error = $exception;
		} catch ( Error $error ) {
			$parser_error = $error;
		}

		if ( $parser_error ) {
			Analytics::record_error( $parser_error );
			$error_message = sprintf( 'Error parsing post ID %d: %s', $post_id, $parser_error->getMessage() );

			// Early return to skip parse time check
			return new WP_Error( 'vip-block-data-api-parser-error', $error_message );
		}

		$parse_time    = microtime( true ) - $parse_time_start;
		$parse_time_ms = floor( $parse_time * 1000 );

		if ( $parse_time_ms > WPCOMVIP__BLOCK_DATA_API__PARSE_TIME_ERROR_MS ) {
			$error_message = sprintf( 'Parse time for post ID %d exceeded threshold: %dms', $post_id, $parse_time_ms );
			Analytics::record_error( $error_message );
		}

		return $parser_results;
	}
}

RestApi::init();
