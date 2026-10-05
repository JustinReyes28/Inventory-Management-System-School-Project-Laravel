"""Debug probe: read Inertia page props via the DOM dataset."""
from playwright.sync_api import sync_playwright

BASE = "http://127.0.0.1:8099"

JS = """() => {
    const el = document.getElementById('app');
    const raw = el && el.dataset ? el.dataset.page : null;
    if (!raw) return { error: 'no dataset.page', html: document.body.innerHTML.slice(0, 500) };
    const page = JSON.parse(raw);
    return {
        keys: Object.keys(page),
        propsKeys: Object.keys(page.props || {}),
        auth: page.props ? page.props.auth : null,
    };
}"""

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

    import json
    print(json.dumps(page.evaluate(JS), indent=1)[:2000])
    browser.close()
