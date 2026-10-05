"""Debug probe: category modal open/submit."""
from playwright.sync_api import sync_playwright

BASE = "http://127.0.0.1:8099"

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)
    page = browser.new_page(viewport={"width": 1280, "height": 800})
    page.on("console", lambda m: print(f"CONSOLE[{m.type}] {m.text}"))
    page.on("pageerror", lambda e: print(f"PAGEERROR {e}"))

    page.goto(f"{BASE}/login")
    page.wait_for_load_state("networkidle")
    page.fill('input[name="username"]', "employee")
    page.fill('input[name="password"]', "Employee@1234")
    page.click('form button[type="submit"]')
    page.wait_for_timeout(2500)

    page.goto(f"{BASE}/categories")
    page.wait_for_timeout(1500)
    print("add btn count:", page.locator("text=Add category").count())
    page.click("text=Add category")
    page.wait_for_timeout(1200)
    print("form count:", page.locator("form#category-form").count())
    print("any forms:", [f.get_attribute("id") for f in page.locator("form").all()])
    print("dialog count:", page.locator("[role=dialog]").count())
    body = page.locator("body").inner_text()
    print("BODY TAIL:", body[-500:].replace("\n", " | "))
    browser.close()
