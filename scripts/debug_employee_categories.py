"""Debug probe: employee categories page actions."""
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

    print("URL:", page.url)
    print("Add category count:", page.locator("text=Add category").count())
    print("buttons:", [b.inner_text() for b in page.locator("button").all()])
    data_page = page.locator("#app").get_attribute("data-page")
    if data_page:
        import json
        props = json.loads(data_page)["props"]
        print("auth.permissions:", props.get("auth", {}).get("permissions"))
        print("auth.roles:", props.get("auth", {}).get("roles"))
    page.screenshot(path="docs/screenshots/debug-employee-categories.png", full_page=True)
    browser.close()
