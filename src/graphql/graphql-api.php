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
class GraphQLAPI {
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
	 * Register types and fields graphql integration.
	 *
	 * @return void
	 */
	public static function register_types() {
	}
}
