// No database: real PHP view + app.js, mocked availability endpoint.
const { chromium } = require('playwright');
const { execFileSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '..');
(async () => {
    const browser = await chromium.launch({ headless: true, ...(process.env.QA_BROWSER_CHANNEL ? { channel: process.env.QA_BROWSER_CHANNEL } : {}) });
    try {
        for (const time of ['', '09:00', '11:00']) {
            const markup = execFileSync('php', [path.join(__dirname, 'fixtures/booking-availability.php'), time], { encoding: 'utf8' });
            const page = await browser.newPage();
            const errors = [];
            let calls = 0;
            let fail = false;
            page.on('pageerror', error => errors.push(error.message));
            await page.route('http://booking-qa.local/**', async route => {
                const url = new URL(route.request().url());
                if (url.pathname.endsWith('/disponibilidade')) {
                    calls++;
                    return route.fulfill({ status: fail ? 503 : 200, json: fail ? { error: 'Falha simulada' } : {
                        slots: ['09:00', '10:00'], staff: [{ id: '11111111-1111-4111-8111-111111111111', available: true }],
                    } });
                }
                if (url.pathname === '/app.js') return route.fulfill({ contentType: 'text/javascript', body: fs.readFileSync(path.join(root, 'public/assets/js/app.js')) });
                return route.fulfill({ contentType: 'text/html', body: `<!doctype html><html><body>${markup}<script src="/app.js" defer></script></body></html>` });
            });
            await page.goto('http://booking-qa.local/booking');
            await page.waitForTimeout(150);
            assert.equal(calls, 0, 'Opening SSR page must not fetch again');
            assert.equal(await page.locator('input[name="staff_id"][value=""]').isDisabled(), time !== '09:00');
            await page.selectOption('[data-booking-time]', '09:00');
            await page.waitForFunction(() => !document.querySelector('[data-booking-time]').disabled);
            assert.equal(calls, 1, 'Changing time must refresh');
            await page.locator('[data-booking-date]').fill('2030-01-08');
            await page.locator('[data-booking-date]').dispatchEvent('change');
            await page.waitForFunction(() => !document.querySelector('[data-booking-time]').disabled);
            assert.equal(await page.locator('[data-booking-time]').inputValue(), '');
            const beforeRestore = calls;
            await page.evaluate(() => window.dispatchEvent(new PageTransitionEvent('pageshow', { persisted: true })));
            await page.waitForFunction(() => !document.querySelector('[data-booking-time]').disabled);
            assert.equal(calls, beforeRestore + 1, 'BFCache restore must refresh');
            fail = true;
            await page.selectOption('[data-booking-time]', '10:00');
            await page.waitForFunction(() => document.querySelector('[data-slot-status]').textContent === 'Falha simulada');
            assert.equal(await page.locator('button[type="submit"]').isDisabled(), true);
            assert.deepEqual(errors, []);
            await page.close();
        }
        console.log('OK: zero initial requests; selected/unavailable slots, date/time changes, BFCache and API failure.');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
