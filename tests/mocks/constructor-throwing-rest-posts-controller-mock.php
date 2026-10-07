<?php
/**
 * REST posts controller mock that throws during construction.
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

use Exception;
use WP_REST_Posts_Controller;

/**
 * Test controller that throws during construction.
 */
class ConstructorThrowingRestPostsController extends WP_REST_Posts_Controller {
	/**
	 * Throw instead of constructing the controller.
	 *
	 * @param string $post_type Post type.
	 * @throws Exception Always.
	 */
	public function __construct( $post_type ) {
		throw new Exception( 'Controller constructor failure' );
	}
}
