<?php
/**
 * Class GraphQLAPITest
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

use GraphQLRelay\Relay;

/**
 * Tests for the GraphQL API.
 */
class GraphQLAPIV2Test extends RegistryTestCase {

	protected function setUp(): void {
		parent::setUp();

		// Reset block ID counter before each test
		Relay::reset();
	}

	public function test_is_graphql_enabled_true() {
		$this->assertTrue( apply_filters( 'vip_block_data_api__is_graphql_enabled', true ) );
	}

	public function test_is_graphql_enabled_false() {
		$is_graphql_enabled_function = function () {
			return false;
		};
		add_filter( 'vip_block_data_api__is_graphql_enabled', $is_graphql_enabled_function, 10, 0 );
		$this->assertFalse( apply_filters( 'vip_block_data_api__is_graphql_enabled', true ) );
		remove_filter( 'vip_block_data_api__is_graphql_enabled', $is_graphql_enabled_function, 10, 0 );
	}

	// get_blocks_data() tests

	public function test_get_blocks_data() {
		$this->register_block_with_attributes( 'test/custom-paragraph', [
			'content'     => [
				'type'               => 'rich-text',
				'source'             => 'rich-text',
				'selector'           => 'p',
				'__experimentalRole' => 'content',
			],
			'dropCap'     => [
				'type'    => 'boolean',
				'default' => false,
			],
			'placeholder' => [
				'type' => 'string',
			],
		] );

		$this->register_block_with_attributes( 'test/custom-quote', [
			'value'    => [
				'type'               => 'string',
				'source'             => 'html',
				'selector'           => 'blockquote',
				'multiline'          => 'p',
				'default'            => '',
				'__experimentalRole' => 'content',
			],
			'citation' => [
				'type'               => 'rich-text',
				'source'             => 'rich-text',
				'selector'           => 'cite',
				'__experimentalRole' => 'content',
			],
		] );

		$this->register_block_with_attributes( 'test/custom-heading', [
			'content' => [
				'type'               => 'rich-text',
				'source'             => 'rich-text',
				'selector'           => 'h1,h2,h3,h4,h5,h6',
				'__experimentalRole' => 'content',
			],
			'level'   => [
				'type'    => 'number',
				'default' => 2,
			],
		] );

		$html = '
			<!-- wp:test/custom-paragraph -->
			<p>Welcome to WordPress. This is your first post. Edit or delete it, then start writing!</p>
			<!-- /wp:test/custom-paragraph -->

			<!-- wp:test/custom-quote -->
			<blockquote class="wp-block-quote"><!-- wp:test/custom-paragraph -->
			<p>This is a heading inside a quote</p>
			<!-- /wp:test/custom-paragraph -->

			<!-- wp:test/custom-quote -->
			<blockquote class="wp-block-quote"><!-- wp:test/custom-heading -->
			<h2 class="wp-block-heading">This is a heading</h2>
			<!-- /wp:test/custom-heading --></blockquote>
			<!-- /wp:test/custom-quote --></blockquote>
			<!-- /wp:test/custom-quote -->
		';

		$expected_blocks = [
			'blocks' => [
				[
					'name'       => 'test/custom-paragraph',
					'id'         => '1',
					'parentId'   => null,
					'attributes' => [
						[
							'name'               => 'content',
							'value'              => 'Welcome to WordPress. This is your first post. Edit or delete it, then start writing!',
							'isValueJsonEncoded' => false,
						],
						[
							'name'               => 'dropCap',
							'value'              => 'false',
							'isValueJsonEncoded' => true,
						],
					],
				],
				[
					'name'       => 'test/custom-quote',
					'id'         => '2',
					'parentId'   => null,
					'attributes' => [
						[
							'name'               => 'value',
							'value'              => '',
							'isValueJsonEncoded' => false,
						],
					],
				],
				[
					'name'       => 'test/custom-paragraph',
					'id'         => '3',
					'parentId'   => '2',
					'attributes' => [
						[
							'name'               => 'content',
							'value'              => 'This is a heading inside a quote',
							'isValueJsonEncoded' => false,
						],
						[
							'name'               => 'dropCap',
							'value'              => 'false',
							'isValueJsonEncoded' => true,
						],
					],
				],
				[
					'name'       => 'test/custom-quote',
					'id'         => '4',
					'parentId'   => '2',
					'attributes' => [
						[
							'name'               => 'value',
							'value'              => '',
							'isValueJsonEncoded' => false,
						],
					],
				],
				[
					'name'       => 'test/custom-heading',
					'id'         => '5',
					'parentId'   => '4',
					'attributes' => [
						[
							'name'               => 'content',
							'value'              => 'This is a heading',
							'isValueJsonEncoded' => false,
						],
						[
							'name'               => 'level',
							'value'              => '2',
							'isValueJsonEncoded' => true,
						],
					],
				],
			],
		];

		$post = $this->factory()->post->create_and_get( [
			'post_content' => $html,
		] );

		$blocks_data = GraphQLApiV2::get_blocks_data( $post );

		$this->assertEquals( $expected_blocks, $blocks_data );
	}

	// get_blocks_data() attribute type tests

	public function test_array_data_in_attribute() {
		$this->register_block_with_attributes( 'test/custom-table', [
			'head' => [
				'type'     => 'array',
				'default'  => [],
				'source'   => 'query',
				'selector' => 'thead tr',
				'query'    => [
					'cells' => [
						'type'     => 'array',
						'default'  => [],
						'source'   => 'query',
						'selector' => 'td,th',
						'query'    => [
							'content' => [
								'type'   => 'rich-text',
								'source' => 'rich-text',
							],
							'tag'     => [
								'type'    => 'string',
								'default' => 'td',
								'source'  => 'tag',
							],
						],
					],
				],
			],
			'body' => [
				'type'     => 'array',
				'default'  => [],
				'source'   => 'query',
				'selector' => 'tbody tr',
				'query'    => [
					'cells' => [
						'type'     => 'array',
						'default'  => [],
						'source'   => 'query',
						'selector' => 'td,th',
						'query'    => [
							'content' => [
								'type'   => 'rich-text',
								'source' => 'rich-text',
							],
							'tag'     => [
								'type'    => 'string',
								'default' => 'td',
								'source'  => 'tag',
							],
						],
					],
				],
			],
			'foot' => [
				'type'     => 'array',
				'default'  => [],
				'source'   => 'query',
				'selector' => 'tfoot tr',
				'query'    => [
					'cells' => [
						'type'     => 'array',
						'default'  => [],
						'source'   => 'query',
						'selector' => 'td,th',
						'query'    => [
							'content' => [
								'type'   => 'rich-text',
								'source' => 'rich-text',
							],
							'tag'     => [
								'type'    => 'string',
								'default' => 'td',
								'source'  => 'tag',
							],
						],
					],
				],
			],
		] );

		$html = '
			<!-- wp:test/custom-table -->
			<figure class="wp-block-table">
				<table>
					<thead>
						<tr>
							<th>Header A</th>
							<th>Header B</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>Value A</td>
							<td>Value B</td>
						</tr>
						<tr>
							<td>Value C</td>
							<td>Value D</td>
						</tr>
					</tbody>
					<tfoot>
						<tr>
							<td>Footer A</td>
							<td>Footer B</td>
						</tr>
					</tfoot>
				</table>
			</figure>
			<!-- /wp:test/custom-table -->
		';

		$expected_blocks = [
			'blocks' => [
				[
					'name'       => 'test/custom-table',
					'id'         => '1',
					'parentId'   => null,
					'attributes' => [
						[
							'name'               => 'body',
							'value'              => '[{"cells":[{"content":"Value A","tag":"td"},{"content":"Value B","tag":"td"}]},{"cells":[{"content":"Value C","tag":"td"},{"content":"Value D","tag":"td"}]}]',
							'isValueJsonEncoded' => true,
						],
						[
							'name'               => 'foot',
							'value'              => '[{"cells":[{"content":"Footer A","tag":"td"},{"content":"Footer B","tag":"td"}]}]',
							'isValueJsonEncoded' => true,
						],
						[
							'name'               => 'head',
							'value'              => '[{"cells":[{"content":"Header A","tag":"th"},{"content":"Header B","tag":"th"}]}]',
							'isValueJsonEncoded' => true,
						],
					],
				],
			],
		];

		$post = $this->factory()->post->create_and_get( [
			'post_content' => $html,
		] );

		$blocks_data = GraphQLApiV2::get_blocks_data( $post );

		$this->assertEquals( $expected_blocks, $blocks_data );
	}

	public function test_get_block_data_with_boolean_attributes() {
		$this->register_block_with_attributes( 'test/toggle-text', [
			'isVisible'  => [
				'type' => 'boolean',
			],
			'isBordered' => [
				'type' => 'boolean',
			],
		] );

		$html = '
			<!-- wp:test/toggle-text { "isVisible": true, "isBordered": false } -->
			<div>Block</div>
			<!-- /wp:test/toggle-text -->
		';

		$expected_blocks = [
			'blocks' => [
				[
					'id'         => '1',
					'parentId'   => null,
					'name'       => 'test/toggle-text',
					'attributes' => [
						[
							'name'               => 'isBordered',
							'value'              => 'false',
							'isValueJsonEncoded' => true,
						],
						[
							'name'               => 'isVisible',
							'value'              => 'true',
							'isValueJsonEncoded' => true,
						],
					],
				],
			],
		];

		$post = $this->factory()->post->create_and_get( [
			'post_content' => $html,
		] );

		$blocks_data = GraphQLApiV2::get_blocks_data( $post );

		$this->assertEquals( $expected_blocks, $blocks_data );
	}

	public function test_get_block_data_with_number_attributes() {
		$this->register_block_with_attributes( 'test/gallery-block', [
			'tileCount'   => [
				'type' => 'number',
			],
			'tileWidthPx' => [
				'type' => 'integer', // Same as 'number'
			],
			'tileOpacity' => [
				'type' => 'number',
			],
		] );

		$html = '
			<!-- wp:test/gallery-block { "tileCount": 5, "tileWidthPx": 300, "tileOpacity": 0.5 } -->
			<div>Gallery</div>
			<!-- /wp:test/gallery-block -->
		';

		$expected_blocks = [
			'blocks' => [
				[
					'id'         => '1',
					'parentId'   => null,
					'name'       => 'test/gallery-block',
					'attributes' => [
						[
							'name'               => 'tileCount',
							'value'              => '5',
							'isValueJsonEncoded' => true,
						],
						[
							'name'               => 'tileOpacity',
							'value'              => '0.5',
							'isValueJsonEncoded' => true,
						],
						[
							'name'               => 'tileWidthPx',
							'value'              => '300',
							'isValueJsonEncoded' => true,
						],
					],
				],
			],
		];

		$post = $this->factory()->post->create_and_get( [
			'post_content' => $html,
		] );

		$blocks_data = GraphQLApiV2::get_blocks_data( $post );

		$this->assertEquals( $expected_blocks, $blocks_data );
	}

	public function test_get_block_data_with_string_attribute() {
		$this->register_block_with_attributes( 'test/custom-block', [
			'myComment' => [
				'type' => 'string',
			],
		] );

		$html = '
			<!-- wp:test/custom-block { "myComment": "great!" } -->
			<p>Toggleable text</p>
			<!-- /wp:test/custom-block -->
		';

		$expected_blocks = [
			'blocks' => [
				[
					'id'         => '1',
					'parentId'   => null,
					'name'       => 'test/custom-block',
					'attributes' => [
						[
							'name'               => 'myComment',
							'value'              => 'great!',
							'isValueJsonEncoded' => false, // Strings should not be marked JSON encoded
						],
					],
				],
			],
		];

		$post = $this->factory()->post->create_and_get( [
			'post_content' => $html,
		] );

		$blocks_data = GraphQLApiV2::get_blocks_data( $post );

		$this->assertEquals( $expected_blocks, $blocks_data );
	}

	// flatten_blocks() tests

	public function test_flatten_blocks() {
		$blocks = [
			[
				'name'       => 'core/paragraph',
				'id'         => '1',
				'attributes' => [
					[
						'name'  => 'content',
						'value' => 'Welcome to WordPress. This is your first post. Edit or delete it, then start writing!',
					],
					[
						'name'  => 'dropCap',
						'value' => '',
					],
				],
			],
			[
				'name'        => 'core/quote',
				'id'          => '2',
				'attributes'  => [
					[
						'name'  => 'value',
						'value' => '',
					],
				],
				'innerBlocks' => [
					[
						'name'       => 'core/paragraph',
						'id'         => '3',
						'attributes' => [
							[
								'name'  => 'content',
								'value' => 'This is a heading inside a quote',
							],
							[
								'name'  => 'dropCap',
								'value' => '',
							],
						],
					],
					[
						'name'        => 'core/quote',
						'id'          => '4',
						'attributes'  => [
							[
								'name'  => 'value',
								'value' => '',
							],
						],
						'innerBlocks' => [
							[
								'name'       => 'core/heading',
								'id'         => '5',
								'attributes' => [
									[
										'name'  => 'content',
										'value' => 'This is a heading',
									],
									[
										'name'  => 'level',
										'value' => '2',
									],
								],
							],
						],
					],
				],
			],
		];

		$expected_blocks = [
			[
				'name'       => 'core/paragraph',
				'attributes' => [
					[
						'name'  => 'content',
						'value' => 'Welcome to WordPress. This is your first post. Edit or delete it, then start writing!',
					],
					[
						'name'  => 'dropCap',
						'value' => '',
					],
				],
				'parentId'   => null,
				'id'         => '1',
			],
			[
				'name'       => 'core/quote',
				'attributes' => [
					[
						'name'  => 'value',
						'value' => '',
					],
				],
				'id'         => '2',
				'parentId'   => null,
			],
			[
				'name'       => 'core/paragraph',
				'attributes' => [
					[
						'name'  => 'content',
						'value' => 'This is a heading inside a quote',
					],
					[
						'name'  => 'dropCap',
						'value' => '',
					],
				],
				'id'         => '3',
				'parentId'   => '2',
			],
			[
				'name'       => 'core/quote',
				'attributes' => [
					[
						'name'  => 'value',
						'value' => '',
					],
				],
				'id'         => '4',
				'parentId'   => '2',
			],
			[
				'name'       => 'core/heading',
				'attributes' => [
					[
						'name'  => 'content',
						'value' => 'This is a heading',
					],
					[
						'name'  => 'level',
						'value' => '2',
					],
				],
				'id'         => '5',
				'parentId'   => '4',
			],
		];

		$flattened_blocks = GraphQLApiV2::flatten_blocks( $blocks );

		$this->assertEquals( $expected_blocks, $flattened_blocks );
	}
}
