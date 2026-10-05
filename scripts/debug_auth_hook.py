"""Debug probe: read window.__AUTH_DEBUG from the employee categories page."""
import json
from playwright.sync_api import sync_playwright

BASE = "http://127.0.0.1:8099"

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)
    page = browser.new_page(viewport={"width": 1280, "height": 800})
    page.on("console", lambda m: print(f"CONSOLE[{m.type}] {m.text}") if m.type in ("error", "warning") else None)
    page.on("pageerror", lambda e: print(f"PAGEERROR {e}"))

    page.goto(f"{BASE}/login")
    page.wait_for_load_state("networkidle")
    page.fill('input[name="username"]', "employee")
    page.fill('input[name="password"]', "Employee@1234")
    page.click('form button[type="submit"]')
    page.wait_for_timeout(2500)

    page.goto(f"{BASE}/categories")
    page.wait_for_timeout(2500)

    print("AUTH_DEBUG:", json.dumps(page.evaluate("() => window.__AUTH_DEBUG || null"))[:600])
    print("Add category count:", page.locator("text=Add category").count())
    browser.close()
