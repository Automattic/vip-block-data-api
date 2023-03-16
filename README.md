# VIP Block Data API

![VIP Block Data API title animation][media-title-animation]

A REST API to retrieve block editor posts structured as JSON data. While primarily designed for use in decoupled WordPress, the block data API can be used in a variety of places to represent block markup as structured data.

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
- [Placeholder: React example](#placeholder-react-example)
- [Limitations](#limitations)
	- [Client-side blocks](#client-side-blocks)
		- [Client-side example](#client-side-example)
		- [Registering client-side blocks](#registering-client-side-blocks)
	- [Rich text support](#rich-text-support)
	- [Placeholder: Deprecated block structures (e.g. core/list-item)](#placeholder-deprecated-block-structures-eg-corelist-item)
- [Filters](#filters)
	- [`vip_block_data_api__rest_validate_post_id`](#vip_block_data_api__rest_validate_post_id)
	- [`vip_block_data_api__rest_permission_callback`](#vip_block_data_api__rest_permission_callback)
	- [`vip_block_data_api__sourced_block_result`](#vip_block_data_api__sourced_block_result)
	- [Block additions](#block-additions)
		- [Custom block additions](#custom-block-additions)
- [Development](#development)
	- [Tests](#tests)

## Installation

The latest version of the VIP Block Data API plugin is available in the `release` branch of this repository.

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

Note: we do not recommend using `git submodule` as [submodules that require authentication][wpvip-plugin-submodules] will fail to deploy.

### Install via ZIP file

The latest version of the plugin can be found on the [repository's Releases page][repo-releases] and unzipped into your site's `plugins/` folder.

### Plugin activation

Once the VIP Block Data API plugin is available in your site's plugins, follow these steps to activate the plugin:

1. Go to the WordPress admin panel
2. Select the **Plugins** page from the sidebar
3. Locate the "VIP Block Data API" plugin and click the "Activate" link below it:

    ![Plugin activation][media-plugin-activate]

## Usage

The VIP Block Data API plugin provides a REST endpoint for reading post block data as JSON. The REST URL is located at:

```js
/wp-json/vip-block-data-api/v1/posts/<post_id>/blocks

// e.g. https://my-site.com/wp-json/vip-block-data-api/v1/posts/139/blocks
```

This public endpoint will return editor block metadata as structured JSON for any published post, page, or other `WP_Post` object. For more information on limiting access to the REST endpoint, see [`vip_block_data_api__rest_validate_post_id`](#vip_block_data_api__rest_validate_post_id) and [`vip_block_data_api__rest_permission_callback`](#vip_block_data_api__rest_permission_callback) in the [Filters](#filters) section below.

The block data API [uses server-side registered blocks][wordpress-block-metadata-php-registration] to determine block attributes. See the [Client-side blocks](#client-side-blocks) section for more information about client-side block support limitations.

## Block Data API Examples

### Basic text blocks: `core/heading` and `core/paragraph`

<table>
<tr>
<td>Gutenberg Markup</td>
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

### Text attributes in `core/pullquote`

<table>
<tr>
<td>Gutenberg Markup</td>
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

### Nested blocks in `core/media-text`

<table>
<tr>
<td>Gutenberg Markup</td>
<td>Block Data API</td>
</tr>
<tr>
<td>

```html
<!-- wp:media-text {"mediaId":256,
  "mediaType":"image"} -->
<div class="wp-block-media-text">
  <figure class="wp-block-media-text__media">
    <img src="http://my.site/image.jpg"
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
    "mediaUrl": "http://my.site/image.jpg",
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

## Placeholder: React example

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

### Placeholder: Deprecated block structures (e.g. core/list-item)

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

For example, this filter can be used to allow only published `page` types to be available:

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
[media-plugin-activate]: https://github.com/Automattic/vip-block-data-api/blob/media/plugin-activate.png
[media-title-animation]: https://github.com/Automattic/vip-block-data-api/blob/media/vip-block-data-api-animation.gif
[repo-core-image-block-addition]: parser/block-additions/core-image.php
[repo-releases]: https://github.com/Automattic/vip-block-data-api/releases
[wordpress-application-passwords]: https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/
[wordpress-block-metadata-php-registration]: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#php-server-side
[wordpress-register-block-type-php]: https://developer.wordpress.org/reference/functions/register_block_type/
[wordpress-register-block-type-js]: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/#registerblocktype
[wordpress-block-json-recommendation]: https://make.wordpress.org/core/2021/06/23/block-api-enhancements-in-wordpress-5-8/
[wp-env]: https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/
[wpvip-plugin-submodules]: https://docs.wpvip.com/technical-references/plugins/installing-plugins-best-practices/#h-submodules
[wpvip-plugin-subtrees]: https://docs.wpvip.com/technical-references/plugins/installing-plugins-best-practices/#h-subtrees
