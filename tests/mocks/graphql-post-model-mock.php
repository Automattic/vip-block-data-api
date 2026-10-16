<?php
/**
 * WPGraphQL post model mock.
 *
 * @package vip-block-data-api
 */

namespace WPCOMVIP\BlockDataApi;

use WP_Post;

/**
 * Minimal stand-in for \WPGraphQL\Model\Post.
 *
 * WPGraphQL models expose their data through magic properties. When the current user is not
 * allowed to view the post in full, the model is "restricted" and only an allow-list of fields
 * is readable. Every other field, including ID, reads as null. This mock reproduces that
 * behavior so the resolvers can be tested without WPGraphQL installed.
 */
class GraphQLPostModelMock {
	/**
	 * Fields readable on a restricted model, mirroring WPGraphQL's allow-list for posts.
	 *
	 * @var string[]
	 */
	private const ALLOWED_RESTRICTED_FIELDS = [ 'databaseId', 'isRestricted', 'hasPassword' ];

	/**
	 * Resolved field values.
	 *
	 * @var array<string, mixed>
	 */
	private array $fields;

	/**
	 * Constructor.
	 *
	 * @param WP_Post $post          The post being modeled.
	 * @param bool    $is_restricted   Whether WPGraphQL would restrict this model for the current user.
	 * @param array   $field_overrides Optional. Field values to override before restriction is applied.
	 */
	public function __construct( WP_Post $post, bool $is_restricted, array $field_overrides = [] ) {
		$this->fields = array_merge( [
			'ID'           => $post->ID,
			'databaseId'   => $post->ID,
			'hasPassword'  => ! empty( $post->post_password ),
			'isRestricted' => $is_restricted,
			'content'      => $post->post_content,
		], $field_overrides );

		if ( $is_restricted ) {
			$this->fields = array_intersect_key( $this->fields, array_flip( self::ALLOWED_RESTRICTED_FIELDS ) );
		}
	}

	/**
	 * Mirror WPGraphQL's magic isset.
	 *
	 * @param string $key Field name.
	 * @return bool
	 */
	public function __isset( $key ) {
		return isset( $this->fields[ $key ] );
	}

	/**
	 * Mirror WPGraphQL's magic getter, which returns null for fields withheld from the model.
	 *
	 * @param string $key Field name.
	 * @return mixed|null
	 */
	public function __get( $key ) {
		if ( ! array_key_exists( $key, $this->fields ) ) {
			return null;
		}

		return $this->fields[ $key ];
	}
}
