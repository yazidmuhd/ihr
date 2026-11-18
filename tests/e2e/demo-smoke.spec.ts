import { expect, test, type Page } from '@playwright/test';

/**
 * Smoke test for the public i-HR live demo. It checks what a recruiter would try first,
 * plus the access and security basics. The demo resets itself, so applying is fine,
 * but the test withdraws its application again to leave the demo as it found it.
 */

const BANNER = 'Demo version of i-HR. All data is fictional and resets regularly.';

/** Fails the test on any browser console error. Pass the URLs where an error is expected (a 403 page). */
function watchConsole(page: Page, allowed: RegExp[] = []): string[] {
    const errors: string[] = [];
    page.on('console', (msg) => {
        if (msg.type() !== 'error') return;
        const where = msg.location().url;
        if (!allowed.some((re) => re.test(msg.text()) || re.test(where))) errors.push(msg.text());
    });
    return errors;
}

async function logInAs(page: Page, who: 'Applicant' | 'HR staff', landing: string): Promise<void> {
    await page.goto('/login');
    await page.getByRole('button', { name: new RegExp(`Log in as ${who}`) }).click();
    await page.waitForURL((url) => url.pathname === landing);
}

test.describe('Visitors', () => {
    test('the login page shows the banner and both demo accounts @phone', async ({ page }) => {
        const errors = watchConsole(page);

        await page.goto('/');
        await expect(page).toHaveURL(/\/login$/);
        await expect(page.getByText(BANNER)).toBeVisible();
        await expect(page.getByText('Try the demo')).toBeVisible();
        await expect(page.getByRole('button', { name: /Log in as Applicant/ })).toBeVisible();
        await expect(page.getByRole('button', { name: /Log in as HR staff/ })).toBeVisible();

        // Nothing wider than the screen on a phone
        const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
        expect(overflow).toBeLessThanOrEqual(1);

        expect(errors).toEqual([]);
    });

    test('the public job list loads', async ({ page }) => {
        const errors = watchConsole(page);

        await page.goto('/jobs');
        await expect(page.getByText(BANNER)).toBeVisible();
        await expect(page.locator('a[href^="/jobs/"]').first()).toBeVisible();

        expect(errors).toEqual([]);
    });

    test('HR pages send a guest to the login page', async ({ page }) => {
        await page.goto('/hr/dashboard');
        await expect(page).toHaveURL(/\/login$/);
    });
});

test.describe('Applicant', () => {
    test('logs in and sees their own dashboard @phone', async ({ page }) => {
        const errors = watchConsole(page);

        await logInAs(page, 'Applicant', '/applicant/dashboard');
        await expect(page.getByText('Welcome back, Nur Aisyah Kamal!')).toBeVisible();
        await expect(page.getByText(BANNER)).toBeVisible();

        expect(errors).toEqual([]);
    });

    test('can view the jobs and apply', async ({ page }) => {
        const errors = watchConsole(page);
        await logInAs(page, 'Applicant', '/applicant/dashboard');

        // Earlier visitors may have applied already, so find a job that is still open to this account
        await page.goto('/jobs');
        const jobLinks = await page.locator('a[href^="/jobs/"]:not([href*="#"])').evaluateAll((links) => [
            ...new Set(links.map((a) => a.getAttribute('href') as string)),
        ]);
        expect(jobLinks.length).toBeGreaterThan(0);

        let title = '';
        for (const link of jobLinks) {
            await page.goto(link);
            const apply = page.getByRole('button', { name: 'Apply Now' });
            if (await apply.isVisible()) {
                title = (await page.getByRole('heading', { level: 1 }).first().innerText()).trim();
                await apply.click();
                break;
            }
        }
        test.skip(!title, 'The demo applicant has applied to every job; it resets after the next sleep');

        await page.getByRole('button', { name: 'Confirm Application' }).click();
        await expect(page.getByRole('button', { name: 'Application Submitted' })).toBeVisible();

        await page.goto('/applications');
        await expect(page.getByText(title, { exact: true }).first()).toBeVisible();

        // Leave the demo as we found it
        await page.goBack();
        page.once('dialog', (dialog) => dialog.accept());
        await page.getByRole('button', { name: 'Withdraw', exact: true }).first().click();
        await expect(page.getByText(/withdrawn/i).first()).toBeVisible();

        expect(errors).toEqual([]);
    });

    test('cannot open HR pages', async ({ page }) => {
        await logInAs(page, 'Applicant', '/applicant/dashboard');

        for (const path of ['/hr/dashboard', '/hr/vacancies', '/hr/vacancies/1/ai', '/hr/activity']) {
            const res = await page.goto(path);
            expect(res?.status(), path).toBe(403);
        }
    });
});

test.describe('HR staff', () => {
    test('logs in and sees the HR dashboard @phone', async ({ page }) => {
        const errors = watchConsole(page);

        await logInAs(page, 'HR staff', '/hr/dashboard');
        // The sidebar copy of the name is hidden on a phone; the welcome heading is not
        await expect(page.getByText('Farah Hanim').filter({ visible: true }).first()).toBeVisible();
        await expect(page.getByText('OPEN VACANCIES', { exact: false }).first()).toBeVisible();

        expect(errors).toEqual([]);
    });

    test('sees applicants ranked with their match scores', async ({ page }) => {
        const errors = watchConsole(page);
        await logInAs(page, 'HR staff', '/hr/dashboard');

        await page.goto('/hr/vacancies');
        await page.locator('a[href$="/ai"]').last().click(); // the oldest vacancy has the most applicants
        await expect(page.getByText('AI-Powered Candidate Ranking')).toBeVisible();
        await expect(page.getByText('Top 3 Candidates')).toBeVisible();

        const scores = await page.getByText(/^MATCH SCORE$/i).count();
        expect(scores).toBeGreaterThan(0);

        expect(errors).toEqual([]);
    });
});

test.describe('Security basics', () => {
    test('serves HTTPS with security headers', async ({ request, baseURL }) => {
        test.skip(!baseURL?.startsWith('https://'), 'only meaningful on the live site');

        const res = await request.get('/login');
        expect(res.headers()['strict-transport-security']).toContain('max-age=');
        expect(res.headers()['content-security-policy']).toContain("default-src 'self'");
        expect(res.headers()['x-frame-options']).toBe('SAMEORIGIN');
        expect(res.headers()['x-powered-by']).toBeUndefined();
    });

    test('hides the .env file and shows no error details', async ({ request }) => {
        for (const path of ['/.env', '/this-page-does-not-exist']) {
            const res = await request.get(path);
            expect(res.status(), path).toBe(404);
            const body = await res.text();
            expect(body).not.toMatch(/APP_KEY|DB_URL|Stack trace|Whoops|vendor\/laravel/i);
        }
    });

    test('demo account passwords cannot be reset', async ({ page }) => {
        await page.goto('/password/request');
        await page.locator('input[type="email"]').fill('hr@demo.ihr');
        await page.locator('button[type="submit"]').click();
        await expect(page.getByText('Demo account passwords cannot be changed.')).toBeVisible();
    });
});
