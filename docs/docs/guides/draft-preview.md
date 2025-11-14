---
sidebar_position: 5
---

# Draft & Preview Access Guide

Enable secure access to draft posts, scheduled content, and preview functionality for headless WordPress applications.

## Overview

By default, the VIP Block Data API only serves published posts. This guide shows how to safely enable access to drafts, pending posts, and scheduled content using the `vip_block_data_api__rest_validate_post_id` filter.

## Basic Draft Access

### Allow Authenticated Users

The simplest approach - any logged-in user can view drafts:

```php
add_filter( 'vip_block_data_api__rest_validate_post_id', function( $is_valid, $post_id ) {
    $post = get_post( $post_id );

    if ( ! $post ) {
        return false;
    }

    // Published posts available to everyone
    if ( 'publish' === $post->post_status ) {
        return true;
    }

    // Drafts only for authenticated users
    if ( 'draft' === $post->post_status && is_user_logged_in() ) {
        return true;
    }

    return false;
}, 10, 2 );
```

### Authors Can View Their Own Drafts

More secure - users can only view their own drafts:

```php
add_filter( 'vip_block_data_api__rest_validate_post_id', function( $is_valid, $post_id ) {
    $post = get_post( $post_id );

    if ( ! $post ) {
        return false;
    }

    if ( 'publish' === $post->post_status ) {
        return true;
    }

    // Allow drafts for post author or editors
    if ( 'draft' === $post->post_status ) {
        $current_user_id = get_current_user_id();

        // Post author can view their own draft
        if ( $current_user_id && (int) $post->post_author === $current_user_id ) {
            return true;
        }

        // Editors can view all drafts
        if ( current_user_can( 'edit_others_posts' ) ) {
            return true;
        }
    }

    return false;
}, 10, 2 );
```

## Headless WordPress Preview

### Using WordPress Cookies

For headless apps that maintain WordPress sessions:

```php
add_filter( 'vip_block_data_api__rest_validate_post_id', function( $is_valid, $post_id ) {
    $post = get_post( $post_id );

    if ( ! $post ) {
        return false;
    }

    if ( 'publish' === $post->post_status ) {
        return true;
    }

    // Check for preview mode
    $is_preview = isset( $_GET['preview'] ) && $_GET['preview'] === 'true';

    if ( $is_preview && in_array( $post->post_status, [ 'draft', 'pending' ], true ) ) {
        // WordPress cookie authentication
        if ( is_user_logged_in() ) {
            return current_user_can( 'edit_post', $post_id );
        }
    }

    return false;
}, 10, 2 );
```

**Frontend Implementation:**

```javascript
// Fetch with WordPress authentication cookie
async function fetchPreview(postId) {
  const response = await fetch(
    `https://wordpress.site/wp-json/vip-block-data-api/v1/posts/${postId}/blocks?preview=true`,
    {
      credentials: 'include', // Include cookies
    }
  );

  return response.json();
}
```

### Using Preview Tokens

For shareable preview links that don't require login:

```php
/**
 * Generate a preview token when post is saved
 */
add_action( 'save_post', function( $post_id ) {
    $post = get_post( $post_id );

    // Only for non-published posts
    if ( ! in_array( $post->post_status, [ 'draft', 'pending' ], true ) ) {
        return;
    }

    // Generate or retrieve existing token
    $token = get_post_meta( $post_id, '_preview_token', true );

    if ( empty( $token ) ) {
        $token = wp_generate_password( 32, false );
        update_post_meta( $post_id, '_preview_token', $token );
    }

    // Set expiry (24 hours from now)
    $expiry = time() + DAY_IN_SECONDS;
    update_post_meta( $post_id, '_preview_token_expiry', $expiry );
} );

/**
 * Validate preview token
 */
add_filter( 'vip_block_data_api__rest_validate_post_id', function( $is_valid, $post_id ) {
    $post = get_post( $post_id );

    if ( ! $post ) {
        return false;
    }

    if ( 'publish' === $post->post_status ) {
        return true;
    }

    // Check for preview token
    $preview_token = $_GET['preview_token'] ?? '';

    if ( ! empty( $preview_token ) && 'draft' === $post->post_status ) {
        $stored_token = get_post_meta( $post_id, '_preview_token', true );
        $token_expiry = get_post_meta( $post_id, '_preview_token_expiry', true );

        // Validate token and expiry
        if ( hash_equals( $stored_token, $preview_token ) &&
             $token_expiry &&
             time() < $token_expiry ) {
            return true;
        }
    }

    return false;
}, 10, 2 );
```

**Usage:**

```javascript
// Preview URL with token
const previewUrl = `https://wordpress.site/wp-json/vip-block-data-api/v1/posts/123/blocks?preview_token=${token}`;

// Shareable preview link
const shareableLink = `https://frontend.site/preview/123?token=${token}`;
```

**Generate Preview Links in WordPress Admin:**

```php
add_filter( 'preview_post_link', function( $preview_link, $post ) {
    $token = get_post_meta( $post->ID, '_preview_token', true );

    if ( $token ) {
        return add_query_arg(
            'preview_token',
            $token,
            'https://frontend.site/preview/' . $post->ID
        );
    }

    return $preview_link;
}, 10, 2 );
```

## Scheduled Posts (Future)

Allow previewing scheduled posts before they're published:

```php
add_filter( 'vip_block_data_api__rest_validate_post_id', function( $is_valid, $post_id ) {
    $post = get_post( $post_id );

    if ( ! $post ) {
        return false;
    }

    if ( 'publish' === $post->post_status ) {
        return true;
    }

    // Allow scheduled posts for users who can edit them
    if ( 'future' === $post->post_status ) {
        return is_user_logged_in() && current_user_can( 'edit_post', $post_id );
    }

    return false;
}, 10, 2 );
```

## Private Posts

Enable access to private posts for authorized users:

```php
add_filter( 'vip_block_data_api__rest_validate_post_id', function( $is_valid, $post_id ) {
    $post = get_post( $post_id );

    if ( ! $post ) {
        return false;
    }

    if ( 'publish' === $post->post_status ) {
        return true;
    }

    // Allow private posts for authorized users
    if ( 'private' === $post->post_status ) {
        return is_user_logged_in() && current_user_can( 'read_post', $post_id );
    }

    return false;
}, 10, 2 );
```

## Next.js Preview Mode

### Complete Implementation

**WordPress Filter:**

```php
add_filter( 'vip_block_data_api__rest_validate_post_id', function( $is_valid, $post_id ) {
    $post = get_post( $post_id );

    if ( ! $post || 'publish' === $post->post_status ) {
        return $is_valid;
    }

    // Check preview authentication
    $preview_secret = $_GET['preview_secret'] ?? '';
    $stored_secret = get_option( 'nextjs_preview_secret' );

    if ( ! empty( $preview_secret ) &&
         hash_equals( $stored_secret, $preview_secret ) &&
         current_user_can( 'edit_post', $post_id ) ) {
        return true;
    }

    return false;
}, 10, 2 );
```

**Next.js Preview API Route:**

```javascript
// pages/api/preview.js
export default async function handler(req, res) {
  const { secret, id } = req.query;

  // Check secret
  if (secret !== process.env.PREVIEW_SECRET) {
    return res.status(401).json({ message: 'Invalid token' });
  }

  // Fetch the post to verify it exists
  const response = await fetch(
    `https://wordpress.site/wp-json/vip-block-data-api/v1/posts/${id}/blocks?preview_secret=${secret}`,
    {
      headers: {
        Cookie: req.headers.cookie || '',
      },
    }
  );

  if (!response.ok) {
    return res.status(401).json({ message: 'Invalid post ID' });
  }

  // Enable Preview Mode
  res.setPreviewData({
    postId: id,
  });

  // Redirect to the path
  res.redirect(`/posts/${id}`);
}
```

**Next.js Page:**

```javascript
// pages/posts/[id].js
export async function getServerSideProps(context) {
  const { id } = context.params;
  const { preview = false } = context;

  // Build API URL
  const apiUrl = `https://wordpress.site/wp-json/vip-block-data-api/v1/posts/${id}/blocks`;

  const params = new URLSearchParams();
  if (preview) {
    params.append('preview_secret', process.env.PREVIEW_SECRET);
  }

  const response = await fetch(
    `${apiUrl}?${params}`,
    {
      headers: {
        Cookie: context.req.headers.cookie || '',
      },
    }
  );

  const data = await response.json();

  return {
    props: {
      blocks: data.blocks,
      preview,
    },
  };
}
```

## Security Best Practices

### 1. Always Validate Permissions

```php
// ❌ DON'T: Allow all drafts without checks
if ( 'draft' === $post->post_status ) {
    return true; // Insecure!
}

// ✅ DO: Verify user permissions
if ( 'draft' === $post->post_status && current_user_can( 'edit_post', $post_id ) ) {
    return true;
}
```

### 2. Use Time-Limited Tokens

```php
// Set reasonable expiry times
$expiry = time() + DAY_IN_SECONDS; // 24 hours
update_post_meta( $post_id, '_preview_token_expiry', $expiry );

// Always check expiry
if ( time() > $token_expiry ) {
    return false; // Token expired
}
```

### 3. Use Constant-Time Comparison

```php
// ❌ DON'T: Use direct comparison (timing attack vulnerability)
if ( $token === $stored_token ) {
    return true;
}

// ✅ DO: Use hash_equals() for constant-time comparison
if ( hash_equals( $stored_token, $token ) ) {
    return true;
}
```

### 4. Sanitize Input

```php
// Always sanitize query parameters
$preview_token = sanitize_text_field( $_GET['preview_token'] ?? '' );
$preview_secret = sanitize_text_field( $_GET['preview_secret'] ?? '' );
```

### 5. Rate Limiting

```php
add_filter( 'vip_block_data_api__rest_validate_post_id', function( $is_valid, $post_id ) {
    // Implement rate limiting for preview requests
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $rate_key = "preview_rate_{$ip}";
    $attempts = (int) get_transient( $rate_key );

    if ( $attempts > 100 ) { // Max 100 per hour
        return false;
    }

    set_transient( $rate_key, $attempts + 1, HOUR_IN_SECONDS );

    // Continue with normal validation...
    return $is_valid;
}, 10, 2 );
```

## Troubleshooting

### "Post not found" errors

- Verify the post exists and has the expected status
- Check user authentication is working
- Ensure cookies are being sent with requests (credentials: 'include')

### Preview tokens not working

- Verify token is being generated on save
- Check token hasn't expired
- Use `hash_equals()` for comparison
- Sanitize token input

### Drafts visible to public

- Review filter logic carefully
- Always check post status AND permissions
- Test with logged-out users
- Add logging during development

## Next Steps

- [Filters Reference](../hooks/filters)
- [REST API Examples](../examples/rest-api-examples)
- [Getting Started](../getting-started)
