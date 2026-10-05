"""Debug probe for the register page."""
import sys
from playwright.sync_api import sync_playwright

BASE = "http://127.0.0.1:8099"

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)
    page = browser.new_page(viewport={"width": 1280, "height": 800})
    page.on("console", lambda m: print(f"CONSOLE[{m.type}] {m.text}"))
    page.on("pageerror", lambda e: print(f"PAGEERROR {e}"))
    page.on("requestfailed", lambda r: print(f"REQFAILED {r.url} {r.failure}"))
    page.on("response", lambda r: print(f"RESP {r.status} {r.url}") if r.status >= 400 else None)

    page.goto(f"{BASE}/register")
    page.wait_for_load_state("networkidle")
    print("URL:", page.url)
    print("forms:", page.locator("form").count(), "buttons:", page.locator("button[type=submit]").count())
    print("field-error before:", page.locator(".field-error").count())

    page.fill('input[name="full_name"]', "")
    page.fill('input[name="username"]', "admin")
    page.fill('input[name="email"]', "not-an-email")
    page.fill('input[name="password"]', "short")
    page.fill('input[name="password_confirmation"]', "different")
    page.click('form button[type="submit"]')
    page.wait_for_timeout(2500)

    print("URL after submit:", page.url)
    print("field-error after:", page.locator(".field-error").count())
    body = page.locator("body").inner_text()
    print("BODY SNIPPET:", body[:600].replace("\n", " | "))
    page.screenshot(path="docs/screenshots/debug-register.png", full_page=True)
    browser.close()
