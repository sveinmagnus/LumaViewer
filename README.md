# Luma Viewer

**Show your [Lu.ma](https://lu.ma) events beautifully on your WordPress site.**

Luma Viewer brings your organization's Lu.ma calendar into WordPress with
polished, ready-made views you can drop onto any page — as a block, an Elementor
widget, or a shortcode. Pick a layout, and your upcoming events appear
automatically and stay up to date.

---

## ⬇️ Get it

1. **Download the latest version:**
   **[luma-viewer.zip](https://github.com/sveins/luma-viewer/releases/latest/download/luma-viewer.zip)**
   (all versions on the [Releases page](https://github.com/sveins/luma-viewer/releases)).
2. In WordPress, go to **Plugins → Add New → Upload Plugin**, choose the file,
   and click **Install Now**, then **Activate**.
3. Open **Settings → Luma Viewer**, paste your Luma API key, and click
   **Test connection**.
4. Add the **Luma Calendar** block to any page — done!

Full steps: **[Installation guide](docs/installation.md)**.

## ✨ What you get

- 📅 **Six beautiful views** — **List, Week, Month, Day, Photo, and Summary** —
  and visitors can switch between them and browse dates right on the page.
- 🧩 **Works with your editor** — a **Gutenberg block**, an **Elementor widget**,
  and a classic **shortcode**, all with the same options and a live preview.
- 🎨 **Lists, your way** — full cards, compact rows, or a minimal title-only
  list; group by day or by month. Great for a full events page *or* a sidebar.
- 🔒 **Members-only events** — pair it with MemberPress to show certain event
  categories only to your members; everyone else sees a teaser or nothing.
- ⚡ **Fast and always fresh** — events are cached and refreshed for you in the
  background, so pages load instantly and never wait on the network.
- 🔎 **Friendly to search engines** — each event gets its own page with rich
  event data built in, so Google can show it properly.
- 🔗 **One-click register** — "Register" buttons send people straight to the
  event on Lu.ma. No payments or personal data handled on your site.
- 🔄 **Automatic updates** — new versions show up right in your WordPress
  dashboard, with optional one-click auto-update.

## The views at a glance

| View | Best for |
|---|---|
| **List** | Your main "upcoming events" page (cards, compact, or minimal). |
| **Week** | A focused look at the next seven days. |
| **Month** | A familiar full-month calendar grid. |
| **Day** | A single day's agenda. |
| **Photo** | An eye-catching grid led by event cover images. |
| **Summary** | A tidy, space-saving date-grouped list. |

## Add it to a page

Use the **Luma Calendar** block or Elementor widget and pick your options in the
sidebar — or use a shortcode:

```text
[luma_calendar]                                   Upcoming events (List)
[luma_calendar view="month"]                      Month calendar
[luma_calendar view="list" layout="minimal" count="5"]   Compact sidebar list
[luma_calendar view="photo" tag="Workshops"]      Photo grid for one category
```

See the **[usage guide](docs/usage.md)** for every option.

## Members-only events

With **MemberPress**, map your Lu.ma event categories to memberships under
**Settings → Luma Viewer → Membership access**. Members see the full event;
everyone else sees a teaser with a join/log-in button — or nothing at all, your
choice. Events without a mapped category stay public.
[Learn more](docs/membership.md).

## Requirements

- WordPress 6.4+ and a Lu.ma calendar on **Luma Plus** (needed for an API key).
- Optional: **MemberPress** for members-only events, **Elementor** for the
  Elementor widgets.

## Documentation & support

📖 **[Read the docs](docs/)** — installation, usage, blocks & Elementor,
members-only events, updates, and FAQ. Found a bug or have an idea? Please
[open an issue](https://github.com/sveins/luma-viewer/issues).

---

<sub>Building or contributing? See **[CONTRIBUTING.md](CONTRIBUTING.md)**.
Licensed under [GPL-2.0-or-later](LICENSE).
"Lu.ma"/"Luma" and "MemberPress" are trademarks of their respective owners; this
is an independent integration, not affiliated with or endorsed by them.</sub>
