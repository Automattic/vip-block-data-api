<?php
/**
 * Output invariants for shared DOM sourcing.
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

class DomReuseTest extends RegistryTestCase {
	public function test_mixed_sources_do_not_change_each_others_selection() {
		$attributes = [
			'title'   => [
				'type'     => 'string',
				'source'   => 'html',
				'selector' => 'h2',
			],
			'url'     => [
				'type'      => 'string',
				'source'    => 'attribute',
				'selector'  => 'a',
				'attribute' => 'href',
			],
			'items'   => [
				'type'     => 'array',
				'source'   => 'query',
				'selector' => 'li',
				'query'    => [ 'text' => [ 'source' => 'text' ] ],
			],
			'content' => [
				'type'     => 'string',
				'source'   => 'rich-text',
				'selector' => 'p',
			],
			'raw'     => [
				'type'   => 'string',
				'source' => 'raw',
			],
		];
		$this->register_block_with_attributes( 'test/forward', $attributes );
		$this->register_block_with_attributes( 'test/reverse', array_reverse( $attributes, true ) );
		$html     = '<div><h2>Heading <em>one</em></h2><a href="/example?a=1&amp;b=2">Link</a><p>Text <strong>two</strong></p><ul><li>First</li><li>Second</li></ul></div>';
		$expected = [
			'content' => 'Text <strong>two</strong>',
			'items'   => [ [ 'text' => 'First' ], [ 'text' => 'Second' ] ],
			'raw'     => $html,
			'title'   => 'Heading <em>one</em>',
			'url'     => '/example?a=1&b=2',
		];
		foreach ( [ 'forward', 'reverse' ] as $name ) {
			$result = ( new ContentParser() )->parse( "<!-- wp:test/{$name} -->{$html}<!-- /wp:test/{$name} -->" );
			$this->assertSame( $expected, $result['blocks'][0]['attributes'] );
		}
	}

	/** @dataProvider metadata_provider */
	public function test_metadata_and_dom_sources_keep_values_and_defaults( $value, $present, $expected ) {
		$this->register_block_with_attributes( 'test/mixed-meta', [
			'meta'  => [
				'type'    => 'string',
				'source'  => 'meta',
				'meta'    => 'dom_reuse_value',
				'default' => 'Default',
			],
			'title' => [
				'type'     => 'string',
				'source'   => 'text',
				'selector' => 'h2',
			],
		] );
		$post_id = self::factory()->post->create();
		if ( $present ) {
			update_post_meta( $post_id, 'dom_reuse_value', $value );
		}
		$result = ( new ContentParser() )->parse( '<!-- wp:test/mixed-meta --><h2>Heading</h2><!-- /wp:test/mixed-meta -->', $post_id );
		$this->assertSame( [
			'meta'  => $expected,
			'title' => 'Heading',
		], $result['blocks'][0]['attributes'] );
	}

	public function metadata_provider() {
		return [
			'empty'   => [ '', true, '' ],
			'zero'    => [ '0', true, '0' ],
			'missing' => [ null, false, 'Default' ],
		];
	}
}
