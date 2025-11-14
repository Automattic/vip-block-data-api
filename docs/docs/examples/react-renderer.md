---
sidebar_position: 3
---

# React Block Renderer

Build a complete React component system for rendering WordPress blocks.

## Overview

This guide shows you how to create a flexible, extensible React renderer for WordPress blocks fetched from the VIP Block Data API.

## Project Setup

```bash
npm install react react-dom
# or
yarn add react react-dom
```

TypeScript types (optional but recommended):

```bash
npm install --save-dev @types/react @types/react-dom
```

## Type Definitions

```typescript
// types.ts
export interface BlockAttributes {
  [key: string]: any;
}

export interface Block {
  name: string;
  attributes: BlockAttributes;
  innerBlocks: Block[];
}

export interface BlockRendererProps {
  blocks: Block[];
  renderers?: BlockRenderers;
}

export interface BlockComponentProps {
  attributes: BlockAttributes;
  innerBlocks: Block[];
}

export type BlockComponent = React.ComponentType<BlockComponentProps>;

export interface BlockRenderers {
  [blockName: string]: BlockComponent;
}
```

## Core Renderer Component

```tsx
// BlockRenderer.tsx
import React from 'react';
import { Block, BlockRenderers, BlockComponentProps } from './types';
import { coreBlocks } from './blocks';

interface BlockRendererProps {
  blocks: Block[];
  renderers?: BlockRenderers;
}

export const BlockRenderer: React.FC<BlockRendererProps> = ({
  blocks,
  renderers = {},
}) => {
  // Merge custom renderers with core blocks
  const allRenderers = { ...coreBlocks, ...renderers };

  const renderBlock = (block: Block, index: number) => {
    const { name, attributes, innerBlocks } = block;
    const BlockComponent = allRenderers[name];

    if (!BlockComponent) {
      console.warn(`No renderer found for block: ${name}`);
      return (
        <div key={index} className="block-not-found">
          <p>Block not found: {name}</p>
        </div>
      );
    }

    return (
      <BlockComponent
        key={index}
        attributes={attributes}
        innerBlocks={innerBlocks}
      />
    );
  };

  return (
    <div className="block-renderer">
      {blocks.map((block, index) => renderBlock(block, index))}
    </div>
  );
};
```

## Core Block Components

```tsx
// blocks/CoreParagraph.tsx
import React from 'react';
import { BlockComponentProps } from '../types';
import { BlockRenderer } from '../BlockRenderer';

export const CoreParagraph: React.FC<BlockComponentProps> = ({
  attributes,
  innerBlocks,
}) => {
  const { content, align, dropCap, className } = attributes;

  const classNames = [
    'wp-block-paragraph',
    align && `has-text-align-${align}`,
    dropCap && 'has-drop-cap',
    className,
  ]
    .filter(Boolean)
    .join(' ');

  return (
    <p
      className={classNames}
      dangerouslySetInnerHTML={{ __html: content }}
    />
  );
};

// blocks/CoreHeading.tsx
export const CoreHeading: React.FC<BlockComponentProps> = ({
  attributes,
}) => {
  const { content, level = 2, align, anchor, className } = attributes;

  const Tag = `h${level}` as keyof JSX.IntrinsicElements;

  const classNames = [
    'wp-block-heading',
    align && `has-text-align-${align}`,
    className,
  ]
    .filter(Boolean)
    .join(' ');

  return (
    <Tag
      id={anchor}
      className={classNames}
      dangerouslySetInnerHTML={{ __html: content }}
    />
  );
};

// blocks/CoreImage.tsx
export const CoreImage: React.FC<BlockComponentProps> = ({ attributes }) => {
  const {
    url,
    alt = '',
    caption,
    href,
    align,
    width,
    height,
    sizeSlug,
    className,
  } = attributes;

  const classNames = [
    'wp-block-image',
    align && `align${align}`,
    sizeSlug && `size-${sizeSlug}`,
    className,
  ]
    .filter(Boolean)
    .join(' ');

  const image = (
    <img
      src={url}
      alt={alt}
      width={width}
      height={height}
      loading="lazy"
    />
  );

  return (
    <figure className={classNames}>
      {href ? <a href={href}>{image}</a> : image}
      {caption && (
        <figcaption dangerouslySetInnerHTML={{ __html: caption }} />
      )}
    </figure>
  );
};

// blocks/CoreButton.tsx
export const CoreButton: React.FC<BlockComponentProps> = ({ attributes }) => {
  const {
    text,
    url,
    linkTarget,
    rel,
    backgroundColor,
    textColor,
    borderRadius,
    className,
  } = attributes;

  const classNames = [
    'wp-block-button',
    className,
  ]
    .filter(Boolean)
    .join(' ');

  const linkClassNames = [
    'wp-block-button__link',
    backgroundColor && `has-${backgroundColor}-background-color`,
    textColor && `has-${textColor}-color`,
  ]
    .filter(Boolean)
    .join(' ');

  const style: React.CSSProperties = {};
  if (borderRadius !== undefined) {
    style.borderRadius = `${borderRadius}px`;
  }

  return (
    <div className={classNames}>
      <a
        href={url}
        className={linkClassNames}
        target={linkTarget}
        rel={rel}
        style={style}
      >
        {text}
      </a>
    </div>
  );
};

// blocks/CoreList.tsx
export const CoreList: React.FC<BlockComponentProps> = ({
  attributes,
  innerBlocks,
}) => {
  const { ordered, values, className } = attributes;

  const Tag = ordered ? 'ol' : 'ul';

  const classNames = ['wp-block-list', className]
    .filter(Boolean)
    .join(' ');

  return (
    <Tag
      className={classNames}
      dangerouslySetInnerHTML={{ __html: values }}
    />
  );
};

// blocks/CoreColumns.tsx
export const CoreColumns: React.FC<BlockComponentProps> = ({
  attributes,
  innerBlocks,
}) => {
  const { verticalAlignment, className } = attributes;

  const classNames = [
    'wp-block-columns',
    verticalAlignment && `are-vertically-aligned-${verticalAlignment}`,
    className,
  ]
    .filter(Boolean)
    .join(' ');

  return (
    <div className={classNames}>
      <BlockRenderer blocks={innerBlocks} />
    </div>
  );
};

// blocks/CoreColumn.tsx
export const CoreColumn: React.FC<BlockComponentProps> = ({
  attributes,
  innerBlocks,
}) => {
  const { width, verticalAlignment, className } = attributes;

  const classNames = [
    'wp-block-column',
    verticalAlignment && `is-vertically-aligned-${verticalAlignment}`,
    className,
  ]
    .filter(Boolean)
    .join(' ');

  const style: React.CSSProperties = {};
  if (width) {
    style.flexBasis = width;
  }

  return (
    <div className={classNames} style={style}>
      <BlockRenderer blocks={innerBlocks} />
    </div>
  );
};

// blocks/CoreGroup.tsx
export const CoreGroup: React.FC<BlockComponentProps> = ({
  attributes,
  innerBlocks,
}) => {
  const { backgroundColor, textColor, className } = attributes;

  const classNames = [
    'wp-block-group',
    backgroundColor && `has-${backgroundColor}-background-color`,
    textColor && `has-${textColor}-color`,
    className,
  ]
    .filter(Boolean)
    .join(' ');

  return (
    <div className={classNames}>
      <div className="wp-block-group__inner-container">
        <BlockRenderer blocks={innerBlocks} />
      </div>
    </div>
  );
};

// blocks/CoreQuote.tsx
export const CoreQuote: React.FC<BlockComponentProps> = ({
  attributes,
  innerBlocks,
}) => {
  const { value, citation, className } = attributes;

  const classNames = ['wp-block-quote', className]
    .filter(Boolean)
    .join(' ');

  return (
    <blockquote className={classNames}>
      {value && <div dangerouslySetInnerHTML={{ __html: value }} />}
      <BlockRenderer blocks={innerBlocks} />
      {citation && (
        <cite dangerouslySetInnerHTML={{ __html: citation }} />
      )}
    </blockquote>
  );
};

// blocks/index.ts - Export all core blocks
export const coreBlocks = {
  'core/paragraph': CoreParagraph,
  'core/heading': CoreHeading,
  'core/image': CoreImage,
  'core/button': CoreButton,
  'core/buttons': CoreGroup, // Reuse group component
  'core/list': CoreList,
  'core/columns': CoreColumns,
  'core/column': CoreColumn,
  'core/group': CoreGroup,
  'core/quote': CoreQuote,
};
```

## Fetching and Rendering Blocks

```tsx
// PostContent.tsx
import React, { useEffect, useState } from 'react';
import { BlockRenderer } from './BlockRenderer';
import { Block } from './types';

interface PostContentProps {
  postId: number;
}

export const PostContent: React.FC<PostContentProps> = ({ postId }) => {
  const [blocks, setBlocks] = useState<Block[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchBlocks = async () => {
      try {
        setLoading(true);
        const response = await fetch(
          `https://your-site.com/wp-json/vip-block-data-api/v1/posts/${postId}/blocks`
        );

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();

        if (data.warnings && data.warnings.length > 0) {
          console.warn('Block warnings:', data.warnings);
        }

        setBlocks(data.blocks);
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Unknown error');
      } finally {
        setLoading(false);
      }
    };

    fetchBlocks();
  }, [postId]);

  if (loading) {
    return <div className="loading">Loading content...</div>;
  }

  if (error) {
    return <div className="error">Error: {error}</div>;
  }

  return (
    <article className="post-content">
      <BlockRenderer blocks={blocks} />
    </article>
  );
};
```

## Custom Block Renderers

```tsx
// customBlocks.tsx
import React from 'react';
import { BlockComponentProps } from './types';

export const MyPluginHeroBanner: React.FC<BlockComponentProps> = ({
  attributes,
}) => {
  const {
    title,
    subtitle,
    backgroundImage,
    ctaText,
    ctaUrl,
  } = attributes;

  return (
    <div
      className="hero-banner"
      style={{
        backgroundImage: `url(${backgroundImage})`,
      }}
    >
      <div className="hero-banner__content">
        <h1>{title}</h1>
        {subtitle && <p className="subtitle">{subtitle}</p>}
        {ctaText && ctaUrl && (
          <a href={ctaUrl} className="cta-button">
            {ctaText}
          </a>
        )}
      </div>
    </div>
  );
};

// Usage with custom renderers
<BlockRenderer
  blocks={blocks}
  renderers={{
    'my-plugin/hero-banner': MyPluginHeroBanner,
  }}
/>
```

## With Next.js (Server-Side Rendering)

```tsx
// pages/post/[id].tsx
import { GetStaticProps, GetStaticPaths } from 'next';
import { BlockRenderer } from '@/components/BlockRenderer';
import { Block } from '@/types';

interface PostPageProps {
  blocks: Block[];
  title: string;
}

export default function PostPage({ blocks, title }: PostPageProps) {
  return (
    <article>
      <h1>{title}</h1>
      <BlockRenderer blocks={blocks} />
    </article>
  );
}

export const getStaticProps: GetStaticProps = async ({ params }) => {
  const postId = params?.id as string;

  const response = await fetch(
    `https://your-site.com/wp-json/vip-block-data-api/v1/posts/${postId}/blocks`
  );

  const data = await response.json();

  // Also fetch post metadata
  const postResponse = await fetch(
    `https://your-site.com/wp-json/wp/v2/posts/${postId}`
  );
  const post = await postResponse.json();

  return {
    props: {
      blocks: data.blocks,
      title: post.title.rendered,
    },
    revalidate: 60, // Revalidate every minute
  };
};

export const getStaticPaths: GetStaticPaths = async () => {
  return {
    paths: [],
    fallback: 'blocking',
  };
};
```

## Styling

```css
/* styles/blocks.css */

/* Paragraph */
.wp-block-paragraph {
  margin-bottom: 1em;
}

.wp-block-paragraph.has-text-align-center {
  text-align: center;
}

.wp-block-paragraph.has-text-align-right {
  text-align: right;
}

.wp-block-paragraph.has-drop-cap::first-letter {
  float: left;
  font-size: 3.5em;
  line-height: 0.8;
  margin: 0.07em 0.15em 0 0;
}

/* Heading */
.wp-block-heading {
  margin-top: 1.5em;
  margin-bottom: 0.5em;
}

/* Image */
.wp-block-image {
  margin: 1em 0;
}

.wp-block-image img {
  max-width: 100%;
  height: auto;
}

.wp-block-image.alignleft {
  float: left;
  margin-right: 1em;
}

.wp-block-image.alignright {
  float: right;
  margin-left: 1em;
}

.wp-block-image.aligncenter {
  text-align: center;
}

.wp-block-image figcaption {
  margin-top: 0.5em;
  font-size: 0.9em;
  color: #666;
  text-align: center;
}

/* Button */
.wp-block-button {
  margin: 1em 0;
}

.wp-block-button__link {
  display: inline-block;
  padding: 0.75em 1.5em;
  text-decoration: none;
  border-radius: 4px;
  background-color: #0675C4;
  color: white;
  transition: opacity 0.2s;
}

.wp-block-button__link:hover {
  opacity: 0.9;
}

/* Columns */
.wp-block-columns {
  display: flex;
  gap: 2em;
  margin: 2em 0;
}

.wp-block-column {
  flex: 1;
}

@media (max-width: 768px) {
  .wp-block-columns {
    flex-direction: column;
  }
}

/* Quote */
.wp-block-quote {
  border-left: 4px solid #0675C4;
  padding-left: 1.5em;
  margin: 2em 0;
  font-style: italic;
}

.wp-block-quote cite {
  display: block;
  margin-top: 1em;
  font-style: normal;
  font-size: 0.9em;
}
```

## Error Boundaries

```tsx
// ErrorBoundary.tsx
import React, { Component, ReactNode } from 'react';

interface Props {
  children: ReactNode;
  fallback?: ReactNode;
}

interface State {
  hasError: boolean;
  error?: Error;
}

export class BlockErrorBoundary extends Component<Props, State> {
  constructor(props: Props) {
    super(props);
    this.state = { hasError: false };
  }

  static getDerivedStateFromError(error: Error): State {
    return { hasError: true, error };
  }

  componentDidCatch(error: Error, errorInfo: React.ErrorInfo) {
    console.error('Block rendering error:', error, errorInfo);
  }

  render() {
    if (this.state.hasError) {
      return (
        this.props.fallback || (
          <div className="block-error">
            <p>Unable to render block</p>
          </div>
        )
      );
    }

    return this.props.children;
  }
}

// Usage
<BlockErrorBoundary>
  <BlockRenderer blocks={blocks} />
</BlockErrorBoundary>
```

## Performance Optimization

```tsx
// Memoized renderer
import React, { memo } from 'react';

export const OptimizedBlockRenderer = memo<BlockRendererProps>(
  ({ blocks, renderers }) => {
    return <BlockRenderer blocks={blocks} renderers={renderers} />;
  },
  (prevProps, nextProps) => {
    // Custom comparison
    return (
      JSON.stringify(prevProps.blocks) === JSON.stringify(nextProps.blocks)
    );
  }
);

// Lazy loading for large content
const [visibleBlocks, setVisibleBlocks] = useState(10);

const handleLoadMore = () => {
  setVisibleBlocks(prev => prev + 10);
};

<BlockRenderer blocks={blocks.slice(0, visibleBlocks)} />
```

## Next Steps

- [REST API Examples](./rest-api-examples)
- [GraphQL Examples](./graphql-examples)
- [Filtering Blocks Guide](../guides/block-filtering)
