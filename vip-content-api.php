<?php
/**
 * Plugin Name: VIP Content API
 * Plugin URI: https://wpvip.com
 * Description: Access Gutenberg block content in JSON via the REST API.
 * Author: WordPress VIP
 * Text Domain: vip-content-api
 * Version: 0.1.0
 * Requires at least: 5.6.0
 * Tested up to: 6.1.0
 * Requires PHP: 7.4
 * License: GPL-3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package vip-content-api
 */

namespace WPCOMVIP\ContentApi;

define( 'WPCOMVIP__CONTENT_API__PLUGIN_VERSION', '0.1.0' );
define( 'WPCOMVIP__CONTENT_API__REST_ROUTE', 'vip-content-api/v1' );

// Composer dependencies
require_once __DIR__ . '/vendor/autoload.php';

// /wp-json/ API
require_once __DIR__ . '/src/rest/rest-api.php';

// Block parsing
require_once __DIR__ . '/src/parser/content-parser.php';
require_once __DIR__ . '/src/parser/block-additions/core-image.php';

// Analytics
require_once __DIR__ . '/src/analytics/analytics.php';
