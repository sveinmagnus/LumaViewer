# Changelog

All notable changes to this project are documented here. The format is based on
[Keep a Changelog](https://keepachangelog.com/), and this project adheres to
[Semantic Versioning](https://semver.org/).

## [Unreleased]

## [0.1.0]

Initial release.

- Views: List (cards / compact / minimal layouts, grouped by day, month, or none), Week, Month, Day, Photo, Summary, and single-event pages.
- Gutenberg blocks: Luma Calendar and Luma Event (dynamic, with editor previews).
- Elementor widgets mirroring the block options.
- MemberPress category-based access (visible / teaser / hidden) with a settings mapping UI.
- Cache-only architecture with WP-Cron pre-warm and a webhook for instant invalidation.
- Accessible client-side view switching and date navigation.
- Single-event pages with title + JSON-LD `Event` schema.
- Hardening: output escaping, per-IP REST rate limiting, gated content kept out of shared caches.
- Self-hosted updates via GitHub Releases (Plugin Update Checker).

[Unreleased]: https://github.com/sveins/luma-viewer/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/sveins/luma-viewer/releases/tag/v0.1.0
