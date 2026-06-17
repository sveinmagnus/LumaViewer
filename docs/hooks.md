# Developer reference: hooks, styling & CLI

Extension points for developers. (End users won't need this page.)

## Filters

| Filter | Arguments | Purpose |
|---|---|---|
| `luma_viewer_event_args` | `$args`, `$atts` | Alter the repository query (count, tag, calendar, after, before) before events are fetched. |
| `luma_viewer_calendar_html` | `$html`, `$view`, `$atts` | Filter the final rendered calendar markup. |
| `luma_viewer_client_ip` | `$ip` | Override the client IP used for REST rate limiting (e.g. behind a CDN). |
| `luma_viewer_rest_rate_limit` | `$limit` (default 60) | Max anonymous REST requests per window. |
| `luma_viewer_rest_rate_window` | `$seconds` (default 60) | Rate-limit window length. |
| `luma_viewer_enable_github_updates` | `$enabled` (default true) | Toggle the GitHub-Releases update checker (disable for WordPress.org builds). |
| `luma_viewer_update_repo` | `$repo` | The GitHub repository URL update checks point at. |

```php
// Example: only show events tagged "public" everywhere.
add_filter( 'luma_viewer_event_args', function ( $args ) {
    if ( '' === $args['tag'] ) {
        $args['tag'] = 'public';
    }
    return $args;
} );
```

## Template overrides

Every view is a template that a theme can override by placing a file at
`luma-viewer/<name>.php` in the (child) theme. Resolution order: child theme →
parent theme → plugin defaults. Available templates: `list`, `week`, `month`,
`day`, `photo`, `summary`, `single`, and `partials/event-card`.

## Styling (CSS custom properties)

The front-end CSS is scoped to `.luma-viewer` and driven by custom properties,
so you can re-theme without overriding many rules:

| Property | Default | Used for |
|---|---|---|
| `--lv-accent` | `currentColor` | Buttons, active view tab, today/highlights |
| `--lv-gap` | `1rem` | Spacing between cards |
| `--lv-radius` | `10px` | Corner radius |
| `--lv-border` | `rgba(0,0,0,.1)` | Borders / dividers |
| `--lv-muted` | `rgba(0,0,0,.6)` | Secondary text |

```css
.luma-viewer { --lv-accent: #e8590c; --lv-radius: 4px; }
```

The **Accent color** setting (Settings → Luma-viewer → Display) sets
`--lv-accent` for you.

## WP-CLI

```bash
wp luma-viewer refresh            # clear + re-warm the event cache
wp luma-viewer clear              # clear cached events
wp luma-viewer list [--count=20]  # list upcoming events
```
