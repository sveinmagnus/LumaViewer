"""End-to-end smoke test for Luma Viewer against a running WordPress site.

Native Playwright. The WordPress site (with this plugin active and an 'Events'
page containing [luma_calendar]) is started outside this script. CI uses wp-env
(Docker), which is reliable on GitHub Actions:

    npx @wordpress/env start
    npx @wordpress/env run cli wp rewrite structure '/%postname%/' --hard
    npx @wordpress/env run cli wp post create --post_type=page \
        --post_title=Events --post_name=events --post_status=publish \
        "--post_content=[luma_calendar]"
    PLAYWRIGHT_BASE_URL=http://localhost:8888 pytest -q tests/e2e

Any WordPress with the plugin active works too — just point PLAYWRIGHT_BASE_URL
at it (default http://localhost:8888).
"""

import os

from playwright.sync_api import sync_playwright

BASE_URL = os.environ.get("PLAYWRIGHT_BASE_URL", "http://localhost:8888")


def test_events_page_loads():
    """The blueprint creates an 'Events' page containing [luma_calendar]."""
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        page.goto(f"{BASE_URL}/events/", wait_until="networkidle")

        # The Events page contains [luma_calendar]. With no API key configured it
        # renders the wrapper around an empty/error state, which is enough to prove
        # the render path is wired end-to-end.
        page.wait_for_selector(".luma-viewer", timeout=15000)
        assert page.locator(".luma-viewer").count() >= 1

        browser.close()


if __name__ == "__main__":
    test_events_page_loads()
    print("ok")
