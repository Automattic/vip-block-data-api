---
sidebar_position: 1
slug: /
---

# Introduction

**VIP Block Data API** is a WordPress plugin that converts Gutenberg block editor content into structured JSON data. It's designed for headless WordPress applications, enabling developers to consume block content through standardized REST and GraphQL APIs instead of parsing HTML.

## Key Features

- 🚀 **REST API** - Simple REST endpoint for fetching block data as nested JSON
- 📊 **GraphQL API** - Seamless integration with WPGraphQL
- 🎯 **Block Filtering** - Include/exclude specific block types
- 🔌 **Extensible** - Comprehensive hooks and filters for customization
- ⚡ **Performance** - Built-in caching and optimizations for VIP sites
- 🔒 **Secure** - Respects WordPress permissions and access controls
- 🎨 **Block Bindings** - Full support for WordPress 6.5+ block bindings
- 🔄 **Synced Patterns** - Automatically expands reusable blocks

## Why Use This Plugin?

Traditional WordPress themes render blocks as HTML, which is difficult to parse and manipulate in decoupled applications. This plugin:

1. **Provides structured data** - Get clean JSON instead of HTML soup
2. **Preserves block hierarchy** - Maintains parent-child relationships
3. **Extracts attributes correctly** - Uses server-side block registration for accurate parsing
4. **Handles complex blocks** - Supports all WordPress attribute sources
5. **Enables headless WordPress** - Perfect for React, Vue, or mobile apps

## Use Cases

- **Headless WordPress sites** - Build frontends with React, Next.js, Vue, etc.
- **Mobile applications** - Native iOS/Android apps consuming WordPress content
- **Content as a Service** - Expose WordPress as a content API
- **Multi-platform publishing** - One content source, many destinations
- **Custom block renderers** - Build your own block rendering logic

## Quick Example

### REST API Request
```bash
GET /wp-json/vip-block-data-api/v1/posts/123/blocks
```

### Response
```json
{
  "blocks": [
    {
      "name": "core/heading",
      "attributes": {
        "level": 2,
        "content": "Welcome to My Site"
      },
      "innerBlocks": []
    },
    {
      "name": "core/paragraph",
      "attributes": {
        "content": "This is a paragraph with <strong>bold text</strong>."
      },
      "innerBlocks": []
    }
  ]
}
```

## Requirements

- **WordPress:** 6.0 or higher
- **PHP:** 8.1 or higher
- **WPGraphQL:** Optional, required only for GraphQL functionality

## What's Next?

- [Get Started](./getting-started) - Installation and basic setup
- [REST API Reference](./api/rest-api) - Complete REST API documentation
- [GraphQL API Reference](./api/graphql-api) - Complete GraphQL documentation
- [WordPress Hooks](./hooks/filters) - Customize with filters and actions
- [Examples](./examples/rest-api-examples) - Real-world code examples

## Support

- 🐛 [Report Issues](https://github.com/Automattic/vip-block-data-api/issues)
- 📖 [View Source Code](https://github.com/Automattic/vip-block-data-api)
- 💬 [WordPress VIP Support](https://wpvip.com/support/)

## License

This plugin is licensed under [GPL-3.0](https://github.com/Automattic/vip-block-data-api/blob/trunk/LICENSE).
