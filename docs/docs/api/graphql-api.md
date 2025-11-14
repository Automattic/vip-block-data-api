---
sidebar_position: 2
---

# GraphQL API Reference

The VIP Block Data API integrates seamlessly with [WPGraphQL](https://www.wpgraphql.com/), providing a powerful GraphQL interface for fetching block data.

## Requirements

- **WPGraphQL plugin** must be installed and activated
- Automatically enabled when WPGraphQL is detected
- Can be disabled via filter hook

## API Versions

### V2 (Current - Recommended)

Field name: `blocksDataV2`

Returns **flattened block structure** with `id` and `parentId` for reconstructing hierarchy.

### V1 (Legacy)

Field name: `blocksData`

Returns **nested block structure** similar to REST API. Not recommended for new implementations.

:::info
This documentation focuses on V2. See [migration guide](#migrating-from-v1-to-v2) for upgrading from V1.
:::

## Schema

The `blocksDataV2` field is available on all post types that implement the `NodeWithContentEditor` interface.

### BlocksData Type

```graphql
type BlocksData {
  blocks: [Block!]
  warnings: [String!]
}
```

### Block Type

```graphql
type Block {
  id: ID!
  parentId: ID
  name: String!
  attributes: [BlockAttribute!]
}
```

### BlockAttribute Type

```graphql
type BlockAttribute {
  name: String!
  value: String!
  isValueJsonEncoded: Boolean!
}
```

## Key Concepts

### Flattened Structure

Unlike the REST API's nested structure, GraphQL V2 returns a **flat array** of blocks with parent-child relationships defined by `id` and `parentId`.

**Benefits:**
- More efficient for GraphQL
- Easier to query specific blocks
- Better performance with large block trees
- Flexible reconstruction on the client

### Attribute Encoding

Attributes are represented as **name-value pairs** rather than key-value objects.

- Simple values (strings, numbers, booleans): Plain string values
- Complex values (arrays, objects): JSON-encoded strings with `isValueJsonEncoded: true`

## Basic Query

### Fetch All Blocks

```graphql
query GetPostBlocks {
  post(id: "123", idType: DATABASE_ID) {
    title
    blocksDataV2 {
      blocks {
        id
        parentId
        name
        attributes {
          name
          value
          isValueJsonEncoded
        }
      }
      warnings
    }
  }
}
```

### Response

```json
{
  "data": {
    "post": {
      "title": "My Post",
      "blocksDataV2": {
        "blocks": [
          {
            "id": "1",
            "parentId": null,
            "name": "core/heading",
            "attributes": [
              {
                "name": "level",
                "value": "2",
                "isValueJsonEncoded": false
              },
              {
                "name": "content",
                "value": "Welcome",
                "isValueJsonEncoded": false
              }
            ]
          },
          {
            "id": "2",
            "parentId": null,
            "name": "core/paragraph",
            "attributes": [
              {
                "name": "content",
                "value": "This is a paragraph.",
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

## Advanced Queries

### Multiple Posts

```graphql
query GetMultiplePosts {
  posts(first: 10) {
    nodes {
      id
      title
      blocksDataV2 {
        blocks {
          id
          name
        }
      }
    }
  }
}
```

### Custom Post Types

```graphql
query GetPageBlocks {
  page(id: "456", idType: DATABASE_ID) {
    title
    blocksDataV2 {
      blocks {
        id
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

### Conditional Fragments

```graphql
query GetContentWithBlocks {
  contentNode(id: "123", idType: DATABASE_ID) {
    __typename
    ... on Post {
      title
      date
    }
    ... on Page {
      title
    }
    ... on NodeWithContentEditor {
      blocksDataV2 {
        blocks {
          id
          name
        }
      }
    }
  }
}
```

## Working with Block Data

### Reconstructing Block Hierarchy

The flattened structure requires client-side reconstruction:

```javascript
function buildBlockTree(blocks) {
  const blockMap = new Map();
  const rootBlocks = [];

  // First pass: Create map of all blocks
  blocks.forEach(block => {
    blockMap.set(block.id, {
      ...block,
      innerBlocks: []
    });
  });

  // Second pass: Build hierarchy
  blocks.forEach(block => {
    const blockWithChildren = blockMap.get(block.id);

    if (block.parentId) {
      const parent = blockMap.get(block.parentId);
      if (parent) {
        parent.innerBlocks.push(blockWithChildren);
      }
    } else {
      rootBlocks.push(blockWithChildren);
    }
  });

  return rootBlocks;
}

// Usage
const { blocks } = data.post.blocksDataV2;
const blockTree = buildBlockTree(blocks);
```

### Parsing Block Attributes

Convert attribute arrays to objects and decode JSON values:

```javascript
function parseBlockAttributes(block) {
  const attributes = {};

  block.attributes.forEach(attr => {
    if (attr.isValueJsonEncoded) {
      try {
        attributes[attr.name] = JSON.parse(attr.value);
      } catch (e) {
        console.error(`Failed to parse attribute ${attr.name}:`, e);
        attributes[attr.name] = attr.value;
      }
    } else {
      // Convert string values to appropriate types
      attributes[attr.name] = parseAttributeValue(attr.value);
    }
  });

  return attributes;
}

function parseAttributeValue(value) {
  // Try to parse as number
  if (/^-?\d+$/.test(value)) {
    return parseInt(value, 10);
  }
  if (/^-?\d*\.\d+$/.test(value)) {
    return parseFloat(value);
  }

  // Try to parse as boolean
  if (value === 'true') return true;
  if (value === 'false') return false;

  // Return as string
  return value;
}
```

### Complete Utility Function

```javascript
function transformGraphQLBlocks(blocksData) {
  if (!blocksData || !blocksData.blocks) {
    return [];
  }

  const blocks = blocksData.blocks.map(block => ({
    id: block.id,
    parentId: block.parentId,
    name: block.name,
    attributes: parseBlockAttributes(block),
    innerBlocks: []
  }));

  return buildBlockTree(blocks);
}

// Usage with GraphQL query result
const blockTree = transformGraphQLBlocks(data.post.blocksDataV2);
```

## Examples

### React Component

```jsx
import { useQuery, gql } from '@apollo/client';

const GET_POST_BLOCKS = gql`
  query GetPostBlocks($id: ID!) {
    post(id: $id, idType: DATABASE_ID) {
      title
      blocksDataV2 {
        blocks {
          id
          parentId
          name
          attributes {
            name
            value
            isValueJsonEncoded
          }
        }
      }
    }
  }
`;

function PostContent({ postId }) {
  const { loading, error, data } = useQuery(GET_POST_BLOCKS, {
    variables: { id: postId }
  });

  if (loading) return <p>Loading...</p>;
  if (error) return <p>Error: {error.message}</p>;

  const blocks = transformGraphQLBlocks(data.post.blocksDataV2);

  return (
    <article>
      <h1>{data.post.title}</h1>
      <BlockRenderer blocks={blocks} />
    </article>
  );
}
```

### Next.js Static Generation

```javascript
import { ApolloClient, InMemoryCache, gql } from '@apollo/client';

const client = new ApolloClient({
  uri: 'https://example.com/graphql',
  cache: new InMemoryCache()
});

export async function getStaticProps({ params }) {
  const { data } = await client.query({
    query: gql`
      query GetPostBlocks($id: ID!) {
        post(id: $id, idType: DATABASE_ID) {
          title
          blocksDataV2 {
            blocks {
              id
              parentId
              name
              attributes {
                name
                value
                isValueJsonEncoded
              }
            }
          }
        }
      }
    `,
    variables: {
      id: params.id
    }
  });

  const blocks = transformGraphQLBlocks(data.post.blocksDataV2);

  return {
    props: {
      post: data.post,
      blocks
    },
    revalidate: 60 // Revalidate every 60 seconds
  };
}

export default function Post({ post, blocks }) {
  return (
    <article>
      <h1>{post.title}</h1>
      <BlockRenderer blocks={blocks} />
    </article>
  );
}
```

### Filtering Blocks (Client-Side)

```javascript
function getBlocksByType(blocks, blockType) {
  return blocks.filter(block => block.name === blockType);
}

function excludeBlockTypes(blocks, excludeTypes) {
  return blocks.filter(block => !excludeTypes.includes(block.name));
}

// Usage
const paragraphs = getBlocksByType(blocks, 'core/paragraph');
const withoutEmbeds = excludeBlockTypes(blocks, ['core/embed', 'core/html']);
```

:::info
Unlike the REST API, GraphQL doesn't support server-side block filtering via include/exclude parameters. You must filter on the client side.
:::

## Complex Attribute Example

### Block with JSON-Encoded Attributes

```graphql
{
  "id": "3",
  "name": "core/gallery",
  "attributes": [
    {
      "name": "images",
      "value": "[{\"id\":123,\"url\":\"image1.jpg\"},{\"id\":124,\"url\":\"image2.jpg\"}]",
      "isValueJsonEncoded": true
    },
    {
      "name": "columns",
      "value": "3",
      "isValueJsonEncoded": false
    }
  ]
}
```

### Parsing the Above

```javascript
const attributes = parseBlockAttributes(block);

console.log(attributes);
// {
//   images: [
//     { id: 123, url: 'image1.jpg' },
//     { id: 124, url: 'image2.jpg' }
//   ],
//   columns: 3
// }
```

## Error Handling

### GraphQL Errors

```javascript
const { loading, error, data } = useQuery(GET_POST_BLOCKS, {
  variables: { id: postId },
  onError: (error) => {
    console.error('GraphQL error:', error);
    // Handle different error types
    if (error.networkError) {
      console.error('Network error:', error.networkError);
    }
    if (error.graphQLErrors) {
      error.graphQLErrors.forEach(err => {
        console.error('GraphQL error:', err.message);
      });
    }
  }
});
```

### Warnings

Check the `warnings` field for non-fatal issues:

```javascript
const { blocksDataV2 } = data.post;

if (blocksDataV2.warnings && blocksDataV2.warnings.length > 0) {
  console.warn('Block parsing warnings:', blocksDataV2.warnings);
}
```

## Performance Optimization

### Query Only What You Need

```graphql
query GetMinimalBlocks {
  post(id: "123", idType: DATABASE_ID) {
    blocksDataV2 {
      blocks {
        id
        name
        # Only query attributes if needed
      }
    }
  }
}
```

### Batch Multiple Posts

```graphql
query GetMultiplePostsBlocks($ids: [ID!]) {
  posts(where: { in: $ids }) {
    nodes {
      databaseId
      blocksDataV2 {
        blocks {
          id
          name
        }
      }
    }
  }
}
```

### Use Fragments

```graphql
fragment BlockData on BlocksData {
  blocks {
    id
    parentId
    name
    attributes {
      name
      value
      isValueJsonEncoded
    }
  }
  warnings
}

query GetPost {
  post(id: "123", idType: DATABASE_ID) {
    title
    blocksDataV2 {
      ...BlockData
    }
  }
}
```

## Migrating from V1 to V2

### V1 Structure (Legacy)

```graphql
query V1Example {
  post(id: "123", idType: DATABASE_ID) {
    blocksData {
      blocks {
        name
        attributes
        innerBlocks {
          name
          attributes
        }
      }
    }
  }
}
```

### V2 Structure (Current)

```graphql
query V2Example {
  post(id: "123", idType: DATABASE_ID) {
    blocksDataV2 {
      blocks {
        id
        parentId
        name
        attributes {
          name
          value
          isValueJsonEncoded
        }
      }
    }
  }
}
```

### Migration Checklist

- [ ] Update GraphQL queries from `blocksData` to `blocksDataV2`
- [ ] Implement block tree reconstruction logic
- [ ] Update attribute parsing to handle name-value pairs
- [ ] Handle JSON-encoded complex attributes
- [ ] Test with posts containing nested blocks
- [ ] Update client-side block filtering logic

## Disabling GraphQL Integration

To disable GraphQL support:

```php
add_filter( 'vip_block_data_api__is_graphql_enabled', '__return_false' );
```

## Next Steps

- [Response Format Details](./response-format)
- [REST API](./rest-api)
- [GraphQL Examples](../examples/graphql-examples)
- [React Renderer](../examples/react-renderer)
