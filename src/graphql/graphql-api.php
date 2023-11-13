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
		$is_graphql_to_be_enabled = apply_filters( 'vip_block_data_api__is_graphql_enabled', true );

		if ( ! $is_graphql_to_be_enabled ) {
			return;
		}

		add_filter( 'vip_block_data_api__sourced_block_result_transform', [ __CLASS__, 'transform_block_attributes' ], 10, 5 );

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

		// ToDo: Modify the parser to give a flattened array for the innerBlocks, if the right filter_option is provided.
		$parser_results = $content_parser->parse( $post->post_content, $post_id, $filter_options );

		// ToDo: Verify if this is better, or is returning it another way in graphQL is better.
		if ( is_wp_error( $parser_results ) ) {
			// Return API-safe error with extra data (e.g. stack trace) removed.
			return new \Exception( $parser_results->get_error_message() );
		}

		// ToDo: Transform the attributes into a tuple where the name is one field and the value is another. GraphQL requires an expected format. Might be worth turning this into a filter within the parser.

		// ToDo: Provide a filter to modify the output. Not sure if the individual block, or the entire thing should be allowed to be modified.

		return $parser_results;
	}

	/**
	 * Transform the block attribute's format to the format expected by the graphQL API.
	 *
	 * @param array  $sourced_block An associative array of parsed block data with keys 'name' and 'attribute'.
	 * @param string $block_name Name of the parsed block, e.g. 'core/paragraph'.
	 * @param int    $post_id Post ID associated with the parsed block.
	 * @param array  $block Result of parse_blocks() for this block. Contains 'blockName', 'attrs', 'innerHTML', and 'innerBlocks' keys.
	 * @param array  $filter_options Options to filter using, if any.
	 *
	 * @return array
	 */
	public static function transform_block_attributes( $sourced_block, $block_name, $post_id, $block, $filter_options ) { // phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( isset( $filter_options['graphQL'] ) && $filter_options['graphQL'] ) {

			// Flatten the inner blocks, if any.
			if ( isset( $sourced_block['innerBlocks'] ) && ! isset( $sourced_block['parentId'] ) ) {
				$sourced_block['innerBlocks'] = self::flatten_inner_blocks( $sourced_block );
			}

			if ( isset( $sourced_block['attributes'] ) && ! isset( $sourced_block['attributes'][0]['name'] ) ) {
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

	public static function flatten_inner_blocks( $inner_blocks ) {
		if ( ! isset( $inner_blocks['innerBlocks'] ) ) {
			return [ $inner_blocks ];
		} else {
			foreach ( $inner_blocks['innerBlocks'] as $inner_block ) {
				$inner_blocks['innerBlocks'] = array_merge( $inner_blocks['innerBlocks'], self::flatten_inner_blocks( $inner_block ) );
			}
		}

		return $inner_blocks['innerBlocks'];
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
					'id'          => [
						'type'        => 'String',
						'description' => 'ID of the block',
					],
					'parentId'    => [
						'type'        => 'String',
						'description' => 'ID of the parent for this inner block, if it is an inner block. This will match the ID of the block',
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
					]
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
