<?php
namespace WPCOMVIP\ContentApi;

class UnregisteredBlocksStore {

	private static UnregisteredBlocksStore $instance;

	private const REGISTRY_POST_TYPE = 'vip_block_registry';

	private const REGISTRY_CACHE_KEY = 'vip_block_registry';

	private function __construct() {
		add_action( 'init', [ $this, 'init' ] );
	}

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new UnregisteredBlocksStore();
		}

		return self::$instance;
	}

	public function init() {
		// Create block registry custom post type
		register_post_type( self::REGISTRY_POST_TYPE, [
			'labels' => [
				'name' => __( 'VIP Block Registry', 'vip-content-api' ),
			],
			'public' => false,
			'supports' => [ 'title', 'custom-fields' ],
			'show_ui' => false,
			'query_var' => false,
		] );

		// Register the stored blocks on WordPress
		$this->register_blocks();
	}

	public function add_block( $block_name, $meta = array() ) {
		// Check if block is already registered
		$block = get_page_by_title( $block_name, OBJECT, self::REGISTRY_POST_TYPE );

		if ( $block ) {
			return new \WP_Error( 'block_already_registered', 'Block already registered' );
		}

		// Create new block
		$block_id = wp_insert_post( [
			'post_type' => self::REGISTRY_POST_TYPE,
			'post_title' => $block_name,
			'post_status' => 'publish',
		] );

		// Add meta
		if ( ! is_wp_error( $block_id) && ! empty( $meta ) ) {
			// TODO?: Validate if meta follows the schema at https://schemas.wp.org/trunk/block.json
			update_post_meta( $block_id, 'block_meta', $meta );
		}

		wp_cache_delete( self::REGISTRY_CACHE_KEY );
		return $block_id;
	}

	public function remove_block( $block_name ) {
		// Check if block is already registered
		$block = get_page_by_title( $block_name, OBJECT, self::REGISTRY_POST_TYPE );

		if ( ! $block ) {
			return;
		}

		// Delete block
		wp_delete_post( $block->ID, true );

		wp_cache_delete( self::REGISTRY_CACHE_KEY );
	}

	public function flush_all_blocks() {
		$blocks = $this->get_blocks();

		foreach ( $blocks as $block ) {
			wp_delete_post( $block->ID, true );
		}

		wp_cache_delete( self::REGISTRY_CACHE_KEY );
	}

	/**
	 * Returns all the stored unregistered blocks.
	 *
	 * @return int[]|mixed|\WP_Post[]
	 */
	public function get_blocks() {
		$blocks = wp_cache_get( self::REGISTRY_CACHE_KEY );

		if ( $blocks ) {
			return $blocks;
		}

		$query = new \WP_Query( [
			'post_type' => self::REGISTRY_POST_TYPE,
			'post_status' => 'publish',
			// TODO: use pagination to get all the blocks?
			'posts_per_page' => -1, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
		] );

		$blocks = $query->posts;

		wp_cache_set( self::REGISTRY_CACHE_KEY, $blocks );

		return $blocks;
	}

	/**
	 * Register all the blocks in the WordPress Block Type Registry.
	 *
	 * @return array
	 */
	public function register_blocks() {
		$blocks     = $this->get_blocks();
		$registered = [];

		foreach ( $blocks as $block ) {
			$block_name  = $block->post_title;
			$block_args = get_post_meta( $block->ID, 'block_meta', true );

			$registered = register_block_type( $block_name, $block_args );
		}

		return $registered;
	}

	/**
	 * Checks for unregistered blocks on the server-side.
	 *
	 * @param $blocks
	 *
	 * @return array
	 */
	public function check_unregistered_blocks( $blocks ) {
		$unregistered = [];
		$registered_blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();

		foreach ( $blocks as $block ) {
			// Check if the block is registered on the WordPress server-side block registry
			if ( ! isset( $registered_blocks[ $block ] ) ) {
				$unregistered[] = $block;
			}
		}

		return $unregistered;
	}

}

// Initialize the registry
UnregisteredBlocksStore::instance();
