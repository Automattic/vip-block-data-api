---
sidebar_position: 3
---

# Caching Guide

Implement effective caching strategies for the VIP Block Data API.

## Built-in Caching

### WordPress VIP

On VIP platforms, API responses are automatically cached for 1 minute using the edge cache.

### Cache Invalidation

Caches are automatically invalidated when:
- Post content is updated
- Post is published/unpublished
- Post metadata changes

## Client-Side Caching

### Browser Cache

```javascript
const response = await fetch(url, {
  headers: {
    'Cache-Control': 'max-age=300' // 5 minutes
  }
});
```

### LocalStorage

```javascript
class BlockCache {
  constructor(ttl = 300000) {
    this.ttl = ttl;
  }

  get(postId) {
    const cached = localStorage.getItem(`blocks_${postId}`);
    if (!cached) return null;

    const { blocks, timestamp } = JSON.parse(cached);
    if (Date.now() - timestamp > this.ttl) {
      this.delete(postId);
      return null;
    }

    return blocks;
  }

  set(postId, blocks) {
    localStorage.setItem(`blocks_${postId}`, JSON.stringify({
      blocks,
      timestamp: Date.now()
    }));
  }

  delete(postId) {
    localStorage.removeItem(`blocks_${postId}`);
  }
}
```

## Server-Side Caching

### Object Cache

```php
function get_blocks_cached( $post_id ) {
    $cache_key = "vip_blocks_{$post_id}";
    $cached = wp_cache_get( $cache_key, 'vip_block_data_api' );

    if ( false !== $cached ) {
        return $cached;
    }

    // Fetch from API
    $response = wp_remote_get(
        rest_url( "vip-block-data-api/v1/posts/{$post_id}/blocks" )
    );

    $blocks = json_decode( wp_remote_retrieve_body( $response ), true );

    wp_cache_set( $cache_key, $blocks, 'vip_block_data_api', HOUR_IN_SECONDS );

    return $blocks;
}
```

### Transients

```php
function get_blocks_with_transient( $post_id ) {
    $transient_key = "blocks_{$post_id}";
    $cached = get_transient( $transient_key );

    if ( false !== $cached ) {
        return $cached;
    }

    // Fetch blocks
    $blocks = fetch_blocks_from_api( $post_id );

    set_transient( $transient_key, $blocks, HOUR_IN_SECONDS );

    return $blocks;
}

// Invalidate on post update
add_action( 'save_post', function( $post_id ) {
    delete_transient( "blocks_{$post_id}" );
} );
```

## CDN Caching

For static sites, cache responses at the CDN level:

```javascript
// Next.js with CDN caching
export async function getStaticProps() {
  const blocks = await fetchBlocks(postId);

  return {
    props: { blocks },
    revalidate: 300 // Revalidate every 5 minutes
  };
}
```

## Best Practices

1. **Set appropriate TTLs** - Balance freshness vs. performance
2. **Implement cache busting** - Clear cache on content updates
3. **Monitor cache hit rates** - Optimize TTLs based on metrics
4. **Use cache headers** - Leverage browser and CDN caching
5. **Handle cache failures gracefully** - Fallback to fresh data

## Next Steps

- [Performance Guide](./performance)
- [REST API Reference](../api/rest-api)
