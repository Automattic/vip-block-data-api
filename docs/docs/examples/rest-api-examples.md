---
sidebar_position: 1
---

# REST API Examples

Practical examples for working with the VIP Block Data API REST endpoint.

## Basic Fetch Examples

### JavaScript Fetch

```javascript
// Simple fetch
async function getBlocks(postId) {
  const response = await fetch(
    `https://example.com/wp-json/vip-block-data-api/v1/posts/${postId}/blocks`
  );

  if (!response.ok) {
    throw new Error(`HTTP error ${response.status}`);
  }

  const data = await response.json();
  return data.blocks;
}

// With error handling
async function getBlocksSafe(postId) {
  try {
    const blocks = await getBlocks(postId);

    return { success: true, blocks };
  } catch (error) {
    console.error('Failed to fetch blocks:', error);
    return { success: false, error: error.message };
  }
}

// Usage
const result = await getBlocksSafe(123);
if (result.success) {
  console.log('Blocks:', result.blocks);
} else {
  console.error('Error:', result.error);
}
```

### Axios

```javascript
import axios from 'axios';

async function getBlocks(postId) {
  try {
    const { data } = await axios.get(
      `https://example.com/wp-json/vip-block-data-api/v1/posts/${postId}/blocks`
    );

    return data.blocks;
  } catch (error) {
    if (error.response) {
      // Server responded with error
      console.error('Server error:', error.response.data);
    } else if (error.request) {
      // Request made but no response
      console.error('No response:', error.request);
    } else {
      console.error('Error:', error.message);
    }
    throw error;
  }
}
```

### WordPress API Client

```javascript
import apiFetch from '@wordpress/api-fetch';

// Inside WordPress
const blocks = await apiFetch({
  path: '/vip-block-data-api/v1/posts/123/blocks'
});

// With parameters
const filteredBlocks = await apiFetch({
  path: '/vip-block-data-api/v1/posts/123/blocks',
  data: {
    include: 'core/paragraph,core/heading'
  }
});
```

## Filtering Examples

### Include Specific Block Types

```javascript
async function getTextBlocks(postId) {
  const params = new URLSearchParams({
    include: 'core/paragraph,core/heading,core/list'
  });

  const response = await fetch(
    `https://example.com/wp-json/vip-block-data-api/v1/posts/${postId}/blocks?${params}`
  );

  const data = await response.json();
  return data.blocks;
}
```

### Exclude Block Types

```javascript
async function getBlocksWithoutEmbeds(postId) {
  const excludeBlocks = [
    'core/embed',
    'core/html',
    'core-embed/youtube',
    'core-embed/twitter'
  ];

  const params = new URLSearchParams({
    exclude: excludeBlocks.join(',')
  });

  const response = await fetch(
    `https://example.com/wp-json/vip-block-data-api/v1/posts/${postId}/blocks?${params}`
  );

  const data = await response.json();
  return data.blocks;
}
```

## Caching Examples

### Local Storage Cache

```javascript
class BlockCache {
  constructor(ttl = 5 * 60 * 1000) { // 5 minutes default
    this.ttl = ttl;
  }

  get(postId) {
    const cached = localStorage.getItem(`blocks_${postId}`);

    if (!cached) return null;

    const { blocks, timestamp } = JSON.parse(cached);

    // Check if expired
    if (Date.now() - timestamp > this.ttl) {
      this.delete(postId);
      return null;
    }

    return blocks;
  }

  set(postId, blocks) {
    const cacheData = {
      blocks,
      timestamp: Date.now()
    };

    localStorage.setItem(`blocks_${postId}`, JSON.stringify(cacheData));
  }

  delete(postId) {
    localStorage.removeItem(`blocks_${postId}`);
  }

  clear() {
    Object.keys(localStorage)
      .filter(key => key.startsWith('blocks_'))
      .forEach(key => localStorage.removeItem(key));
  }
}

// Usage
const cache = new BlockCache();

async function getBlocksCached(postId) {
  // Check cache first
  const cached = cache.get(postId);
  if (cached) {
    console.log('Cache hit');
    return cached;
  }

  // Fetch from API
  console.log('Cache miss, fetching from API');
  const blocks = await getBlocks(postId);

  // Store in cache
  cache.set(postId, blocks);

  return blocks;
}
```

### React Query

```javascript
import { useQuery } from '@tanstack/react-query';

function useBlocks(postId) {
  return useQuery({
    queryKey: ['blocks', postId],
    queryFn: async () => {
      const response = await fetch(
        `https://example.com/wp-json/vip-block-data-api/v1/posts/${postId}/blocks`
      );

      if (!response.ok) {
        throw new Error('Failed to fetch blocks');
      }

      const data = await response.json();
      return data.blocks;
    },
    staleTime: 5 * 60 * 1000, // 5 minutes
    cacheTime: 10 * 60 * 1000, // 10 minutes
  });
}

// Usage in component
function PostContent({ postId }) {
  const { data: blocks, isLoading, error } = useBlocks(postId);

  if (isLoading) return <div>Loading...</div>;
  if (error) return <div>Error: {error.message}</div>;

  return <BlockRenderer blocks={blocks} />;
}
```

### SWR (Stale-While-Revalidate)

```javascript
import useSWR from 'swr';

const fetcher = (url) => fetch(url).then(res => res.json());

function useBlocks(postId) {
  const { data, error, isLoading } = useSWR(
    `https://example.com/wp-json/vip-block-data-api/v1/posts/${postId}/blocks`,
    fetcher,
    {
      revalidateOnFocus: false,
      revalidateOnReconnect: false,
      dedupingInterval: 60000, // 1 minute
    }
  );

  return {
    blocks: data?.blocks,
    isLoading,
    error
  };
}
```

## Batch Requests

### Fetch Multiple Posts

```javascript
async function getMultiplePostBlocks(postIds) {
  const requests = postIds.map(id =>
    fetch(`https://example.com/wp-json/vip-block-data-api/v1/posts/${id}/blocks`)
      .then(res => res.json())
      .then(data => ({ id, blocks: data.blocks }))
      .catch(error => ({ id, error: error.message }))
  );

  return Promise.all(requests);
}

// Usage
const postIds = [123, 456, 789];
const results = await getMultiplePostBlocks(postIds);

results.forEach(result => {
  if (result.blocks) {
    console.log(`Post ${result.id}:`, result.blocks);
  } else {
    console.error(`Post ${result.id} failed:`, result.error);
  }
});
```

### Concurrent with Limit

```javascript
async function fetchWithLimit(postIds, limit = 5) {
  const results = [];

  for (let i = 0; i < postIds.length; i += limit) {
    const batch = postIds.slice(i, i + limit);
    const batchResults = await Promise.all(
      batch.map(id => getBlocks(id))
    );
    results.push(...batchResults);
  }

  return results;
}
```

## Client-Side Filtering

### Filter by Block Type

```javascript
function getBlocksByType(blocks, blockType) {
  const result = [];

  function traverse(blockList) {
    blockList.forEach(block => {
      if (block.name === blockType) {
        result.push(block);
      }

      if (block.innerBlocks?.length > 0) {
        traverse(block.innerBlocks);
      }
    });
  }

  traverse(blocks);
  return result;
}

// Usage
const blocks = await getBlocks(123);
const paragraphs = getBlocksByType(blocks, 'core/paragraph');
const images = getBlocksByType(blocks, 'core/image');
```

### Extract Text Content

```javascript
function extractTextContent(blocks) {
  let text = '';

  function traverse(blockList) {
    blockList.forEach(block => {
      // Extract content from text blocks
      if (['core/paragraph', 'core/heading'].includes(block.name)) {
        const content = block.attributes.content || '';
        // Strip HTML tags
        text += content.replace(/<[^>]*>/g, '') + '\n';
      }

      if (block.innerBlocks?.length > 0) {
        traverse(block.innerBlocks);
      }
    });
  }

  traverse(blocks);
  return text.trim();
}

// Usage
const blocks = await getBlocks(123);
const plainText = extractTextContent(blocks);
console.log('Plain text:', plainText);
```

### Count Blocks

```javascript
function countBlocks(blocks) {
  const counts = {};

  function traverse(blockList) {
    blockList.forEach(block => {
      counts[block.name] = (counts[block.name] || 0) + 1;

      if (block.innerBlocks?.length > 0) {
        traverse(block.innerBlocks);
      }
    });
  }

  traverse(blocks);
  return counts;
}

// Usage
const blocks = await getBlocks(123);
const blockCounts = countBlocks(blocks);
console.log('Block counts:', blockCounts);
// { 'core/paragraph': 5, 'core/heading': 3, 'core/image': 2 }
```

## React Hooks

### Custom Hook

```javascript
import { useState, useEffect } from 'react';

function useBlockData(postId) {
  const [blocks, setBlocks] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    let cancelled = false;

    async function fetchData() {
      try {
        setLoading(true);
        setError(null);

        const response = await fetch(
          `https://example.com/wp-json/vip-block-data-api/v1/posts/${postId}/blocks`
        );

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();

        if (!cancelled) {
          setBlocks(data.blocks);
        }
      } catch (err) {
        if (!cancelled) {
          setError(err.message);
        }
      } finally {
        if (!cancelled) {
          setLoading(false);
        }
      }
    }

    fetchData();

    return () => {
      cancelled = true;
    };
  }, [postId]);

  return { blocks, loading, error };
}

// Usage
function PostComponent({ postId }) {
  const { blocks, loading, error } = useBlockData(postId);

  if (loading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;

  return <BlockRenderer blocks={blocks} />;
}
```

## Next.js Examples

### Static Generation

```javascript
// pages/posts/[id].js
export async function getStaticProps({ params }) {
  const response = await fetch(
    `https://example.com/wp-json/vip-block-data-api/v1/posts/${params.id}/blocks`
  );

  const data = await response.json();

  return {
    props: {
      blocks: data.blocks
    },
    revalidate: 60 // Revalidate every minute
  };
}

export async function getStaticPaths() {
  // Fetch list of post IDs
  const response = await fetch('https://example.com/wp-json/wp/v2/posts');
  const posts = await response.json();

  const paths = posts.map(post => ({
    params: { id: post.id.toString() }
  }));

  return {
    paths,
    fallback: 'blocking'
  };
}

export default function Post({ blocks }) {
  return <BlockRenderer blocks={blocks} />;
}
```

### Server-Side Rendering

```javascript
// pages/preview/[id].js
export async function getServerSideProps({ params, req }) {
  // Get authentication from cookies
  const authToken = req.cookies.auth_token;

  const response = await fetch(
    `https://example.com/wp-json/vip-block-data-api/v1/posts/${params.id}/blocks`,
    {
      headers: {
        'Authorization': `Bearer ${authToken}`
      }
    }
  );

  if (!response.ok) {
    return { notFound: true };
  }

  const data = await response.json();

  return {
    props: {
      blocks: data.blocks
    }
  };
}

export default function Preview({ blocks }) {
  return (
    <div className="preview-mode">
      <BlockRenderer blocks={blocks} />
    </div>
  );
}
```

## Error Handling

### Retry Logic

```javascript
async function fetchWithRetry(url, retries = 3, delay = 1000) {
  for (let i = 0; i < retries; i++) {
    try {
      const response = await fetch(url);

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      return await response.json();
    } catch (error) {
      if (i === retries - 1) throw error;

      console.log(`Retry ${i + 1}/${retries} after ${delay}ms`);
      await new Promise(resolve => setTimeout(resolve, delay));

      // Exponential backoff
      delay *= 2;
    }
  }
}

// Usage
try {
  const data = await fetchWithRetry(
    `https://example.com/wp-json/vip-block-data-api/v1/posts/123/blocks`,
    3,
    1000
  );
  console.log('Blocks:', data.blocks);
} catch (error) {
  console.error('Failed after retries:', error);
}
```

## Next Steps

- [GraphQL Examples](./graphql-examples)
- [React Renderer](./react-renderer)
- [REST API Reference](../api/rest-api)
