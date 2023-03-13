# VIP Content API

A REST API to retrieve Gutenberg editor blocks structured as JSON. This plugin is designed for use in decoupled WordPress.

## Table of contents

- [Placeholder: Installation](#placeholder-installation)
- [Placeholder: Usage](#placeholder-usage)
- [Placeholder: React example](#placeholder-react-example)
- [Content API Examples](#content-api-examples)
	- [Basic text blocks: `core/heading` and `core/paragraph`](#basic-text-blocks-coreheading-and-coreparagraph)
	- [Text attributes in `core/pullquote`](#text-attributes-in-corepullquote)
	- [Nested blocks in `core/media-text`](#nested-blocks-in-coremedia-text)
- [Filters](#filters)
	- [`vip_content_api__rest_validate_post_id`](#vip_content_api__rest_validate_post_id)
	- [`vip_content_api__rest_permission_callback`](#vip_content_api__rest_permission_callback)
	- [`vip_content_api__sourced_block_result`](#vip_content_api__sourced_block_result)
	- [Block additions](#block-additions)
		- [Custom block additions](#custom-block-additions)
- [Limitations](#limitations)
	- [Placeholder: Client-side block support + delimiter attributes](#placeholder-client-side-block-support--delimiter-attributes)
	- [Placeholder: Deprecated block strcutures (e.g. core/list-item)](#placeholder-deprecated-block-strcutures-eg-corelist-item)
	- [Placeholder: Rich text support](#placeholder-rich-text-support)
- [Development](#development)
	- [Tests](#tests)

## Placeholder: Installation

## Placeholder: Usage

To view the block output for an arbitrary post ID, use this url:

```
https://gutenberg-content-api-test.go-vip.net/wp-json/vip-content-api/v1/posts/<post_id>/blocks
```

## Placeholder: React example

## Content API Examples

### Basic text blocks: `core/heading` and `core/paragraph`

<table>
<tr>
<td>Gutenberg Markup</td>
<td>Content API</td>
</tr>
<tr>
<td>

```html
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">VIP Content API</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Gutenberg markup in JSON.</p>
<!-- /wp:paragraph -->
```

</td>

<td>

```json
{
  "name": "core/heading",
  "attributes": {
    "level": 3,
    "content": "VIP Content API"
  }
},
{
  "name": "core/paragraph",
  "attributes": {
    "content": "Gutenberg markup in JSON."
  }
}
```

</td>
</tr>
</table>

### Text attributes in `core/pullquote`

<table>
<tr>
<td>Gutenberg Markup</td>
<td>Content API</td>
</tr>
<tr>
<td>

```html
<!-- wp:pullquote -->
<figure class="wp-block-pullquote">
    <blockquote>
        <p>Pull block props from markup.</p>
        <cite>~ WPVIP</cite>
    </blockquote>
</figure>
<!-- /wp:pullquote -->
```

</td>

<td>

```json
{
  "name": "core/pullquote",
  "attributes": {
    "value": "Turn block markup into props.",
    "citation": "~ WPVIP"
  }
}
```

</td>
</tr>
</table>

### Nested blocks in `core/media-text`

<table>
<tr>
<td>Gutenberg Markup</td>
<td>Content API</td>
</tr>
<tr>
<td>

```html
<!-- wp:media-text {"mediaId":256,
    "mediaType":"image"} -->
<div class="wp-block-media-text">
    <figure class="wp-block-media-text__media">
        <img src="http://my.site/my-image.jpg"
            class="wp-image-256 size-full" />
    </figure>
    <div class="wp-block-media-text__content">
        <!-- wp:heading -->
        <h2 class="wp-block-heading">REST API</h2>
        <!-- /wp:heading -->
    </div>
</div>
<!-- /wp:media-text -->
```

</td>
<td>

```json
{
  "name": "core/media-text",
  "attributes": {
    "mediaId": 256,
    "mediaType": "image",
    "mediaPosition": "left",
    "mediaUrl": "http://my.site/my-image.jpg",
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
}
```

</td>
</tr>
</table>

## Filters

The content API provides filters to limit access to the REST API and change the output of parsed blocks.

---

### `vip_content_api__rest_validate_post_id`

Used to limit which post IDs are valid in the REST API. By default, all posts with `post_status` set to `publish` are valid.

```php
/**
 * Validates a post can be queryied via the content API REST endpoint.
 * Return false to disable access to a post.
 *
 * @param boolean $is_valid Whether the post ID is valid for querying.
 *                          Defaults to true when post status is 'publish'.
 * @param int     $post_id  The queried post ID.
 */
return apply_filters( 'vip_content_api__rest_validate_post_id', $is_valid, $post_id );
```

For example, this filter can be used to allow only published `page` types to be available:

```php
add_filter( 'vip_content_api__rest_validate_post_id', function( $is_valid, $post_id ) {
    // Only allow published pages
    return 'page' === get_post_type( $post_id ) && 'publish' === get_post_status( $post_id );
}, 10, 2);
```

---

### `vip_content_api__rest_permission_callback`

Use this filter to limit content API access to specific users or roles.

```php
/**
 * Validates a request can access the content API. This filter can be used to
 * limit access to authenticated users.
 * Return false to disable access.
 *
 * @param boolean $is_permitted Whether the request is permitted. Defaults to true.
 */
return apply_filters( 'vip_content_api__rest_permission_callback', true );
```

By default no authentication is required, as posts must be in a `publish` state to be queried. If limited access is desired, e.g. [via Application Password credentials][wordpress-application-passwords], use this filter to check user permissions:

```php
add_filter( 'vip_content_api__rest_permission_callback', function( $is_permitted ) {
    // Require authenticated user access with 'publish_posts' permission
    return current_user_can( 'publish_posts' );
});
```

---

### `vip_content_api__sourced_block_result`

Used to modify and add additional attribute data to a block's output in the content API

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
$sourced_block = apply_filters( 'vip_content_api__sourced_block_result', $sourced_block, $block_name, $post_id, $block );
```

This is useful when a block requires attributes stored in post metadata or outside of a block's markup. See the section below for an example.

### Block additions

The `core/image` block uses the `vip_content_api__sourced_block_result` filter to add `width` and `height` attributes to the content API output in [`parser/block-additions/core-image.php`][core-image-block-addition].

For example, this is Gutenberg markup for a `core/image` block:

```html
<!-- wp:image {"id":191,"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large">
    <img src="https://my.site/wp-content/uploads/2023/header.jpg" alt="" class="wp-image-191"/>
</figure>
<!-- /wp:image -->
```

After being parsed by the content API, these attributes are sourced from the `core/image` block:

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

Some frontend JavaScript frameworks require image dimensions for responsive images. These are not available by default, as they are not present in `core/image` markup. The [`core/image` block addition][core-image-block-addition] filter is used to include `width` and `height` in the result:

```js
{
    "name": "core/image",
    "attributes": {
        "id": 191,
        "sizeSlug": "large",
        "linkDestination": "none",
        "url": "https://content-api.vipdev.lndo.site/wp-content/uploads/2023/header.jpg",
        "width": 1024, /* Added by filter */
        "height": 683  /* Added by filter */
    }
}
```

#### Custom block additions

In addition to built-in Gutenberg blocks, the `vip_content_api__sourced_block_result` fitler can be used with custom blocks to add attributes in PHP:

```php
add_filter( 'vip_content_api__sourced_block_result', 'add_custom_block_metadata', 10, 4 );

function add_custom_block_metadata( $sourced_block, $block_name, $post_id, $block ) {
    if ( 'vip/my-custom-block' !== $block_name ) {
        return $sourced_block;
    }

    $sourced_block['attributes']['custom-attribute-name'] = 'custom-attribute-value';

    return $sourced_block;
}
```

Direct block HTML can be accessed through `$block['innerHTML']`. This may be useful if manual HTML parsing is necessary to gather data from a block.

## Limitations

### Placeholder: Client-side block support + delimiter attributes

### Placeholder: Deprecated block strcutures (e.g. core/list-item)

### Placeholder: Rich text support

## Development

### Tests

Run tests locally with [`wp-env`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) and Docker:

```
wp-env start
composer install
composer run test
```

<!-- Links -->
[core-image-block-addition]: parser/block-additions/core-image.php
[wordpress-application-passwords]: https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/
