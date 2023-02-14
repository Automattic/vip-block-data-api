<?php
/**
 * Plugin Name: VIP Gutenberg Content API
 * Plugin URI: https://wpvip.com
 * Description: Plugin to access Gutenberg block content via a JSON API.
 * Author: WordPress VIP
 * Text Domain: vip-gutenberg-content-api
 * Version: 0.0.1
 * Requires at least: 5.6.0
 * Tested up to: 5.7.1
 * Requires PHP: 7.2
 * License: GPL-3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package vip-gutenberg-content-api
 */

namespace WPCOMVIP\GutenbergContentApi;

define( 'WPCOMVIP__CONTENT_API__REST_ROUTE', 'vip-content-api/v1' );

# Composer dependencies
require_once __DIR__ . '/vendor/autoload.php';

# /wp-json/ API
require_once __DIR__ . '/rest/rest-api.php';

# Block parsing
require_once __DIR__ . '/parser/content-parser.php';
require_once __DIR__ . '/parser/block-additions/core-image.php';
