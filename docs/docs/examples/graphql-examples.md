---
sidebar_position: 2
---

# GraphQL Examples

Practical examples for working with the VIP Block Data API GraphQL integration.

## Prerequisites

Install and activate [WPGraphQL](https://www.wpgraphql.com/):

```bash
wp plugin install wp-graphql --activate
```

## Basic Queries

### Fetch Post with Blocks

```graphql
query GetPostWithBlocks {
  post(id: "123", idType: DATABASE_ID) {
    id
    title
    date
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

### Fetch Multiple Posts

```graphql
query GetMultiplePostsWithBlocks {
  posts(first: 10) {
    nodes {
      id
      databaseId
      title
      date
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

### Fetch Page with Blocks

```graphql
query GetPageWithBlocks {
  page(id: "456", idType: DATABASE_ID) {
    id
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
```

## Using Fragments

```graphql
fragment BlockData on Block {
  id
  parentId
  name
  attributes {
    name
    value
    isValueJsonEncoded
  }
}

fragment PostBlockData on BlocksData {
  blocks {
    ...BlockData
  }
  warnings
}

query GetPostWithFragments {
  post(id: "123", idType: DATABASE_ID) {
    title
    blocksDataV2 {
      ...PostBlockData
    }
  }
}
```

## Apollo Client Setup

### Installation

```bash
npm install @apollo/client graphql
```

### Client Configuration

```javascript
import { ApolloClient, InMemoryCache, createHttpLink } from '@apollo/client';

const httpLink = createHttpLink({
  uri: 'https://example.com/graphql',
});

const client = new ApolloClient({
  link: httpLink,
  cache: new InMemoryCache(),
});

export default client;
```

### React Component with Apollo

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
        warnings
      }
    }
  }
`;

function PostContent({ postId }) {
  const { loading, error, data } = useQuery(GET_POST_BLOCKS, {
    variables: { id: postId },
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

## Data Transformation

### Transform to REST-like Structure

```javascript
function transformGraphQLBlocks(blocksData) {
  if (!blocksData || !blocksData.blocks) {
    return [];
  }

  // Parse attributes
  const blocks = blocksData.blocks.map(block => ({
    id: block.id,
    parentId: block.parentId,
    name: block.name,
    attributes: parseAttributes(block.attributes),
    innerBlocks: []
  }));

  // Build tree structure
  return buildBlockTree(blocks);
}

function parseAttributes(attributesArray) {
  const attributes = {};

  attributesArray.forEach(attr => {
    if (attr.isValueJsonEncoded) {
      try {
        attributes[attr.name] = JSON.parse(attr.value);
      } catch (e) {
        console.error(`Failed to parse ${attr.name}:`, e);
        attributes[attr.name] = attr.value;
      }
    } else {
      attributes[attr.name] = coerceValue(attr.value);
    }
  });

  return attributes;
}

function coerceValue(value) {
  // Convert string values to appropriate types
  if (value === 'true') return true;
  if (value === 'false') return false;
  if (value === 'null') return null;

  if (/^-?\d+$/.test(value)) return parseInt(value, 10);
  if (/^-?\d*\.\d+$/.test(value)) return parseFloat(value);

  return value;
}

function buildBlockTree(blocks) {
  const blockMap = new Map();
  const rootBlocks = [];

  // Create map of all blocks
  blocks.forEach(block => {
    blockMap.set(block.id, block);
  });

  // Build hierarchy
  blocks.forEach(block => {
    if (block.parentId) {
      const parent = blockMap.get(block.parentId);
      if (parent) {
        parent.innerBlocks.push(block);
      }
    } else {
      rootBlocks.push(block);
    }
  });

  return rootBlocks;
}
```

## Advanced Queries

### With Variables

```javascript
import { gql } from '@apollo/client';

const GET_POST_BLOCKS = gql`
  query GetPostBlocks($id: ID!, $idType: PostIdType = DATABASE_ID) {
    post(id: $id, idType: $idType) {
      title
      blocksDataV2 {
        blocks {
          id
          name
        }
      }
    }
  }
`;

// By database ID
const { data } = await client.query({
  query: GET_POST_BLOCKS,
  variables: {
    id: '123',
    idType: 'DATABASE_ID'
  }
});

// By slug
const { data: dataBySlug } = await client.query({
  query: GET_POST_BLOCKS,
  variables: {
    id: 'my-post-slug',
    idType: 'SLUG'
  }
});
```

### Pagination

```graphql
query GetPostsWithPagination($first: Int!, $after: String) {
  posts(first: $first, after: $after) {
    pageInfo {
      hasNextPage
      endCursor
    }
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

```javascript
function PostsList() {
  const { loading, error, data, fetchMore } = useQuery(GET_POSTS_PAGINATED, {
    variables: { first: 10 },
  });

  const loadMore = () => {
    fetchMore({
      variables: {
        after: data.posts.pageInfo.endCursor,
      },
    });
  };

  if (loading) return <p>Loading...</p>;
  if (error) return <p>Error: {error.message}</p>;

  return (
    <div>
      {data.posts.nodes.map(post => (
        <PostItem key={post.id} post={post} />
      ))}
      {data.posts.pageInfo.hasNextPage && (
        <button onClick={loadMore}>Load More</button>
      )}
    </div>
  );
}
```

### Custom Post Types

```graphql
query GetCustomPostTypeBlocks {
  books(first: 10) {
    nodes {
      id
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
}
```

## Caching Strategies

### Apollo Cache Configuration

```javascript
const cache = new InMemoryCache({
  typePolicies: {
    Query: {
      fields: {
        post: {
          // Cache by ID
          read(existing, { args, toReference }) {
            return existing || toReference({
              __typename: 'Post',
              id: args.id,
            });
          },
        },
      },
    },
    Post: {
      fields: {
        blocksDataV2: {
          merge(existing, incoming) {
            return incoming;
          },
        },
      },
    },
  },
});
```

### Cache-First Strategy

```javascript
const { data } = useQuery(GET_POST_BLOCKS, {
  variables: { id: postId },
  fetchPolicy: 'cache-first', // Use cache if available
});
```

### Network-Only Strategy

```javascript
const { data } = useQuery(GET_POST_BLOCKS, {
  variables: { id: postId },
  fetchPolicy: 'network-only', // Always fetch fresh data
});
```

### Polling

```javascript
const { data } = useQuery(GET_POST_BLOCKS, {
  variables: { id: postId },
  pollInterval: 30000, // Refetch every 30 seconds
});
```

## Next.js Integration

### Static Site Generation

```javascript
import { ApolloClient, InMemoryCache, gql } from '@apollo/client';

export async function getStaticProps({ params }) {
  const client = new ApolloClient({
    uri: 'https://example.com/graphql',
    cache: new InMemoryCache(),
  });

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
      id: params.id,
    },
  });

  const blocks = transformGraphQLBlocks(data.post.blocksDataV2);

  return {
    props: {
      post: data.post,
      blocks,
    },
    revalidate: 60,
  };
}
```

### Server-Side Rendering

```javascript
export async function getServerSideProps({ params, req }) {
  const client = new ApolloClient({
    uri: 'https://example.com/graphql',
    cache: new InMemoryCache(),
    headers: {
      // Forward authentication
      cookie: req.headers.cookie || '',
    },
  });

  const { data } = await client.query({
    query: GET_POST_BLOCKS,
    variables: { id: params.id },
  });

  return {
    props: {
      initialApolloState: client.cache.extract(),
      post: data.post,
    },
  };
}
```

## Error Handling

### GraphQL Errors

```javascript
const { loading, error, data } = useQuery(GET_POST_BLOCKS, {
  variables: { id: postId },
  onError: (error) => {
    if (error.networkError) {
      console.error('Network error:', error.networkError);
    }

    if (error.graphQLErrors) {
      error.graphQLErrors.forEach(err => {
        console.error('GraphQL error:', err.message);
        console.error('Path:', err.path);
        console.error('Extensions:', err.extensions);
      });
    }
  },
});
```

### Error Boundary

```jsx
import { ApolloProvider } from '@apollo/client';
import { ErrorBoundary } from 'react-error-boundary';

function ErrorFallback({ error }) {
  return (
    <div role="alert">
      <p>Something went wrong:</p>
      <pre>{error.message}</pre>
    </div>
  );
}

function App() {
  return (
    <ApolloProvider client={client}>
      <ErrorBoundary FallbackComponent={ErrorFallback}>
        <PostContent postId={123} />
      </ErrorBoundary>
    </ApolloProvider>
  );
}
```

## Performance Optimization

### Query Debouncing

```javascript
import { useLazyQuery } from '@apollo/client';
import { debounce } from 'lodash';

function SearchPosts() {
  const [searchPosts, { loading, data }] = useLazyQuery(SEARCH_POSTS);

  const debouncedSearch = debounce((searchTerm) => {
    searchPosts({ variables: { search: searchTerm } });
  }, 300);

  return (
    <input
      type="search"
      onChange={(e) => debouncedSearch(e.target.value)}
      placeholder="Search posts..."
    />
  );
}
```

### Query Batching

```javascript
import { ApolloClient, InMemoryCache, HttpLink } from '@apollo/client';
import { BatchHttpLink } from '@apollo/client/link/batch-http';

const batchLink = new BatchHttpLink({
  uri: 'https://example.com/graphql',
  batchMax: 5, // Max 5 queries per batch
  batchInterval: 20, // Wait 20ms
});

const client = new ApolloClient({
  link: batchLink,
  cache: new InMemoryCache(),
});
```

## Testing

### Mock Apollo Provider

```javascript
import { MockedProvider } from '@apollo/client/testing';

const mocks = [
  {
    request: {
      query: GET_POST_BLOCKS,
      variables: { id: '123' },
    },
    result: {
      data: {
        post: {
          title: 'Test Post',
          blocksDataV2: {
            blocks: [
              {
                id: '1',
                parentId: null,
                name: 'core/paragraph',
                attributes: [
                  { name: 'content', value: 'Test content', isValueJsonEncoded: false }
                ]
              }
            ],
            warnings: []
          }
        }
      }
    }
  }
];

test('renders post content', async () => {
  render(
    <MockedProvider mocks={mocks}>
      <PostContent postId="123" />
    </MockedProvider>
  );

  expect(await screen.findByText('Test Post')).toBeInTheDocument();
});
```

## Next Steps

- [REST API Examples](./rest-api-examples)
- [GraphQL API Reference](../api/graphql-api)
- [React Renderer](./react-renderer)
