<?php

namespace GraphQLRelay;

// Ported over to mock out this functionality for tests.
class Relay {
	public static $current_id = 0;

	/**
	 * Takes a type name and an ID specific to that type name, and returns a
	 * "global ID" that is unique among all types.
	 *
	 * @param string $type
	 * @param string $id
	 * @return string
	 *
	 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
	 * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	 */
	public static function toGlobalId( $type, $id ) {
		++self::$current_id;
		return strval( self::$current_id );
	}
}
