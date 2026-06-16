---
name: luma-api
description: >-
  Reference for the Lu.ma (luma.com) public events/calendar API: base URL, the
  x-luma-api-key auth scheme, the full endpoint index, cursor pagination, rate
  limits and 429 backoff, webhook events, and the read-endpoint response shapes.
  Use this skill whenever the task involves the Luma API, lu.ma events or
  calendars, an "x-luma-api-key", public-api.luma.com, syncing/displaying Luma
  events, Luma webhooks, or building anything that reads or writes Luma event
  data — even if the user only says "the calendar API" in a Luma context.
---

# Lu.ma Public API

Authoritative reference distilled from `docs.luma.com` (endpoint index from
`docs.luma.com/llms.txt`). The SPA reference pages are not fetchable as plain
HTML, so **exact response field names should be confirmed against the live API
or `docs.luma.com/reference` during implementation** — the structures below are
correct in shape but treat individual field names as "verify before relying on".

## Essentials

- **Base URL:** `https://public-api.luma.com`
- **Auth:** send the API key in the header `x-luma-api-key: <key>`. No OAuth
  needed for first-party use (OAuth tokens exist for third-party apps).
- **Subscription:** the calendar must be on **Luma Plus** for API access.
- **Content type:** JSON. POST bodies are JSON.
- **Get a key:** Luma calendar → Settings → Developer → API Keys.

## Two kinds of key (pick based on scope)

| Key type | Scope | Rate limit |
|---|---|---|
| **Calendar key** | one calendar | 200 requests / minute / calendar |
| **Organization key** | all calendars in an org | 500 requests / minute / org |

This project (Luma Viewer) uses a **single Calendar key**.

## Rate limits & resilience

- Exceeding the limit returns **HTTP 429**. Respect `Retry-After` if present;
  otherwise back off exponentially (e.g. 1s, 2s, 4s) with jitter and a cap.
- Because limits are low for a public website, **never call the API on the
  front-end hot path**. Cache responses (transient/object cache) and refresh on
  a schedule or via webhooks. See [[luma-viewer]] for the caching pattern and
  the wp-performance library skill for object-cache / HTTP-API guidance.

## Pagination (cursor-based)

List endpoints return a page plus a cursor. The conventional shape:

```jsonc
{
  "entries": [ /* … */ ],
  "has_more": true,
  "next_cursor": "..."   // pass back as the cursor param to get the next page
}
```

Request params (confirm exact names live): `pagination_limit` (page size),
`pagination_cursor` (the `next_cursor` from the previous page). To fetch
everything, loop while `has_more` is true, passing `next_cursor` each time.

## Read endpoints this project uses

Full list of every endpoint is in `references/endpoints.md`. The ones Luma
Viewer depends on:

| Purpose | Method & path |
|---|---|
| Validate key / get calendar info | `GET /v1/calendars/get` |
| List events on the calendar | `GET /v1/calendars/events/list` |
| Get one event (detail page) | `GET /v1/events/get?event_api_id=<id>` |
| List event tags (= our "categories") | `GET /v1/calendars/event-tags/list` |
| List a ticket type / pricing | `GET /v1/events/ticket-types/list` |

### `GET /v1/calendars/events/list`

Returns events managed by the calendar. Useful request params (verify names):
`before` / `after` (ISO 8601 date filters), `sort_direction` (`asc`/`desc`),
`sort_column` (e.g. `start_at`), plus the pagination params above.

Documented response structure (verify field names live):

```jsonc
{
  "entries": [
    {
      "api_id": "evt-xxxxxxxx",
      "event": {
        "api_id": "evt-xxxxxxxx",
        "calendar_api_id": "cal-xxxxxxxx",
        "name": "Event title",
        "start_at": "2026-07-01T18:00:00.000Z",   // ISO 8601, UTC
        "end_at":   "2026-07-01T20:00:00.000Z",
        "timezone": "America/New_York",            // display tz
        "cover_url": "https://images.lu.ma/...",
        "url": "my-event-slug",                    // -> https://lu.ma/<url>
        "geo_address_json": {                       // null for online events
          "city": "...", "type": "...",
          "address": "...", "full_address": "...",
          "description": "Venue name"
        },
        "geo_latitude": "40.7",
        "geo_longitude": "-74.0",
        "visibility": "public"
      },
      "tags": [ { "api_id": "evttag-xxxx", "name": "Members" } ]
    }
  ],
  "has_more": false,
  "next_cursor": null
}
```

Notes that matter for rendering:
- Times are UTC ISO 8601; `timezone` is the event's display zone. Convert for
  display; don't assume the site timezone.
- `url` is a **slug**, not a full URL — the public page is `https://lu.ma/<url>`.
- `geo_address_json` is `null` for online events — treat missing location as
  "Online / virtual".
- `tags[]` are the categories the MemberPress mapping keys off of.
- The endpoint lists events **managed by** the calendar; events merely listed
  on it (managed elsewhere) may not appear.

## Webhooks (cache invalidation)

Create/manage via `POST /v2/webhooks/create`, `GET /v1/webhooks/list`, etc.
Relevant event types for keeping a cache fresh:

- `Event Created`, `Event Updated`, `Event Canceled`
- `Calendar Event Added`

Verify the webhook signature on receipt before busting cache (see the webhook
docs for the signing header/secret). Use these to invalidate cached event lists
instead of short TTLs alone.

## Gotchas

- **Luma Plus required** — without it, key generation/API access is unavailable.
- **Key is per calendar** — a Calendar key only sees its own calendar.
- **Low rate limits** — always cache; never fan out per page view.
- **SPA docs** — reference pages render client-side; fetch `llms.txt` for the
  machine-readable index, and confirm field names against a real API response.
- **Write endpoints exist** (create/update events, guests, coupons) but this
  project is read-only + link-out; avoid writes unless explicitly required.

## Quick test (PowerShell)

```powershell
$headers = @{ "x-luma-api-key" = $env:LUMA_API_KEY }
Invoke-RestMethod -Uri "https://public-api.luma.com/v1/calendars/get" -Headers $headers
```

A 200 with calendar JSON confirms the key + Luma Plus are valid.
