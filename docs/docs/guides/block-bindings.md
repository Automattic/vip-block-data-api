---
sidebar_position: 3
---

# Block Bindings Guide

WordPress 6.5+ introduced block bindings, which allow block attributes to dynamically source values from post meta, custom fields, or other sources.

## What are Block Bindings?

Block bindings connect block attributes to dynamic data sources. The VIP Block Data API automatically resolves these bindings server-side.

## Example

A paragraph block with bound content:

```html
<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"core/post-meta","args":{"key":"custom_description"}}}}} -->
<p>Fallback content</p>
<!-- /wp:paragraph -->
```

The API will automatically fetch the value from `custom_description` post meta and include it in the response.

## Response Format

```json
{
  "name": "core/paragraph",
  "attributes": {
    "content": "Value from custom_description meta field"
  }
}
```

## Requirements

- WordPress 6.5 or higher
- Block must be registered server-side
- Binding source must be registered

## Next Steps

- [WordPress Block Bindings Documentation](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#bindings)
- [Custom Attributes Guide](./custom-attributes)
