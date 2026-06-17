# Blocks & Elementor

The same options are available everywhere because all three surfaces render
through one shared engine.

## Gutenberg blocks

- **Luma Calendar** — insert it and use the block sidebar to set the view
  (including Map), category (tag), number of events, anchor date, list layout,
  grouping, calendar, filter bar, and past/upcoming. The editor shows a live
  server-rendered preview.
- **Luma Event** — set an event ID to embed a single event.
- **Luma Featured Event** — a hero banner. Leave the ID blank to feature the
  next upcoming event automatically.
- **Luma Event Countdown** — a live countdown timer. Leave the ID blank to count
  down to the next upcoming event.

The blocks are built into `build/` by `npm run build` and ship inside the
release zip.

## Widgets (classic sidebars)

- **Luma: Upcoming Events** — a compact list of the next few events for classic
  (non-block) sidebars. Set a title and how many events to show.

## Elementor widgets

With Elementor active, two widgets appear under the **General** category:

- **Luma Calendar** — same controls as the block (view, tag, count, anchor date,
  layout, grouping).
- **Luma Event** — an event ID field.

The widgets only load when Elementor is active, so the plugin has no dependency
on it.

## Shortcodes

`[luma_calendar]`, `[luma_event]`, `[luma_featured]`, and `[luma_countdown]`
work in any content area, widget, or page builder that accepts shortcodes. See
[Usage](usage.md) for all attributes.
