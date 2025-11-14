---
sidebar_position: 4
---

# Filtering Blocks Examples

Comprehensive examples of filtering blocks using various methods.

## Server-Side Filtering

### Exclude Security-Sensitive Blocks

```php
add_filter( 'vip_block_data_api__allow_block', function( $is_allowed, $block_name, $block ) {
    $excluded = [ 'core/html', 'core/code', 'core/shortcode' ];
    return ! in_array( $block_name, $excluded, true );
}, 10, 3 );
```

### Content-Based Filtering

```php
add_filter( 'vip_block_data_api__allow_block', function( $is_allowed, $block_name, $block ) {
    // Exclude images without alt text
    if ( $block_name === 'core/image' ) {
        return ! empty( $block['attrs']['alt'] ?? '' );
    }
    return $is_allowed;
}, 10, 3 );
```

## Client-Side Filtering

### React Component

```jsx
function FilteredContent({ blocks, allowedTypes }) {
  const filteredBlocks = useMemo(() => {
    return filterByType(blocks, allowedTypes);
  }, [blocks, allowedTypes]);

  return <BlockRenderer blocks={filteredBlocks} />;
}

function filterByType(blocks, types) {
  return blocks
    .filter(block => types.includes(block.name))
    .map(block => ({
      ...block,
      innerBlocks: filterByType(block.innerBlocks, types)
    }));
}
```

## Next Steps

- [Block Filtering Guide](../guides/block-filtering)
- [Filters Reference](../hooks/filters)
