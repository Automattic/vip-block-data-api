<?php
/**
 * Class GraphQLAPITest
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

/**
 * Tests for the GraphQL API.
 */
class GraphQLAPITest extends RegistryTestCase {

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
		$html = '
			<!-- wp:paragraph -->
			<p>Welcome to WordPress. This is your first post. Edit or delete it, then start writing!</p>
			<!-- /wp:paragraph -->

			<!-- wp:quote -->
			<blockquote class="wp-block-quote"><!-- wp:paragraph -->
			<p>This is a heading inside a quote</p>
			<!-- /wp:paragraph -->

			<!-- wp:quote -->
			<blockquote class="wp-block-quote"><!-- wp:heading -->
			<h2 class="wp-block-heading">This is a heading</h2>
			<!-- /wp:heading --></blockquote>
			<!-- /wp:quote --></blockquote>
			<!-- /wp:quote -->
		';

		$expected_blocks = [
			'blocks' => [
				[
					'name'       => 'core/paragraph',
					'attributes' => [
						[
							'name'               => 'content',
							'value'              => 'Welcome to WordPress. This is your first post. Edit or delete it, then start writing!',
							'isValueJsonEncoded' => false,
						],
						[
							'name'               => 'dropCap',
							'value'              => '',
							'isValueJsonEncoded' => false,
						],
					],
					'id'         => '1',
				],
				[
					'name'        => 'core/quote',
					'attributes'  => [
						[
							'name'               => 'value',
							'value'              => '',
							'isValueJsonEncoded' => false,
						],
					],
					'innerBlocks' => [
						[
							'name'       => 'core/paragraph',
							'attributes' => [
								[
									'name'               => 'content',
									'value'              => 'This is a heading inside a quote',
									'isValueJsonEncoded' => false,
								],
								[
									'name'               => 'dropCap',
									'value'              => '',
									'isValueJsonEncoded' => false,
								],
							],
							'id'         => '3',
						],
						[
							'name'        => 'core/quote',
							'attributes'  => [
								[
									'name'               => 'value',
									'value'              => '',
									'isValueJsonEncoded' => false,
								],
							],
							'innerBlocks' => [
								[
									'name'       => 'core/heading',
									'attributes' => [
										[
											'name'  => 'content',
											'value' => 'This is a heading',
											'isValueJsonEncoded' => false,
										],
										[
											'name'  => 'level',
											'value' => '2',
											'isValueJsonEncoded' => false,
										],
									],
									'id'         => '5',
								],
							],
							'id'          => '4',
						],
					],
					'id'          => '2',
				],
			],
		];

		$post = $this->factory()->post->create_and_get( [
			'post_content' => $html,
		] );

		$blocks_data = GraphQLApi::get_blocks_data( $post );

		$this->assertEquals( $expected_blocks, $blocks_data );
	}

	public function test_array_data_in_attribute() {
		$html = '
			<!-- wp:table -->
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
			<!-- /wp:table -->
		';

		$expected_blocks = [
			'blocks' => [
				[
					'name'       => 'core/table',
					'attributes' => [
						[
							'name'               => 'hasFixedLayout',
							'value'              => false,
							'isValueJsonEncoded' => false,
						],
						[
							'name'               => 'head',
							'value'              => '[{"cells":[{"content":"Header A","tag":"th"},{"content":"Header B","tag":"th"}]}]',
							'isValueJsonEncoded' => true,
						],
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
					],
					'id'         => '6',
				],
			],
		];

		$post = $this->factory()->post->create_and_get( [
			'post_content' => $html,
		] );

		$blocks_data = GraphQLApi::get_blocks_data( $post );

		$this->assertEquals( $expected_blocks, $blocks_data );
	}

	// flatten_inner_blocks() tests

	public function test_flatten_inner_blocks() {
		$inner_blocks = [
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
				'id'         => '2',
			],
			[
				'name'        => 'core/quote',
				'attributes'  => [
					[
						'name'  => 'value',
						'value' => '',
					],
				],
				'innerBlocks' => [
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
						'id'         => '4',
					],
					[
						'name'        => 'core/quote',
						'attributes'  => [
							[
								'name'  => 'value',
								'value' => '',
							],
						],
						'innerBlocks' => [
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
								'id'         => '6',
							],
						],
						'id'          => '5',
					],
				],
				'id'          => '3',
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
				'parentId'   => '1',
				'id'         => '2',
			],
			[
				'name'       => 'core/quote',
				'attributes' => [
					[
						'name'  => 'value',
						'value' => '',
					],
				],
				'id'         => '3',
				'parentId'   => '1',
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
				'id'         => '4',
				'parentId'   => '3',
			],
			[
				'name'       => 'core/quote',
				'attributes' => [
					[
						'name'  => 'value',
						'value' => '',
					],
				],
				'id'         => '5',
				'parentId'   => '3',
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
				'id'         => '6',
				'parentId'   => '5',
			],
		];

		$flattened_blocks = GraphQLApi::flatten_inner_blocks( $inner_blocks, '1' );

		$this->assertEquals( $expected_blocks, $flattened_blocks );
	}
}
