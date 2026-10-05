"""Debug probe: raw DOM + inertia props location on the employee categories page."""
import json
import re
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

    html = page.content()
    print("HTML length:", len(html))
    m = re.search(r'data-page="([^"]{0,200})', html)
    print("data-page attr found:", bool(m), m.group(1)[:120] if m else "")
    print("has app div:", 'id="app"' in html)

    # Extract the full page JSON wherever it is embedded.
    m2 = re.search(r'data-page="(.+?)"', html, re.S)
    if m2:
        import html as htmllib
        props = json.loads(htmllib.unescape(m2.group(1)))["props"]
        print("auth:", json.dumps(props.get("auth", {}))[:400])

    idx = html.find("Categories")
    print("HTML AROUND HEADER:", html[max(0, idx - 300): idx + 700].replace("\n", " "))
    browser.close()
