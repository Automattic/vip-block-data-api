<?php

namespace GraphQLRelay;

// Ported over to mock out this functionality for tests.
class Relay {

	/**
	 * Takes a type name and an ID specific to that type name, and returns a
	 * "global ID" that is unique among all types.
	 *
	 * @param string $type
	 * @param string $id
	 * @return string
	 * 
	 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
	 */
	public static function toGlobalId( $type, $id ) {
		return "{$id}";
	}
}
