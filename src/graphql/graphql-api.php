<?php
/**
 * GraphQL API
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

defined( 'ABSPATH' ) || die();

/**
 * GraphQL API to offer an alternative to the REST API.
 */
class GraphQLApi {
	/**
	 * Initiatilize the graphQL API, if its allowed
	 *
	 * @access private
	 */
	public static function init() {
		/**
		 * Filter to enable/disable the graphQL API. By default, it is enabled.
		 * 
		 * @param bool $is_graphql_to_be_enabled Whether the graphQL API should be enabled or not.
		 */
		$is_graphql_to_be_enabled = apply_filters( 'vip_block_data_api__is_graphql_enabled', true );

		if ( ! $is_graphql_to_be_enabled ) {
			return;
		}

		add_filter( 'vip_block_data_api__sourced_block_result', [ __CLASS__, 'transform_block_format' ], 10, 5 );

		add_action( 'graphql_register_types', [ __CLASS__, 'register_types' ] );
	}

	/**
	 * Extract the blocks data for a post, and return back in the format expected by the graphQL API.
	 *
	 * @param  WPGraphQL\Model\Post $post_model Post model for post.
	 * @return array
	 */
	public static function get_blocks_data( $post_model ) {
		$post_id        = $post_model->ID;
		$post           = get_post( $post_id );
		$filter_options = [ 'graphQL' => true ];

		$content_parser = new ContentParser();

		$parser_results = $content_parser->parse( $post->post_content, $post_id, $filter_options );

		// We need to not return a WP_Error object, and so a regular exception is returned.
		if ( is_wp_error( $parser_results ) ) {
			Analytics::record_error( $parser_results );

			// Return API-safe error with extra data (e.g. stack trace) removed.
			return new \Exception( $parser_results->get_error_message() );
		}

		return $parser_results;
	}

	/**
	 * Transform the block's format to the format expected by the graphQL API.
	 *
	 * @param array  $sourced_block An associative array of parsed block data with keys 'name' and 'attribute'.
	 * @param string $block_name Name of the parsed block, e.g. 'core/paragraph'.
	 * @param int    $post_id Post ID associated with the parsed block.
	 * @param array  $block Result of parse_blocks() for this block. Contains 'blockName', 'attrs', 'innerHTML', and 'innerBlocks' keys.
	 * @param array  $filter_options Options to filter using, if any.
	 *
	 * @return array
	 */
	public static function transform_block_format( $sourced_block, $block_name, $post_id, $block, $filter_options ) { // phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( isset( $filter_options['graphQL'] ) && $filter_options['graphQL'] ) {

			// Add the ID to the block, if it is not already there.
			if ( ! isset( $sourced_block['id'] ) && isset( $filter_options['id'] ) ) {
				$sourced_block['id'] = $filter_options['id'];
			}
			
			// Flatten the inner blocks, if any.
			if ( isset( $sourced_block['innerBlocks'] ) ) {
				$sourced_block['innerBlocks'] = self::flatten_inner_blocks( $sourced_block['innerBlocks'], $filter_options );
			}

			// Convert the attributes to be in the name-value format that the schema expects.
			if ( isset( $sourced_block['attributes'] ) && ! empty( $sourced_block['attributes'] ) && ! isset( $sourced_block['attributes'][0]['name'] ) ) {
				$sourced_block['attributes'] = array_map(
					function ( $name, $value ) {
						return [
							'name'  => $name,
							'value' => $value,
						];
					},
					array_keys( $sourced_block['attributes'] ),
					array_values( $sourced_block['attributes'] )
				);
			}
		}

		return $sourced_block;
	}

	/**
	 * Flatten the inner blocks, no matter how many levels of nesting is there.
	 *
	 * @param array $inner_blocks the inner blocks in the block.
	 * @param array $filter_options Options to filter using, if any.
	 * @param array $flattened_blocks the flattened blocks that's built up as we go through the inner blocks.
	 * 
	 * @return array
	 */
	public static function flatten_inner_blocks( $inner_blocks, $filter_options, $flattened_blocks = [] ) {
		foreach ( $inner_blocks as $inner_block ) {
			// This block doesnt have any inner blocks, so just add it to the flattened blocks. Ensure the parentId is set.
			if ( ! isset( $inner_block['innerBlocks'] ) ) {
				$inner_block['parentId'] = $inner_block['parentId'] ?? $filter_options['parentId'];
				array_push( $flattened_blocks, $inner_block );
				// This block is a root block, so go through the inner blocks recursively.
			} elseif ( ! isset( $inner_block['parentId'] ) ) {
				$inner_blocks_copy = $inner_block['innerBlocks'];
				unset( $inner_block['innerBlocks'] );
				$inner_block['parentId'] = $inner_block['parentId'] ?? $filter_options['parentId'];
				array_push( $flattened_blocks, $inner_block );
				$flattened_blocks = self::flatten_inner_blocks( $inner_blocks_copy, $filter_options, $flattened_blocks );
			}
		}

		return $flattened_blocks;
	}

	/**
	 * Register types and fields graphql integration.
	 *
	 * @return void
	 */
	public static function register_types() {
		// Register the type corresponding to the attributes of each individual block.
		register_graphql_object_type(
			'BlockDataAttribute',
			[
				'description' => 'Block data attribute',
				'fields'      => [
					'name'  => [
						'type'        => 'String',
						'description' => 'Block data attribute name',
					],
					'value' => [
						'type'        => 'String',
						'description' => 'Block data attribute value',
					],
				],
			],
		);

		// Register the type corresponding to the individual inner block, with the above attribute.
		register_graphql_type(
			'InnerBlockData',
			[
				'description' => 'Block data',
				'fields'      => [
					'id'         => [
						'type'        => 'String',
						'description' => 'ID of the block',
					],
					'parentId'   => [
						'type'        => 'String',
						'description' => 'ID of the parent for this inner block, if it is an inner block. This will match the ID of the block',
					],
					'name'       => [
						'type'        => 'String',
						'description' => 'Block name',
					],
					'attributes' => [
						'type'        => [
							'list_of' => 'BlockDataAttribute',
						],
						'description' => 'Block data attributes',
					],
				],
			],
		);

		// Register the type corresponding to the individual block, with the above attribute.
		register_graphql_type(
			'BlockData',
			[
				'description' => 'Block data',
				'fields'      => [
					'id'          => [
						'type'        => 'String',
						'description' => 'ID of the block',
					],
					'name'        => [
						'type'        => 'String',
						'description' => 'Block name',
					],
					'attributes'  => [
						'type'        => [
							'list_of' => 'BlockDataAttribute',
						],
						'description' => 'Block data attributes',
					],
					'innerBlocks' => [
						'type'        => [ 'list_of' => 'InnerBlockData' ],
						'description' => 'Flattened list of inner blocks of this block',
					],
				],
			],
		);

		// Register the type corresponding to the list of individual blocks, with each item being the above type.
		register_graphql_type(
			'BlocksData',
			[
				'description' => 'Data for all the blocks',
				'fields'      => [
					'blocks'   => [
						'type'        => [ 'list_of' => 'BlockData' ],
						'description' => 'List of blocks data',
					],
					'warnings' => [
						'type'        => [ 'list_of' => 'String' ],
						'description' => 'List of warnings related to processing the blocks data',
					],
				],
			],
		);

		// Register the field on every post type that supports 'editor'.
		register_graphql_field(
			'NodeWithContentEditor',
			'BlocksData',
			[
				'type'        => 'BlocksData',
				'description' => 'A block representation of post content',
				'resolve'     => [ __CLASS__, 'get_blocks_data' ],
			]
		);
	}
}

GraphQLApi::init();
