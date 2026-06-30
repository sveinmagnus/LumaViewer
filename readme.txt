=== Calendar Viewer for Luma Events ===
Contributors: (your-wordpress-org-username)
Tags: events, calendar, event-calendar, membership, agenda
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show a Lu.ma calendar on your site with List, Month, Map, Carousel and more views, blocks, Elementor widgets, filtering and membership-aware access.

== Description ==

Luma-viewer displays an organization's Lu.ma calendar on WordPress with views
modeled on popular event-calendar plugins, available as shortcodes, Gutenberg
blocks, and Elementor widgets. It is read-only: events are fetched from the Luma
API and cached, and visitors register on Luma via deep links.

**Views:** List (cards / compact / minimal layouts, grouped by day or month),
Week, Month, Day, Photo, Summary, Map (lazy-loaded OpenStreetMap), Carousel, and
single-event pages. A filter bar (search, tags, online/in-person, free/paid),
past-events toggle, date-range, sort order, and load-more or numbered pagination
are available too, plus an optional quick-view popup.

**Customize the look:** show or hide the cover, location, host, price, excerpt,
tags and dates; set the excerpt length, date/time format and link target;
colour-code categories; restrict which tags appear; and choose how often the
cache refreshes — all as site-wide defaults with per-instance overrides.

**Blocks & widgets:** Gutenberg blocks for the calendar, a single event, a
featured-event hero, and a live countdown; an Elementor calendar/event widget;
and an "Upcoming Events" classic sidebar widget — plus the `[luma_calendar]`,
`[luma_event]`, `[luma_featured]`, and `[luma_countdown]` shortcodes.

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
* Initial release: List/Week/Month/Day/Photo/Summary/Map/Carousel views,
  single-event pages, filter bar (search, tags, online/in-person, free/paid),
  past-events, date-range, sort order, load-more or numbered pagination, and an
  optional quick-view popup. Configurable card elements, excerpt length,
  date/time format, category colors, tag allow/deny, and refresh interval.
  Gutenberg blocks (calendar, event, featured hero, countdown), Elementor
  widgets, an Upcoming Events sidebar widget, organization (multi-calendar)
  mode, MemberPress category access, caching with cron pre-warm and webhook
  invalidation, and a rate-limited REST endpoint for AJAX navigation.

== Upgrade Notice ==

= 0.1.0 =
Initial release.
