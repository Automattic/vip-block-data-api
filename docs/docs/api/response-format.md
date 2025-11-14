---
sidebar_position: 3
---

# Response Format

Understanding the structure of API responses is essential for working with block data effectively.

## REST API Response Structure

```typescript
interface BlockDataResponse {
  blocks: Block[];
  warnings?: string[];
  debug?: DebugInfo;
}

interface Block {
  name: string;
  attributes: Record<string, any>;
  innerBlocks: Block[];
}

interface DebugInfo {
  rawBlocks?: any[];
  parseTime?: number;
  [key: string]: any;
}
```

## Block Object

Each block in the response contains:

| Field | Type | Description |
|-------|------|-------------|
| `name` | string | The block type identifier (e.g., `core/paragraph`) |
| `attributes` | object | Key-value pairs of block attributes |
| `innerBlocks` | array | Nested child blocks (recursive structure) |

### Example Block

```json
{
  "name": "core/paragraph",
  "attributes": {
    "content": "This is a paragraph with <strong>bold text</strong>.",
    "dropCap": false,
    "align": "left"
  },
  "innerBlocks": []
}
```

## Common Block Attributes

### Core/Paragraph

```json
{
  "name": "core/paragraph",
  "attributes": {
    "content": "HTML string content",
    "dropCap": false,
    "align": "left|center|right",
    "backgroundColor": "color-slug",
    "textColor": "color-slug",
    "fontSize": "size-slug",
    "className": "custom-class"
  }
}
```

### Core/Heading

```json
{
  "name": "core/heading",
  "attributes": {
    "content": "Heading text",
    "level": 2,
    "align": "left",
    "anchor": "custom-id",
    "className": "custom-class"
  }
}
```

### Core/Image

```json
{
  "name": "core/image",
  "attributes": {
    "id": 123,
    "url": "https://example.com/image.jpg",
    "alt": "Image description",
    "caption": "Image caption",
    "href": "https://link-url.com",
    "align": "center",
    "sizeSlug": "large",
    "width": 1024,
    "height": 768
  }
}
```

:::info
The `width` and `height` attributes are automatically added by the plugin based on image metadata.
:::

### Core/List

```json
{
  "name": "core/list",
  "attributes": {
    "ordered": false,
    "values": "<li>Item 1</li><li>Item 2</li>"
  }
}
```

### Core/Button

```json
{
  "name": "core/button",
  "attributes": {
    "text": "Click me",
    "url": "https://example.com",
    "linkTarget": "_blank",
    "rel": "noopener noreferrer",
    "placeholder": "",
    "backgroundColor": "primary",
    "textColor": "white",
    "gradient": "gradient-slug",
    "width": 50
  }
}
```

## Nested Blocks Structure

Blocks with inner content return nested structures:

```json
{
  "name": "core/columns",
  "attributes": {
    "verticalAlignment": "center"
  },
  "innerBlocks": [
    {
      "name": "core/column",
      "attributes": {
        "width": "50%"
      },
      "innerBlocks": [
        {
          "name": "core/paragraph",
          "attributes": {
            "content": "Left column content"
          },
          "innerBlocks": []
        }
      ]
    },
    {
      "name": "core/column",
      "attributes": {
        "width": "50%"
      },
      "innerBlocks": [
        {
          "name": "core/paragraph",
          "attributes": {
            "content": "Right column content"
          },
          "innerBlocks": []
        }
      ]
    }
  ]
}
```

## HTML Content in Attributes

Some attributes contain HTML markup:

```json
{
  "name": "core/paragraph",
  "attributes": {
    "content": "Text with <strong>bold</strong> and <em>italic</em> and <a href=\"https://example.com\">links</a>."
  }
}
```

When rendering, use `dangerouslySetInnerHTML` (React) or `v-html` (Vue):

```jsx
// React
<div dangerouslySetInnerHTML={{ __html: attributes.content }} />

// Vue
<div v-html="attributes.content"></div>
```

## Warnings Array

The API may return warnings for non-fatal issues:

```json
{
  "blocks": [...],
  "warnings": [
    "Block 'custom/unregistered-block' is not registered"
  ]
}
```

### Common Warnings

| Warning | Cause | Solution |
|---------|-------|----------|
| Block is not registered | Client-only block or missing `block.json` | Register block server-side |
| No blocks found | Empty post content | Check post has block content |
| Parse error | Malformed block syntax | Validate post content |

## Debug Information

When `VIP_BLOCK_DATA_API__PARSE_DEBUG` is enabled:

```php
define( 'VIP_BLOCK_DATA_API__PARSE_DEBUG', true );
```

The response includes debug data:

```json
{
  "blocks": [...],
  "debug": {
    "rawBlocks": [...],
    "parseTimeMs": 45.67,
    "blockCount": 12,
    "memoryUsed": "2.5MB"
  }
}
```

## GraphQL Response Structure

GraphQL V2 uses a different structure:

```typescript
interface BlocksDataV2 {
  blocks: GraphQLBlock[];
  warnings: string[];
}

interface GraphQLBlock {
  id: string;
  parentId: string | null;
  name: string;
  attributes: BlockAttribute[];
}

interface BlockAttribute {
  name: string;
  value: string;
  isValueJsonEncoded: boolean;
}
```

### Example GraphQL Response

```json
{
  "data": {
    "post": {
      "blocksDataV2": {
        "blocks": [
          {
            "id": "1",
            "parentId": null,
            "name": "core/columns",
            "attributes": []
          },
          {
            "id": "2",
            "parentId": "1",
            "name": "core/column",
            "attributes": [
              {
                "name": "width",
                "value": "50%",
                "isValueJsonEncoded": false
              }
            ]
          },
          {
            "id": "3",
            "parentId": "2",
            "name": "core/paragraph",
            "attributes": [
              {
                "name": "content",
                "value": "Column content",
                "isValueJsonEncoded": false
              }
            ]
          }
        ],
        "warnings": []
      }
    }
  }
}
```

## Custom Block Attributes

Custom blocks return their registered attributes:

```json
{
  "name": "my-plugin/hero-banner",
  "attributes": {
    "title": "Welcome",
    "subtitle": "To our site",
    "backgroundImage": "https://example.com/bg.jpg",
    "buttonText": "Learn More",
    "buttonUrl": "/about",
    "theme": "dark"
  },
  "innerBlocks": []
}
```

## Empty and Null Values

The API handles empty values consistently:

```json
{
  "name": "core/paragraph",
  "attributes": {
    "content": "",           // Empty string
    "align": null,           // Not set
    "backgroundColor": null  // Not set
  },
  "innerBlocks": []          // Always an array, never null
}
```

## Type Coercion

Attribute values maintain their types:

```json
{
  "attributes": {
    "level": 2,              // number
    "ordered": false,        // boolean
    "content": "text",       // string
    "columns": null,         // null
    "images": [...]          // array
  }
}
```

## Response Size Optimization

### Reduce Payload with Filtering

```bash
# Only headings and paragraphs
curl "https://example.com/wp-json/vip-block-data-api/v1/posts/123/blocks?include=core/heading,core/paragraph"
```

### Typical Response Sizes

| Post Type | Average Size | Notes |
|-----------|--------------|-------|
| Simple blog post | 2-10 KB | Mostly text blocks |
| Rich media post | 20-50 KB | Images, embeds, galleries |
| Landing page | 50-200 KB | Complex layouts, many blocks |

## Error Responses

### 404 Not Found

```json
{
  "code": "rest_post_invalid_id",
  "message": "Invalid post ID.",
  "data": {
    "status": 404
  }
}
```

### 403 Forbidden

```json
{
  "code": "rest_forbidden",
  "message": "Sorry, you are not allowed to view this post.",
  "data": {
    "status": 403
  }
}
```

### Parse Error (200 OK with warning)

```json
{
  "blocks": [],
  "warnings": [
    "Failed to parse block content: Invalid block syntax"
  ]
}
```

## Next Steps

- [REST API Reference](./rest-api)
- [GraphQL API Reference](./graphql-api)
- [React Renderer Example](../examples/react-renderer)
