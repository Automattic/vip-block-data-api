---
sidebar_position: 2
---

# Getting Started

Get up and running with VIP Block Data API in minutes.

## Installation

### WordPress VIP

The plugin is automatically available on WordPress VIP sites. No installation needed!

To activate:

```bash
# Enable via VIP-CLI
vip plugin activate vip-block-data-api
```

Or add to your `vip-config/vip-config.php`:

```php
wpcom_vip_load_plugin( 'vip-block-data-api' );
```

### Standard WordPress

#### Via Composer (Recommended)

```bash
composer require automattic/vip-block-data-api
```

#### Manual Installation

1. Download the latest release from [GitHub](https://github.com/Automattic/vip-block-data-api/releases)
2. Upload to `/wp-content/plugins/vip-block-data-api/`
3. Activate via WordPress admin or WP-CLI:

```bash
wp plugin activate vip-block-data-api
```

## Verify Installation

### Test REST API

```bash
curl https://your-site.com/wp-json/vip-block-data-api/v1/posts/1/blocks
```

You should receive a JSON response with block data.

### Test GraphQL API (Optional)

First, install [WPGraphQL](https://www.wpgraphql.com/):

```bash
wp plugin install wp-graphql --activate
```

Then query your GraphQL endpoint:

```graphql
query {
  post(id: "1", idType: DATABASE_ID) {
    title
    blocksDataV2 {
      blocks {
        name
        attributes {
          name
          value
        }
      }
    }
  }
}
```

## Basic Configuration

### Enable for Custom Post Types

Make sure your custom post types have `show_in_rest` enabled:

```php
register_post_type( 'book', [
    'public' => true,
    'show_in_rest' => true, // Required!
    'supports' => [ 'editor', 'title' ],
] );
```

### Register Custom Blocks

For custom blocks to work properly, register them server-side with `block.json`:

```json
{
  "name": "my-plugin/custom-block",
  "title": "Custom Block",
  "attributes": {
    "heading": {
      "type": "string",
      "source": "html",
      "selector": "h2"
    },
    "imageUrl": {
      "type": "string",
      "source": "attribute",
      "selector": "img",
      "attribute": "src"
    }
  }
}
```

Then register in PHP:

```php
register_block_type( __DIR__ . '/build/custom-block' );
```

:::info
Blocks that are only registered client-side will show warnings and only include delimiter attributes.
:::

## Your First API Request

### Using cURL

```bash
# Get all blocks from post ID 123
curl https://your-site.com/wp-json/vip-block-data-api/v1/posts/123/blocks

# Include only specific block types
curl "https://your-site.com/wp-json/vip-block-data-api/v1/posts/123/blocks?include=core/paragraph,core/heading"

# Exclude specific block types
curl "https://your-site.com/wp-json/vip-block-data-api/v1/posts/123/blocks?exclude=core/embed"
```

### Using JavaScript (Fetch API)

```javascript
async function getBlocks(postId) {
  const response = await fetch(
    `https://your-site.com/wp-json/vip-block-data-api/v1/posts/${postId}/blocks`
  );

  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }

  const data = await response.json();
  return data.blocks;
}

// Usage
getBlocks(123)
  .then(blocks => console.log('Blocks:', blocks))
  .catch(error => console.error('Error:', error));
```

### Using WordPress REST API Client

```javascript
import apiFetch from '@wordpress/api-fetch';

apiFetch({
  path: '/vip-block-data-api/v1/posts/123/blocks'
})
  .then(data => console.log('Blocks:', data.blocks))
  .catch(error => console.error('Error:', error));
```

## Next Steps

Now that you have the plugin installed and working:

1. **Explore the APIs** - Learn about [REST API](./api/rest-api) and [GraphQL API](./api/graphql-api)
2. **Customize behavior** - Check out available [filters](./hooks/filters) and [actions](./hooks/actions)
3. **Build a renderer** - See the [React renderer example](./examples/react-renderer)
4. **Filter blocks** - Learn [block filtering techniques](./guides/block-filtering)

## Troubleshooting

### "Post not found" Error

Ensure the post:
- Exists and is published (or you have permission to view it)
- Has a post type with `show_in_rest` enabled
- Is accessible via the WordPress REST API

Test REST API access:
```bash
curl https://your-site.com/wp-json/wp/v2/posts/123
```

### "No blocks found" Warning

This occurs when:
- Post has no block content (only classic editor content)
- Post content is empty
- All blocks are filtered out

### Empty Attributes

If block attributes are missing:
- Verify the block is registered server-side
- Check that `block.json` includes attribute sources
- Ensure the block's HTML matches the selectors

See [Troubleshooting Guide](./advanced/troubleshooting) for more help.

## Configuration Options

### Debug Mode

Enable debug output in responses:

```php
define( 'VIP_BLOCK_DATA_API__PARSE_DEBUG', true );
```

This adds raw block data and timing information to API responses.

### Performance Threshold

Set a custom error threshold for slow parsing:

```php
define( 'WPCOMVIP__BLOCK_DATA_API__PARSE_TIME_ERROR_MS', 1000 ); // 1 second
```

### Disable GraphQL Integration

```php
add_filter( 'vip_block_data_api__is_graphql_enabled', '__return_false' );
```

## Development Setup

For plugin development:

```bash
# Clone repository
git clone https://github.com/Automattic/vip-block-data-api.git
cd vip-block-data-api

# Install dependencies
composer install
npm install

# Run tests
composer run test

# Run linting
composer run lint
npm run lint
```

## Resources

- [REST API Documentation](./api/rest-api)
- [GraphQL API Documentation](./api/graphql-api)
- [Code Examples](./examples/rest-api-examples)
- [WordPress Hooks Reference](./hooks/filters)
