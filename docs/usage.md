# Usage

Add a calendar with the **Luma Calendar** block, the Elementor **Luma Calendar**
widget, or the `[luma_calendar]` shortcode. A single event can be embedded with
`[luma_event id="evt-…"]`.

## Shortcode attributes

| Attribute | Values | Default | Applies to |
|---|---|---|---|
| `view` | `list`, `week`, `month`, `day`, `photo`, `summary` | site default (`list`) | all |
| `layout` | `cards`, `compact`, `minimal` | `cards` | List, Week, Day |
| `group_by` | `day`, `month`, `none` | `day` | List |
| `tag` | a Luma event tag (id or name) | — | all |
| `count` | integer (0 = site default) | site per-page | list-style views |
| `date` | `YYYY-MM` (Month) or `YYYY-MM-DD` (Week/Day) | current | Week, Month, Day |

## Examples

```text
[luma_calendar]
[luma_calendar view="week"]
[luma_calendar view="month" date="2026-07"]
[luma_calendar view="list" layout="compact" group_by="month" count="20"]
[luma_calendar view="list" layout="minimal" count="5"]   (great for sidebars)
[luma_calendar view="photo" tag="Workshops"]
[luma_event id="evt-xxxxxxxx"]
```

## Views

- **List** — upcoming events; choose a `layout` (full cards, compact rows, or
  minimal title+time) and `group_by` (day, month, or no grouping).
- **Week** — a 7-day agenda honoring your site's start-of-week.
- **Month** — a semantic calendar table.
- **Day** — a single day's agenda.
- **Photo** — a cover-image grid.
- **Summary** — a compact, date-grouped list.

Visitors can switch views and move between dates without a full page reload
(progressively enhanced; it works without JavaScript too).

## Single-event pages

Each event is available at `/<base>/<event-id>/` (base configurable under
**Settings → Luma-viewer → Display**). These pages render within your theme and
include a title and JSON-LD `Event` schema for SEO.

## Time zones

Choose whether times display in each event's own time zone or your site's time
zone under **Settings → Luma-viewer → Display**.
