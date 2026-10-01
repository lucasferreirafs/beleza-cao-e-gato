// Run with Node.js, Playwright/Chromium and PHP available. No server/database needed.
const { chromium } = require('playwright');
const { execFileSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '..');

(async () => {
    const browser = await chromium.launch({ headless: true, ...(process.env.QA_BROWSER_CHANNEL ? { channel: process.env.QA_BROWSER_CHANNEL } : {}) });
    const output = fs.mkdtempSync(path.join(os.tmpdir(), 'client-dashboard-layout-'));
    let checks = 0;
    try {
        for (const scenario of ['normal', 'long', 'empty', 'single', 'pair']) {
            const html = execFileSync('php', [path.join(__dirname, 'fixtures/client-dashboard.php'), scenario], { cwd: root, encoding: 'utf8' });
            const page = await browser.newPage();
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', async route => {
                const url = new URL(route.request().url());
                if (url.hostname !== 'dashboard-qa.local') return route.abort();
                if (url.pathname === '/cliente') return route.fulfill({ contentType: 'text/html; charset=utf-8', body: html });
                const file = path.resolve(root, 'public', '.' + url.pathname);
                if (!file.startsWith(path.join(root, 'public') + path.sep) || !fs.existsSync(file)) return route.fulfill({ status: 404 });
                const types = { '.css': 'text/css', '.js': 'text/javascript', '.webp': 'image/webp', '.svg': 'image/svg+xml' };
                return route.fulfill({ contentType: types[path.extname(file)] || 'application/octet-stream', body: fs.readFileSync(file) });
            });
            await page.goto('http://dashboard-qa.local/cliente');
            for (const width of [320, 360, 390, 430, 680, 768, 1024, 1440]) {
                await page.setViewportSize({ width, height: 900 });
                if (width <= 1000) {
                    await page.waitForFunction(() => document.querySelector('.sidebar').getBoundingClientRect().right <= 1);
                }
                const geometry = await page.evaluate(() => {
                    const panels = [...document.querySelectorAll('.main .panel, .main .metric-card')];
                    return {
                        viewport: innerWidth,
                        documentWidth: document.documentElement.scrollWidth,
                        overflowing: panels.filter(el => el.scrollWidth > el.clientWidth + 1 || el.getBoundingClientRect().right > innerWidth + 1).map(el => el.className),
                        horizontalTable: [...document.querySelectorAll('.table-wrap')].some(el => el.scrollWidth > el.clientWidth + 1),
                        rowDisplay: document.querySelector('tbody tr') && getComputedStyle(document.querySelector('tbody tr')).display,
                    };
                });
                await page.screenshot({ path: path.join(output, `${scenario}-${width}.png`), fullPage: true });
                if (width === 320) await page.locator('.appointment-highlight').screenshot({ path: path.join(output, `${scenario}-appointment.png`) });
                assert.ok(geometry.documentWidth <= width + 1, `${scenario}/${width}: document overflow ${JSON.stringify(geometry)}`);
                assert.deepEqual(geometry.overflowing, [], `${scenario}/${width}: overflowing panels`);
                assert.equal(geometry.horizontalTable, false, `${scenario}/${width}: horizontal table scrolling`);
                if (scenario !== 'empty') assert.equal(geometry.rowDisplay, width <= 680 ? 'grid' : 'table-row', `${scenario}/${width}: history layout`);
                const total = await page.locator('#appointment-history-table tbody tr').count();
                assert.equal(await page.locator('#appointment-history-table tbody tr:visible').count(), width <= 680 ? Math.min(2, total) : total);
                assert.equal(await page.locator('[data-history-pagination]').isVisible(), width <= 680 && total > 2);
                checks++;
            }
            if (scenario === 'normal' || scenario === 'long') {
                const rows = page.locator('#appointment-history-table tbody tr');
                const visible = page.locator('#appointment-history-table tbody tr:visible');
                const allText = await rows.allTextContents();
                await page.setViewportSize({ width: 320, height: 900 });
                await page.waitForFunction(() => document.querySelectorAll('#appointment-history-table tbody tr:not([hidden])').length === 2);
                assert.deepEqual(await visible.allTextContents(), allText.slice(0, 2));
                assert.equal(await page.locator('[data-history-previous]').isDisabled(), true);
                await page.locator('[data-history-next]').click();
                assert.deepEqual(await visible.allTextContents(), allText.slice(2, 4));
                await page.locator('[data-history-next]').click();
                assert.deepEqual(await visible.allTextContents(), allText.slice(4));
                assert.equal(await page.locator('[data-history-next]').isDisabled(), true);
                await page.locator('[data-history-previous]').click();
                assert.deepEqual(await visible.allTextContents(), allText.slice(2, 4));
                await page.getByRole('button', { name: 'Página 1', exact: true }).click();
                assert.deepEqual(await visible.allTextContents(), allText.slice(0, 2));
                await page.getByRole('button', { name: 'Página 3', exact: true }).focus();
                await page.keyboard.press('Enter');
                assert.deepEqual(await visible.allTextContents(), allText.slice(4));
                assert.equal(await page.locator('[data-history-pages] [aria-current="page"]').textContent(), '3');
                assert.equal(await page.locator('[data-request-loading]').isVisible(), false);
                const centered = await page.locator('.appointment-history-pagination-controls').evaluate(el => {
                    const box = el.getBoundingClientRect();
                    const card = el.closest('.appointment-history').getBoundingClientRect();
                    return Math.abs((box.left + box.right) / 2 - (card.left + card.right) / 2) < 2;
                });
                assert.ok(centered, 'Pagination should be centered inside the card');
                await page.screenshot({ path: path.join(output, `${scenario}-last-page.png`), fullPage: true });
                await page.setViewportSize({ width: 1440, height: 900 });
                await page.waitForFunction(() => !document.querySelector('#appointment-history-table tbody tr[hidden]'));
                assert.equal(await visible.count(), 5);
                assert.equal(await page.locator('[data-history-pagination]').isVisible(), false);
                await page.setViewportSize({ width: 390, height: 900 });
                await page.waitForFunction(() => document.querySelectorAll('#appointment-history-table tbody tr:not([hidden])').length === 2);
                assert.deepEqual(await visible.allTextContents(), allText.slice(0, 2));
                checks++;
            }
            assert.deepEqual(errors, [], `JavaScript errors (${scenario})`);
            await page.close();
        }
        console.log(`OK: ${checks} layout/pagination scenarios (320–1440px), including navigation, keyboard, boundaries and resize. No overflow or JavaScript errors. Screenshots: ${output}`);
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
