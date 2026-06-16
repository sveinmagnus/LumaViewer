# MemberPress integration (project-specific)

How Luma Viewer ties Luma event **tags/categories** to MemberPress
**memberships** ("member classes") and decides what each visitor may see. This
is project-specific because the library has no MemberPress skill; for general
security/sanitization/escaping practice defer to the **wp-plugin-development**
skill.

MemberPress's class/method names can change between versions — treat the names
below as the intended approach and **verify against the installed MemberPress
version**, guarding every call so the plugin degrades gracefully when
MemberPress is absent.

## Detecting MemberPress

```php
function luma_viewer_memberpress_active(): bool {
    return defined( 'MEPR_VERSION' ) || class_exists( 'MeprUser' );
}
```

If inactive: treat every event as public, skip all gating, and show a dismissible
admin notice. The front end must never fatal because MemberPress is missing.

## Listing membership levels (for the settings mapping UI)

MemberPress memberships are the `memberpressproduct` custom post type. The admin
mapping screen pairs each Luma event-tag with one or more of these:

```php
$levels = get_posts( array(
    'post_type'   => 'memberpressproduct',
    'post_status' => 'publish',
    'numberposts' => -1,
    'orderby'     => 'title',
    'order'       => 'ASC',
) );
// Use $level->ID as the stored value, $level->post_title as the label.
```

Luma tag ids/names come from `GET /v1/calendars/event-tags/list` (see
[[luma-api]]). Cache that list for the settings screen too. Store the mapping in
the plugin's option:

```php
// luma_viewer_settings['category_map']
[
  'evttag-abc' => [ 12, 47 ],   // Luma tag api_id => array of membership post IDs
  'evttag-xyz' => [ 47 ],
]
```

## Checking a user's active memberships

```php
function luma_viewer_user_has_membership( int $user_id, array $level_ids ): bool {
    if ( ! $user_id || empty( $level_ids ) || ! class_exists( 'MeprUser' ) ) {
        return false;
    }
    $mepr_user = new MeprUser( $user_id );
    $active    = (array) $mepr_user->active_product_subscriptions(); // verify per version
    return (bool) array_intersect(
        array_map( 'intval', $active ),
        array_map( 'intval', $level_ids )
    );
}
```

Notes:
- `MeprUser::active_product_subscriptions()` is the common accessor; some
  versions also expose `is_already_subscribed_to( $product_id )`. Pick what the
  installed version supports.
- Check `is_user_logged_in()` first — logged-out users hold nothing.
- Optionally let `manage_options` bypass gating for admin previewing.

## The gate decision

`Membership\Gate::resolve( Event $event, ?int $user_id ): string` → `visible` |
`teaser` | `hidden`:

1. Collect the event's tag ids → map through `category_map` → union of required
   membership level ids.
2. No mapped levels → **visible** (public event).
3. User holds any required level → **visible**.
4. Otherwise the admin-configured behavior:
   - **hidden** — omit from lists entirely.
   - **teaser** — show title/date/cover, hide details, replace the register
     button with a join/login CTA (configurable text + URL).

The Renderer applies the gate **per request** (never cached per user) and marks
any teaser/hidden response `Cache-Control: private, no-store`.

## Edge cases

- Multiple tags, user holds **any one** required level → visible (union, not
  intersection) unless stricter AND logic is requested.
- Tag deleted on Luma but still mapped → ignore stale entries; flag them in the
  settings UI as "removed in Luma".
- Expired/cancelled subscriptions are excluded by
  `active_product_subscriptions()` — don't reimplement expiry.
