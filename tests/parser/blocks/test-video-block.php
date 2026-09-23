<?php
/**
 * Tests for the core/video block.
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

/**
 * Core video block tests.
 */
class VideoBlockTest extends RegistryTestCase {
	public function test_controls_follow_video_markup() {
		$parser = new ContentParser( $this->get_block_registry() );
		$off    = $parser->parse( '<!-- wp:video --><figure class="wp-block-video"><video src="/video.mp4"></video></figure><!-- /wp:video -->' );
		$on     = $parser->parse( '<!-- wp:video --><figure class="wp-block-video"><video controls src="/video.mp4"></video></figure><!-- /wp:video -->' );

		$this->assertIsArray( $off );
		$this->assertIsArray( $on );
		$this->assertFalse( $off['blocks'][0]['attributes']['controls'] );
		$this->assertTrue( $on['blocks'][0]['attributes']['controls'] );
	}
}
