<?php

namespace WPCOMVIP\ContentApi;

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

		register_rest_route( WPCOMVIP__CONTENT_API__REST_ROUTE, 'client-side-blocks', [
			'methods'             => 'GET',
			'permission_callback' => '__return_true', // TODO: permissions
			'callback'            => [ __CLASS__, 'get_client_side_blocks' ],
		] );

		register_rest_route( WPCOMVIP__CONTENT_API__REST_ROUTE, 'client-side-blocks', [
			'methods'             => 'DELETE',
			'permission_callback' => '__return_true', // TODO: permissions
			'callback'            => [ __CLASS__, 'delete_purge_client_side_blocks' ],
		] );

		register_rest_route( WPCOMVIP__CONTENT_API__REST_ROUTE, 'check-blocks-registry-status',
			[
				'methods'   	   => 'POST',
				'permission_callback' => '__return_true', // TODO: permissions
				'callback'   	   => [ __CLASS__, 'post_check_blocks_registry_status' ],
				'args'             => [
					'blocks' => [
						'required' => true,
						'type' => 'array',
						'items' => [
							'type' => 'string',
							'required' => true,
							'pattern' => '^[a-z0-9-]+/[a-z0-9-]+$',
						],
					],
				],
			],
		);

		register_rest_route( WPCOMVIP__CONTENT_API__REST_ROUTE, 'register-client-side-blocks',
			[
				'methods'   	   => 'POST',
				'permission_callback' => '__return_true', // TODO: permissions
				'callback'   	   => [ __CLASS__, 'post_register_client_side_blocks' ],
				'args'             => [
					'blocks' => [
						'required' => true,
						'type' => 'array',
						'items' => [
							'type' => 'object',
							'properties' => [
								'name' => [
									'type' => 'string',
									'required' => true,
									'pattern' => '^[a-z0-9-]+/[a-z0-9-]+$',
								],
								'meta' => [
									'type' => 'object',
								],
							],
						],
					],
				],
			],
		);
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

	public static function get_client_side_blocks() {
		$blocks = UnregisteredBlocksStore::instance()->get_blocks();

		return [ 'blocks' => $blocks ];
	}

	public static function post_check_blocks_registry_status( $params ) {
		$blocks = $params['blocks'];

		$blocks_to_register = UnregisteredBlocksStore::instance()->check_unregistered_blocks( $blocks );
		return [ 'unregistered' => $blocks_to_register ];
	}

	public static function post_register_client_side_blocks( $params ) {
		$blocks = $params['blocks'];
		$stored_blocks = [];
		$failed_blocks = [];

		foreach ( $blocks as $block ) {
			// Store the unregistered block in the database
			$stored_block = UnregisteredBlocksStore::instance()->add_block( $block['name'], $block['meta'] ?? [] );

			if ( ! is_wp_error( $stored_block ) ) {
				$stored_blocks[] = $block['name'];
			} else {
				$failed_blocks[] = [
					'name' => $block['name'],
					'error' => $stored_block->get_error_message(),
				];
			}
		}

		return [ 'registered' => $stored_blocks, 'failed' => $failed_blocks ];
	}

	public static function delete_purge_client_side_blocks() {
		$total = UnregisteredBlocksStore::instance()->flush_all_blocks();

		return [ 'total_purged' => $total ];
	}
}

RestApi::init();
