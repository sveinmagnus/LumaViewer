# FAQ

**Do I need a paid Luma plan?**
Yes — the Luma public API requires an active Luma Plus subscription on the
calendar you connect.

**Does it duplicate events into WordPress posts?**
No. Events are fetched and cached on demand; nothing is stored as posts. Single
events are served on virtual URLs with proper SEO metadata.

**How do visitors register?**
On Luma. The plugin links out to each event's Luma page; it never handles
payments or personal data.

**Is MemberPress required?**
Only for category-based access control. Without it, all events are public.

**Will it slow down my site?**
No — events are cached and pre-warmed by WP-Cron, so normal page views don't call
the Luma API. The AJAX navigation endpoint reads from cache and is rate-limited.

**The calendar is empty / shows an error.**
Check **Settings → Luma-viewer → Test connection**. A valid Luma Plus API key is
required. Admins see the underlying error message; visitors see a friendly
notice.

**Single-event links 404.**
Re-save **Settings → Permalinks** once after installing or after changing the
single-event URL base.

**Do the blocks work after cloning the repo?**
Run `npm install && npm run build` first. Release zips already include the built
blocks.
