# Changelog

All notable changes to this project are documented here. The format is based on
[Keep a Changelog](https://keepachangelog.com/), and this project adheres to
[Semantic Versioning](https://semver.org/).

## [Unreleased]

## [0.1.0]

Initial release.

- Views: List (cards / compact / minimal layouts, grouped by day, month, or none), Week, Month, Day, Photo, Summary, Map (lazy-loaded OpenStreetMap, optional center/zoom/clustering), Carousel, and single-event pages.
- Filtering: search, tag chips, multi-tag, online/in-person, free/paid, past-events toggle, date-range, offset, and sort order — all progressively enhanced.
- Pagination as a "load more" button or numbered pages; optional quick-view popup that opens an event summary without leaving the site.
- Configurable display: per-element card toggles (cover, location, host, price, excerpt, tags, relative date), excerpt length, date/time format, link target, category colors, tag allow/deny, and a custom empty-state message — as global defaults and per-instance overrides.
- Configurable background refresh interval (15/30/60 min) with an option to disable pre-warming, and a show/hide toggle for cancelled events.
- Gutenberg blocks: Luma Calendar, Luma Event, Luma Featured Event (hero), and Luma Event Countdown (dynamic, with editor previews).
- Elementor widgets mirroring the block options.
- "Luma: Upcoming Events" classic sidebar widget.
- Organization mode: pull from multiple calendars and filter by calendar.
- MemberPress category-based access (visible / teaser / hidden) with a settings mapping UI.
- Cache-only architecture with WP-Cron pre-warm and a webhook for instant invalidation.
- Accessible client-side view switching, filtering, and date navigation.
- Single-event pages with title + JSON-LD `Event` schema.
- Hardening: output escaping, per-IP REST rate limiting, gated content kept out of shared caches.
- Self-hosted updates via GitHub Releases (Plugin Update Checker).

[Unreleased]: https://github.com/sveinmagnus/LumaViewer/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/sveinmagnus/LumaViewer/releases/tag/v0.1.0
