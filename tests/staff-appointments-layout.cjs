const { chromium } = require('playwright');
const assert = require('node:assert/strict');

(async () => {
    const browser = await chromium.launch({ headless: true, ...(process.env.QA_BROWSER_CHANNEL ? { channel: process.env.QA_BROWSER_CHANNEL } : {}) });
    const page = await browser.newPage();
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    try {
        await page.goto('http://127.0.0.1:18080/login');
        await page.locator('input[name="email"]').fill('staff-appointments@example.invalid');
        await page.locator('input[name="password"]').fill('Appointments-only1!');
        await page.locator('form').filter({ has: page.locator('input[name="password"]') }).first().locator('button[type="submit"]').click();
        await page.goto('http://127.0.0.1:18080/staff/atendimentos');
        assert.equal(await page.locator('.staff-appointment-item').count(), 2);
        await page.locator('.staff-appointment-item summary').first().click();
        await page.waitForFunction(() => document.querySelectorAll('.staff-appointment-item[open]').length === 1);
        assert.equal(await page.locator('.staff-appointment-item[open]').count(), 1);
        await page.locator('.staff-appointment-item summary').last().click();
        await page.waitForFunction(() => document.querySelectorAll('.staff-appointment-item[open]').length === 1);
        assert.equal(await page.locator('.staff-appointment-item[open]').count(), 1);
        let checks = 0;
        for (const width of [320, 390, 680, 768, 1024, 1440]) {
            await page.setViewportSize({ width, height: 900 });
            const scroll = await page.evaluate(() => document.documentElement.scrollWidth);
            assert.ok(scroll <= width + 1, `Listagem transbordou em ${width}px: ${scroll}`);
            checks++;
        }
        await page.locator('.staff-appointment-item summary').first().click();
        await page.waitForFunction(() => document.querySelectorAll('.staff-appointment-item[open]').length === 1);
        await page.locator('.staff-appointment-item[open] a').click();
        await page.waitForURL('**/staff/atendimentos/*');
        assert.equal(await page.locator('.staff-attendance-panel').count(), 3);
        assert.ok(await page.locator('.staff-attendance-action').isVisible());
        for (const width of [320, 390, 680, 768, 1024, 1440]) {
            await page.setViewportSize({ width, height: 900 });
            const scroll = await page.evaluate(() => document.documentElement.scrollWidth);
            assert.ok(scroll <= width + 1, `Detalhes transbordaram em ${width}px: ${scroll}`);
            checks++;
        }
        assert.deepEqual(errors, []);
        console.log(`OK: ${checks} verificações responsivas, accordion e página de detalhes.`);
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
