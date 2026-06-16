# MemberPress access by category

With **MemberPress** active, you can restrict events by their Luma category
(tag). This is optional — without MemberPress, every event is public.

## How it works

1. In Luma, tag events with categories (e.g. "Members", "VIP").
2. In WordPress: **Settings → Luma Viewer → Membership access**, map each Luma
   category to one or more MemberPress memberships.
3. Choose what non-members see for gated events:
   - **Teaser** — title, date, and cover are shown, with a "join / log in"
     button instead of the event link.
   - **Hidden** — the event is omitted entirely.

Events whose category isn't mapped stay public. A member who holds **any** of a
category's mapped memberships sees the full event. Administrators always see
everything (for previewing).

## Privacy & caching

- The cache stores membership-agnostic data; access is decided per request, so
  one member's view is never cached and served to someone else.
- Pages that contain gated content for a logged-in user are flagged
  `DONOTCACHEPAGE` and sent no-cache headers, so full-page caches don't leak
  member-only content. Anonymous visitors all see the same public/teaser result,
  which remains cacheable.

## If MemberPress isn't active

The mapping UI explains that MemberPress is required, and all events are shown to
everyone until it's enabled — the plugin never errors because MemberPress is
missing.
