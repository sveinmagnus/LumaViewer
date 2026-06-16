# Updates & distribution

There are two ways to give users update notices (and optional auto-update) in
the WordPress admin. They are mutually exclusive — pick one per build.

## Option A — Self-hosted updates via GitHub Releases (built in)

This plugin bundles the [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker)
library and wires it to this repository's GitHub Releases. Once a user installs
a release zip, WordPress will:

- show an update notice on **Dashboard → Updates** and the **Plugins** screen
  when a newer release exists, and
- let them **enable auto-updates** for the plugin (the per-plugin toggle WP added
  in 5.5) just like a directory-hosted plugin.

### How it works

`src/Update/Updater.php` calls the library on load, pointing at the repo and
preferring the release **asset** (`luma-viewer.zip`) over the source tarball, so
users receive the production build (with `vendor/` and compiled blocks).

### What you need to do

1. Make sure the repository URL is correct. By default it points at
   `https://github.com/sveins/luma-viewer/`. Override it without editing code:

   ```php
   add_filter( 'luma_viewer_update_repo', fn() => 'https://github.com/OWNER/REPO/' );
   ```

2. Publish releases with the built zip attached. Pushing a `vX.Y.Z` tag runs
   `.github/workflows/release.yml`, which builds `luma-viewer.zip` and attaches
   it to the GitHub release automatically. Before tagging, bump the `Version`
   header in `luma-viewer.php`, the `Stable tag` in `readme.txt`, and
   `CHANGELOG.md`.

3. The checker polls roughly every 12 hours; users can also click
   **Check again** on the Updates screen.

To disable GitHub update checks entirely (for example, in a WordPress.org build):

```php
add_filter( 'luma_viewer_enable_github_updates', '__return_false' );
```

Private repositories work too — see the library's docs for adding an
authentication token.

## Option B — The WordPress.org Plugin Directory

Hosting on WordPress.org gives the same native update notices and auto-update
toggle, plus discoverability and install counts. The trade-off is a review
process and ongoing compliance.

### Process (summary)

1. Prepare a compliant `readme.txt` (this repo includes one) with an accurate
   **Stable tag**, **Tested up to**, and a GPL-compatible **License**.
2. Submit the plugin for **manual review** at
   <https://wordpress.org/plugins/developers/add/>. Review typically takes about
   **1–10 days**; you'll get email feedback if changes are needed.
3. On approval you receive a **Subversion (SVN)** repository
   (`https://plugins.svn.wordpress.org/<slug>/`). You publish by committing to
   SVN `trunk` and tagging releases there (not via Git). Screenshots/banner/icon
   go in SVN `assets/`.
4. Updates then flow through WordPress.org automatically — **disable Option A**
   for that build (see the filter above) so the two don't conflict.

References: the
[Plugin Handbook — submitting & maintaining](https://developer.wordpress.org/plugins/wordpress-org/planning-submitting-and-maintaining-plugins/)
and the
[Detailed Plugin Guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/).

### ⚠️ Naming / trademark caveat (Guideline 17)

WordPress.org's guidelines say a third-party trademark may appear in a plugin's
display name **only after a connector** such as "for", "with", or "using" — it
cannot lead the name, and the **slug** generally cannot be the bare trademark.
Because "Lu.ma"/"Luma" is a third-party mark:

- The directory **display name** likely needs to be something like
  **"Event Calendar for Luma"** rather than "Luma Viewer".
- The **slug** `luma-viewer` may be rejected; expect to need a name like
  `event-calendar-for-luma` (the WordPress.org team assigns/approves the slug).
- Remove any trademarked **tags** from `readme.txt`.

None of this affects self-hosted (Option A) distribution — the plugin folder and
text domain can remain `luma-viewer`. It only matters if/when you submit to the
directory. Confirm current rules before submitting.

## Recommendation

Use **Option A (GitHub Releases)** now — it already provides update alerts and
auto-update with no review. Consider **Option B** later for reach, planning for
the naming change above.
