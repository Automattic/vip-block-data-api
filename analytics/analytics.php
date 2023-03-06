<?php

namespace WPCOMVIP\ContentApi;

defined( 'ABSPATH' ) || die();

define( 'WPCOMVIP__CONTENT_API__STAT_NAME__USAGE', 'vip-test-content-api-usage' );

class Analytics {
	private static $analytics_to_send = [];

	public static function init() {
		add_action( 'shutdown', [ __CLASS__, 'send_analytics' ] );
	}

	public static function record_usage() {
		if ( defined( 'FILES_CLIENT_SITE_ID' ) ) {
			self::$analytics_to_send[ WPCOMVIP__CONTENT_API__STAT_NAME__USAGE ] = constant( 'FILES_CLIENT_SITE_ID' );
		}
	}

	public static function send_analytics() {
		if ( empty( self::$analytics_to_send ) || ! self::is_analytics_enabled() ) {
			return;
		}

		if ( function_exists( 'fastcgi_finish_request' ) ) {
			// Flush content to client first to prevent blocking REST request
			fastcgi_finish_request();
		}

		if ( function_exists( '\Automattic\VIP\Stats\send_pixel' ) ) {
			\Automattic\VIP\Stats\send_pixel( self::$analytics_to_send );
		}
	}

	private static function is_analytics_enabled() {
		return defined( 'WPCOM_IS_VIP_ENV' ) && constant( 'WPCOM_IS_VIP_ENV' ) === true
			&& defined( 'WPCOM_SANDBOXED' ) && constant( 'WPCOM_SANDBOXED' ) === false;
	}
}


Analytics::init();
