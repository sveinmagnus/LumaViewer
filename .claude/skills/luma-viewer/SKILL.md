---
name: luma-viewer
description: >-
  Project-specific architecture and conventions for the Luma Viewer WordPress
  plugin in THIS repo — the cache-only data model, single-renderer-three-surfaces
  design, MemberPress-by-Luma-category gating, single-calendar setup, and the
  synthetic single-event route. Use this skill whenever working on the Luma
  Viewer plugin's code, structure, blocks, Elementor widgets, caching, or
  membership gating. It DEFERS to the installed wp-* library skills for general
  WordPress practice and to luma-api for the Luma API itself — consult those, do
  not duplicate them here.
---

# Luma Viewer — project conventions

This is a thin, project-specific overlay. For anything general, use the library
skills (in `~/.agents/skills/`) — they have scripts and reference docs:

| For… | Use the library skill |
|---|---|
| Plugin structure, hooks, lifecycle, Settings API, security baseline, packaging | **wp-plugin-development** |
| Gutenberg blocks (`block.json`, `render_callback`, `@wordpress/scripts`) | **wp-block-development** |
| `register_rest_route`, controllers, `permission_callback`, schema | **wp-rest-api** |
| Front-end interactivity (view switch, month nav, pagination) | **wp-interactivity-api** |
| Caching, object cache, transients, cron, remote HTTP calls | **wp-performance** |
| Local testing (disposable WP, blueprints) | **wp-playground** + **blueprint** |
| Static analysis | **wp-phpstan** |
| GPL / naming / trademark (note: "Luma" is a third-party mark) | **wp-plugin-directory-guidelines** |
| The Lu.ma API (endpoints, auth, shapes, rate limits, webhooks) | [[luma-api]] |

## Locked product decisions (don't re-litigate)

- **Cache-only — no custom post type.** Fetch events from the Luma API, cache
  the response, render from cache. (Trade-offs accepted: synthetic single-event
  pages, page-cache discipline for gated content — see below.)
- **Read-only + link-out.** Never write to Luma. "Register" buttons deep-link to
  the event's Luma page (`https://lu.ma/<event url slug>`).
- **Single calendar**, one Luma **Calendar API key** (Luma Plus; 200 req/min).
- **MemberPress-aware by category.** Admin maps Luma event-tags → MemberPress
  membership levels; the plugin enforces visibility. See `references/memberpress.md`.

## Naming

slug/text-domain `luma-viewer` · namespace `LumaViewer\` (PSR-4 from `src/`) ·
options under one array option `luma_viewer_settings` · constant prefix
`LUMA_VIEWER_` · REST namespace `lumaviewer/v1`.

## Architecture: one model, one renderer, three surfaces

Luma's raw JSON is normalized once into `Model\Event` (+ `Model\Location`), and a
single `View\Renderer` turns events + a view name into HTML. **Shortcodes, block
`render_callback`s, and Elementor widgets all call that same Renderer** — markup
never diverges. Views mirror The Events Calendar: Month, List (default), Day,
Photo, Summary, and a single-event detail.

## Caching rules specific to gating

- Cache the **membership-agnostic** normalized payload, keyed by a hash of the
  query args (`luma_viewer_` + md5(json)). Apply MemberPress filtering
  **per request, after** reading cache — never cache per-user results.
- Any response containing gated (hidden/teaser) content must send
  `Cache-Control: private, no-store` so full-page caches don't leak it.
- Invalidate on Luma webhooks (Event Created/Updated/Canceled); WP-Cron
  pre-warms the main list so visitors never wait on the API.

## Front end

Use the **Interactivity API** (per the wp-interactivity-api skill) for view
switching, month navigation, and pagination — backed by a `lumaviewer/v1/events`
REST route that reads from cache. Avoid hand-rolled fetch/jQuery.

## Single-event pages (synthetic, since no CPT)

Register a rewrite route `/<single_base>/<event-api-id>/` (default base
`events`) that renders the Single view and emits `<title>`, a meta description,
and **JSON-LD `Event` schema** for SEO. Also offer a `[luma_event id="…"]`
shortcode/block for embedding.

## Testing (both supported)

- **Fast/disposable:** `npx @wp-playground/cli@latest server --auto-mount`
  (Node-only; mounts this plugin). Optional `blueprint.json` at repo root
  preconfigures permalinks + a demo "Events" page.
- **Full stack:** `npx wp-env start` (Docker) when you need MemberPress /
  Elementor — both are commercial and must be installed manually.

## Quality, testing & security

Map each concern to a skill; don't reinvent.

| Concern | Skill / tool |
|---|---|
| PHP coding standards | PHPCS + WPCS (`phpcs.xml.dist`) |
| Static analysis | **wp-phpstan** — needs WP stubs *and* third-party stubs/ignores for `Mepr*` (MemberPress) and Elementor classes |
| Unit tests | PHPUnit with WP function mocking (Brain Monkey / WP_Mock) for `Api\Client`, `Cache`, `Membership\Gate` — **no library skill for this; gap** |
| Front-end E2E | **webapp-testing** (Python Playwright + `scripts/with_server.py`) against the Playground server |
| Accessibility / UX review | **web-design-guidelines**, **frontend-design** |
| Security review (diffs) | built-in **security-review** + **code-review** (require a git repo) |
| GPL / "Lu.ma" trademark / naming | **wp-plugin-directory-guidelines** |
| Repo baseline | **wp-project-triage** |
| CI to run PHP tooling | **github-actions-docs** (local machine has no PHP/Composer) |

**Project security surface to review every phase** (remote Luma data is
untrusted; gated data must not leak):
1. Escape all Luma-derived output (`esc_html`/`esc_url`/`wp_kses_post`) — names,
   descriptions, and locations are the main XSS vector.
2. API key + webhook secret: stored in options, never sent to the front end,
   masked in admin. Note DB-readable (no WP secret vault).
3. Webhook receiver: verify signature, reject unsigned, no action otherwise.
4. REST `lumaviewer/v1/events`: `permission_callback` + nonce; filter by the
   server-side user's memberships — never trust client-supplied membership.
5. Gated responses: `Cache-Control: private, no-store` so page caches don't leak.
6. Admin actions (settings, clear-cache): `manage_options` + nonce.

**Constraint:** PHPCS/PHPStan/PHPUnit need PHP — run them in CI or inside the
Playground/wp-env container, not on the host. The diff-based review skills need
the repo under git (`git init`).
