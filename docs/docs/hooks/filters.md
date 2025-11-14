---
sidebar_position: 1
---

# Filters Reference

The VIP Block Data API provides comprehensive filter hooks for customizing block parsing and output.

## Access Control Filters

### `vip_block_data_api__rest_validate_post_id`

Control which posts are accessible via the REST API.

**Parameters:**
- `$is_valid` *(bool)* - Whether the post ID is valid (default: true if post exists)
- `$post_id` *(int)* - The post ID being requested

**Returns:** *(bool)* - Whether to allow access to this post

**Example: Restrict Access to Specific Post Types**

```php
add_filter( 'vip_block_data_api__rest_validate_post_id', function( $is_valid, $post_id ) {
    $post = get_post( $post_id );

    // Only allow posts and pages
    if ( ! in_array( $post->post_type, [ 'post', 'page' ], true ) ) {
        return false;
    }

    return $is_valid;
}, 10, 2 );
```

**Example: Block Access to Specific Posts**

```php
add_filter( 'vip_block_data_api__rest_validate_post_id', function( $is_valid, $post_id ) {
    // Block access to specific post IDs
    $blocked_posts = [ 123, 456, 789 ];

    if ( in_array( $post_id, $blocked_posts, true ) ) {
        return false;
    }

    return $is_valid;
}, 10, 2 );
```

---

### `vip_block_data_api__rest_permission_callback`

Control who can access the REST API endpoint globally.

**Parameters:**
- `$is_permitted` *(bool)* - Whether access is permitted (default: true)

**Returns:** *(bool)* - Whether to allow access to the API

**Example: Require Authentication**

```php
add_filter( 'vip_block_data_api__rest_permission_callback', function( $is_permitted ) {
    // Require user to be logged in
    return is_user_logged_in();
} );
```

**Example: Require Specific Capability**

```php
add_filter( 'vip_block_data_api__rest_permission_callback', function( $is_permitted ) {
    // Only allow editors and administrators
    return current_user_can( 'edit_others_posts' );
} );
```

**Example: IP Whitelist**

```php
add_filter( 'vip_block_data_api__rest_permission_callback', function( $is_permitted ) {
    $allowed_ips = [ '192.168.1.1', '10.0.0.1' ];
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? '';

    return in_array( $client_ip, $allowed_ips, true );
} );
```

---

## Block Filtering

### `vip_block_data_api__allow_block`

Filter blocks from the output on the server side. More efficient than client-side filtering.

**Parameters:**
- `$is_block_included` *(bool)* - Whether to include the block (default: true)
- `$block_name` *(string)* - The block type (e.g., 'core/paragraph')
- `$block` *(array)* - The block data array

**Returns:** *(bool)* - Whether to include this block

**Example: Exclude All Embed Blocks**

```php
add_filter( 'vip_block_data_api__allow_block', function( $is_block_included, $block_name, $block ) {
    // Exclude all core embed blocks
    if ( strpos( $block_name, 'core-embed/' ) === 0 || $block_name === 'core/embed' ) {
        return false;
    }

    return $is_block_included;
}, 10, 3 );
```

**Example: Only Allow Specific Blocks**

```php
add_filter( 'vip_block_data_api__allow_block', function( $is_block_included, $block_name, $block ) {
    $allowed_blocks = [
        'core/paragraph',
        'core/heading',
        'core/image',
        'core/list',
    ];

    return in_array( $block_name, $allowed_blocks, true );
}, 10, 3 );
```

**Example: Conditional Filtering Based on Attributes**

```php
add_filter( 'vip_block_data_api__allow_block', function( $is_block_included, $block_name, $block ) {
    // Exclude images without alt text
    if ( $block_name === 'core/image' ) {
        $alt = $block['attrs']['alt'] ?? '';
        if ( empty( $alt ) ) {
            return false;
        }
    }

    return $is_block_included;
}, 10, 3 );
```

---

## Block Content Modification

### `vip_block_data_api__sourced_block_result`

Modify or add attributes to a block before it's returned in the API response.

**Parameters:**
- `$sourced_block` *(array)* - The processed block data with attributes
- `$block_name` *(string)* - The block type
- `$post_id` *(int)* - The post ID
- `$block` *(WP_Block)* - The WP_Block object

**Returns:** *(array)* - The modified block data

**Example: Add Custom Metadata to Images**

```php
add_filter( 'vip_block_data_api__sourced_block_result', function( $sourced_block, $block_name, $post_id, $block ) {
    if ( $block_name === 'core/image' ) {
        $image_id = $sourced_block['attributes']['id'] ?? 0;

        if ( $image_id ) {
            // Add custom metadata
            $sourced_block['attributes']['photographer'] = get_post_meta( $image_id, 'photographer', true );
            $sourced_block['attributes']['license'] = get_post_meta( $image_id, 'license', true );

            // Add srcset for responsive images
            $sourced_block['attributes']['srcset'] = wp_get_attachment_image_srcset( $image_id );
        }
    }

    return $sourced_block;
}, 10, 4 );
```

**Example: Add Read Time to Content**

```php
add_filter( 'vip_block_data_api__sourced_block_result', function( $sourced_block, $block_name, $post_id, $block ) {
    // Add word count to paragraph blocks
    if ( $block_name === 'core/paragraph' ) {
        $content = $sourced_block['attributes']['content'] ?? '';
        $word_count = str_word_count( wp_strip_all_tags( $content ) );

        $sourced_block['attributes']['wordCount'] = $word_count;
    }

    return $sourced_block;
}, 10, 4 );
```

**Example: Add Related Posts to Heading Blocks**

```php
add_filter( 'vip_block_data_api__sourced_block_result', function( $sourced_block, $block_name, $post_id, $block ) {
    // Add custom data to headings
    if ( $block_name === 'core/heading' ) {
        $content = $sourced_block['attributes']['content'] ?? '';

        // Generate an anchor ID
        $sourced_block['attributes']['anchorId'] = sanitize_title( wp_strip_all_tags( $content ) );

        // Add heading level class
        $level = $sourced_block['attributes']['level'] ?? 2;
        $sourced_block['attributes']['className'] = "heading-level-{$level}";
    }

    return $sourced_block;
}, 10, 4 );
```

**Example: Transform Embed URLs**

```php
add_filter( 'vip_block_data_api__sourced_block_result', function( $sourced_block, $block_name, $post_id, $block ) {
    if ( $block_name === 'core/embed' ) {
        $url = $sourced_block['attributes']['url'] ?? '';

        // Convert YouTube URLs to embed format
        if ( strpos( $url, 'youtube.com' ) !== false ) {
            $sourced_block['attributes']['embedUrl'] = str_replace( 'watch?v=', 'embed/', $url );
            $sourced_block['attributes']['provider'] = 'youtube';
        }
    }

    return $sourced_block;
}, 10, 4 );
```

---

### `vip_block_data_api__sourced_block_inner_blocks`

Modify a block's inner blocks before they're processed.

**Parameters:**
- `$inner_blocks` *(array)* - Array of inner block data
- `$block_name` *(string)* - The parent block type
- `$post_id` *(int)* - The post ID
- `$block` *(WP_Block)* - The parent WP_Block object

**Returns:** *(array)* - The modified inner blocks array

**Example: Limit Number of Inner Blocks**

```php
add_filter( 'vip_block_data_api__sourced_block_inner_blocks', function( $inner_blocks, $block_name, $post_id, $block ) {
    // Limit columns to maximum of 3
    if ( $block_name === 'core/columns' ) {
        return array_slice( $inner_blocks, 0, 3 );
    }

    return $inner_blocks;
}, 10, 4 );
```

**Example: Sort Inner Blocks**

```php
add_filter( 'vip_block_data_api__sourced_block_inner_blocks', function( $inner_blocks, $block_name, $post_id, $block ) {
    // Sort list items alphabetically
    if ( $block_name === 'core/list' ) {
        usort( $inner_blocks, function( $a, $b ) {
            $content_a = $a['innerHTML'] ?? '';
            $content_b = $b['innerHTML'] ?? '';
            return strcmp( $content_a, $content_b );
        } );
    }

    return $inner_blocks;
}, 10, 4 );
```

---

## Content Transformation

### `vip_block_data_api__before_parse_post_content`

Modify the raw post content before it's parsed into blocks.

**Parameters:**
- `$post_content` *(string)* - The raw post content
- `$post_id` *(int)* - The post ID

**Returns:** *(string)* - The modified post content

:::danger
**Use with extreme caution!** Modifying post content can break block parsing. Only use if you know what you're doing.
:::

**Example: Remove Specific Blocks from Content**

```php
add_filter( 'vip_block_data_api__before_parse_post_content', function( $post_content, $post_id ) {
    // Remove all HTML blocks from content
    $post_content = preg_replace(
        '/<!-- wp:html -->.*?<!-- \/wp:html -->/s',
        '',
        $post_content
    );

    return $post_content;
}, 10, 2 );
```

**Example: Replace Shortcodes**

```php
add_filter( 'vip_block_data_api__before_parse_post_content', function( $post_content, $post_id ) {
    // Replace legacy shortcodes with blocks
    $post_content = str_replace(
        '[gallery]',
        '<!-- wp:gallery --><!-- /wp:gallery -->',
        $post_content
    );

    return $post_content;
}, 10, 2 );
```

---

### `vip_block_data_api__after_parse_blocks`

Modify the final API response after all blocks have been parsed.

**Parameters:**
- `$result` *(array)* - The complete API response with blocks
- `$post_id` *(int)* - The post ID

**Returns:** *(array)* - The modified response

**Example: Add Post Metadata**

```php
add_filter( 'vip_block_data_api__after_parse_blocks', function( $result, $post_id ) {
    $post = get_post( $post_id );

    // Add post metadata to response
    $result['postMeta'] = [
        'author' => get_the_author_meta( 'display_name', $post->post_author ),
        'date' => get_the_date( 'c', $post ),
        'modified' => get_the_modified_date( 'c', $post ),
        'categories' => wp_get_post_categories( $post_id, [ 'fields' => 'names' ] ),
        'tags' => wp_get_post_tags( $post_id, [ 'fields' => 'names' ] ),
    ];

    return $result;
}, 10, 2 );
```

**Example: Add Block Statistics**

```php
add_filter( 'vip_block_data_api__after_parse_blocks', function( $result, $post_id ) {
    $blocks = $result['blocks'] ?? [];

    // Count blocks by type
    $block_counts = [];
    $total_word_count = 0;

    array_walk_recursive( $blocks, function( $value, $key ) use ( &$block_counts, &$total_word_count ) {
        if ( $key === 'name' ) {
            $block_counts[ $value ] = ( $block_counts[ $value ] ?? 0 ) + 1;
        }
        if ( $key === 'content' && is_string( $value ) ) {
            $total_word_count += str_word_count( wp_strip_all_tags( $value ) );
        }
    } );

    $result['statistics'] = [
        'totalBlocks' => count( $blocks ),
        'blockCounts' => $block_counts,
        'totalWordCount' => $total_word_count,
        'estimatedReadTime' => ceil( $total_word_count / 200 ), // Assuming 200 words per minute
    ];

    return $result;
}, 10, 2 );
```

**Example: Add Debug Information**

```php
add_filter( 'vip_block_data_api__after_parse_blocks', function( $result, $post_id ) {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        $result['debug'] = [
            'timestamp' => current_time( 'c' ),
            'memory_usage' => memory_get_usage( true ),
            'query_count' => get_num_queries(),
        ];
    }

    return $result;
}, 10, 2 );
```

---

## GraphQL Integration

### `vip_block_data_api__is_graphql_enabled`

Enable or disable GraphQL integration.

**Parameters:**
- `$is_graphql_enabled` *(bool)* - Whether GraphQL is enabled (default: true if WPGraphQL is active)

**Returns:** *(bool)* - Whether to enable GraphQL integration

**Example: Disable GraphQL**

```php
add_filter( 'vip_block_data_api__is_graphql_enabled', '__return_false' );
```

**Example: Conditional GraphQL**

```php
add_filter( 'vip_block_data_api__is_graphql_enabled', function( $is_enabled ) {
    // Only enable GraphQL for administrators
    return current_user_can( 'manage_options' );
} );
```

---

## Filter Priority Best Practices

When using multiple filters, priority matters:

```php
// Run first (priority 5)
add_filter( 'vip_block_data_api__allow_block', 'exclude_embeds', 5, 3 );

// Run second (priority 10, default)
add_filter( 'vip_block_data_api__allow_block', 'exclude_html', 10, 3 );

// Run last (priority 20)
add_filter( 'vip_block_data_api__allow_block', 'log_excluded_blocks', 20, 3 );
```

## Performance Considerations

- **Cache expensive operations** - Use transients or object cache
- **Limit database queries** - Batch queries when possible
- **Avoid heavy processing** - Defer to client-side when appropriate
- **Test with large posts** - Ensure filters don't slow down parsing

## Next Steps

- [Actions Reference](./actions)
- [Block Filtering Guide](../guides/block-filtering)
- [Custom Attributes Guide](../guides/custom-attributes)
- [Filtering Examples](../examples/filtering-blocks)
