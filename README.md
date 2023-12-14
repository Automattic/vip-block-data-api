# VIP Block Data API

<picture>
  <source srcset="https://github.com/Automattic/vip-block-data-api/blob/media/vip-block-data-api-animation-1660.gif" media="(-webkit-min-device-pixel-ratio: 2.0)" />
  <source srcset="https://github.com/Automattic/vip-block-data-api/blob/media/vip-block-data-api-animation-830.gif" media="(-webkit-min-device-pixel-ratio: 1.0)" />
  <img src="https://github.com/Automattic/vip-block-data-api/blob/media/vip-block-data-api-animation-830.gif" alt="VIP Block Data API attribute sourcing animation" />
</picture>

The Block Data API is an API for retrieving block editor posts structured as JSON data, with integrations for both the official WordPress REST API and WPGraphQL. While primarily designed for use in decoupled WordPress, the Block Data API can be used anywhere you want to represent block markup as structured data.

This plugin is currently developed for use on WordPress sites hosted on the VIP Platform.

## Table of contents

- [Installation](#installation)
  - [Install on WordPress VIP](#install-on-wordpress-vip)
  - [Install via ZIP file](#install-via-zip-file)
    - [Plugin activation](#plugin-activation)
- [APIs](#apis)
  - [REST](#rest)
    - [Usage](#usage)
    - [Versioning](#versioning)
    - [Examples](#examples)
      - [Example: Basic text blocks: `core/heading` and `core/paragraph`](#example-basic-text-blocks-coreheading-and-coreparagraph)
      - [Example: Text attributes in `core/pullquote`](#example-text-attributes-in-corepullquote)
      - [Example: Nested blocks in `core/media-text`](#example-nested-blocks-in-coremedia-text)
  - [GraphQL](#graphql)
    - [Setup](#setup)
    - [Usage](#usage-1)
    - [Block Attributes](#block-attributes)
    - [Example: Simple nested blocks: `core/list` and `core/quote`](#example-simple-nested-blocks-corelist-and-corequote)
- [API Consumption](#api-consumption)
  - [Preact](#preact)
  - [Block hierarchy reconstruction](#block-hierarchy-reconstruction)
- [Limitations](#limitations)
  - [Client-side blocks](#client-side-blocks)
    - [Client-side example](#client-side-example)
    - [Registering client-side blocks](#registering-client-side-blocks)
  - [Rich text support](#rich-text-support)
  - [Deprecated blocks](#deprecated-blocks)
- [Rest API Query Parameters](#rest-api-query-parameters)
  - [Example Post](#example-post)
  - [`include`](#include)
  - [`exclude`](#exclude)
- [Code Filters](#code-filters)
  - [GraphQL](#graphql-1)
  - [REST](#rest-1)
  - [`vip_block_data_api__rest_validate_post_id`](#vip_block_data_api__rest_validate_post_id)
  - [`vip_block_data_api__rest_permission_callback`](#vip_block_data_api__rest_permission_callback)
  - [`vip_block_data_api__allow_block`](#vip_block_data_api__allow_block)
  - [`vip_block_data_api__sourced_block_result`](#vip_block_data_api__sourced_block_result)
- [Analytics](#analytics)
- [Caching on WPVIP](#caching-on-wpvip)
- [Errors and Warnings](#errors-and-warnings)
  - [Error: `vip-block-data-api-no-blocks`](#error-vip-block-data-api-no-blocks)
  - [Error: `vip-block-data-api-parser-error`](#error-vip-block-data-api-parser-error)
  - [Warning: Unregistered block type](#warning-unregistered-block-type)
- [Development](#development)
  - [Tests](#tests)

## Installation

### Install on WordPress VIP

The Block Data API plugin is authored and maintained by [WordPress VIP][wpvip], and made available to all WordPress sites by [VIP MU plugins][vip-go-mu-plugins]. Customers who host on WordPress VIP or use [`vip dev-env`](https://docs.wpvip.com/how-tos/local-development/use-the-vip-local-development-environment/) to develop locally have access to the Block Data API automatically. We recommend this activation method for WordPress VIP customers.

Enable the plugin by adding the method shown below to your application's [`client-mu-plugins/plugin-loader.php`][vip-go-skeleton-plugin-loader-example]:

```php
// client-mu-plugins/plugin-loader.php

\Automattic\VIP\Integrations\activate( 'block-data-api' );
```

Create this path in your WordPress VIP site if it does not yet exist.

This will automatically install and activate the latest mu-plugins release of the Block Data API. Remove this line to deactivate the plugin. For more WordPress VIP-specific information about using this plugin, see documentation for the [Block Data API plugin on WordPress VIP][wpvip-mu-plugins-block-data-api].

We plan to utilize API versioning to make automatic updates safe for consumer code. See [Versioning](#versioning) for more information.

To use the Block Data API after activation, skip to [Usage](#usage).

### Install via ZIP file

The latest version of the plugin can be downloaded from the [repository's Releases page][repo-releases]. Unzip the downloaded plugin and add it to the `plugins/` directory of your site's GitHub repository.

#### Plugin activation

Usually VIP recommends [activating plugins with code][wpvip-plugin-activate]. In this case, we are recommending activating the plugin in the WordPress Admin dashboard. This will allow the plugin to be more easily enabled and disabled during testing.

To activate the installed plugin:

1. Navigate to the WordPress Admin dashboard as a logged-in user.
2. Select **Plugins** from the lefthand navigation menu.
3. Locate the "VIP Block Data API" plugin in the list and select the "Activate" link located below it.

    ![Plugin activation][media-plugin-activate]

## APIs

The VIP Block Data API plugin provides two types of APIs to use - REST and GraphQL. The Block Data API [uses server-side registered blocks][wordpress-block-metadata-php-registration] to determine block attributes. Refer to the **[Client-side blocks](#client-side-blocks)** section for more information about client-side block support limitations.

### REST

There is no extra setup necessary for the REST API. It is ready to use out of the box.

#### Usage

The REST URL is located at:

```js
/wp-json/vip-block-data-api/v1/posts/<post_id>/blocks

// e.g. https://my-site.com/wp-json/vip-block-data-api/v1/posts/139/blocks
```

This public endpoint will return editor block metadata as structured JSON for any published post, page, or published `WP_Post` object.

Review these [**Filters**](#filters) to learn more about limiting access to the REST endpoint:

- [`vip_block_data_api__rest_validate_post_id`](#vip_block_data_api__rest_validate_post_id)
- [`vip_block_data_api__rest_permission_callback`](#vip_block_data_api__rest_permission_callback)

#### Versioning

The current REST endpoint uses a `v1` prefix:

```
/wp-json/vip-block-data-api/v1/...
```

We plan to utilize API versioning to avoid unexpected changes to the plugin. In the event that we make breaking changes to API output, we will add a new endpoint (e.g. `/wp-json/vip-block-data-api/v2/`) with access to new data. Previous versions will remain accessible for backward compatibility.

#### Examples

Examples of WordPress block markup and the associated data structure returned by the Block Data API.

##### Example: Basic text blocks: `core/heading` and `core/paragraph`

![Heading and paragraph block in editor][media-example-heading-paragraph]

<table>
<tr>
<td>Block Markup</td>
<td>Block Data API</td>
</tr>
<tr>
<td>

```html
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Block Data API</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Blocks as JSON.</p>
<!-- /wp:paragraph -->
```

</td>

<td>

```json
[{
  "name": "core/heading",
  "attributes": {
    "level": 3,
    "content": "Block Data API"
  }
},
{
  "name": "core/paragraph",
  "attributes": {
    "content": "Blocks as JSON."
  }
}]
```

</td>
</tr>
</table>

---

##### Example: Text attributes in `core/pullquote`

![Pullquote block in editor][media-example-pullquote]

<table>
<tr>
<td>Block Markup</td>
<td>Block Data API</td>
</tr>
<tr>
<td>

```html
<!-- wp:pullquote -->
<figure class="wp-block-pullquote">
    <blockquote>
        <p>From markup -> props</p>
        <cite>~ WPVIP</cite>
    </blockquote>
</figure>
<!-- /wp:pullquote -->
```

</td>

<td>

```json
[{
  "name": "core/pullquote",
  "attributes": {
    "value": "From markup -> props",
    "citation": "~ WPVIP"
  }
}]
```

</td>
</tr>
</table>

---

##### Example: Nested blocks in `core/media-text`

![Media-text block containing heading in editor][media-example-media-text]

<table>
<tr>
<td>Block Markup</td>
<td>Block Data API</td>
</tr>
<tr>
<td>

```html
<!-- wp:media-text {"mediaId":256,
  "mediaType":"image"} -->
<div class="wp-block-media-text">
  <figure class="wp-block-media-text__media">
    <img src="http://my.site/api.jpg"
      class="wp-image-256 size-full" />
  </figure>
  <div class="wp-block-media-text__content">
    <!-- wp:heading -->
    <h2 class="wp-block-heading">
      REST API
    </h2>
    <!-- /wp:heading -->
  </div>
</div>
<!-- /wp:media-text -->
```

</td>
<td>

```json
[{
  "name": "core/media-text",
  "attributes": {
    "mediaId": 256,
    "mediaType": "image",
    "mediaPosition": "left",
    "mediaUrl": "http://my.site/api.jpg",
    "mediaWidth": 50
  },
  "innerBlocks": [
    {
      "name": "core/heading",
      "attributes": {
        "content": "REST API",
        "level": 2
      }
    }
  ]
}]
```

</td>
</tr>
</table>

### GraphQL

The GraphQL API requires some setup before it can be it can be used.

#### Setup

The Block Data API integrates with **WPGraphQL** to provide a GraphQL API. It is necessary to have [WPGraphQL installed and activated][wpgraphql-install].

Once WPGraphQL has been installed and setup, a new field called `blocksData` will be available for post types that provide content, like posts, pages, etc.

#### Usage

The `blocksData` field provides block data for post types that support it. Here is an example query:

```graphQL
query NewQuery {
  post(id: "1", idType: DATABASE_ID) {
    blocksData {
      blocks {
        id
        name
        attributes {
          name
          value
        }
        innerBlocks {
          name
          parentId
          id
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

Here, the `id` and `parentId` fields are dynamically generated, unique IDs that help to identify parent-child relationships in the `innerBlocks` under a block, in the overall block structure. The resulting `innerBlocks` is a flattened list that can be untangled using the combination of `id` and `parentId` fields. This is helpful in being able to give back a complicated nesting structure, without having any knowledge as to how deep this nesting goes. For more information on recreating `innerBlocks`, see [Block Hierarchy Reconstruction](#block-hierarchy-reconstruction).

This behaviour can be changed by passing in `flatten: false`. This would give back the same block hierarchy as shown in the block editor, without the `parentId` being set. In addition, the correct depth would need to be requested in the query so that the entire block hierarchy can be given back. By default, `flatten` is set to true and so can be skipped if flattenining the innerBlocks is the intended behaviour. The same query above, would now look like:

```graphQL
query NewQuery {
  post(id: "1", idType: DATABASE_ID) {
    blocksData {
      blocks(flatten: false) {
        id
        name
        attributes {
          name
          value
        }
        innerBlocks {
          attributes {
            name
            value
          }
          name
          id
        }
      }
    }
  }
}
```

#### Block Attributes

The attributes of a block in GraphQL are available in a list of `name` / `value` string pairs, e.g.

```js
"attributes": [
  {
    "name": "content",
    "value": "This is item 1 in the list",
  },
  {
    "name": "fontSize",
    "value": "small"
  }
]
```

This is used instead of a key-value structure. This is a trade-off that makes it easy to retrieve block attributes without specifying the the block type ahead of time, but attribute type information is lost.

---

#### Example: Simple nested blocks: `core/list` and `core/quote`

![List and Quote block in editor][media-example-list-quote]

*Block Markup*

```html
<!-- wp:list -->
<ul><!-- wp:list-item -->
  <li>This is item 1 in the list</li>
  <!-- /wp:list-item -->

  <!-- wp:list-item -->
  <li>This is item 2 in the list</li>
  <!-- /wp:list-item -->
</ul>
<!-- /wp:list -->

<!-- wp:quote -->
<blockquote class="wp-block-quote">
  <!-- wp:paragraph -->
  <p>This is a paragraph within a quote</p>
  <!-- /wp:paragraph -->
</blockquote>
<!-- /wp:quote -->
```

<table>
<tr>
<td>Query</td>
<td>Block Data API</td>
</tr>
<tr>
<td>

```graphQL
query NewQuery {
  post(id: "1", idType: DATABASE_ID) {
    blocksData {
      blocks {
        id
        name
        attributes {
          name
          value
        }
        innerBlocks {
          name
          parentId
          id
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

</td>
<td>

```json
{
  "data": {
    "post": {
      "blocksData": {
        "blocks": [
          {
            "attributes": [
              {
                "name": "ordered",
                "value": ""
              },
              {
                "name": "values",
                "value": ""
              }
            ],
            "id": "1",
            "name": "core/list",
            "innerBlocks": [
              {
                "id": "2",
                "name": "core/list-item",
                "parentId": "1",
                "attributes": [
                  {
                    "name": "content",
                    "value": "This is item 1 in the list"
                  }
                ]
              },
              {
                "id": "3",
                "name": "core/list-item",
                "parentId": "1",
                "attributes": [
                  {
                    "name": "content",
                    "value": "This is item 2 in the list"
                  }
                ]
              }
            ]
          },
          {
            "attributes": [
              {
                "name": "value",
                "value": ""
              },
              {
                "name": "citation",
                "value": ""
              }
            ],
            "id": "4",
            "name": "core/quote",
            "innerBlocks": [
              {
                "id": "5",
                "name": "core/paragraph",
                "parentId": "4",
                "attributes": [
                  {
                    "name": "content",
                    "value": "This is a paragraph within a quote"
                  },
                  {
                    "name": "dropCap",
                    "value": ""
                  }
                ]
              }
            ]
          }
        ]
      }
    }
  }
}
```

</td>
</tr>
</table>

Note that `id` values returned from GraphQL will be alpha-numeric strings, e.g. `"id": "SUQ6MQ=="` and not integers.

## API Consumption

### Preact

An example [Preact app][preact] app that queries for block data and maps it into customized components.

The example post being queried contains a `core/media-text` element with an image on the left and `core/heading` and `core/paragraph` blocks on the right side:

![Screenshot of example media-text post content][media-preact-media-text]

The following code uses the REST API to retrieve post and block metadata and map each block onto a custom component.

```html
<!DOCTYPE html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VIP Block Data API Preact example</title>
</head>

<body></body>

<script type="module">
  import { h, render } from 'https://esm.sh/preact';

  renderPost('https://gutenberg-content-api-test.go-vip.net/wp-json', 55);

  async function renderPost(restUrl, postId) {
    const postResponse = await fetch(`${restUrl}/wp/v2/posts/${postId}`);
    const postTitle = (await postResponse.json())?.title?.rendered;

    const blocksResponse = await fetch(`${restUrl}/vip-block-data-api/v1/posts/${postId}/blocks`);
    const blocks = (await blocksResponse.json())?.blocks;

    const App = Post(postTitle, blocks);
    render(App, document.body);
  }

  function mapBlockToComponent(block) {
    if (block.name === 'core/heading') {
      return Heading(block);
    } else if (block.name === 'core/paragraph') {
      return Paragraph(block);
    } else if (block.name === 'core/media-text') {
      return MediaText(block);
    } else {
      return null;
    }
  }

  /* Components */

  function Post(title, blocks) {
    return h('div', { className: 'post' },
      h('h1', null, title),
      blocks.map(mapBlockToComponent),
    );
  }

  function Heading(props) {
    // Use dangerouslySetInnerHTML for rich text formatting
    return h('h2', { dangerouslySetInnerHTML: { __html: props.attributes.content } });
  }

  function Paragraph(props) {
    // Use dangerouslySetInnerHTML for rich text formatting
    return h('p', { dangerouslySetInnerHTML: { __html: props.attributes.content } });
  }

  function MediaText(props) {
    return h('div', { className: 'media-text' },
      h('div', { className: 'media' },
        h('img', { src: props.attributes.mediaUrl })
      ),
      h('div', { className: 'text' },
        props.innerBlocks ? props.innerBlocks.map(mapBlockToComponent) : null,
      ),
    )
  }
</script>
</html>
```

The code above produces this HTML from post data:

```html
<div class="post">
  <h1>Post with a media-text</h1>

  <div class="media-text">
    <div class="media">
      <img src="https://gutenberg-content-api-test.go-vip.net/.../api.webp?w=1024">
    </div>

    <div class="text">
      <h2>Heading content</h2>
      <p>Paragraph content</p>
    </div>
  </div>
</div>
```

### Block hierarchy reconstruction

The purpose of this function is to take the flattened `innerBlocks` list under each root block, and reconstruct the block hierarchy.

The logic is as follows:

1. Loop through each block.
2. Loop through each block's `innerBlocks`:
   * For each `innerBlock`, check if the `parentId` matches the `id` of the root block.
   * If yes, add that `innerBlock` to a new list.
   * If no, go over the newly constructed list and repeat step 2's logic as the block could be nested under another `innerBlock`.

This logic has been split over two functions, with the core logic (steps 1, 2a, 2b) being in the function below and the recursive case (2c) being handled in the second function called `convertInnerBlocksToHierarchy`.

```js
const blocks = payload.data?.post?.blocksData?.blocks ?? [];

// Iterate over the blocks.
for (const block of blocks) {
  // skip if the innerBlocks are not set.
  if (!block.innerBlocks) {
    continue;
  }

  // Get the innerBlocks.
  const innerBlocks = block.innerBlocks;
  // Create a new array to store the hierarchy.
  let innerBlockHierarchy = [];
  // Iterate over the innerBlocks and use the parentID and ID to reconstruct the hierarchy.
  for (const innerBlock of innerBlocks) {
    // If the innerBlock's parentId matches the block's id, add it to the hierarchy.
    if (innerBlock.parentId === block.id) {
      innerBlockHierarchy.push(innerBlock);
    } else {
      // Otherwise, use the recursive function to find the right parent.
      convertInnerBlocksToHierarchy(innerBlock, innerBlockHierarchy);
    }
  }

  // Add the innerBlockHierarchy to the block.
  block.innerBlocks = innerBlockHierarchy;
}

function convertInnerBlocksToHierarchy( innerBlock, innerBlockHierarchy) {
  for (const innerBlockParent of innerBlockHierarchy) {
    // If the innerBlock's parentId matches the innerBlockParent's id, add it to the hierarchy.
    if (innerBlock.parentId === innerBlockParent.id) {
      innerBlockParent.innerBlocks = innerBlockParent.innerBlocks || [];
      innerBlockParent.innerBlocks.push(innerBlock);
    // If the innerBlockParent has innerBlocks, loop over them and add it under it the right parent.
    } else if (innerBlockParent.innerBlocks) {
      convertInnerBlocksToHierarchy(innerBlock, innerBlockParent.innerBlocks);
    }
  }
}
```

## Limitations

### Client-side blocks

The Block Data API relies on [server-side registered blocks][wordpress-block-metadata-php-registration] to source attributes from HTML. Custom blocks that register via [`register_block_type()`][wordpress-register-block-type-php] and `block.json` will automatically be available in the Block Data API. All Gutenberg core blocks are registered server-side.

Modern blocks are likely to be registered server-side and work immediately with the Block Data API. However, some custom blocks may only use [`registerBlockType()`][wordpress-register-block-type-js] in JavaScript and will not provide server-side registration. For these blocks, some attribute data may be missing. To address this issue, we recommend:

- Creating a `block.json` file for each of your site's custom blocks.
- Using [`register_block_type()`][wordpress-register-block-type-php] with the `block.json` file to expose the block information to the server.

For more information on using `block.json` to enhance block capabilities, [read this WordPress core post][wordpress-block-json-recommendation].

#### Client-side example

For legacy block content or third-party blocks that are not registered server-side, some attributes may still be available through the Block Data API. For example, this is a hero block that is registered *only* in JavaScript:

```js
blocks.registerBlockType('wpvip/hero-block', {
    title: __('Hero Block', 'wpvip'),
    icon: 'index-card',
    category: 'text',
    attributes: {
        title: {
            type: 'string',
            source: 'html',
            selector: 'h2',
        },
        mediaURL: {
            type: 'string',
            source: 'attribute',
            selector: 'img',
            attribute: 'src',
        },
        content: {
            type: 'string',
            source: 'html',
            selector: '.hero-text',
        },
        mediaID: {
            type: 'number',
        }
    }
});
```

The block's output markup will render like this:

```html
<!-- wp:wpvip/hero-block {"mediaID":9} -->
<div class="wp-block-wpvip-hero-block">
    <h2>Hero title</h2>
    <div class="hero-image">
        <img src="http://my.site/uploads/hero-image.png" />
    </div>
    <p class="hero-text">Hero summary</p>
</div>
<!-- /wp:wpvip/hero-block -->
```

Because the block is not registered server-side, the server is unaware of the block's sourced attributes like `title` and `mediaURL`. The Block Data API can only return a subset of the block's attributes:

```js
[{
  "name": "wpvip/hero-block",
  "attributes": {
    "mediaID": 9
  }
}]
```

`mediaID` is stored directly in the block's delimiter (`<!-- wp:wpvip/hero-block {"mediaID":9} -->`), and will be available in the Block Data API. Any other sourced attributes will be missing.

#### Registering client-side blocks

The example above shows block attributes missing on a client-side block. To fix this problem, the block can be changed to register with a `block.json` via [`register_block_type()`][wordpress-register-block-type-php]:

*block.json*
```json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 2,
  "name": "wpvip/hero-block",
  "title": "Hero block",
  "icon": "index-card",
  "category": "text",
  "attributes": {
    "title": {
      "type": "string",
      "source": "html",
      "selector": "h2"
    },
    "mediaURL": {
      "type": "string",
      "source": "attribute",
      "selector": "img",
      "attribute": "src"
    },
    "content": {
      "type": "string",
      "source": "html",
      "selector": ".hero-text"
    },
    "mediaID": {
      "type": "number"
    }
  }
}
```

The `block.json` file is used to register the block both server-side and client-side:

*In PHP plugin code*:

```php
register_block_type( __DIR__ . '/block.json' );
```

*In JavaScript*:

```js
import metadata from './block.json';

registerBlockType( metadata, {
    edit: Edit,
    // ...other client-side settings
} );
```

After server-side registration, the block's full structure is available via the Block Data API:

```js
[{
  "name": "wpvip/hero-block",
  "attributes": {
    "mediaID": 9,
    "title": "Hero title",
    "mediaURL": "http://my.site/uploads/hero-image.png",
    "content": "Hero summary"
  }
}]
```

### Rich text support

Blocks with [`html`-sourced attributes][wordpress-block-attributes-html] can contain HTML rich-text formatting, but that may not always be apparent. For example, this is an image with a basic plain-text caption:

![Image with plain-text caption][media-example-caption-plain]

The image is saved in WordPress with this markup:

```html
<!-- wp:image {"id":17,"sizeSlug":"large","linkDestination":"media"} -->
<figure class="wp-block-image size-large">
  <a href="https://my.site/wp-content/wpvip.jpg">
    <img src="https://my.site/wp-content/wpvip.jpg" alt="" class="wp-image-17"/>
  </a>

  <figcaption class="wp-element-caption">This is a center-aligned image with a caption</figcaption>
</figure>
<!-- /wp:image -->
```

The Block Data API uses the `caption` property definition from [`core/image`'s `block.json` file][gutenberg-code-image-caption]:

```js
"attributes": {
  "caption": {
    "type": "string",
    "source": "html",
    "selector": "figcaption",
    /* ... */
  },
}
```

The sourced caption is returned in the Block Data API:

```js
{
  "name": "core/image",
  "attributes": {
    /* ... */
    "caption": "This is a center-aligned image with a caption",
  }
}
```

Because the `caption` property in this example is plaintext, it seems possible to print the caption to the page safely (e.g. without using `innerHTML` or React's `dangerouslySetInnerHTML`). However, this is not the case and may result in incorrect rendering.

Attributes with the `html` source like the image block caption attribute above can contain plain-text as well as markup.

![Image with rich-text caption][media-example-caption-rich-text]

Retrieving the `caption` through the Block Data API yields this result:

```js
{
  "name": "core/image",
  "attributes": {
    /* ... */
    "caption": "This is a caption with <strong>bold text</strong> <a href=\"https://wpvip.com/\">and a link</a>.",
  }
}
```

`caption` now contains inline HTML. In order to view rich-text formatting in a decoupled component, direct HTML usage with `innerHTML` or `dangerouslySetInnerHTML` is necessary. You could also use the [`vip_block_data_api__sourced_block_result`](#vip_block_data_api__sourced_block_result) filter to remove HTML from attributes. Formatting would be removed as well, but the resulting data may be more flexible.

In the future we are considering providing a rich-text data format so that no direct HTML is required to render blocks correctly. This would improve the flexibility of the Block Data API in non-browser locations such as in native mobile applications. For now, however, some direct HTML is still required to render blocks with rich formatting.

### Deprecated blocks

When core or custom editor blocks are updated to a new version, block attributes can change. This can result in the Block Data API returning a different block structure for the same block type depending on when the post containing a block was authored.

For example, the `core/list` block [was updated in 2022][gutenberg-pr-core-list-innerblocks] from storing list items in the `values` attribute to use `innerBlocks` instead. Before this change, a list with two items was structured like this:

```html
<!-- wp:list -->
<ul>
  <li>List item 1</li>
  <li>List item 2</li>
</ul>
<!-- /wp:list -->
```

The resulting attributes for a `core/list` block pulled from the Block Data API would be structured like this:

```json
{
  "name": "core/list",
  "attributes": {
    "ordered": false,
    "values": "<li>List item 1</li><li>List item 2</li>"
  }
}
```

List items are stored as HTML in the `values` attribute, which is not an ideal structure for mapping to custom components. After the [`core/list` block was updated][gutenberg-pr-core-list-innerblocks] in WordPress, the same two-item list block is represented this way in HTML:

```html
<!-- wp:list -->
<ul>
  <!-- wp:list-item -->
  <li>List item 1</li>
  <!-- /wp:list-item -->

  <!-- wp:list-item -->
  <li>List item 2</li>
  <!-- /wp:list-item -->
</ul>
<!-- /wp:list -->
```

The resulting `core/list` item from the Block Data API parses the list items as `core/list-item` children in `innerBlocks`:

```json
{
  "name": "core/list",
  "attributes": {
    "ordered": false,
    "values": ""
  },
  "innerBlocks": [
    {
      "name": "core/list-item",
      "attributes": {
        "content": "List item 1"
      }
    },
    {
      "name": "core/list-item",
      "attributes": {
        "content": "List item 2"
      }
    }
  ]
}
```

Deprecated blocks can be a tricky problem when using the Block Data API to render multiple versions of the same block. A `core/list` block from a post in 2021 has a different data shape than a `core/list` block created in 2023. Consumers of the API need to be aware of legacy block structures in order to implement custom frontend components. This issue applies to custom blocks as well; if a block has legacy markup saved in the database, this can result in legacy block representation in the Block Data API.

We are considering ways to mitigate this problem for consumers of the API, such as [implementing server-side block deprecation rules][wordpress-block-deprecation] or providing type structures to represent legacy block data shapes. For now, ensure that Block Data API consumers test against older content to ensure that legacy block versions used in content are covered by code.

## Rest API Query Parameters

These query parameters can be passed in the REST API to filter the results of the Block Data API. The example post below will be used to demonstrate the filters:

### Example Post

```html
<!-- wp:heading -->
<h2>Heading 1</h2>
<!-- /wp:heading -->

<!-- wp:quote -->
<blockquote class="wp-block-quote">
  <!-- wp:paragraph -->
  <p>Text in quote</p>
  <!-- /wp:paragraph -->
  <cite>~ Citation, 2023</cite>
</blockquote>
<!-- /wp:quote -->
```

### `include`

Limit the types of blocks that will be returned by the Block Data API. This can be useful for providing an allowed list of supported blocks, and skipping the contents of all other blocks. Multiple block types can be specified using commas, e.g. `?include=core/heading,core/paragraph`.

Example: using the [post data above](#example-post) with `?include=core/heading`, only return `core/heading` blocks in the output:

```js
GET /wp-json/vip-block-data-api/v1/posts/<post_id>/blocks?include=core/heading
```

```json
{
  "blocks": [
    {
      "name": "core/heading",
      "attributes": {
        "content": "Heading 1",
        "level": 2
      }
    }
  ]
}
```

This query parameter cannot be used at the same time as [the `exclude` query parameter](#exclude).

Note that custom block filter rules can also be created in code via [the `vip_block_data_api__allow_block` filter](#vip_block_data_api__allow_block).

---

### `exclude`

Exclude some block types from the Block Data API. This can be useful for providing a block list of unsupported blocks and skipping those in REST API output. Multiple block types can be specified using commas, e.g. `?exclude=core/heading,core/paragraph`.

Example: using the [post data above](#example-post) with `?exclude=core/heading`, skip `core/heading` blocks in the output:

```js
GET /wp-json/vip-block-data-api/v1/posts/<post_id>/blocks?exclude=core/heading
```

```json
{
  "blocks": [
    {
      "name": "core/quote",
      "attributes": {
        "value": "",
        "citation": "Citation, 2023"
      },
      "innerBlocks": [
        {
          "name": "core/paragraph",
          "attributes": {
            "content": "Text in quote",
            "dropCap": false
          }
        }
      ]
    }
  ]
}
```

This query parameter cannot be used at the same time as [the `include` query parameter](#include).

Note that custom block filter rules can also be created in code via [the `vip_block_data_api__allow_block` filter](#vip_block_data_api__allow_block).

## Code Filters

### GraphQL

By default, the VIP Block Data API enables GraphQL integration automatically if WPGraphQL is activated. To disable this behavior, use the `vip_block_data_api__is_graphql_enabled` filter:

```php
// Disable GraphQL integration
add_filter( 'vip_block_data_api__is_graphql_enabled', '__return_false', 10, 1 );
```

### REST

These filters can be applied to limit access to the REST API and modify the output of parsed blocks.

### `vip_block_data_api__rest_validate_post_id`

Limit which post IDs are valid in the REST API. By default, posts that are available via the [WordPress `/posts` REST API][wordpress-rest-api-posts] are queryable.

```php
/**
 * Validates that a post can be queried via the Block Data API REST endpoint.
 * Return false to disable access to a post.
 *
 * @param boolean $is_valid Whether the post ID is valid for querying. Defaults to true
 *                          when a post is available via the WordPress REST API.
 * @param int     $post_id  The queried post ID.
 */
return apply_filters( 'vip_block_data_api__rest_validate_post_id', $is_valid, $post_id );
```

For example, this filter can be used to allow only pages that are published to be available:

```php
add_filter( 'vip_block_data_api__rest_validate_post_id', function( $is_valid, $post_id ) {
    // Only allow published pages
    return 'page' === get_post_type( $post_id ) && 'publish' === get_post_status( $post_id );
}, 10, 2);
```

---

### `vip_block_data_api__rest_permission_callback`

Limit Block Data API access to specific users or roles.

```php
/**
 * Validates that a request can access the Block Data API. This filter can be used to
 * limit access to authenticated users.
 * Return false to disable access.
 *
 * @param boolean $is_permitted Whether the request is permitted. Defaults to true.
 */
return apply_filters( 'vip_block_data_api__rest_permission_callback', true );
```

**Warning**: Authenticated requests to the Block Data API will bypass WPVIP's built-in REST API caching. Review [**Caching on WPVIP**](#caching-on-wpvip) for more information.

By default no authentication is required, as posts must be published to be available on the Block Data API. If limited access is desired (e.g. [via Application Password credentials][wordpress-application-passwords]) this filter can be used to check user permissions:

```php
add_filter( 'vip_block_data_api__rest_permission_callback', function( $is_permitted ) {
    // Require authenticated user access with 'publish_posts' permission
    return current_user_can( 'publish_posts' );
});
```

---

### `vip_block_data_api__allow_block`

Filter out blocks from the output of the Block Data API. This is a server-side alternative to the [`include`](#include) and [`exclude`](#exclude) query parameters.

```php
/**
 * Filter out blocks from the blocks output
 *
 * @param bool   $is_block_included True if the block should be included, or false to filter it out.
 * @param string $block_name    The name of the parsed block, e.g. 'core/paragraph'.
 * @param string $block         The result of parse_blocks() for this block.
 *                              Contains 'blockName', 'attrs', 'innerHTML', and 'innerBlocks' keys.
 */
apply_filters( 'vip_block_data_api__allow_block', $is_block_included, $block_name, $block );
```

This is useful for restricting types of blocks returned from the Block Data API. Blocks that are disallowed by this filter will be removed from `innerBlocks` of other blocks as well.

This filter can be used to create a server-side deny list. In the example below, `core/quote` blocks are fully removed from the Block Data API output:

```php
add_filter( 'vip_block_data_api__allow_block', 'custom_allow_block', 10, 3 );

function custom_allow_block( $is_block_included, $block_name, $block ) {
    if ( 'core/quote' === $block_name ) {
        return false;
    }

    // Use $is_block_included result to allow additional filtering by query parameters
    return $is_block_included;
};
```

This filter can also be used to create a server-side allowlist. In the example below, we only want to return `core/heading` and `core/paragraphs` blocks from the Block Data API:

```php
add_filter( 'vip_block_data_api__allow_block', 'add_allowlist', 10, 3 );

function add_allowlist( $is_block_included, $block_name, $block ) {
    if ( 'core/paragraph' === $block_name || 'core/heading' === $block_name ) {
        // Use $is_block_included result to allow additional filtering by query parameters
        return $is_block_included;
    }

    return false;
};
```

Note that this filter is evaluated after the [`include`](#include) and [`exclude`](#exclude) query parameters, which means that the filter's result takes precedence. If a block type is disallowed by this filter, query parameters will not be able to override the behavior.

---

### `vip_block_data_api__sourced_block_result`

Modify or add attributes to a block's output in the Block Data API.

```php
/**
 * Filters a block when parsing is complete.
 *
 * @param array  $sourced_block An associative array of parsed block data with keys 'name' and 'attributes'.
 * @param string $block_name    The name of the parsed block, e.g. 'core/paragraph'.
 * @param string $post_id       The post ID associated with the parsed block.
 * @param string $block         The result of parse_blocks() for this block.
 *                              Contains 'blockName', 'attrs', 'innerHTML', and 'innerBlocks' keys.
 */
$sourced_block = apply_filters( 'vip_block_data_api__sourced_block_result', $sourced_block, $block_name, $post_id, $block);
```

This is useful when block rendering requires attributes stored in post metadata or outside of a block's markup. This filter can be used to add attributes to any core or custom block. For example:

```php
add_filter( 'vip_block_data_api__sourced_block_result', 'add_custom_block_metadata', 10, 4 );

function add_custom_block_metadata( $sourced_block, $block_name, $post_id, $block ) {
    if ( 'wpvip/my-custom-block' !== $block_name ) {
        return $sourced_block;
    }

    // Add custom attribute to REST API result
    $sourced_block['attributes']['custom-attribute-name'] = 'custom-attribute-value';

    return $sourced_block;
}
```

Direct block HTML can be accessed through `$block['innerHTML']`. This may be useful if manual HTML parsing is necessary to gather data from a block.

For another example of how this filter can be used to extend block data, we have implemented a default image block filter in [`src/parser/block-additions/core-image.php`][repo-core-image-block-addition]. This filter is automatically called on `core/image` blocks to add `width` and `height` to image attributes.

## Analytics

**Please note that, this is for VIP sites only. Analytics are disabled if this plugin is not being run on VIP sites.**

The plugin records two data points for analytics, on VIP sites:

1. A usage metric when the `/wp-json/vip-block-data-api` REST API is used to retrive block data. This analytic data simply is a counter, and includes no information about the post's content or metadata. It will only include the customer site ID to associate the usage.

2. When an error occurs from within the plugin on the [WordPress VIP][wpvip] platform. This is used to identify issues with customers for private follow-up.

Both of these data points are a counter that is incremented, and do not contain any other telemetry or sensitive data. You can see what's being [collected in code here][repo-analytics], and WPVIP's privacy policy [here](https://wpvip.com/privacy/).

In addition, the analytics are sent every 10 seconds only.

## Caching on WPVIP

All requests to the Block Data API on WPVIP will automatically be cached for 1 minute. Be aware that authenticated requests will bypass this cache, so be very cautious when using the [REST permissions filter](#vip_block_data_api__rest_permission_callback).

More information about WPVIP's caching [can be found here][wpvip-page-cache].

## Errors and Warnings

### Error: `vip-block-data-api-no-blocks`

The VIP Block Data API is designed to parse structured block data, and can not read content from WordPress before the release of Gutenberg in [WordPress 5.0][wordpress-release-5-0] or created using the [classic editor plugin][wordpress-plugin-classic-editor]. If the parser encounters post content that does not contain block data, this error will be returned with an HTTP `400` response code:

```js
{
  "code": "vip-block-data-api-no-blocks",
  "message": "Error parsing post ID ...: This post does not appear to contain block content. The VIP Block Data API is designed to parse Gutenberg blocks and can not read classic editor content."
}
```

### Error: `vip-block-data-api-parser-error`

If any unexpected errors are encountered during block parsing, the block API will return error data with an HTTP `500` response code:

```js
{
  "code": "vip-block-data-api-parser-error",
  "message": "..."
}
```

The full stack trace for the error will be available in the site's logs:

```
[29-Mar-2023 07:42:58 UTC] PHP Warning: vip-block-data-api (<version>): Exception: ...
Stack trace:
#0 ...
```

If you encounter an error, we would really appreciate it if you could [create a bug report][repo-issue-create] so that we can understand and fix the issue.

### Warning: Unregistered block type

The Block Data API requires blocks to be [server-side registered][wordpress-block-metadata-php-registration] in order to return full block attributes. When the plugin encounters post content containing a block that is not registered, a warning will be returned with block data:

```js
{
  "blocks": [{
    "name": "wpvip/client-side-block",
    "attributes": { /* ... */ }
  }],
  "warnings": [
      "Block type 'wpvip/client-side-block' is not server-side registered. Sourced block attributes will not be available."
  ]
}
```

These warnings indicate blocks that are missing from the server-side registry. Review the **[Client-side blocks](#client-side-blocks)** section for information about this limitation, which attributes will be accessible in client-side blocks, and recommendations for registering custom blocks server-side.

## Development

In order to ensure no dev dependencies go in, the following can be done while installing the packages:

```
composer install --no-dev
```

### Tests

Run tests locally with [`wp-env`][wp-env] and Docker:

```
wp-env start
composer install
composer run test
```

<!-- Links -->
[gutenberg-code-image-caption]: https://github.com/WordPress/gutenberg/blob/3d2a6d7eaa4509c4d89bde674e9b73743868db2c/packages/block-library/src/image/block.json#L30-L35
[gutenberg-pr-core-list-innerblocks]: https://href.li/?https://github.com/WordPress/gutenberg/pull/39487
[media-example-caption-plain]: https://github.com/Automattic/vip-block-data-api/blob/media/example-caption-plain.png
[media-example-caption-rich-text]: https://github.com/Automattic/vip-block-data-api/blob/media/example-caption-rich-text.png
[media-example-heading-paragraph]: https://github.com/Automattic/vip-block-data-api/blob/media/example-header-paragraph.png
[media-example-list-quote]: https://github.com/Automattic/vip-block-data-api/blob/media/example-utility-quote-list.png
[media-example-media-text]: https://github.com/Automattic/vip-block-data-api/blob/media/example-media-text.png
[media-example-pullquote]: https://github.com/Automattic/vip-block-data-api/blob/media/example-pullquote.png
[media-example-utility-quote-list]: https://github.com/Automattic/vip-block-data-api/blob/media/example-list-quote.png
[media-plugin-activate]: https://github.com/Automattic/vip-block-data-api/blob/media/plugin-activate.png
[media-preact-media-text]: https://github.com/Automattic/vip-block-data-api/blob/media/preact-media-text.png
[preact]: https://preactjs.com
[repo-analytics]: src/analytics/analytics.php
[repo-core-image-block-addition]: src/parser/block-additions/core-image.php
[repo-issue-create]: https://github.com/Automattic/vip-block-data-api/issues/new/choose
[repo-releases]: https://github.com/Automattic/vip-block-data-api/releases
[vip-go-mu-plugins]: https://github.com/Automattic/vip-go-mu-plugins/
[vip-go-skeleton-plugin-loader-example]: https://github.com/Automattic/vip-go-skeleton/blob/ce21ab0/client-mu-plugins/plugin-loader.php
[wordpress-application-passwords]: https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/
[wordpress-block-attributes-html]: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-attributes/#html-source
[wordpress-block-deprecation]: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-deprecation/
[wordpress-block-json-recommendation]: https://make.wordpress.org/core/2021/06/23/block-api-enhancements-in-wordpress-5-8/
[wordpress-block-metadata-php-registration]: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#php-server-side
[wordpress-plugin-classic-editor]: https://wordpress.org/plugins/classic-editor/
[wordpress-register-block-type-js]: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/#registerblocktype
[wordpress-register-block-type-php]: https://developer.wordpress.org/reference/functions/register_block_type/
[wordpress-release-5-0]: https://wordpress.org/documentation/wordpress-version/version-5-0/
[wordpress-rest-api-posts]: https://developer.wordpress.org/rest-api/reference/posts/
[wp-env]: https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/
[wpgraphql-install]: https://www.wpgraphql.com/docs/quick-start#install
[wpvip-mu-plugins-block-data-api]: https://docs.wpvip.com/technical-references/vip-go-mu-plugins/block-data-api-plugin/
[wpvip-page-cache]: https://docs.wpvip.com/technical-references/caching/page-cache/
[wpvip-plugin-activate]: https://docs.wpvip.com/how-tos/activate-plugins-through-code/
[wpvip-plugin-submodules]: https://docs.wpvip.com/technical-references/plugins/installing-plugins-best-practices/#h-submodules
[wpvip-plugin-subtrees]: https://docs.wpvip.com/technical-references/plugins/installing-plugins-best-practices/#h-subtrees
[wpvip]: https://wpvip.com/
