---
sidebar_position: 2
---

# Actions Reference

The VIP Block Data API provides action hooks that allow you to execute custom code at specific points during block parsing and rendering.

## Block Rendering Actions

### `vip_block_data_api__before_block_render`

Fires before blocks are rendered and parsed.

**Parameters:**
- `$blocks` *(array)* - Array of parsed block data from WordPress
- `$post_id` *(int)* - The post ID being processed

**Use Cases:**
- Initialize custom services
- Set up temporary state
- Log API requests
- Clear caches

**Example: Log API Access**

```php
add_action( 'vip_block_data_api__before_block_render', function( $blocks, $post_id ) {
    if ( function_exists( 'wpcom_vip_log' ) ) {
        wpcom_vip_log( 'block_api_access', [
            'post_id' => $post_id,
            'block_count' => count( $blocks ),
            'timestamp' => time(),
        ] );
    }
}, 10, 2 );
```

**Example: Track API Usage**

```php
add_action( 'vip_block_data_api__before_block_render', function( $blocks, $post_id ) {
    // Increment API usage counter
    $count = get_transient( 'vip_block_api_usage_count' ) ?: 0;
    set_transient( 'vip_block_api_usage_count', $count + 1, HOUR_IN_SECONDS );

    // Track per-post access
    $post_key = "vip_block_api_post_{$post_id}_access";
    $post_count = get_transient( $post_key ) ?: 0;
    set_transient( $post_key, $post_count + 1, DAY_IN_SECONDS );
}, 10, 2 );
```

**Example: Warm Up Caches**

```php
add_action( 'vip_block_data_api__before_block_render', function( $blocks, $post_id ) {
    // Pre-fetch post metadata that might be needed
    $post = get_post( $post_id );

    // Warm up author cache
    if ( $post && $post->post_author ) {
        get_userdata( $post->post_author );
    }

    // Warm up taxonomy caches
    wp_get_post_terms( $post_id, 'category' );
    wp_get_post_terms( $post_id, 'post_tag' );

    // Collect image IDs for bulk fetching
    $image_ids = [];
    foreach ( $blocks as $block ) {
        if ( $block['blockName'] === 'core/image' && isset( $block['attrs']['id'] ) ) {
            $image_ids[] = $block['attrs']['id'];
        }
    }

    // Warm up image metadata cache
    if ( ! empty( $image_ids ) ) {
        _prime_post_caches( $image_ids, false, true );
    }
}, 10, 2 );
```

**Example: Set Up Custom Context**

```php
class BlockApiContext {
    private static $instance = null;
    public $start_time;
    public $post_id;

    public static function init( $post_id ) {
        self::$instance = new self();
        self::$instance->start_time = microtime( true );
        self::$instance->post_id = $post_id;
    }

    public static function get() {
        return self::$instance;
    }
}

add_action( 'vip_block_data_api__before_block_render', function( $blocks, $post_id ) {
    BlockApiContext::init( $post_id );
}, 10, 2 );
```

---

### `vip_block_data_api__after_block_render`

Fires after all blocks have been rendered and processed.

**Parameters:**
- `$sourced_blocks` *(array)* - Array of processed block data with attributes
- `$post_id` *(int)* - The post ID that was processed

**Use Cases:**
- Clean up temporary resources
- Log performance metrics
- Clear temporary caches
- Send analytics

**Example: Log Performance Metrics**

```php
add_action( 'vip_block_data_api__after_block_render', function( $sourced_blocks, $post_id ) {
    $context = BlockApiContext::get();

    if ( $context ) {
        $elapsed_time = ( microtime( true ) - $context->start_time ) * 1000;

        // Log slow queries
        if ( $elapsed_time > 500 ) {
            if ( function_exists( 'wpcom_vip_log' ) ) {
                wpcom_vip_log( 'slow_block_parse', [
                    'post_id' => $post_id,
                    'elapsed_ms' => $elapsed_time,
                    'block_count' => count( $sourced_blocks ),
                ] );
            }
        }
    }
}, 10, 2 );
```

**Example: Send Analytics Events**

```php
add_action( 'vip_block_data_api__after_block_render', function( $sourced_blocks, $post_id ) {
    // Count blocks by type
    $block_types = array_count_values(
        array_column( $sourced_blocks, 'name' )
    );

    // Send to analytics service
    if ( function_exists( 'send_analytics_event' ) ) {
        send_analytics_event( 'block_api_request', [
            'post_id' => $post_id,
            'total_blocks' => count( $sourced_blocks ),
            'block_types' => $block_types,
        ] );
    }
}, 10, 2 );
```

**Example: Clear Temporary Caches**

```php
add_action( 'vip_block_data_api__after_block_render', function( $sourced_blocks, $post_id ) {
    // Clean up any temporary caches created during rendering
    delete_transient( "temp_block_data_{$post_id}" );

    // Clean up global state
    global $vip_block_api_temp_data;
    $vip_block_api_temp_data = null;
}, 10, 2 );
```

**Example: Update Usage Statistics**

```php
add_action( 'vip_block_data_api__after_block_render', function( $sourced_blocks, $post_id ) {
    $stats_key = 'vip_block_api_stats';
    $stats = get_option( $stats_key, [
        'total_requests' => 0,
        'total_blocks_served' => 0,
        'last_request' => 0,
    ] );

    $stats['total_requests']++;
    $stats['total_blocks_served'] += count( $sourced_blocks );
    $stats['last_request'] = time();

    // Update daily
    if ( time() - $stats['last_request'] > DAY_IN_SECONDS ) {
        update_option( $stats_key, $stats );
    }
}, 10, 2 );
```

---

## Complete Example: Request Lifecycle Tracking

This example shows how to use both actions together to track the complete lifecycle of an API request.

```php
class VIP_Block_API_Monitor {
    private static $request_data = [];

    /**
     * Initialize monitoring on request start
     */
    public static function start_monitoring( $blocks, $post_id ) {
        $request_id = uniqid( 'req_', true );

        self::$request_data[ $request_id ] = [
            'post_id' => $post_id,
            'start_time' => microtime( true ),
            'start_memory' => memory_get_usage( true ),
            'block_count' => count( $blocks ),
            'user_id' => get_current_user_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ];

        // Store request ID for later use
        define( 'VIP_BLOCK_API_REQUEST_ID', $request_id );
    }

    /**
     * Finalize monitoring on request end
     */
    public static function end_monitoring( $sourced_blocks, $post_id ) {
        if ( ! defined( 'VIP_BLOCK_API_REQUEST_ID' ) ) {
            return;
        }

        $request_id = VIP_BLOCK_API_REQUEST_ID;
        $start_data = self::$request_data[ $request_id ] ?? null;

        if ( ! $start_data ) {
            return;
        }

        // Calculate metrics
        $end_time = microtime( true );
        $end_memory = memory_get_usage( true );

        $metrics = [
            'request_id' => $request_id,
            'post_id' => $post_id,
            'duration_ms' => ( $end_time - $start_data['start_time'] ) * 1000,
            'memory_used_mb' => ( $end_memory - $start_data['start_memory'] ) / 1024 / 1024,
            'blocks_in' => $start_data['block_count'],
            'blocks_out' => count( $sourced_blocks ),
            'user_id' => $start_data['user_id'],
            'timestamp' => gmdate( 'Y-m-d H:i:s' ),
        ];

        // Log metrics
        self::log_metrics( $metrics );

        // Send to monitoring service
        self::send_to_monitoring( $metrics );

        // Clean up
        unset( self::$request_data[ $request_id ] );
    }

    /**
     * Log metrics to file or database
     */
    private static function log_metrics( $metrics ) {
        if ( function_exists( 'wpcom_vip_log' ) ) {
            wpcom_vip_log( 'block_api_metrics', $metrics );
        }

        // Or store in database for analysis
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'block_api_metrics',
            $metrics,
            [ '%s', '%d', '%f', '%f', '%d', '%d', '%d', '%s' ]
        );
    }

    /**
     * Send metrics to external monitoring service
     */
    private static function send_to_monitoring( $metrics ) {
        // Example: Send to New Relic
        if ( extension_loaded( 'newrelic' ) ) {
            newrelic_custom_metric( 'Custom/BlockAPI/Duration', $metrics['duration_ms'] );
            newrelic_custom_metric( 'Custom/BlockAPI/Memory', $metrics['memory_used_mb'] );
            newrelic_custom_metric( 'Custom/BlockAPI/Blocks', $metrics['blocks_out'] );
        }

        // Example: Send to StatsD
        if ( function_exists( 'stats_increment' ) ) {
            stats_timing( 'block_api.duration', $metrics['duration_ms'] );
            stats_gauge( 'block_api.memory', $metrics['memory_used_mb'] );
            stats_increment( 'block_api.requests' );
        }
    }
}

// Register the monitoring hooks
add_action( 'vip_block_data_api__before_block_render', [ 'VIP_Block_API_Monitor', 'start_monitoring' ], 10, 2 );
add_action( 'vip_block_data_api__after_block_render', [ 'VIP_Block_API_Monitor', 'end_monitoring' ], 10, 2 );
```

---

## Complete Example: Cache Warming System

Automatically warm caches for related content when blocks are accessed.

```php
class VIP_Block_API_Cache_Warmer {
    /**
     * Warm up caches before block rendering
     */
    public static function warm_caches( $blocks, $post_id ) {
        // Collect all entity IDs that will be needed
        $image_ids = [];
        $user_ids = [];
        $term_ids = [];

        foreach ( $blocks as $block ) {
            switch ( $block['blockName'] ) {
                case 'core/image':
                    if ( isset( $block['attrs']['id'] ) ) {
                        $image_ids[] = $block['attrs']['id'];
                    }
                    break;

                case 'core/embed':
                    // Fetch oEmbed data proactively
                    if ( isset( $block['attrs']['url'] ) ) {
                        wp_oembed_get( $block['attrs']['url'] );
                    }
                    break;
            }
        }

        // Bulk fetch image metadata
        if ( ! empty( $image_ids ) ) {
            _prime_post_caches( $image_ids, false, true );

            // Also fetch image meta
            foreach ( $image_ids as $image_id ) {
                wp_get_attachment_metadata( $image_id );
            }
        }

        // Fetch post metadata
        $post = get_post( $post_id );
        if ( $post ) {
            // Warm author cache
            get_userdata( $post->post_author );

            // Warm taxonomy caches
            $taxonomies = get_object_taxonomies( $post->post_type );
            foreach ( $taxonomies as $taxonomy ) {
                wp_get_object_terms( $post_id, $taxonomy );
            }
        }
    }
}

add_action( 'vip_block_data_api__before_block_render', [ 'VIP_Block_API_Cache_Warmer', 'warm_caches' ], 10, 2 );
```

---

## Complete Example: Error Tracking and Alerting

Monitor errors and send alerts for failures.

```php
class VIP_Block_API_Error_Tracker {
    private static $error_threshold = 10; // errors per hour
    private static $alert_sent = false;

    /**
     * Track successful requests
     */
    public static function track_success( $sourced_blocks, $post_id ) {
        // Clear error flag for this post
        delete_transient( "block_api_error_{$post_id}" );
    }

    /**
     * Track and alert on errors
     */
    public static function track_error( $post_id, $error_message ) {
        // Increment error counter
        $error_key = 'block_api_errors_' . gmdate( 'YmdH' );
        $error_count = (int) get_transient( $error_key );
        $error_count++;
        set_transient( $error_key, $error_count, HOUR_IN_SECONDS );

        // Log the error
        if ( function_exists( 'wpcom_vip_log' ) ) {
            wpcom_vip_log( 'block_api_error', [
                'post_id' => $post_id,
                'error' => $error_message,
                'count' => $error_count,
            ] );
        }

        // Send alert if threshold exceeded
        if ( $error_count >= self::$error_threshold && ! self::$alert_sent ) {
            self::send_alert( $error_count );
            self::$alert_sent = true;
        }
    }

    /**
     * Send alert email
     */
    private static function send_alert( $error_count ) {
        $admin_email = get_option( 'admin_email' );
        $subject = 'Block API Error Threshold Exceeded';
        $message = sprintf(
            'The Block Data API has encountered %d errors in the past hour. Please investigate.',
            $error_count
        );

        wp_mail( $admin_email, $subject, $message );
    }
}

add_action( 'vip_block_data_api__after_block_render', [ 'VIP_Block_API_Error_Tracker', 'track_success' ], 10, 2 );
```

---

## Action Priority Best Practices

Like filters, action priority determines execution order:

```php
// Run first (high priority tasks like logging)
add_action( 'vip_block_data_api__before_block_render', 'start_logging', 5, 2 );

// Run middle (default priority)
add_action( 'vip_block_data_api__before_block_render', 'warm_caches', 10, 2 );

// Run last (low priority tasks like analytics)
add_action( 'vip_block_data_api__after_block_render', 'send_analytics', 20, 2 );
```

## Performance Considerations

- **Keep actions lightweight** - Heavy processing slows down API responses
- **Use async processing** - Queue heavy tasks with WP Cron or background jobs
- **Avoid database writes** - Use transients or batch updates
- **Monitor execution time** - Track how much time actions add to requests

## Next Steps

- [Filters Reference](./filters)
- [Performance Guide](../advanced/performance)
- [Troubleshooting](../advanced/troubleshooting)
