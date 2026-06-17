# Usage

Add a calendar with the **Luma Calendar** block, the Elementor **Luma Calendar**
widget, or the `[luma_calendar]` shortcode. A single event can be embedded with
`[luma_event id="evt-…"]`, featured as a hero with `[luma_featured]`, or counted
down to with `[luma_countdown]`.

## `[luma_calendar]` attributes

| Attribute | Values | Default | Applies to |
|---|---|---|---|
| `view` | `list`, `week`, `month`, `day`, `photo`, `summary`, `map` | site default (`list`) | all |
| `layout` | `cards`, `compact`, `minimal` | `cards` | List, Week, Day |
| `group_by` | `day`, `month`, `none` | `day` | List |
| `tag` | a Luma event tag (id or name) | — | all |
| `count` | integer (0 = site default) | site per-page | list-style views |
| `date` | `YYYY-MM` (Month) or `YYYY-MM-DD` (Week/Day) | current | Week, Month, Day |
| `calendar` | a calendar API id (organization mode) | all | all |
| `filters` | `true` to show the search + tag filter bar | off | all |
| `past` | `true` to show past instead of upcoming events | off | list-style views |
| `from` / `to` | `YYYY-MM-DD` date-range bounds | — | list-style views |
| `offset` | integer — skip the first N events | `0` | list-style views |

## Single-event widgets

`[luma_event]`, `[luma_featured]`, and `[luma_countdown]` each take an optional
`id`. Leave `id` blank on `[luma_featured]` / `[luma_countdown]` and they track
the **next upcoming event** automatically.

| Shortcode | Purpose |
|---|---|
| `[luma_event id="evt-…"]` | A single event's details (summary + "register on Luma"). |
| `[luma_featured id="evt-…"]` | A full-width hero banner for one event. |
| `[luma_countdown id="evt-…"]` | A live ticking countdown to the event start. |

## Examples

```text
[luma_calendar]
[luma_calendar view="week"]
[luma_calendar view="month" date="2026-07"]
[luma_calendar view="map" filters="true"]
[luma_calendar view="list" layout="compact" group_by="month" count="20"]
[luma_calendar view="list" layout="minimal" count="5"]   (great for sidebars)
[luma_calendar view="list" past="true" count="10"]        (recent past events)
[luma_calendar view="list" from="2026-09-01" to="2026-09-30"]
[luma_calendar view="photo" tag="Workshops"]
[luma_event id="evt-xxxxxxxx"]
[luma_featured]            (hero for the next event)
[luma_countdown]           (countdown to the next event)
```

## Views

- **List** — upcoming events; choose a `layout` (full cards, compact rows, or
  minimal title+time) and `group_by` (day, month, or no grouping).
- **Week** — a 7-day agenda honoring your site's start-of-week.
- **Month** — a semantic calendar table.
- **Day** — a single day's agenda.
- **Photo** — a cover-image grid.
- **Summary** — a compact, date-grouped list.
- **Map** — events with a venue plotted on an OpenStreetMap map (the map library
  loads only on pages that actually show a map).

Visitors can switch views, filter, and move between dates without a full page
reload (progressively enhanced; it works without JavaScript too). The featured
hero, countdown, and the **Upcoming Events** sidebar widget all track the next
event for you.

## Single-event pages

Each event is available at `/<base>/<event-id>/` (base configurable under
**Settings → Luma-viewer → Display**). These pages render within your theme and
include a title and JSON-LD `Event` schema for SEO.

## Time zones

Choose whether times display in each event's own time zone or your site's time
zone under **Settings → Luma-viewer → Display**.
