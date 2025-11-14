---
sidebar_position: 4
---

# Synced Patterns Guide

Synced patterns (formerly reusable blocks) allow you to create blocks that can be reused across multiple posts.

## How Synced Patterns Work

The VIP Block Data API automatically expands synced patterns, returning their inner blocks directly in the response.

## Example

When a post contains a `core/block` (synced pattern), the API expands it:

```json
{
  "name": "core/block",
  "attributes": {
    "ref": 123
  },
  "innerBlocks": [
    {
      "name": "core/heading",
      "attributes": {
        "content": "Pattern Heading"
      }
    },
    {
      "name": "core/paragraph",
      "attributes": {
        "content": "Pattern content"
      }
    }
  ]
}
```

## Benefits

- No additional API calls needed to fetch pattern content
- Content is expanded automatically
- Supports pattern overrides (WordPress 6.5+)

## Next Steps

- [Response Format](../api/response-format)
- [WordPress Patterns Documentation](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-patterns/)
