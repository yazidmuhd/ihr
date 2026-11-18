// Takes the README screenshots from the live demo: `npm run screenshots` (DEMO_URL overrides the address).
// Saves to docs/screenshots/. Read-only: it only logs in and looks.
import { chromium, devices } from '@playwright/test';
import { mkdirSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const BASE = process.env.DEMO_URL ?? 'https://ihr-demo.onrender.com';
const OUT = fileURLToPath(new URL('../../docs/screenshots/', import.meta.url));
mkdirSync(OUT, { recursive: true });

// The free host sleeps; wait until it answers
for (let i = 0; i < 48; i++) {
    try { if ((await fetch(`${BASE}/up`, { signal: AbortSignal.timeout(20_000) })).ok) break; } catch {}
    await new Promise((r) => setTimeout(r, 5_000));
}

const browser = await chromium.launch();

async function session(who, landing, options = { viewport: { width: 1366, height: 820 } }) {
    const context = await browser.newContext({ baseURL: BASE, ...options });
    const page = await context.newPage();
    await page.goto('/login');
    if (who) {
        await page.getByRole('button', { name: new RegExp(`Log in as ${who}`) }).click();
        await page.waitForURL((url) => url.pathname === landing);
    }
    return page;
}

async function shot(page, path, file, clip) {
    if (path) await page.goto(path);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(800); // let charts and transitions settle
    await page.screenshot({ path: OUT + file, ...(clip ? { clip, fullPage: true } : {}) });
    console.log('saved docs/screenshots/' + file);
}

const guest = await session(null);
await shot(guest, null, 'login.png');

const applicant = await session('Applicant', '/applicant/dashboard');
await shot(applicant, '/applications', 'applicant-applications.png');

const hr = await session('HR staff', '/hr/dashboard');
// Skip the page header so the scored candidates show
await shot(hr, '/hr/vacancies/1/ai', 'hr-ai-ranking.png', { x: 0, y: 520, width: 1366, height: 900 });
await shot(hr, '/hr/activity', 'hr-activity.png');

const phone = await session(null, null, devices['Pixel 7']);
await shot(phone, null, 'phone-login.png');

await browser.close();
