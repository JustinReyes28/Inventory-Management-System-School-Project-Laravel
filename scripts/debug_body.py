"""Debug probe: full body text and error state after the layout fix."""
import json
from playwright.sync_api import sync_playwright

BASE = "http://127.0.0.1:8099"

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)
    page = browser.new_page(viewport={"width": 1280, "height": 800})
    page.on("console", lambda m: print(f"CONSOLE[{m.type}] {m.text}"))
    page.on("pageerror", lambda e: print(f"PAGEERROR {e}"))
    page.on("response", lambda r: print(f"RESP {r.status} {r.url}") if r.status >= 400 else None)

    page.goto(f"{BASE}/login")
    page.wait_for_load_state("networkidle")
    page.fill('input[name="username"]', "employee")
    page.fill('input[name="password"]', "Employee@1234")
    page.click('form button[type="submit"]')
    page.wait_for_timeout(2500)

    print("AFTER LOGIN URL:", page.url)
    print("AFTER LOGIN BODY:", page.locator("body").inner_text()[:500].replace("\n", " | "))
    print("AUTH_DEBUG:", json.dumps(page.evaluate("() => window.__AUTH_DEBUG || null"))[:400])

    page.goto(f"{BASE}/categories")
    page.wait_for_timeout(2500)
    print("CATEGORIES URL:", page.url)
    print("CATEGORIES BODY:", page.locator("body").inner_text()[:700].replace("\n", " | "))
    print("AUTH_DEBUG:", json.dumps(page.evaluate("() => window.__AUTH_DEBUG || null"))[:400])
    browser.close()
