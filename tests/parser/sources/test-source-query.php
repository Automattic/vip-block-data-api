<?php
/**
 * Class SourceQueryTest
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

/**
 * Test sourced attributes with the 'query' type:
 * https://developer.wordpress.org/block-editor/reference-guides/block-api/block-attributes/#query-source
 */
class SourceQueryTest extends RegistryTestCase {
	public function test_parse_query_source() {
		$this->register_block_with_attributes( 'test/query-images', [
			'images' => [
				'type'     => 'array',
				'source'   => 'query',
				'selector' => 'img',
				'query'    => [
					'url' => [
						'type'      => 'string',
						'source'    => 'attribute',
						'attribute' => 'src',
					],
					'alt' => [
						'type'      => 'string',
						'source'    => 'attribute',
						'attribute' => 'alt',
					],
				],
			],
		] );

		$html = '
			<!-- wp:test/query-images -->
			<div>
				<img src="https://wpvip.com/1-large.jpg" alt="large image" />
				<img src="https://wpvip.com/1-small.jpg" alt="small image" />
			</div>
			<!-- /wp:test/query-images -->';

		$expected_blocks = [
			[
				'name'       => 'test/query-images',
				'attributes' => [
					'images' => [
						[
							'url' => 'https://wpvip.com/1-large.jpg',
							'alt' => 'large image',
						],
						[
							'url' => 'https://wpvip.com/1-small.jpg',
							'alt' => 'small image',
						],
					],
				],
			],
		];

		$content_parser = new ContentParser( $this->get_block_registry() );
		$blocks         = $content_parser->parse( $html );

		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}

	public function test_parse_query_source__with_nested_query() {
		$this->register_block_with_attributes( 'test/table', [
			'body' => [
				'type'     => 'array',
				'source'   => 'query',
				'selector' => 'tbody tr',
				'query'    => [
					'cells' => [
						'type'     => 'array',
						'source'   => 'query',
						'selector' => 'td,th',
						'query'    => [
							'content' => [
								'type'   => 'string',
								'source' => 'html',
							],
							'align'   => [
								'type'      => 'string',
								'source'    => 'attribute',
								'attribute' => 'data-align',
							],
						],
					],
				],
			],
		] );

		$html = '
			<!-- wp:test/table -->
			<figure class="wp-block-table">
				<table>
					<tbody>
						<tr>
							<td class="has-text-align-right" data-align="right">Col 1, Row 1</td>
							<td class="has-text-align-left" data-align="left">Col 2, Row 1</td>
						</tr>
						<tr>
							<td class="has-text-align-right" data-align="right">Col 1, Row 2</td>
							<td class="has-text-align-left" data-align="left">Col 2, Row 2</td>
						</tr>
					</tbody>
				</table>
			</figure>
			<!-- /wp:test/table -->';

		$expected_blocks = [
			[
				'name'       => 'test/table',
				'attributes' => [
					'body' => [
						[
							'cells' => [
								[
									'content' => 'Col 1, Row 1',
									'align'   => 'right',
								],
								[
									'content' => 'Col 2, Row 1',
									'align'   => 'left',
								],
							],
						],
						[
							'cells' => [
								[
									'content' => 'Col 1, Row 2',
									'align'   => 'right',
								],
								[
									'content' => 'Col 2, Row 2',
									'align'   => 'left',
								],
							],
						],
					],
				],
			],
		];

		$content_parser = new ContentParser( $this->get_block_registry() );
		$blocks         = $content_parser->parse( $html );

		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}

	public function test_parse_query_source__with_default_value() {
		$this->register_block_with_attributes( 'test/query-images', [
			'images' => [
				'type'     => 'array',
				'source'   => 'query',
				'selector' => 'img',
				'query'    => [
					'alt' => [
						'type'      => 'string',
						'source'    => 'attribute',
						'attribute' => 'alt',
						'default'   => 'Default alt text',
					],
				],
			],
		] );

		$html = '
			<!-- wp:test/query-images -->
			<div>
				<img src="https://wpvip.com/1-large.jpg" />
				<img src="https://wpvip.com/1-small.jpg" />
			</div>
			<!-- /wp:test/query-images -->';

		$expected_blocks = [
			[
				'name'       => 'test/query-images',
				'attributes' => [
					'images' => [
						[
							'alt' => 'Default alt text',
						],
						[
							'alt' => 'Default alt text',
						],
					],
				],
			],
		];

		$content_parser = new ContentParser( $this->get_block_registry() );
		$blocks         = $content_parser->parse( $html );

		$this->assertArrayHasKey( 'blocks', $blocks, sprintf( 'Unexpected parser output: %s', wp_json_encode( $blocks ) ) );
		$this->assertArraySubset( $expected_blocks, $blocks['blocks'], true );
	}
}
