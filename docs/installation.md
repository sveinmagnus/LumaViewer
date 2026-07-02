# Installation

## Requirements

- WordPress 6.4 or newer, PHP 7.4 or newer
- A Lu.ma calendar on **Luma Plus** (required for API access)
- Optional: **MemberPress** (category-based access) and **Elementor** (widgets)

## Install from the release zip (recommended)

1. Download **[luma-viewer.zip](https://github.com/sveinmagnus/LumaViewer/releases/latest/download/luma-viewer.zip)**.
2. In WordPress: **Plugins → Add New → Upload Plugin** → choose the zip → **Install Now** → **Activate**.

## Install manually

1. Unzip and copy the `luma-viewer` folder into `wp-content/plugins/`.
2. Activate **Luma-viewer** under **Plugins**.

## Connect your Luma calendar

1. In Luma: **Settings → Developer → API Keys** and copy your calendar API key.
2. In WordPress: **Settings → Luma-viewer**, paste the key, **Save Changes**,
   then click **Test connection** — you should see your calendar name.

## Settings overview

- **Display** — default view, events per page, time-zone mode, cache lifetime,
  and the single-event URL base.
- **Membership access** — map Luma categories to MemberPress memberships (see
  [MemberPress access](membership.md)).
- **Cache and sync** — the webhook URL to paste into Luma for instant refresh,
  and a manual "Clear cached events" button.

## Permalinks

Single-event pages use a rewrite rule (`/<base>/<event-id>/`). If those links
return 404 after install or after changing the base, re-save **Settings →
Permalinks** once.
