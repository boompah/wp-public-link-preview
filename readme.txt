=== Public Link Preview ===
Contributors: ryanbollenbach
Tags: preview, draft, share, public preview, link
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Share a secret public link to preview any draft, pending, or scheduled post or page — no login required.

== Description ==

*By [Ryan Bollenbach](https://boompah.com).*


Public Link Preview adds an **Enable public preview link** toggle right next to the publish controls on every post and page (block editor and classic editor).

Turn it on and you get a shareable URL that lets *anyone* — no WordPress account needed — view the unpublished post. The link stays live indefinitely until you turn the toggle off. Disabling it invalidates the link immediately; re-enabling generates a brand-new URL.

= Features =

* Toggle lives in the Status & Visibility panel (block editor) or the Publish box (classic editor), clearly labeled.
* Works for drafts, pending, and scheduled posts and pages (and any public custom post type).
* Links never expire — they work until you disable them.
* One-click copy button for the preview URL.
* Enabling/disabling takes effect immediately, without saving the post.
* Secret 32-character token, compared in constant time; disabling deletes the token so old links can never come back to life.
* Preview pages send no-cache headers, are flagged noindex for search engines, and have comments closed.
* Cleans up all its post meta on uninstall.

== Installation ==

1. Upload the `public-link-preview` folder to `/wp-content/plugins/`, or install via the Plugins screen.
2. Activate the plugin.
3. Edit any unpublished post or page, find **Public preview link** next to the publish controls, and switch it on.
4. Copy the link and share it with anyone.

== Frequently Asked Questions ==

= How long does a preview link last? =

Forever — until you switch the toggle off on that post.

= What happens when I disable a link? =

The secret token is deleted, so the shared URL stops working immediately. Turning the toggle back on creates a new, different URL.

= What happens when I publish the post? =

The post is public anyway, so the preview link simply shows the published post.

== Changelog ==

= 1.0.0 =
* Initial release.
