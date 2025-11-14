---
sidebar_position: 2
---

# Performance Guide

Optimize your use of the VIP Block Data API for best performance.

## Caching Strategies

### WordPress VIP

Responses are automatically cached for 1 minute on VIP platforms.

### Custom Caching

```php
add_filter( 'vip_block_data_api__after_parse_blocks', function( $result, $post_id ) {
    $cache_key = "blocks_{$post_id}";
    wp_cache_set( $cache_key, $result, 'vip_block_data_api', HOUR_IN_SECONDS );
    return $result;
}, 10, 2 );
```

## Query Optimization

### Use Block Filtering

Only fetch the blocks you need:

```bash
# Include only text blocks
?include=core/paragraph,core/heading,core/list

# Exclude heavy media blocks
?exclude=core/video,core/audio,core/gallery
```

### Limit Inner Blocks

```php
add_filter( 'vip_block_data_api__sourced_block_inner_blocks', function( $inner_blocks, $block_name ) {
    // Limit gallery to 10 images
    if ( $block_name === 'core/gallery' ) {
        return array_slice( $inner_blocks, 0, 10 );
    }
    return $inner_blocks;
}, 10, 2 );
```

## Monitoring

### Parse Time Threshold

```php
define( 'WPCOMVIP__BLOCK_DATA_API__PARSE_TIME_ERROR_MS', 1000 );
```

### Track Slow Requests

```php
add_action( 'vip_block_data_api__after_block_render', function( $sourced_blocks, $post_id ) {
    // Log if parsing took too long
    if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
        error_log( "Block parsing for post {$post_id}: {$wpdb->num_queries} queries" );
    }
}, 10, 2 );
```

## Best Practices

1. **Cache aggressively** - Block content doesn't change frequently
2. **Filter server-side** - Reduce payload size
3. **Monitor response times** - Set up alerts for slow requests
4. **Optimize images** - Use appropriate image sizes
5. **Limit nesting depth** - Deep block hierarchies slow parsing

## Next Steps

- [Caching Guide](./caching)
- [Troubleshooting](./troubleshooting)
