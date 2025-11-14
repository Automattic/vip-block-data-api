---
sidebar_position: 4
---

# Troubleshooting

Common issues and solutions when working with the VIP Block Data API.

## API Returns 404

**Symptoms:** `rest_post_invalid_id` error

**Solutions:**
1. Verify the post ID exists
2. Check post is published (or you have permission to view drafts)
3. Ensure post type has `show_in_rest` enabled

```php
// Enable REST for custom post type
register_post_type( 'book', [
    'show_in_rest' => true,
    // ...
] );
```

## Missing Block Attributes

**Symptoms:** Block attributes are empty or missing

**Solutions:**
1. Register block server-side with `block.json`
2. Verify attribute sources are correct
3. Check block HTML matches selectors

```php
// Register block server-side
register_block_type( __DIR__ . '/build/my-block' );
```

## Blocks Not Filtering

**Symptoms:** Excluded blocks still appear in response

**Solutions:**
1. Check filter is returning boolean `false` (not `0` or empty string)
2. Verify filter priority
3. Ensure block name matches exactly (case-sensitive)

```php
// Correct
add_filter( 'vip_block_data_api__allow_block', function( $is_allowed, $block_name ) {
    return $block_name !== 'core/html'; // Returns boolean
}, 10, 2 );
```

## Slow API Responses

**Symptoms:** Parse time exceeds threshold

**Solutions:**
1. Filter blocks to reduce payload
2. Optimize database queries
3. Enable object caching
4. Limit inner blocks

```php
// Limit parse time threshold
define( 'WPCOMVIP__BLOCK_DATA_API__PARSE_TIME_ERROR_MS', 1000 );
```

## GraphQL Not Available

**Symptoms:** `blocksDataV2` field not found

**Solutions:**
1. Install and activate WPGraphQL plugin
2. Verify GraphQL is enabled

```php
// Enable GraphQL integration
add_filter( 'vip_block_data_api__is_graphql_enabled', '__return_true' );
```

## Empty Block Content

**Symptoms:** "No blocks found" warning

**Causes:**
- Post has no block content (classic editor)
- Post content is empty
- All blocks were filtered out

**Solutions:**
1. Convert classic content to blocks
2. Check filtering rules
3. Verify post has content

## Debug Mode

Enable debug output:

```php
define( 'VIP_BLOCK_DATA_API__PARSE_DEBUG', true );
```

This adds debug information to the API response:

```json
{
  "blocks": [...],
  "debug": {
    "parseTimeMs": 45.67,
    "blockCount": 12,
    "rawBlocks": [...]
  }
}
```

## Common Error Codes

| Code | Status | Solution |
|------|--------|----------|
| `rest_post_invalid_id` | 404 | Check post ID exists |
| `rest_forbidden` | 403 | Check user permissions |
| `rest_no_route` | 404 | Verify endpoint URL |
| `vip-block-data-api-no-blocks` | 200 | Post has no block content |

## Getting Help

1. Check error logs: `wp-content/debug.log`
2. Enable WP_DEBUG mode
3. Review [GitHub Issues](https://github.com/Automattic/vip-block-data-api/issues)
4. Contact [VIP Support](https://wpvip.com/support/)

## Next Steps

- [Performance Guide](./performance)
- [Getting Started](../getting-started)
- [Filters Reference](../hooks/filters)
