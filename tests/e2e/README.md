# Live demo smoke test

Playwright checks for the public demo at https://ihr-demo.onrender.com (see [docs/LIVE_DEMO.md](../../docs/LIVE_DEMO.md)). It runs against the live site, so it needs no database or PHP, and it has its own `package.json`, separate from the app.

```sh
cd tests/e2e
npm ci
npx playwright install chromium
npx playwright test                                  # the live demo
DEMO_URL=http://localhost:8080 npx playwright test   # a local demo container
```

The first step wakes the free host, which can take about a minute. The 14 checks cover the login page and banner, both demo logins, applying for a job (the test withdraws its application afterwards), an applicant being refused HR pages, HR seeing ranked scores, security headers, hidden `.env`, no error details, and demo passwords that cannot be reset. Three of them also run on a phone-sized screen.

GitHub Actions runs it after each push to `live-demo`, every morning, and on demand: `.github/workflows/demo-smoke.yml`.
