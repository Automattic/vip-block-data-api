# VIP Block Data API

<picture>
    <source srcset="https://github.com/Automattic/vip-block-data-api/blob/media/vip-block-data-api-animation-1660.gif" media="(-webkit-min-device-pixel-ratio: 2.0)" />
    <source srcset="https://github.com/Automattic/vip-block-data-api/blob/media/vip-block-data-api-animation-830.gif" media="(-webkit-min-device-pixel-ratio: 1.0)" />
    <img src="https://github.com/Automattic/vip-block-data-api/blob/media/vip-block-data-api-animation-830.gif" alt="VIP Block Data API attribute sourcing animation" />
</picture>

A REST API to retrieve block editor posts structured as JSON data. While primarily designed for use in decoupled WordPress, the block data API can be used anywhere you want to represent block markup as structured data.

## Quickstart

You can get started with the Block Data API in less than five minutes.

 1. Install the plugin, we recommend using a [git subtree](#install-via-git-subtree).
 2. Activate the plugin
 3. Make a request to `/wp-json/vip-block-data-api/v1/posts/<post_id>/blocks`
 
Read on for other installation options, examples, and helpful filters that you can use to customize the API to your particular use case.

## Table of contents

- [Installation](#installation)
	- [Install via `git subtree`](#install-via-git-subtree)
	- [Install via ZIP file](#install-via-zip-file)
	- [Plugin activation](#plugin-activation)
- [Usage](#usage)
- [Block Data API Examples](#block-data-api-examples)
	- [Basic text blocks: `core/heading` and `core/paragraph`](#basic-text-blocks-coreheading-and-coreparagraph)
	- [Text attributes in `core/pullquote`](#text-attributes-in-corepullquote)
	- [Nested blocks in `core/media-text`](#nested-blocks-in-coremedia-text)
- [Preact Example](#preact-example)
- [Limitations](#limitations)
	- [Client-side blocks](#client-side-blocks)
		- [Client-side example](#client-side-example)
		- [Registering client-side blocks](#registering-client-side-blocks)
	- [Rich text support](#rich-text-support)
	- [Deprecated blocks](#deprecated-blocks)
- [Filters](#filters)
	- [`vip_block_data_api__rest_validate_post_id`](#vip_block_data_api__rest_validate_post_id)
	- [`vip_block_data_api__rest_permission_callback`](#vip_block_data_api__rest_permission_callback)
	- [`vip_block_data_api__sourced_block_result`](#vip_block_data_api__sourced_block_result)
	- [Block additions](#block-additions)
		- [Custom block additions](#custom-block-additions)
- [Development](#development)
	- [Tests](#tests)

## Installation

The latest version of the VIP Block Data API plugin is available in the default `release` branch of this repository.

### Install via `git subtree`

We recommend installing the latest plugin version [via `git subtree`][wpvip-plugin-subtrees] within your site's repository:

```bash
# Enter your project's root directory:
cd my-site/

# Add a subtree for the release branch:
git subtree add --prefix plugins/vip-block-data-api git@github.com:Automattic/vip-block-data-api.git release --squash
```

To deploy the plugin to a remote branch, `git push` the committed subtree.

The `release` branch will stay up to date with the latest released version of the plugin. Use this command to pull the latest `release` branch changes:

```bash
git subtree pull --prefix plugins/vip-block-data-api git@github.com:Automattic/vip-block-data-api.git release --squash
```

**BETA**: We anticipate frequent updates to the block data API plugin during beta testing. Please ensure the plugin is up-to-date by pulling changes often.

Note: We **do not recommend** using `git submodule` as [submodules that require authentication][wpvip-plugin-submodules] will fail to deploy.

### Install via ZIP file

The latest version of the plugin can be found on the [repository's Releases page][repo-releases] and unzipped into your site's `plugins/` folder.

### Plugin activation

Once the VIP Block Data API plugin is available in your site's plugins, follow these steps to activate the plugin:

1. Go to the WordPress admin panel
2. Select the **Plugins** page from the sidebar
3. Locate the "VIP Block Data API" plugin and click the "Activate" link below it:

    ![Plugin activation][media-plugin-activate]
    
The standard practice is [activate plugins with code][wpvip-plugin-activate]. We are not recommending that here so that the plugin can be easily enabled and disabled durring testing.

## Usage

The VIP Block Data API plugin provides a REST endpoint for reading post block data as JSON. The REST URL is located at:

```js
/wp-json/vip-block-data-api/v1/posts/<post_id>/blocks

// e.g. https://my-site.com/wp-json/vip-block-data-api/v1/posts/139/blocks
```

This public endpoint will return editor block metadata as structured JSON for any published post, page, or published `WP_Post` object.

For more information on limiting access to the REST endpoint, see these [**Filters**](#filters) below:

- [`vip_block_data_api__rest_validate_post_id`](#vip_block_data_api__rest_validate_post_id)
- [`vip_block_data_api__rest_permission_callback`](#vip_block_data_api__rest_permission_callback)

The block data API [uses server-side registered blocks][wordpress-block-metadata-php-registration] to determine block attributes. See the **[Client-side blocks](#client-side-blocks)** section for more information about client-side block support limitations.

## Block Data API Examples

This section provides examples of WordPress block markup, and the associated data structure returned by the block data API.

### Basic text blocks: `core/heading` and `core/paragraph`

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

### Text attributes in `core/pullquote`

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

### Nested blocks in `core/media-text`

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

## Preact Example

This section contains an example [Preact app][preact] app that queries for block data and maps into customized components.

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

## Limitations

### Client-side blocks

The block data API relies on [server-side registered blocks][wordpress-block-metadata-php-registration] to source attributes from HTML. Custom blocks that register via [`register_block_type()`][wordpress-register-block-type-php] and `block.json` will automatically be available in the block data API. All Gutenberg core blocks are registered server-side.

Modern blocks are likely to be registered server-side and work immediately with the block data API. However, some custom blocks may only use  [`registerBlockType()`][wordpress-register-block-type-js] in JavaScript and not provide server-side registration. For these blocks, some attribute data may be missing. We recommend:

- Creating a `block.json` file for each of your site's custom blocks.
- Using [`register_block_type()`][wordpress-register-block-type-php] with the `block.json` file to expose the block information to the server.

For more information on using `block.json` to enhance block capabilities, [see this WordPress core post][wordpress-block-json-recommendation].

#### Client-side example

For legacy block content or third-party blocks that are not registered server-side, some attributes may still be available through the block data API. For example, here is a hero block that is registered *only* in JavaScript:

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

The block's output markup looks like this:

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

Since the block is not registered server-side, the server is unaware of the block's sourced attributes like `title` and `mediaURL`. The block data API can only return a subset of the block's attributes:

```js
[{
  "name": "wpvip/hero-block",
  "attributes": {
    "mediaID": 9
  }
}]
```

`mediaID` is stored directly in the block's delimiter (`<!-- wp:wpvip/hero-block {"mediaID":9} -->`), and will be available in the block data API. Any other sourced attributes will be missing.

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

After server-side registration, the block's full structure is available via the block data API:

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

Blocks with [`html`-sourced attributes][wordpress-block-attributes-html] can contain HTML rich-text formatting, but that may not always be apparent. For example, here is an image with a basic plain-text caption:

![Image with plain-text caption][media-example-caption-plain]

This is saved in WordPress with this markup:

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

The block data API uses the `caption` property definition from [`core/image`'s `block.json` file][gutenberg-code-image-caption]:

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

The sourced caption is returned in the block data API:

```js
{
  "name": "core/image",
  "attributes": {
    /* ... */
    "caption": "This is a center-aligned image with a caption",
  }
}
```

The `caption` property in this example is plain-text, so it seems possible to print the caption to the page safely, e.g. without using `innerHTML` or React's `dangerouslySetInnerHTML`. However, this isn't the case and may result in incorrect rendering.

Attributes with the `html` source like the image block caption attribute above can contain plan text as well as markup:

![Image with rich-text caption][media-example-caption-rich-text]

Retrieving the `caption` through the block data API yields this result:

```js
{
  "name": "core/image",
  "attributes": {
    /* ... */
    "caption": "This is a caption with <strong>bold text</strong> <a href=\"https://wpvip.com/\">and a link</a>.",
  }
}
```

`caption` now contains inline HTML. In order to view rich-text formatting in a decoupled component, direct HTML usage with `innerHTML` or `dangerouslySetInnerHTML` are necessary. You could also use the [`vip_block_data_api__sourced_block_result`](#vip_block_data_api__sourced_block_result) filter to remove HTML from attributes. Formatting would be removed as well, but the resulting data would be more flexible.

In the future we're considering providing a rich-text data format so that no direct HTML is required to render blocks correctly. This would improve the flexibility of the block data API in non-browser locations such as in native mobile applications. For now, however, some direct HTML is still required to render blocks with rich formatting.

### Deprecated blocks

When core or custom editor blocks are updated to a new version, block attributes can change. This can result in the block data API returning a different block structure for the same block type depending on when the post containing a block was authored.

For example, the `core/list` block [was updated in 2022][gutenberg-pr-core-list-innerblocks] from storing list items in the `values` attribute to use `innerBlocks` instead. Before this change, a list with two items was structured like this:

```html
<!-- wp:list -->
<ul>
  <li>List item 1</li>
  <li>List item 2</li>
</ul>
<!-- /wp:list -->
```

The resulting attributes for a `core/list` block pulled from the block data API would look like this:

```json
{
  "name": "core/list",
  "attributes": {
    "ordered": false,
    "values": "<li>List item 1</li><li>List item 2</li>"
  }
}
```

List items are stored as HTML in the `values` attribute, which isn't an ideal structure for mapping to custom components. After the [`core/list` block was updated][gutenberg-pr-core-list-innerblocks] in WordPress, the same two-item list block is represented this way in HTML:

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

The resulting `core/list` item from the block data API parses the list items as `core/list-item` children in `innerBlocks`:

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

Deprecated blocks can be a tricky problem when using the block data API to render multiple versions of the same block. A `core/list` block from a post in 2021 has a different data shape than a `core/list` block created in 2023. Consumers of the API need to be aware of legacy block structures in order to implement custom frontend components. This issue applies to custom blocks as well: If any block has legacy markup saved in the database, this can result in legacy block representation in the block data API.

We're considering ways to mitigate this problem for consumers of the API, such as [implementing server-side block deprecation rules][wordpress-block-deprecation] or providing type structures to represent legacy block data shapes. For now, ensure block data API consumers test against older content to ensure that legacy block versions used in content are covered by code.

## Filters

The block data API provides filters to limit access to the REST API and change the output of parsed blocks.

---

### `vip_block_data_api__rest_validate_post_id`

Used to limit which post IDs are valid in the REST API. By default, all posts with `post_status` set to `publish` are valid.

```php
/**
 * Validates a post can be queried via the block data API REST endpoint.
 * Return false to disable access to a post.
 *
 * @param boolean $is_valid Whether the post ID is valid for querying.
 *                          Defaults to true when post status is 'publish'.
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

Use this filter to limit block data API access to specific users or roles.

```php
/**
 * Validates a request can access the block data API. This filter can be used to
 * limit access to authenticated users.
 * Return false to disable access.
 *
 * @param boolean $is_permitted Whether the request is permitted. Defaults to true.
 */
return apply_filters( 'vip_block_data_api__rest_permission_callback', true );
```

By default no authentication is required, as posts must be in a `publish` state to be queried. If limited access is desired, e.g. [via Application Password credentials][wordpress-application-passwords], use this filter to check user permissions:

```php
add_filter( 'vip_block_data_api__rest_permission_callback', function( $is_permitted ) {
    // Require authenticated user access with 'publish_posts' permission
    return current_user_can( 'publish_posts' );
});
```

---

### `vip_block_data_api__sourced_block_result`

Used to modify and add additional attribute data to a block's output in the block data API

```php
/**
 * Filters a block when parsing is complete.
 *
 * @param array  $sourced_block An associative array of parsed block data with keys 'name' and 'attribute'.
 * @param string $block_name    The name of the parsed block, e.g. 'core/paragraph'.
 * @param string $post_id       The post ID associated with the parsed block.
 * @param string $block         The result of parse_blocks() for this block.
 *                              Contains 'blockName', 'attrs', 'innerHTML', and 'innerBlocks' keys.
 */
$sourced_block = apply_filters( 'vip_block_data_api__sourced_block_result', $sourced_block, $block_name, $post_id, $block );
```

This is useful when a block requires attributes stored in post metadata or outside of a block's markup. See the section below for an example.

### Block additions

The `core/image` block uses the `vip_block_data_api__sourced_block_result` filter to add `width` and `height` attributes to the block data API output in [`parser/block-additions/core-image.php`][repo-core-image-block-addition].

For example, this is Gutenberg markup for a `core/image` block:

```html
<!-- wp:image {"id":191,"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large">
    <img src="https://my.site/wp-content/uploads/2023/header.jpg" alt="" class="wp-image-191"/>
</figure>
<!-- /wp:image -->
```

After being parsed by the block data API, these attributes are sourced from the `core/image` block:

```js
{
    "name": "core/image",
    "attributes": {
        "id": 191,
        "sizeSlug": "large",
        "linkDestination": "none",
        "url": "https://my.site/wp-content/uploads/2023/header.jpg",
    }
}
```

Some frontend JavaScript frameworks require image dimensions for responsive images. These are not available by default, as they are not present in `core/image` markup. The [`core/image` block addition][repo-core-image-block-addition] filter is used to include `width` and `height` in the result:

```js
{
    "name": "core/image",
    "attributes": {
        "id": 191,
        "sizeSlug": "large",
        "linkDestination": "none",
        "url": "https://my.site/wp-content/uploads/2023/header.jpg",
        "width": 1024, /* Added by filter */
        "height": 683  /* Added by filter */
    }
}
```

This filter is automatically called on image blocks.

#### Custom block additions

In addition to built-in Gutenberg blocks, the `vip_block_data_api__sourced_block_result` filter can be used with custom blocks to add attributes in PHP:

```php
add_filter( 'vip_block_data_api__sourced_block_result', 'add_custom_block_metadata', 10, 4 );

function add_custom_block_metadata( $sourced_block, $block_name, $post_id, $block ) {
    if ( 'vip/my-custom-block' !== $block_name ) {
        return $sourced_block;
    }

    $sourced_block['attributes']['custom-attribute-name'] = 'custom-attribute-value';

    return $sourced_block;
}
```

Direct block HTML can be accessed through `$block['innerHTML']`. This may be useful if manual HTML parsing is necessary to gather data from a block.


## Development

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
[media-preact-media-text]: https://github.com/Automattic/vip-block-data-api/blob/media/preact-media-text.png
[media-example-caption-rich-text]: https://github.com/Automattic/vip-block-data-api/blob/media/example-caption-rich-text.png
[media-example-heading-paragraph]: https://github.com/Automattic/vip-block-data-api/blob/media/example-header-paragraph.png
[media-example-media-text]: https://github.com/Automattic/vip-block-data-api/blob/media/example-media-text.png
[media-example-pullquote]: https://github.com/Automattic/vip-block-data-api/blob/media/example-pullquote.png
[media-plugin-activate]: https://github.com/Automattic/vip-block-data-api/blob/media/plugin-activate.png
[media-title-animation]: https://github.com/Automattic/vip-block-data-api/blob/media/vip-block-data-api-animation.gif
[repo-core-image-block-addition]: parser/block-additions/core-image.php
[repo-releases]: https://github.com/Automattic/vip-block-data-api/releases
[wordpress-application-passwords]: https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/
[wordpress-block-attributes-html]: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-attributes/#html-source
[wordpress-block-deprecation]: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-deprecation/
[wordpress-block-json-recommendation]: https://make.wordpress.org/core/2021/06/23/block-api-enhancements-in-wordpress-5-8/
[wordpress-block-metadata-php-registration]: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#php-server-side
[wordpress-register-block-type-js]: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/#registerblocktype
[wordpress-register-block-type-php]: https://developer.wordpress.org/reference/functions/register_block_type/
[wp-env]: https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/
[wpvip-plugin-submodules]: https://docs.wpvip.com/technical-references/plugins/installing-plugins-best-practices/#h-submodules
[wpvip-plugin-subtrees]: https://docs.wpvip.com/technical-references/plugins/installing-plugins-best-practices/#h-subtrees
[preact]: https://preactjs.com
[wpvip-plugin-activate]: https://docs.wpvip.com/how-tos/activate-plugins-through-code/
