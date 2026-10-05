"""Dev-mode boot check: the page must hydrate without the 'can't detect preamble' error."""
from playwright.sync_api import sync_playwright

BASE = "http://127.0.0.1:8099"
errors = []

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)
    page = browser.new_page(viewport={"width": 1280, "height": 800})
    page.on("console", lambda m: errors.append(f"{m.type}: {m.text}") if m.type == "error" else None)
    page.on("pageerror", lambda e: errors.append(f"pageerror: {e}"))

    page.goto(f"{BASE}/login")
    page.wait_for_load_state("networkidle")
    page.wait_for_timeout(1500)
    rendered = page.locator("text=Sign in").first.is_visible()
    blank = page.locator("#app").inner_text().strip() == ""
    print("dev mode login rendered:", rendered, "| blank page:", blank)
    print("console/page errors:", len(errors))
    for err in errors[:5]:
        print("  ", err)
    browser.close()
