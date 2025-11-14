---
sidebar_position: 2
---

# Custom Attributes Guide

Learn how to add custom attributes to blocks using WordPress filters.

## Adding Custom Data to Blocks

Use the `vip_block_data_api__sourced_block_result` filter to add custom attributes:

```php
add_filter( 'vip_block_data_api__sourced_block_result', function( $sourced_block, $block_name, $post_id, $block ) {
    if ( $block_name === 'core/image' ) {
        $image_id = $sourced_block['attributes']['id'] ?? 0;

        if ( $image_id ) {
            // Add custom metadata
            $sourced_block['attributes']['photographer'] = get_post_meta( $image_id, 'photographer', true );
            $sourced_block['attributes']['license'] = get_post_meta( $image_id, 'license', true );
        }
    }

    return $sourced_block;
}, 10, 4 );
```

## Common Use Cases

### Add Post Metadata

```php
add_filter( 'vip_block_data_api__after_parse_blocks', function( $result, $post_id ) {
    $post = get_post( $post_id );

    $result['postMeta'] = [
        'author' => get_the_author_meta( 'display_name', $post->post_author ),
        'date' => get_the_date( 'c', $post ),
        'categories' => wp_get_post_categories( $post_id, [ 'fields' => 'names' ] ),
    ];

    return $result;
}, 10, 2 );
```

### Add Related Content

```php
add_filter( 'vip_block_data_api__sourced_block_result', function( $sourced_block, $block_name, $post_id, $block ) {
    if ( $block_name === 'core/heading' ) {
        // Add unique ID for deep linking
        $content = $sourced_block['attributes']['content'] ?? '';
        $sourced_block['attributes']['anchorId'] = sanitize_title( wp_strip_all_tags( $content ) );
    }

    return $sourced_block;
}, 10, 4 );
```

## Next Steps

- [Filters Reference](../hooks/filters)
- [Block Filtering Guide](./block-filtering)
