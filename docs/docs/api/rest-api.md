---
sidebar_position: 1
---

# REST API Reference

The VIP Block Data API provides a REST endpoint to fetch Gutenberg block content as structured JSON data.

## Endpoint

<div className="api-endpoint">
  <span className="api-method get">GET</span>
  <code>/wp-json/vip-block-data-api/v1/posts/&#123;post_id&#125;/blocks</code>
</div>

## Authentication

The endpoint respects WordPress's built-in REST API permissions:

- **Published posts**: Publicly accessible (no authentication required)
- **Draft/private posts**: Requires authentication and appropriate capabilities
- **Custom post types**: Must have `show_in_rest` enabled

## Parameters

### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `post_id` | integer | Yes | The ID of the post to retrieve blocks from |

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `include` | string | No | Comma-separated list of block types to include. Acts as an allowlist. |
| `exclude` | string | No | Comma-separated list of block types to exclude. Acts as a blocklist. |

:::warning
You cannot use both `include` and `exclude` parameters simultaneously. The API will return an error if both are provided.
:::

## Response Format

### Success Response

```json
{
  "blocks": [
    {
      "name": "core/heading",
      "attributes": {
        "level": 2,
        "content": "Welcome"
      },
      "innerBlocks": []
    }
  ]
}
```

### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `blocks` | array | Array of block objects with nested structure |
| `blocks[].name` | string | The block type (e.g., `core/paragraph`) |
| `blocks[].attributes` | object | Block attributes as key-value pairs |
| `blocks[].innerBlocks` | array | Nested blocks (recursive structure) |

## Examples

### Basic Request

Fetch all blocks from post ID 123:

```bash
curl https://example.com/wp-json/vip-block-data-api/v1/posts/123/blocks
```

### JavaScript (Fetch API)

```javascript
async function fetchBlocks(postId) {
  const response = await fetch(
    `https://example.com/wp-json/vip-block-data-api/v1/posts/${postId}/blocks`
  );

  if (!response.ok) {
    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
  }

  const data = await response.json();
  return data.blocks;
}

// Usage
fetchBlocks(123)
  .then(blocks => {
    console.log('Fetched blocks:', blocks);
  })
  .catch(error => {
    console.error('Error fetching blocks:', error);
  });
```

### WordPress API Client

Using the `@wordpress/api-fetch` package:

```javascript
import apiFetch from '@wordpress/api-fetch';

apiFetch({
  path: '/vip-block-data-api/v1/posts/123/blocks'
})
  .then(data => {
    console.log('Blocks:', data.blocks);
  })
  .catch(error => {
    console.error('Error:', error);
  });
```

### Include Specific Blocks

Only fetch paragraphs and headings:

```bash
curl "https://example.com/wp-json/vip-block-data-api/v1/posts/123/blocks?include=core/paragraph,core/heading"
```

```javascript
const blocks = await fetchBlocks(123, {
  include: ['core/paragraph', 'core/heading']
});

function fetchBlocks(postId, options = {}) {
  const params = new URLSearchParams();

  if (options.include) {
    params.append('include', options.include.join(','));
  }

  return fetch(
    `https://example.com/wp-json/vip-block-data-api/v1/posts/${postId}/blocks?${params}`
  ).then(res => res.json());
}
```

### Exclude Specific Blocks

Exclude embed blocks from the response:

```bash
curl "https://example.com/wp-json/vip-block-data-api/v1/posts/123/blocks?exclude=core/embed,core/html"
```

```javascript
const blocks = await fetchBlocks(123, {
  exclude: ['core/embed', 'core/html']
});
```

### With Authentication

For draft or private posts:

```javascript
import apiFetch from '@wordpress/api-fetch';

// Set up authentication (nonce is automatically handled in WordPress context)
const blocks = await apiFetch({
  path: '/vip-block-data-api/v1/posts/123/blocks',
  headers: {
    'X-WP-Nonce': wpApiSettings.nonce // From wp_localize_script
  }
});
```

Using basic authentication (not recommended for production):

```bash
curl -u username:password \
  https://example.com/wp-json/vip-block-data-api/v1/posts/123/blocks
```

## Response Examples

### Simple Post

**Post Content:**
```html
<!-- wp:heading {"level":2} -->
<h2>About Us</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>We are a <strong>great</strong> company.</p>
<!-- /wp:paragraph -->
```

**API Response:**
```json
{
  "blocks": [
    {
      "name": "core/heading",
      "attributes": {
        "level": 2,
        "content": "About Us"
      },
      "innerBlocks": []
    },
    {
      "name": "core/paragraph",
      "attributes": {
        "content": "We are a <strong>great</strong> company.",
        "dropCap": false
      },
      "innerBlocks": []
    }
  ]
}
```

### Nested Blocks

**Post Content:**
```html
<!-- wp:columns -->
<div class="wp-block-columns">
  <!-- wp:column -->
  <div class="wp-block-column">
    <!-- wp:paragraph -->
    <p>Left column</p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:column -->

  <!-- wp:column -->
  <div class="wp-block-column">
    <!-- wp:paragraph -->
    <p>Right column</p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:column -->
</div>
<!-- /wp:columns -->
```

**API Response:**
```json
{
  "blocks": [
    {
      "name": "core/columns",
      "attributes": {},
      "innerBlocks": [
        {
          "name": "core/column",
          "attributes": {},
          "innerBlocks": [
            {
              "name": "core/paragraph",
              "attributes": {
                "content": "Left column"
              },
              "innerBlocks": []
            }
          ]
        },
        {
          "name": "core/column",
          "attributes": {},
          "innerBlocks": [
            {
              "name": "core/paragraph",
              "attributes": {
                "content": "Right column"
              },
              "innerBlocks": []
            }
          ]
        }
      ]
    }
  ]
}
```

### Image Block

**API Response:**
```json
{
  "blocks": [
    {
      "name": "core/image",
      "attributes": {
        "id": 456,
        "sizeSlug": "large",
        "url": "https://example.com/wp-content/uploads/2024/01/image.jpg",
        "alt": "Description of image",
        "caption": "Image caption",
        "width": 1024,
        "height": 768
      },
      "innerBlocks": []
    }
  ]
}
```

:::info
The `width` and `height` attributes are automatically added by the plugin based on the image dimensions.
:::

### Custom Block

**API Response:**
```json
{
  "blocks": [
    {
      "name": "my-plugin/hero-banner",
      "attributes": {
        "title": "Welcome to Our Site",
        "subtitle": "We build amazing things",
        "backgroundImage": "https://example.com/bg.jpg",
        "ctaText": "Get Started",
        "ctaUrl": "/signup"
      },
      "innerBlocks": []
    }
  ]
}
```

## Error Handling

### Error Response Format

```json
{
  "code": "rest_post_invalid_id",
  "message": "Invalid post ID.",
  "data": {
    "status": 404
  }
}
```

### Common Error Codes

| Code | Status | Description |
|------|--------|-------------|
| `rest_post_invalid_id` | 404 | Post ID doesn't exist |
| `rest_forbidden` | 403 | You don't have permission to view this post |
| `rest_no_route` | 404 | Invalid endpoint URL |
| `vip-block-data-api-no-blocks` | 200* | Post has no block content (warning, not error) |

*Note: "No blocks" returns 200 OK with a warning in the response.

### Handling Errors in JavaScript

```javascript
async function fetchBlocksSafely(postId) {
  try {
    const response = await fetch(
      `https://example.com/wp-json/vip-block-data-api/v1/posts/${postId}/blocks`
    );

    const data = await response.json();

    if (!response.ok) {
      // Handle HTTP error
      throw new Error(data.message || `HTTP ${response.status}`);
    }

    // Check for warnings
    if (data.warnings && data.warnings.length > 0) {
      console.warn('API warnings:', data.warnings);
    }

    return data.blocks;

  } catch (error) {
    console.error('Failed to fetch blocks:', error);
    return [];
  }
}
```

## Caching

### WordPress VIP

On WordPress VIP, responses are automatically cached for **1 minute** using the platform's caching layer.

Cache key includes:
- Post ID
- Include/exclude parameters
- User permissions

### Custom Caching

Implement your own caching layer:

```javascript
class BlockCache {
  constructor(ttl = 60000) { // 1 minute default
    this.cache = new Map();
    this.ttl = ttl;
  }

  get(key) {
    const item = this.cache.get(key);
    if (!item) return null;

    if (Date.now() > item.expiry) {
      this.cache.delete(key);
      return null;
    }

    return item.data;
  }

  set(key, data) {
    this.cache.set(key, {
      data,
      expiry: Date.now() + this.ttl
    });
  }
}

const cache = new BlockCache();

async function fetchBlocksCached(postId) {
  const cacheKey = `blocks-${postId}`;
  const cached = cache.get(cacheKey);

  if (cached) {
    return cached;
  }

  const blocks = await fetchBlocks(postId);
  cache.set(cacheKey, blocks);

  return blocks;
}
```

## Performance Considerations

### Parse Time Threshold

The plugin tracks parsing time and logs an error if it exceeds a threshold (default: 500ms on VIP).

You can customize this threshold:

```php
define( 'WPCOMVIP__BLOCK_DATA_API__PARSE_TIME_ERROR_MS', 1000 ); // 1 second
```

### Best Practices

1. **Cache responses** - Don't fetch on every request
2. **Use include/exclude** - Reduce payload size by filtering blocks
3. **Paginate content** - For posts with many blocks, consider pagination
4. **Monitor performance** - Track API response times

## Next Steps

- [Response Format Details](./response-format)
- [GraphQL API](./graphql-api)
- [Filtering Blocks](../guides/block-filtering)
- [React Renderer Example](../examples/react-renderer)
