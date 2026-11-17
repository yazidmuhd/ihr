# i-HR Live Demo: Deployment Plan for Claude Code

> **How to use this file:** put it in the root of the i-HR repo, open the project in VS Code, and tell Claude Code:
> "Read LIVE_DEMO_PLAN.md and follow it, one phase at a time."

## Goal

Give i-HR a **public live demo link** that recruiters can open from Yazid's resume, portfolio and GitHub README, at **zero monthly cost**.

The demo must:

- Load in a browser with no setup by the visitor.
- Let visitors log in as each role (Applicant, HR, Admin) with ready-made demo accounts.
- Contain **only fictional data**. No real names, résumés, phone numbers or emails.
- Never send real emails, never expose secrets, and never cost money if it gets traffic.

## Rules for Claude Code

1. **Inspect before changing anything.** Complete Phase 0 and report back before writing code.
2. **Ask Yazid before:** creating accounts on any platform, adding a paid service, changing the database schema, or removing features.
3. **Work on a branch** named `live-demo`. Commit after each phase with a clear message. Do not push or merge until Yazid says so.
4. **Never commit secrets.** API keys, database URLs and `APP_KEY` go in the hosting platform's environment variables only. Check that `.env` is in `.gitignore`.
5. **Do not break local development.** Everything added for the demo must be switched on by environment variables (for example `DEMO_MODE=true`), so `php artisan serve` locally still works as before.
6. Keep a running log of what you did and why in `docs/LIVE_DEMO.md`.

---

## Phase 0: Inspect the project and report

Read the codebase and report back to Yazid with:

- Laravel version, PHP version required (`composer.json`), Node version for the frontend build (`package.json`).
- How the Vue 3 frontend is built and served (Vite + Inertia, Vite + Blade, or a separate SPA).
- Database driver and any PostgreSQL-specific features used (JSON columns, enums, extensions).
- Everything the app depends on at runtime:
  - **Queues** (`QUEUE_CONNECTION`): are any jobs dispatched?
  - **Scheduler**: anything in `routes/console.php` or `app/Console/Kernel.php`?
  - **Mail**: which driver, and where is mail sent (registration, application status, interview invites)?
  - **File storage**: where are uploaded résumés saved (`storage/app`, `public` disk, S3)?
  - **AI résumé parsing and scoring**: which provider or library, which environment variables, and what happens if the API key is missing or the call fails?
  - Any other third-party APIs and their environment variables.
- Existing seeders and factories, and the user roles and permission setup.
- Any existing deployment config (Dockerfile, CI workflows, staging or production `.env.example` notes).
- Anything that would block a public demo.

**Stop here and wait for Yazid's OK.**

---

## Phase 1: Make the app demo-safe

Add a demo mode controlled by environment variables. Suggested variables:

```
DEMO_MODE=true
APP_ENV=production
APP_DEBUG=false
MAIL_MAILER=log
```

Then:

1. **Demo banner.** When `DEMO_MODE=true`, show a slim banner on every page:
   *"Demo version of i-HR. All data is fictional and resets regularly."*
2. **Demo accounts.** Create a `DemoSeeder` with one account per role, for example:
   | Role | Email | Password |
   |---|---|---|
   | Applicant | applicant@demo.ihr | Demo@1234 |
   | HR | hr@demo.ihr | Demo@1234 |
   | Admin | admin@demo.ihr | Demo@1234 |

   Show these on the login page when `DEMO_MODE=true`, ideally with one-click "Log in as …" buttons.
3. **Realistic fictional data.** Seed enough data that every screen looks alive: 5 to 8 job postings, 15 to 25 applicants at different stages, a few scored applications, interview schedules and audit trail entries. Use clearly fictional names. Use sample résumé PDFs generated for the demo, never real ones.
4. **Protect the demo from visitors:**
   - Block destructive admin actions that would ruin the demo for the next visitor (deleting all jobs, deleting the demo accounts, changing demo passwords), or reset them automatically.
   - Add rate limiting on login and on file upload.
   - Limit upload size (for example 2 MB, PDF only).
5. **Email:** with `MAIL_MAILER=log`, nothing is sent. Confirm no feature crashes because of it.
6. **AI scoring:** decide with Yazid:
   - **Option A:** keep the real AI provider, with a strict monthly spending cap set on the provider's dashboard and a per-hour limit in the app.
   - **Option B (safer and free):** when `DEMO_MODE=true` and no API key is set, use pre-computed scores for seeded applicants, and a clearly labelled "demo score" for new uploads.
7. **Reset command.** Create `php artisan demo:reset` that wipes the database and re-runs the migrations and `DemoSeeder`. It must refuse to run unless `DEMO_MODE=true`.

Run the full test suite and make sure all existing tests still pass. Add tests for: demo mode off by default, `demo:reset` refusing to run outside demo mode, and demo accounts logging in.

---

## Phase 2: Containerise the app

Create a production `Dockerfile` (multi-stage) so the app can run on Render's free tier:

1. **Stage 1: frontend build.** Node image, `npm ci`, `npm run build` (Vite).
2. **Stage 2: PHP runtime.** A PHP image matching the version in `composer.json`, with the PostgreSQL extension (`pdo_pgsql`) and any other extensions the app needs. Copy the code and the built frontend assets, then run `composer install --no-dev --optimize-autoloader`.
3. **Web server.** Use a single process that serves on the port Render provides in `$PORT` (for example Nginx + PHP-FPM, or a ready-made Laravel-friendly image). Document the choice.
4. **Startup script** (`docker/start.sh`), run on every container start:
   ```
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan migrate --force
   php artisan storage:link
   # Seed demo data only if the database is empty
   ```
5. Add a `.dockerignore` (exclude `node_modules`, `vendor`, `.env`, `.git`, tests output).
6. Build and run the image locally against a local PostgreSQL and confirm the demo works at `http://localhost:8080`.

---

## Phase 3: Database on Neon (free)

Render's free PostgreSQL is **deleted after 30 days**, so use **Neon** instead. Its free plan has no expiry.

- Free plan limits: 1 GB storage per project, compute pauses after 5 minutes idle and wakes on the next query. Plenty for a demo.
- Yazid creates the Neon project himself (Claude Code must not create accounts). Region: **Singapore** (closest to Malaysia).
- Use the **pooled** connection string. Laravel needs `sslmode=require`:
  ```
  DB_CONNECTION=pgsql
  DB_URL=postgresql://USER:PASSWORD@HOST/DBNAME?sslmode=require
  ```
- Set it only in Render's environment variables, never in the repo.

---

## Phase 4: Deploy on Render (free)

Free web service limits to know:

- It **sleeps after 15 minutes without traffic** and takes **about a minute** to wake on the next visit.
- 750 free instance hours per month per workspace.
- The filesystem is **temporary**: anything written to disk (uploaded résumés, logs) is lost on restart, redeploy or sleep.

Steps:

1. Add a `render.yaml` blueprint describing one **free Docker web service** built from the repo, region **Singapore**.
2. Environment variables to set in the Render dashboard (not in the file): `APP_KEY`, `APP_URL`, `DB_URL`, `DEMO_MODE=true`, `APP_ENV=production`, `APP_DEBUG=false`, `MAIL_MAILER=log`, `LOG_CHANNEL=stderr`, `SESSION_DRIVER=database` (or `cookie`), `CACHE_STORE=database`, and the AI key only if Option A was chosen.
3. Because the disk is temporary:
   - Store sessions and cache in the database or cookies, not files.
   - Treat uploaded résumés as temporary in the demo, and say so in the banner text, or store them in the database if the files are small. Ask Yazid which.
   - Send logs to `stderr` so they show in Render's log viewer.
4. Yazid connects the GitHub repo in Render and deploys the `live-demo` branch first.
5. **Keeping the data fresh:** Render's free plan has no cron jobs. Use one of:
   - run `demo:reset` on every deploy (simplest), or
   - a free scheduled GitHub Actions workflow that calls a protected reset URL once a day, secured with a secret token stored in GitHub Secrets and Render.

---

## Phase 5: Verify like a QA engineer

1. **Smoke test with Playwright** (`tests/e2e/demo-smoke.spec.ts`), run against the live URL:
   - Home and login pages load with no console errors.
   - Each demo account logs in and sees its own dashboard.
   - An applicant can view jobs and apply.
   - HR can see applicants and their scores.
   - An applicant cannot open HR or Admin pages (access control check).
2. Add a GitHub Actions workflow that runs the smoke test after each deploy or on demand.
3. Manually check on a phone: layout, login, demo banner.
4. Security quick check: `APP_DEBUG` is off (no stack traces on a bad URL), `.env` is not reachable at `/.env`, HTTPS works, demo accounts cannot change their passwords.

---

## Phase 6: Show it off

Update `README.md` with:

- A **Live demo** link at the top, plus the demo accounts table.
- A note: *"Hosted on a free tier: the first visit after a quiet period takes about a minute to wake up."*
- 3 to 5 screenshots (login, applicant dashboard, HR applicant list with scores, admin audit trail).
- Tech stack, test count, CI badge, and the Industry Panel Award.
- A short "How the demo works" section linking to `docs/LIVE_DEMO.md`.

Then tell Yazid the final URL so he can add it to his resume, portfolio site and LinkedIn.

---

## Done checklist

- [ ] Phase 0 report reviewed by Yazid
- [ ] Demo mode, banner, demo accounts and fictional seed data
- [ ] AI scoring decision made and implemented
- [ ] `demo:reset` command with safety check and tests
- [ ] Dockerfile builds and runs locally
- [ ] Neon database connected (Singapore)
- [ ] Render free web service live (Singapore)
- [ ] Playwright smoke test passing against the live URL
- [ ] README updated with link, accounts and screenshots
- [ ] All existing tests still pass
