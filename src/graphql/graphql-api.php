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
		// ToDo: Add a filter to allow the graphQL API to be disabled.
		add_action( 'graphql_register_types', [ __CLASS__, 'register_types' ] );
	}

	/**
	 * Extract the blocks data for a post, and return back in the format expected by the graphQL API.
	 *
	 * @param  WPGraphQL\Model\Post $post_model Post model for post.
	 * @return array
	 */
	public static function get_blocks_data( $post_model ) {
		$post_id = $post_model->ID;
		$post    = get_post( $post_id );
		
		$content_parser = new ContentParser();

		// ToDo: Modify the parser to give a flattened array for the innerBlocks, if the right filter_option is provided.
		$parser_results = $content_parser->parse( $post->post_content, $post_id );

		// ToDo: Verify if this is better, or is returning it another way in graphQL is better.
		if ( is_wp_error( $parser_results ) ) {
			// Return API-safe error with extra data (e.g. stack trace) removed.
			return new WP_Error( $parser_results->get_error_message() );
		}

		// ToDo: Transform the attributes into a tuple where the name is one field and the value is another. GraphQL requires an expected format. Might be worth turning this into a filter within the parser.

		// ToDo: Provide a filter to modify the output. Not sure if the individual block, or the entire thing should be allowed to be modified.

		return [
			'blocks' => $parser_results,
		];
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
					'parentID'    => [
						'type'        => 'String',
						'description' => 'ID of the parent for this inner block, if it is an inner block. This will match the ID of the block',
					],
					'index'       => [
						'type'        => 'String',
						'description' => 'For an inner block, this will identify the position of the block under the parent block.',
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
						'type'        => [ 'list_of' => 'BlockData' ],
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
					'blocks' => [
						'type'        => [ 'list_of' => 'BlockData' ],
						'description' => 'List of blocks data',
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
