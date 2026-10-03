const { chromium } = require('playwright');
const { execFileSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '..');
(async () => {
    const browser = await chromium.launch({ headless: true, ...(process.env.QA_BROWSER_CHANNEL ? { channel: process.env.QA_BROWSER_CHANNEL } : {}) });
    const output = fs.mkdtempSync(path.join(os.tmpdir(), 'staff-agenda-'));
    let checks = 0;
    try {
        for (const mode of ['day', 'week']) {
            const html = execFileSync('php', [path.join(__dirname, 'fixtures/staff-agenda.php'), mode, 'long'], { cwd: root, encoding: 'utf8' });
            const page = await browser.newPage();
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            let actionResult = 'conflict';
            let submissions = 0;
            await page.route('**/*', async route => {
                const url = new URL(route.request().url());
                if (url.hostname !== 'agenda-qa.local') return route.abort();
                if (url.pathname.endsWith('/status')) {
                    submissions++;
                    assert.equal(route.request().method(), 'POST');
                    assert.ok(new URLSearchParams(route.request().postData()).get('_token'));
                    return route.fulfill({ status: actionResult === 'conflict' ? 409 : 200, contentType: 'application/json',
                        body: JSON.stringify(actionResult === 'conflict' ? { ok: false, message: 'Atendimento expirado. Atualize a agenda.' } : { ok: true, status: actionResult }) });
                }
                if (url.pathname === '/staff/agenda') return route.fulfill({ contentType: 'text/html; charset=utf-8', body: html });
                const file = path.resolve(root, 'public', '.' + url.pathname);
                if (!file.startsWith(path.join(root, 'public') + path.sep) || !fs.existsSync(file)) return route.fulfill({ status: 404 });
                return route.fulfill({ contentType: { '.css': 'text/css', '.js': 'text/javascript', '.svg': 'image/svg+xml' }[path.extname(file)] || 'application/octet-stream', body: fs.readFileSync(file) });
            });
            await page.goto(`http://agenda-qa.local/staff/agenda?view=${mode}`);
            assert.equal(await page.locator('.agenda-day').count(), mode === 'week' ? 7 : 1);
            const first = page.locator('.agenda-card').first();
            for (const width of [320, 390, 680, 768, 1024, 1440]) {
                await page.setViewportSize({ width, height: 900 });
                if (width <= 1000) await page.waitForFunction(() => document.querySelector('.sidebar').getBoundingClientRect().right <= 1);
                await first.locator('summary').click();
                for (const open of [true, false]) {
                    const geometry = await page.evaluate(() => ({ width: innerWidth, scroll: document.documentElement.scrollWidth,
                        bad: [...document.querySelectorAll('.agenda-card, .agenda-day, .agenda-controls')].filter(el => el.scrollWidth > el.clientWidth + 1).map(el => el.className) }));
                    assert.ok(geometry.scroll <= width + 1, JSON.stringify(geometry));
                    assert.deepEqual(geometry.bad, []);
                    if (open) await first.locator('summary').click();
                    checks++;
                }
                if (mode === 'day' && [320, 1440].includes(width)) await page.screenshot({ path: path.join(output, `${mode}-${width}.png`), fullPage: true });
            }
            await first.locator('summary').focus();
            await page.keyboard.press('Enter');
            assert.equal(await first.getAttribute('open'), '');
            assert.ok((await first.innerText()).includes('<script>alert(1)</script>'));
            await first.getByRole('button', { name: 'Iniciar atendimento' }).click();
            await page.waitForFunction(() => !document.querySelector('[data-agenda-feedback]').hidden);
            assert.equal(await page.locator('[data-request-loading]').isVisible(), false);
            assert.equal(await first.getByRole('button', { name: 'Iniciar atendimento' }).isEnabled(), true);
            actionResult = 'in_progress';
            await first.getByRole('button', { name: 'Iniciar atendimento' }).click();
            await page.waitForFunction(() => document.querySelector('[data-agenda-notice]').open);
            assert.equal(await page.locator('[data-request-loading]').isVisible(), true);
            await page.waitForEvent('load');
            assert.equal(await first.getAttribute('open'), '');
            const ongoing = page.locator('.agenda-card[data-appointment="2"]').first();
            await ongoing.locator('summary').click();
            actionResult = 'completed';
            await ongoing.getByRole('button', { name: 'Concluir atendimento' }).click();
            await page.waitForFunction(() => document.querySelector('[data-agenda-notice]').open);
            await page.waitForEvent('load');
            assert.equal(submissions, 3);
            assert.deepEqual(errors, []);
            checks++;
            await page.close();
        }
        console.log(`OK: ${checks} layout/interaction checks. Screenshots: ${output}`);
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
