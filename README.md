# VIP Content API

A REST API to retrieve Gutenberg editor blocks structured as JSON instead of HTML.

## Placeholder: Installation

## Content API Examples

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
<tr></tr>
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
<tr></tr>
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

## Placeholder: Usage

To view the block output for an arbitrary post ID, use this url:

```
https://gutenberg-content-api-test.go-vip.net/wp-json/vip-content-api/v1/posts/<post_id>/blocks
```

## Placeholder: React example

## Filters

### `vip_content_api__sourced_block_result`

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

This filter is used to modify and add additional attribute data to a block's output in the content API. This is useful when a block requires data stored in metadata or outside of the block's attributes.

For example, see [the `core/image` block addition section below](#coreimage-block-additions).

#### `core/image` block addition

The `core/image` block [uses the `vip_content_api__sourced_block_result` filter][core-image-block-addition] to add `width` and `height` attributes to the content API output sourced from attachment metadata.

---

This is the original Gutenberg markup for an example `core/image` block:

```html
<!-- wp:image {"id":191,"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large">
    <img src="https://my.site/wp-content/uploads/2023/header.jpg" alt="" class="wp-image-191"/>
</figure>
<!-- /wp:image -->
```

---

Plain `core/image` attributes sourced from block:

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

---

`core/image` attributes after applying [`core/image` block addition][core-image-block-addition] filter:

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

---

#### Custom block additions

In addition to built-in Gutenberg blocks, this filter can be used with custom blocks to add attributes in PHP:

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

Raw block HTML can be accessed through `$block['innerHTML']`. This may be useful if manual HTML parsing is nessary to gather data from a block.

### Placeholder: Filter for API permissions

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
