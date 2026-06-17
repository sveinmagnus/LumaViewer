# WordPress.org assets

These files are **not** part of the plugin zip. They belong in the WordPress.org
SVN `assets/` directory and power the plugin's directory listing. Add them here
when preparing a WordPress.org submission (the release workflow excludes this
folder from the distributed zip).

Add (PNG, sRGB):

- `icon-128x128.png` and `icon-256x256.png` (or `icon.svg`)
- `banner-772x250.png` and `banner-1544x500.png` (retina)
- `screenshot-1.png`, `screenshot-2.png`, … — must line up with a `== Screenshots ==`
  section in `readme.txt`

> **Naming/trademark:** see [docs/updates.md](../docs/updates.md) → *The WordPress.org
> Plugin Directory*. Because "Lu.ma"/"Luma" is a third-party mark, the directory
> name/slug will likely need a connector form such as **"Event Calendar for Luma"**
> (`event-calendar-for-luma`). This does not affect self-hosted distribution.
