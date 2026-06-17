=== Calendar Viewer for Luma Events ===
Contributors: (your-wordpress-org-username)
Tags: events, calendar, event-calendar, membership, agenda
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show a Lu.ma calendar on your site with List, Week, Month, Day, Photo and Summary views, blocks, Elementor widgets and membership-aware access.

== Description ==

Luma-viewer displays an organization's Lu.ma calendar on WordPress with views
modeled on popular event-calendar plugins, available as shortcodes, Gutenberg
blocks, and Elementor widgets. It is read-only: events are fetched from the Luma
API and cached, and visitors register on Luma via deep links.

**Views:** List (cards / compact / minimal layouts, grouped by day or month),
Week, Month, Day, Photo, Summary, and single-event pages.

**Membership access (optional, requires MemberPress):** map Luma event
categories (tags) to MemberPress memberships. Events in a mapped category are
shown only to members who hold one of those memberships; others see a teaser or
nothing. Events without a mapped category stay public.

**Performance & freshness:** responses are cached (transients / object cache),
pre-warmed every 15 minutes via WP-Cron, and can be refreshed instantly with a
Luma webhook. The public REST endpoint used for AJAX navigation is rate-limited.

This plugin is an independent integration and is not affiliated with or endorsed
by Lu.ma or MemberPress. A Luma Plus subscription is required to obtain an API
key.

== Installation ==

1. Upload the plugin via Plugins → Add New → Upload Plugin, or copy the
   `luma-viewer` folder to `wp-content/plugins/`.
2. Activate the plugin.
3. Go to Settings → Luma-viewer and enter your Luma Calendar API key, then save
   and click "Test connection".
4. Add the "Luma Calendar" block/widget or the `[luma_calendar]` shortcode to a
   page.

== Frequently Asked Questions ==

= Do I need a paid Luma plan? =
Yes. The Luma public API requires an active Luma Plus subscription on the
calendar you connect.

= Does it create WordPress posts for events? =
No. Events are fetched and cached on demand; nothing is duplicated into the
database. Single events are served on virtual URLs with proper SEO metadata.

= Is MemberPress required? =
Only for category-based access control. Without it, all events are public.

= How do visitors register for an event? =
Registration happens on Luma; the plugin links out to each event's Luma page.

== Changelog ==

= 0.1.0 =
* Initial release: List/Week/Month/Day/Photo/Summary views, single-event pages,
  Gutenberg blocks, Elementor widgets, MemberPress category access, caching with
  cron pre-warm and webhook invalidation, and a rate-limited REST endpoint for
  AJAX navigation.

== Upgrade Notice ==

= 0.1.0 =
Initial release.
