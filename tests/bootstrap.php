<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package vip-block-data-api
 */


// Require composer dependencies.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
if ( false !== $_phpunit_polyfills_path ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path );
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( __DIR__ ) . '/vip-block-data-api.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

define( 'WPCOMVIP__BLOCK_DATA_API__TEST_DATA', __DIR__ . '/data' );

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";

// Add custom test classes
require_once __DIR__ . '/registry-test-case.php';

require_once __DIR__ . '/mocks/graphql-relay-mock.php';
