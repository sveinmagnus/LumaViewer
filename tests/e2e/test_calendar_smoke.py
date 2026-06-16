"""End-to-end smoke test for Luma Viewer against a running WordPress site.

Follows the `webapp-testing` skill: native Playwright. The server (a WordPress
Playground instance booted from ../../blueprint.json, or any WP with this plugin
active) is started outside this script — by CI, or locally via the
webapp-testing helper:

    python <webapp-testing>/scripts/with_server.py \
        --server "npx @wp-playground/cli@latest server --auto-mount --blueprint=./blueprint.json --port=9400" \
        --port 9400 -- pytest -q tests/e2e

Configure the base URL with PLAYWRIGHT_BASE_URL (default http://127.0.0.1:9400).
"""

import os

from playwright.sync_api import sync_playwright

BASE_URL = os.environ.get("PLAYWRIGHT_BASE_URL", "http://127.0.0.1:9400")


def test_events_page_loads():
    """The blueprint creates an 'Events' page containing [luma_calendar]."""
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        page.goto(f"{BASE_URL}/events/", wait_until="networkidle")

        # Smoke: the page resolves and is the Events page.
        assert "Events" in page.title()

        # TODO(P1+): once the List/Month views render, assert the calendar
        # container and that at least one event card (or the empty-state) shows:
        #   page.wait_for_selector(".luma-viewer", timeout=10_000)
        #   assert page.locator(".luma-viewer__event, .luma-viewer__empty").count() >= 1

        browser.close()


if __name__ == "__main__":
    test_events_page_loads()
    print("ok")
