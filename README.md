# Luma Viewer

A WordPress plugin that displays a single **Lu.ma** organization calendar on your
site with *The Events Calendar*–style views (Month, List, Day, Photo, Summary,
single event), available as **shortcodes, Gutenberg blocks, and Elementor
widgets**, with **MemberPress-aware access**: map Luma event categories (tags) to
membership levels and the plugin decides what each visitor can see.

> Status: **functional, pre-release.** All core features are implemented (views,
> shortcodes, blocks, Elementor widgets, MemberPress gating, single-event pages,
> webhooks/cron). Field names from the Luma API are isolated in `Model\Event` and
> should be confirmed against a live key. See `.claude/skills/luma-api` and
> `.claude/skills/luma-viewer` for the reference material this is built against.

## How it works

- **Read-only + link-out.** The plugin only *reads* from Luma and caches results;
  visitors register on Luma via deep links. It never writes to Luma.
- **Cache-only (no custom post type).** Events are fetched from the Luma API and
  cached (transients / object cache); MemberPress filtering is applied per
  request so gated content is never cached per user.
- **Single calendar.** Configured with one Luma **Calendar API key** (requires
  Luma Plus; 200 requests/minute).
- **Fresh automatically.** WP-Cron pre-warms the cache every 15 minutes; an
  optional Luma webhook (URL shown in settings) invalidates it instantly.

## Usage

Place a calendar with the **Luma Calendar** block, the **Luma Calendar**
Elementor widget, or the shortcode:

```
[luma_calendar view="month" tag="" count="10" date="2026-07"]
[luma_event id="evt-xxxxxxxx"]
```

`view` is one of `list` (default), `month`, `day`, `photo`, `summary`. Single
events also get their own page at `/<base>/<event-id>/` (base configurable in
settings). Views can be switched and navigated client-side without a reload.

### MemberPress access by category

Under **Settings → Luma Viewer → Membership access**, map Luma event categories
(tags) to MemberPress memberships. Events with a mapped category are shown only
to members holding one of those memberships; others see a teaser (or nothing,
your choice). Events without a mapped category stay public.

## Requirements

- WordPress 6.4+, PHP 7.4+
- A Lu.ma calendar on **Luma Plus** with an API key
  (Luma → Settings → Developer → API Keys)
- Optional: **MemberPress** (for category-based access) and **Elementor**
  (for the Elementor widgets). Both are commercial and must be installed
  manually — they are not bundled.

## Development

```bash
composer install        # PHP deps + QA tooling (optional; runtime has a fallback autoloader)
npm install             # block / asset build tooling
npm run build           # build blocks (added in a later phase)

composer run phpcs      # WordPress Coding Standards (PHPCS)
composer run phpstan    # static analysis (PHPStan + WP stubs)
composer run test       # unit tests (PHPUnit + Brain Monkey)
```

### Quality, tests & CI

- **PHPCS / PHPStan / PHPUnit** run in CI via [.github/workflows/ci.yml](.github/workflows/ci.yml).
  Because this machine has no local PHP/Composer, CI (or the Playground/wp-env
  container) is where these actually execute.
- **Unit tests** live in `tests/Unit/` and mock WordPress with Brain Monkey, so
  they need no WordPress install.
- **E2E smoke** lives in `tests/e2e/` (Python Playwright, per the
  `webapp-testing` skill). It runs against a Playground server; in CI it's a
  manual (`workflow_dispatch`) job until the front-end views land.

Development guidance lives in skills: the `luma-api` and `luma-viewer` skills in
[`.claude/skills/`](.claude/skills) (project-specific), which defer to the
installed WordPress `wp-*` library skills (`wp-plugin-development`,
`wp-block-development`, `wp-rest-api`, `wp-interactivity-api`, `wp-performance`,
`wp-playground`, …) for general practice.

### Local testing

**Fast / disposable (Node only — no Docker or PHP needed):**

```bash
npx @wp-playground/cli@latest server --auto-mount --blueprint=./blueprint.json
```

This boots WordPress in WebAssembly, mounts this plugin, and pre-creates an
**Events** page containing `[luma_calendar]`. Good for quick UI checks. (Luma
Plus + MemberPress + Elementor cannot run here.)

**Full stack (Docker — for MemberPress / Elementor):**

```bash
npx wp-env start
```

Then install MemberPress and Elementor manually (they are commercial and not
bundled), open **Settings → Luma Viewer**, paste your Luma API key, save, and
click **Test connection**.

## License

GPL-2.0-or-later.
