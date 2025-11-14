---
sidebar_position: 1
---

# Block Filtering Guide

Learn how to filter blocks using query parameters and WordPress hooks.

## Server-Side Filtering (Recommended)

Server-side filtering reduces payload size and improves performance by excluding blocks before they're sent to the client.

### Using Query Parameters

#### Include Only Specific Blocks

```bash
# Only paragraphs and headings
GET /wp-json/vip-block-data-api/v1/posts/123/blocks?include=core/paragraph,core/heading
```

```javascript
const params = new URLSearchParams({
  include: 'core/paragraph,core/heading,core/image'
});

const response = await fetch(
  `https://example.com/wp-json/vip-block-data-api/v1/posts/123/blocks?${params}`
);
```

#### Exclude Specific Blocks

```bash
# Exclude all embeds
GET /wp-json/vip-block-data-api/v1/posts/123/blocks?exclude=core/embed,core/html
```

```javascript
const excludeBlocks = ['core/embed', 'core/html', 'core-embed/youtube'];

const params = new URLSearchParams({
  exclude: excludeBlocks.join(',')
});

const response = await fetch(
  `https://example.com/wp-json/vip-block-data-api/v1/posts/123/blocks?${params}`
);
```

:::warning
You cannot use both `include` and `exclude` parameters simultaneously.
:::

### Using WordPress Filters

#### Basic Block Filtering

```php
// Exclude all embed blocks
add_filter( 'vip_block_data_api__allow_block', function( $is_allowed, $block_name, $block ) {
    if ( $block_name === 'core/embed' || strpos( $block_name, 'core-embed/' ) === 0 ) {
        return false;
    }

    return $is_allowed;
}, 10, 3 );
```

#### Conditional Filtering

```php
// Only allow specific blocks for certain post types
add_filter( 'vip_block_data_api__allow_block', function( $is_allowed, $block_name, $block ) {
    $post_id = get_the_ID();
    $post_type = get_post_type( $post_id );

    // For landing pages, only allow specific blocks
    if ( $post_type === 'landing_page' ) {
        $allowed_blocks = [
            'core/heading',
            'core/paragraph',
            'core/image',
            'core/button',
            'core/columns',
            'core/column',
        ];

        return in_array( $block_name, $allowed_blocks, true );
    }

    return $is_allowed;
}, 10, 3 );
```

#### Filtering by Attributes

```php
// Exclude images without alt text
add_filter( 'vip_block_data_api__allow_block', function( $is_allowed, $block_name, $block ) {
    if ( $block_name === 'core/image' ) {
        $alt = $block['attrs']['alt'] ?? '';

        if ( empty( $alt ) ) {
            error_log( "Image block without alt text excluded" );
            return false;
        }
    }

    return $is_allowed;
}, 10, 3 );
```

#### Content-Based Filtering

```php
// Exclude empty paragraphs
add_filter( 'vip_block_data_api__allow_block', function( $is_allowed, $block_name, $block ) {
    if ( $block_name === 'core/paragraph' ) {
        $content = $block['innerHTML'] ?? '';
        $clean_content = trim( wp_strip_all_tags( $content ) );

        if ( empty( $clean_content ) ) {
            return false;
        }
    }

    return $is_allowed;
}, 10, 3 );
```

## Client-Side Filtering

For dynamic filtering that can't be done server-side:

### Filter by Block Type

```javascript
function filterBlocksByType(blocks, allowedTypes) {
  return blocks
    .filter(block => allowedTypes.includes(block.name))
    .map(block => ({
      ...block,
      innerBlocks: block.innerBlocks.length > 0
        ? filterBlocksByType(block.innerBlocks, allowedTypes)
        : []
    }));
}

// Usage
const blocks = await fetchBlocks(123);
const textBlocks = filterBlocksByType(blocks, [
  'core/paragraph',
  'core/heading',
  'core/list'
]);
```

### Exclude Block Types

```javascript
function excludeBlockTypes(blocks, excludedTypes) {
  return blocks
    .filter(block => !excludedTypes.includes(block.name))
    .map(block => ({
      ...block,
      innerBlocks: block.innerBlocks.length > 0
        ? excludeBlockTypes(block.innerBlocks, excludedTypes)
        : []
    }));
}

// Usage
const blocksWithoutEmbeds = excludeBlockTypes(blocks, [
  'core/embed',
  'core/html'
]);
```

### Advanced Filtering

```javascript
function filterBlocks(blocks, predicate) {
  return blocks
    .filter(predicate)
    .map(block => ({
      ...block,
      innerBlocks: block.innerBlocks.length > 0
        ? filterBlocks(block.innerBlocks, predicate)
        : []
    }));
}

// Filter images with captions
const imagesWithCaptions = filterBlocks(blocks, block => {
  if (block.name !== 'core/image') return true;
  return block.attributes.caption && block.attributes.caption.length > 0;
});

// Filter headings of specific level
const h2Blocks = filterBlocks(blocks, block => {
  if (block.name !== 'core/heading') return true;
  return block.attributes.level === 2;
});
```

## Common Filtering Patterns

### Content Security

```php
// Remove potentially unsafe blocks
add_filter( 'vip_block_data_api__allow_block', function( $is_allowed, $block_name, $block ) {
    $unsafe_blocks = [
        'core/html',
        'core/code',
        'core/shortcode',
    ];

    return ! in_array( $block_name, $unsafe_blocks, true );
}, 10, 3 );
```

### Mobile Optimization

```php
// Exclude complex layout blocks for mobile API
add_filter( 'vip_block_data_api__allow_block', function( $is_allowed, $block_name, $block ) {
    // Check if request is from mobile
    if ( ! wp_is_mobile() ) {
        return $is_allowed;
    }

    $mobile_excluded = [
        'core/columns',
        'core/media-text',
        'core/cover',
    ];

    return ! in_array( $block_name, $mobile_excluded, true );
}, 10, 3 );
```

### Performance Optimization

```php
// Exclude resource-intensive blocks
add_filter( 'vip_block_data_api__allow_block', function( $is_allowed, $block_name, $block ) {
    $heavy_blocks = [
        'core/gallery',
        'core/video',
        'core/audio',
    ];

    // Only exclude if post has many blocks
    $post_content = get_post_field( 'post_content', get_the_ID() );
    $block_count = substr_count( $post_content, '<!-- wp:' );

    if ( $block_count > 50 ) {
        return ! in_array( $block_name, $heavy_blocks, true );
    }

    return $is_allowed;
}, 10, 3 );
```

## Filtering by User Permissions

```php
// Show different blocks based on user role
add_filter( 'vip_block_data_api__allow_block', function( $is_allowed, $block_name, $block ) {
    // Premium content block only for subscribers
    if ( $block_name === 'my-plugin/premium-content' ) {
        return is_user_logged_in() && current_user_can( 'subscriber' );
    }

    return $is_allowed;
}, 10, 3 );
```

## Filtering Inner Blocks

```php
// Limit number of inner blocks
add_filter( 'vip_block_data_api__sourced_block_inner_blocks', function( $inner_blocks, $block_name, $post_id, $block ) {
    // Limit columns to maximum of 3
    if ( $block_name === 'core/columns' ) {
        return array_slice( $inner_blocks, 0, 3 );
    }

    // Limit gallery images to 10
    if ( $block_name === 'core/gallery' ) {
        return array_slice( $inner_blocks, 0, 10 );
    }

    return $inner_blocks;
}, 10, 4 );
```

## React Component with Filtering

```jsx
import { useState } from 'react';

function FilterableBlockRenderer({ blocks }) {
  const [filter, setFilter] = useState('all');

  const blockTypes = {
    all: null,
    text: ['core/paragraph', 'core/heading', 'core/list'],
    media: ['core/image', 'core/video', 'core/audio'],
    layout: ['core/columns', 'core/group', 'core/cover']
  };

  const filteredBlocks = filter === 'all'
    ? blocks
    : filterBlocksByType(blocks, blockTypes[filter]);

  return (
    <div>
      <div className="filter-controls">
        <button onClick={() => setFilter('all')}>All</button>
        <button onClick={() => setFilter('text')}>Text</button>
        <button onClick={() => setFilter('media')}>Media</button>
        <button onClick={() => setFilter('layout')}>Layout</button>
      </div>

      <BlockRenderer blocks={filteredBlocks} />
    </div>
  );
}
```

## Performance Comparison

| Method | Payload Size | Processing Time | Cache Efficiency |
|--------|-------------|-----------------|------------------|
| Server-side (include) | Smallest | Fastest | Best |
| Server-side (exclude) | Small | Fast | Good |
| Client-side filter | Large | Slower | Lower |
| No filtering | Largest | Medium | Medium |

## Best Practices

1. **Use server-side filtering when possible** - Reduces bandwidth and improves performance
2. **Cache filtered responses** - Especially for commonly filtered queries
3. **Be specific with include** - Better than excluding when you know exactly what you need
4. **Combine approaches** - Use server-side for broad filtering, client-side for dynamic needs
5. **Document custom filters** - Make it clear what blocks are filtered and why
6. **Test with large posts** - Ensure filtering doesn't slow down parsing
7. **Consider mobile users** - Filter heavy content for mobile devices

## Troubleshooting

### Blocks Still Appearing

Check that:
- Filter is returning `false` (not `0` or empty string)
- Filter priority is correct (default 10)
- Block name matches exactly (case-sensitive)
- No other filters are overriding your filter

### Inner Blocks Not Filtering

```php
// Make sure to filter recursively
add_filter( 'vip_block_data_api__allow_block', function( $is_allowed, $block_name, $block ) {
    // This filter applies to ALL blocks, including inner blocks
    return $block_name !== 'core/html';
}, 10, 3 );
```

## Next Steps

- [Filters Reference](../hooks/filters)
- [Custom Attributes Guide](./custom-attributes)
- [REST API Examples](../examples/rest-api-examples)
