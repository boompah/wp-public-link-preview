# Public Link Preview

Share a secret public link to preview any draft, pending, or scheduled WordPress post or page — no login required. The link stays live **indefinitely** until you turn it off from the editor.

By [Ryan Bollenbach](https://boompah.com) · [ryan@boompah.com](mailto:ryan@boompah.com)

## How it works

The plugin adds an **Enable public preview link** toggle right next to the publish controls on every post and page:

- **Block editor**: a toggle in the *Status & Visibility* panel of the post sidebar.
- **Classic editor**: a labeled checkbox inside the *Publish* box.

Flip it on and you get a shareable URL that lets anyone — no WordPress account needed — view the unpublished post. Flipping it off invalidates the link immediately; re-enabling generates a brand-new URL.

## Features

- Works for drafts, pending, and scheduled posts, pages, and any public custom post type.
- Links never expire — they work until you disable them on the post itself.
- One-click copy button for the preview URL.
- Enabling/disabling takes effect immediately, without saving the post.
- Secret 32-character token, compared in constant time. Disabling deletes the token, so old links can never come back to life.
- Preview pages send no-cache headers, are flagged `noindex` for search engines, and have comments and pings closed.
- No settings page, no database tables — just two post meta fields, cleaned up on uninstall.

## Installation

1. Copy this folder into `wp-content/plugins/public-link-preview/` (or install the zip via **Plugins → Add New → Upload**).
2. Activate **Public Link Preview** on the Plugins screen.
3. Edit any unpublished post or page, find **Public preview link** next to the publish controls, and switch it on.
4. Copy the link and share it.

## Requirements

- WordPress 5.8+
- PHP 7.2+

## Security notes

- The toggle endpoint requires a nonce and the `edit_post` capability for the specific post.
- The front-end token check uses `hash_equals()` and only ever exposes `draft`, `pending`, and `future` posts.

## License

[GPL-2.0-or-later](LICENSE). This is free, open source software.
