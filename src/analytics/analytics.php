<?php

namespace WPCOMVIP\BlockDataApi;

defined( 'ABSPATH' ) || die();

define( 'WPCOMVIP__BLOCK_DATA_API__STAT_NAME__USAGE', 'vip-block-data-api-usage' );
define( 'WPCOMVIP__BLOCK_DATA_API__STAT_NAME__ERROR', 'vip-block-data-api-error' );

class Analytics {
	private static $analytics_to_send = [];

	public static function init() {
		add_action( 'shutdown', [ __CLASS__, 'send_analytics' ] );
	}

	public static function record_usage() {
		if ( defined( 'FILES_CLIENT_SITE_ID' ) ) {
			self::$analytics_to_send[ WPCOMVIP__BLOCK_DATA_API__STAT_NAME__USAGE ] = constant( 'FILES_CLIENT_SITE_ID' );
		}
	}

	public static function record_error( $error_message ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		trigger_error( sprintf( 'vip-block-data-api (%s): %s', WPCOMVIP__BLOCK_DATA_API__PLUGIN_VERSION, $error_message ), E_USER_WARNING );

		if ( defined( 'FILES_CLIENT_SITE_ID' ) ) {
			self::$analytics_to_send[ WPCOMVIP__BLOCK_DATA_API__STAT_NAME__ERROR ] = constant( 'FILES_CLIENT_SITE_ID' );
		}
	}

	public static function send_analytics() {
		if ( empty( self::$analytics_to_send ) || ! self::is_analytics_enabled() ) {
			return;
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
