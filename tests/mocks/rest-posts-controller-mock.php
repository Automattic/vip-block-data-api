<?php
/**
 * REST posts controller mock.
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

use WP_REST_Posts_Controller;
use WP_REST_Request;

/**
 * Test controller that denies every item request.
 */
class DenyingRestPostsController extends WP_REST_Posts_Controller {
	/**
	 * Deny access to every item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return false
	 */
	public function get_item_permissions_check( $request ) {
		return false;
	}
}
