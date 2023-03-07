# VIP Content API

Plugin to provide an API for customers to retrieve Gutenberg posts structured as JSON data. This is accomplished by parsing server-side block registry data and sourcing block attributes from HTML.

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

If block data is encoded in post metadata or outside of a block's attributes, use this filter to modify or add additional attribute data.

#### `core/image` block additions

For example, see [the `core/image` block addition][core-image-block-addition]. This filter matches `core/image` blocks and adds `width` and `height` attributes to block output sourced from attachment metadata:

---

Gutenberg markup:

```html
<!-- wp:image {"id":191,"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="http://content-api.vipdev.lndo.site/wp-content/uploads/2023/03/omYGHPwGEU-1024x683.jpg" alt="" class="wp-image-191"/></figure>
<!-- /wp:image -->
```

---

Original `core/image` attributes sourced from block:

```json
{
    "name": "core/image",
    "attributes": {
        "id": 191,
        "sizeSlug": "large",
        "linkDestination": "none",
        "url": "http://my.site/wp-content/uploads/2023/03/omYGHPwGEU-1024x683.jpg",
    }
}
```

---

`core/image` attributes after applying [`core/image` block addition][core-image-block-addition] filter:

```json
{
	"name": "core/image",
	"attributes": {
		"id": 191,
		"sizeSlug": "large",
		"linkDestination": "none",
		"url": "http://content-api.vipdev.lndo.site/wp-content/uploads/2023/03/omYGHPwGEU-1024x683.jpg",
		"width": 1024,
		"height": 683
	}
}
```

---

#### Accessing block HTML

Raw block HTML can also be accessed through the `$block['innerHTML']` filter parameter. This may be useful if manual HTML parsing is required to extract useful attributes.

## Assumptions & Limitations

- All blocks must be [registered server-side](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#php-server-side). Client-side only blocks are not supported.
- The plugin does not use any client-side editor code or modify existing posts. The content API should work statically with existing Gutenberg content.
- (currently) `html`/rich text attributes are represented with inline HTML. We are researching non-HTML rich text serialization formats as an alternative.
- (currently) The plugin provides a REST-only endpoint. WPGraphQL and typing may be supported in the future.

## Examples

### Live examples

See [example WordPress post here](https://gutenberg-content-api-test.go-vip.net/hello-world/) and the associated content API output: [https://gutenberg-content-api-test.go-vip.net/wp-json/vip-content-api/v1/posts/1/blocks](https://gutenberg-content-api-test.go-vip.net/wp-json/vip-content-api/v1/posts/1/blocks).

To view the block output for an arbitrary post ID, use this url:

```
https://gutenberg-content-api-test.go-vip.net/wp-json/vip-content-api/v1/posts/<post_id>/blocks
```

### Example content API output

<table>
	<tr>
		<td>Example Post</td>
		<td>HTML Content</td>
	</tr>
	<tr>
		<td>
			<img src="https://github.com/wpcomvip/wordpress-vip-testing-gutenberg-content-api-test/raw/media/post-example.png?raw=true" alt="Example WordPress post with heading, quote, separator, and media-text blocks" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			<!-- non-breaking spaces for GitHub image alignment -->
		</td>
<td>

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

<!-- wp:separator -->
<hr class="wp-block-separator has-alpha-channel-opacity"/>
<!-- /wp:separator -->

<!-- wp:media-text {"mediaId":6,"mediaLink":"https://gutenberg-content-api-test.go-vip.net/?attachment_id=6","mediaType":"image"} -->
<div class="wp-block-media-text alignwide is-stacked-on-mobile">
    <figure class="wp-block-media-text__media">
        <img src="https://gutenberg-content-api-test.go-vip.net/wp-content/uploads/2023/01/4365xAanG8.jpg?w=1024" alt="" class="wp-image-6 size-full"/>
    </figure>

    <div class="wp-block-media-text__content">
        <!-- wp:paragraph {"placeholder":"Content…"} -->
        <p>Content on right side of media-text.</p>
        <!-- /wp:paragraph -->
    </div>
</div>
<!-- /wp:media-text -->
```

</td>
</tr>
</table>

---

Content API result:

```json
{
    "blocks": [
        {
            "name": "core/heading",
            "attributes": {
                "content": "Heading 1",
                "level": 2
            }
        },
        {
            "name": "core/quote",
            "attributes": {
                "value": "",
                "citation": "~ Citation, 2023"
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
        },
        {
            "name": "core/separator",
            "attributes": {
                "opacity": "alpha-channel"
            }
        },
        {
            "name": "core/media-text",
            "attributes": {
                "mediaId": 6,
                "mediaLink": "https:/gutenberg-content-api-test.go-vip.net/?attachment_id=6",
                "mediaType": "image",
                "align": "wide",
                "mediaAlt": "",
                "mediaPosition": "left",
                "mediaUrl": "https:/gutenberg-content-api-test.go-vip.net/wp-content/uploads/2023/01/4365xAanG8.jpg?w=1024",
                "mediaWidth": 50,
                "isStackedOnMobile": true
            },
            "innerBlocks": [
                {
                    "name": "core/paragraph",
                    "attributes": {
                        "placeholder": "Content\u2026",
                        "content": "Content on right side of media-text.",
                        "dropCap": false
                    }
                }
            ]
        }
    ]
}
```

## Tests

Run tests locally with [`wp-env`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) and Docker:

```
wp-env start
composer install
composer run test
```

[core-image-block-addition]: parser/block-additions/core-image.php
