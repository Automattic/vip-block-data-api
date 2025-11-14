---
sidebar_position: 1
---

# Block Attribute Sources

Understanding how the VIP Block Data API extracts block attributes from HTML.

## Attribute Source Types

The plugin supports all WordPress attribute sources:

### 1. `attribute`

Extracts HTML element attributes:

```json
{
  "url": {
    "type": "string",
    "source": "attribute",
    "selector": "img",
    "attribute": "src"
  }
}
```

### 2. `html`

Extracts inner HTML content:

```json
{
  "content": {
    "type": "string",
    "source": "html",
    "selector": "p"
  }
}
```

### 3. `text`

Extracts plain text content:

```json
{
  "citation": {
    "type": "string",
    "source": "text",
    "selector": "cite"
  }
}
```

### 4. `query`

Extracts arrays of data:

```json
{
  "images": {
    "type": "array",
    "source": "query",
    "selector": "img",
    "query": {
      "url": {
        "type": "string",
        "source": "attribute",
        "attribute": "src"
      },
      "alt": {
        "type": "string",
        "source": "attribute",
        "attribute": "alt"
      }
    }
  }
}
```

## Server-Side Registration Required

For custom blocks, server-side registration with `block.json` is required:

```php
register_block_type( __DIR__ . '/build/custom-block' );
```

## Next Steps

- [WordPress Block API](https://developer.wordpress.org/block-editor/reference-guides/block-api/)
- [Getting Started Guide](../getting-started)
