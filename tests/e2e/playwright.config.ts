import { defineConfig, devices } from '@playwright/test';

/**
 * Smoke test for the public i-HR live demo (docs/LIVE_DEMO.md, Phase 5).
 * Runs against the live site, not a local server: DEMO_URL overrides the address.
 */
export default defineConfig({
    testDir: '.',
    globalSetup: './wake-up.ts', // the free host sleeps; wake it before timing anything
    timeout: 60_000,
    expect: { timeout: 15_000 },
    retries: process.env.CI ? 1 : 0,
    workers: 1, // the demo accounts are shared, so run one test at a time
    reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : 'list',
    use: {
        baseURL: process.env.DEMO_URL ?? 'https://ihr-demo.onrender.com',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [
        { name: 'desktop', use: { ...devices['Desktop Chrome'] } },
        // Phone check: the pages a visitor sees first, on a phone-sized screen
        { name: 'phone', use: { ...devices['Pixel 7'] }, grep: /@phone/ },
    ],
});
