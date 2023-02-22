<?php

namespace WPCOMVIP\ContentApi\ContentParser\BlockAdditions;

use WP_Query;

defined( 'ABSPATH' ) || die();

class CoreQuery {
	public static function init() {
		add_filter( 'vip_content_api__sourced_block_result', [ __CLASS__, 'add_query_loop_results' ], 5, 4 );
	}

	/**
	 * Parse query args in core/query block attributes and provide results for JSON representation.
	 *
	 * @param array[string]array $sourced_block
	 * @param string $block_name
	 * @param int $post_id
	 * @param array[string]array $block
	 *
	 * @return array[string]array
	 */
	public static function add_query_loop_results( $sourced_block, $block_name, $post_id, $block ) {
		if ( 'core/query' !== $block_name ) {
			return $sourced_block;
		}

		$query = $sourced_block['attributes']['query'] ?? [];

		if ( empty( $query ) ) {
			return $sourced_block;
		}

		$query_args    = build_query_vars_from_query_block( $block, 0 );
		$query         = new WP_Query( $query_args );
		$query_results = $query->posts;

		$sourced_block['attributes'] = array_merge( $sourced_block['attributes'], [
			'queryResults' => $query_results,
		] );

		return $sourced_block;
	}
}

CoreQuery::init();
