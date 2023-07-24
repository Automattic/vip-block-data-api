<?php

namespace WPCOMVIP\BlockDataApi;

defined( 'ABSPATH' ) || die();

class CreateBlocks {
	public static function init() {
		// Assets for block editor UI
		add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'load_js' ] );
	}

	public static function load_js() {
		$asset_file = include WPCOMVIP__BLOCK_DATA_API__ROOT_PLUGIN_DIR . '/build/index.asset.php';

		wp_enqueue_script(
			'wpcomvip-vip-block-data-api-create',
			WPCOMVIP__BLOCK_DATA_API__ROOT_PLUGIN_DIR . '/build/index.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true /* in_footer */
		);
	}
}

CreateBlocks::init();
