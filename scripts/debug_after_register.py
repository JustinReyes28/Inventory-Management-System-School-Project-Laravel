"""Debug probe: what renders after registration."""
import time
from playwright.sync_api import sync_playwright

BASE = "http://127.0.0.1:8099"

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)
    page = browser.new_page(viewport={"width": 1280, "height": 800})
    page.on("console", lambda m: print(f"CONSOLE[{m.type}] {m.text}") if m.type in ("error", "warning") else None)
    page.on("pageerror", lambda e: print(f"PAGEERROR {e}"))
    page.on("response", lambda r: print(f"RESP {r.status} {r.url}") if r.status >= 400 else None)

    suffix = str(int(time.time()))
    page.goto(f"{BASE}/register")
    page.wait_for_load_state("networkidle")
    page.fill('input[name="full_name"]', "Debug Probe")
    page.fill('input[name="username"]', f"debug.probe.{suffix}")
    page.fill('input[name="email"]', f"debug.probe.{suffix}@example.test")
    page.fill('input[name="password"]', "BrowserPass1!")
    page.fill('input[name="password_confirmation"]', "BrowserPass1!")
    page.click('form button[type="submit"]')
    page.wait_for_timeout(3000)

    print("URL:", page.url)
    print("nav count:", page.locator("nav[aria-label='Primary navigation']").count())
    print("aside count:", page.locator("aside").count())
    print("Sign out count:", page.locator("text=Sign out").count())
    print("nav labels:", [t.inner_text() for t in page.locator("nav[aria-label='Primary navigation'] a span").all()])
    body = page.locator("body").inner_text()
    print("BODY:", body[:800].replace("\n", " | "))
    page.screenshot(path="docs/screenshots/debug-after-register.png", full_page=True)
    browser.close()
