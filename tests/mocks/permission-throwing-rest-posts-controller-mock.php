<?php
/**
 * REST posts controller mock that throws during the item permission check.
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

use Exception;
use WP_REST_Posts_Controller;
use WP_REST_Request;

/**
 * Test controller that throws during the item permission check.
 */
class PermissionThrowingRestPostsController extends WP_REST_Posts_Controller {
	/**
	 * Throw instead of checking permissions.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @throws Exception Always.
	 */
	public function get_item_permissions_check( $request ) {
		throw new Exception( 'Permission check failure' );
	}
}
