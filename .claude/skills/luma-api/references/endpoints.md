# Luma API — full endpoint index

Source: `https://docs.luma.com/llms.txt` (machine-readable doc index). Base URL
for all paths: `https://public-api.luma.com`. Auth header on every request:
`x-luma-api-key`.

Read this file when you need an endpoint not covered in the main SKILL.md (e.g.
guests, coupons, ticket types, webhooks, org-level routes).

## Calendar
- `GET  /v1/calendars/get`
- `GET  /v1/calendars/admins/list`
- `GET  /v1/calendars/contact-tags/list`
- `GET  /v1/calendars/contacts/list`
- `GET  /v1/calendars/coupons/list`
- `GET  /v1/calendars/event-tags/list`        # event categories / tags
- `GET  /v1/calendars/events/list`            # primary: list calendar events
- `GET  /v1/calendars/events/lookup`
- `POST /v1/calendars/contact-tags/apply`
- `POST /v1/calendars/contact-tags/create`
- `POST /v1/calendars/contact-tags/delete`
- `POST /v1/calendars/contact-tags/unapply`
- `POST /v1/calendars/contact-tags/update`
- `POST /v1/calendars/contacts/import`
- `POST /v1/calendars/coupons/create`
- `POST /v1/calendars/coupons/update`
- `POST /v1/calendars/event-tags/apply`
- `POST /v1/calendars/event-tags/create`
- `POST /v1/calendars/event-tags/delete`
- `POST /v1/calendars/event-tags/unapply`
- `POST /v1/calendars/event-tags/update`
- `POST /v1/calendars/events/add`
- `POST /v1/calendars/events/approve`
- `POST /v1/calendars/events/reject`
- `POST /v1/calendars/update`

## Event
- `GET  /v1/events/get`                        # primary: single event detail
- `GET  /v1/events/coupons/list`
- `GET  /v1/events/ticket-types/get`
- `GET  /v1/events/ticket-types/list`
- `POST /v1/events/create`
- `POST /v1/events/update`
- `POST /v1/events/cancel`
- `POST /v1/events/cancel/request`
- `POST /v1/events/coupons/create`
- `POST /v1/events/coupons/update`
- `POST /v1/events/ticket-types/create`
- `POST /v1/events/ticket-types/update`
- `POST /v1/events/ticket-types/delete`

## Guests
- `GET  /v1/events/guests/get`
- `GET  /v1/events/guests/list`
- `POST /v1/events/guests/add`
- `POST /v1/events/guests/send-invites`
- `POST /v1/events/guests/update-status`

## Hosts
- `POST /v1/events/hosts/add`
- `POST /v1/events/hosts/remove`
- `POST /v1/events/hosts/update`

## Users / People
- `GET  /v1/users/get-self`

## Memberships (Luma's own membership tiers — distinct from MemberPress)
- `GET  /v1/memberships/tiers/list`
- `POST /v1/memberships/members/add`
- `POST /v1/memberships/members/update-status`

## Organization (needs an Organization key, not used by single-calendar mode)
- `GET  /v1/organizations/admins/list`
- `GET  /v1/organizations/calendars/list`
- `GET  /v1/organizations/events/list`
- `POST /v1/organizations/events/transfer-calendar`
- `POST /v2/organizations/calendars/create`

## Utility
- `GET  /v1/entities/lookup`
- `POST /v1/images/create-upload-url`

## Webhooks
- `GET  /v1/webhooks/list`
- `GET  /v2/webhooks/get`
- `POST /v2/webhooks/create`
- `POST /v2/webhooks/update`
- `POST /v1/webhooks/delete`

### Webhook event types
- Calendar Event Added
- Calendar Person Subscribed
- Event Created
- Event Updated
- Event Canceled
- Guest Registered
- Guest Updated
- Ticket Registered

## Doc pages (for humans)
API Conventions · API Formats · Code Examples · Getting Started · Rate Limits ·
Changelog — all under `docs.luma.com`.
