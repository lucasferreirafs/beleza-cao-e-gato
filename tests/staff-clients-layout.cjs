const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const path = require('node:path');
const os = require('node:os');

// Pré-requisito: tests/staff-clients.php no compose.booking-qa.yaml.
(async () => {
    const browser = await chromium.launch({ headless: true, ...(process.env.QA_BROWSER_CHANNEL ? { channel: process.env.QA_BROWSER_CHANNEL } : {}) });
    const page = await browser.newPage();
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    let checks = 0;
    const settle = async () => {
        await page.evaluate(() => document.fonts.ready);
        await page.locator('[data-sidebar]').evaluate(el => Promise.all(el.getAnimations().map(animation => animation.finished.catch(() => {}))));
    };
    const checkWidths = async label => {
        for (const width of [320, 360, 390, 430, 680, 768, 1024, 1440]) {
            await page.setViewportSize({ width, height: 1000 });
            await settle();
            const result = await page.evaluate(() => {
                const elements = [...document.querySelectorAll('.staff-clients td, .staff-clients dd, .staff-clients h1, .staff-clients .button, .staff-clients input')];
                return {
                    width: document.documentElement.scrollWidth,
                    clipped: elements.filter(el => {
                        const rect = el.getBoundingClientRect();
                        return rect.width > 0 && (rect.right > innerWidth + 1 || rect.left < -1 || el.scrollWidth > el.clientWidth + 2);
                    }).map(el => el.tagName + '.' + el.className)
                };
            });
            assert.ok(result.width <= width + 1, `${label}: overflow da página em ${width}px`);
            assert.deepEqual(result.clipped, [], `${label}: conteúdo cortado em ${width}px`);
            assert.ok(await page.locator('.staff-clients h1').isVisible());
            checks++;
        }
    };
    try {
        await page.goto('http://127.0.0.1:18080/login');
        await page.locator('input[name="email"]').fill('staff-clients@example.invalid');
        await page.locator('input[name="password"]').fill('Clients-only1!');
        await page.locator('form').filter({ has: page.locator('input[name="password"]') }).first().locator('button[type="submit"]').click();
        await page.waitForURL('**/staff');
        await page.goto('http://127.0.0.1:18080/staff/clientes');
        assert.equal(await page.locator('.sc-table tbody tr').count(), 10);
        await checkWidths('Listagem');
        await page.locator('.sc-pagination a[rel="next"]').click();
        await page.waitForURL('**page=2');
        assert.equal(await page.locator('.sc-table tbody tr').count(), 3);
        await page.locator('#client-search').fill('AAA');
        await page.locator('#client-search').press('Enter');
        await page.waitForURL('**q=AAA');
        assert.equal(await page.locator('.sc-table tbody tr').count(), 1);
        await page.setViewportSize({ width: 390, height: 1000 });
        await settle();
        await page.screenshot({ path: path.join(os.tmpdir(), 'beleza-clients-mobile.png'), fullPage: true });
        await page.locator('.sc-table a').first().click();
        await page.waitForURL(/\/staff\/clientes\/[a-f0-9-]+\?q=AAA$/);
        assert.ok((await page.locator('.sc-back').getAttribute('href')).includes('q=AAA'));
        assert.equal(await page.locator('.sc-pet-card').count(), 2);
        assert.ok(!(await page.content()).includes('98765432109'));
        await checkWidths('Perfil do cliente');
        await page.setViewportSize({ width: 390, height: 1000 });
        await settle();
        await page.screenshot({ path: path.join(os.tmpdir(), 'beleza-client-profile-mobile.png'), fullPage: true });
        await page.locator('.sc-pet-card a').first().click();
        await page.waitForURL(/\/pets\/[a-f0-9-]+/);
        assert.ok(await page.locator('#pet-care').isVisible());
        assert.ok((await page.locator('.sc-back').getAttribute('href')).includes('q=AAA'));
        await checkWidths('Perfil do pet');
        await page.setViewportSize({ width: 390, height: 1000 });
        await settle();
        await page.screenshot({ path: path.join(os.tmpdir(), 'beleza-client-pet-mobile.png'), fullPage: true });
        await page.locator('.sc-back').focus();
        assert.equal(await page.locator('.sc-back').evaluate(el => getComputedStyle(el).outlineStyle), 'solid');
        await page.locator('.sc-back').press('Enter');
        await page.waitForURL(/\/staff\/clientes\/[a-f0-9-]+\?q=AAA$/);
        await page.emulateMedia({ reducedMotion: 'reduce' });
        assert.equal(await page.locator('.sc-back').evaluate(el => getComputedStyle(el).transitionDuration), '0s');
        assert.deepEqual(errors, []);
        console.log(`OK: ${checks} verificações de largura, busca, paginação, navegação, foco e console. Capturas em ${os.tmpdir()}`);
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
