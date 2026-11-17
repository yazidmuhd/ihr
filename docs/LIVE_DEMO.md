# i-HR live demo: log

The public demo is i-HR running with `DEMO_MODE=true`: fictional data, two ready-made accounts, no e-mail, no AI costs. With `DEMO_MODE` unset, the app behaves exactly as before. The plan is in [LIVE_DEMO_PLAN.md](../LIVE_DEMO_PLAN.md); this file records what was done and why.

## Decisions (agreed with Yazid, 17 Nov 2026)

| # | Question | Decision |
|---|---|---|
| 1 | AI scoring in the demo | **Option B**: no AI provider. Seeded applicants have pre-computed scores; new uploads are read by a rule-based demo reader and labelled "Demo analysis". |
| 2 | Uploaded résumés on the free host's temporary disk | **Temporary**, and the banner says so. |
| 3 | Admin demo account | **Dropped**: admin has no screens of its own, so it would duplicate HR. |
| 4 | How demo mode is switched on | A dedicated **`DEMO_MODE`** variable, separate from the existing staging mode. |
| 5 | Hosting | **Render** (free web service) with **Neon** (free PostgreSQL), both in Singapore. The Railway set-up stays for a future real launch. |

## Phase 0: inspection (summary)

- Laravel 12.69.3, PHP ^8.2 (Docker image PHP 8.2), Node 20; Vue 3 + Inertia + Vite.
- PostgreSQL with triggers (audit log, status history), `jsonb`, a partial unique index and CHECK constraints. Neon supports all of these.
- Queue: résumé reading and e-mails. With `QUEUE_CONNECTION=sync` no worker is needed.
- Scheduler: reminders, failed-job pruning, staging reset. None is needed for the demo; Render free has no cron.
- Blockers found: the HR area's IP allowlist (must be empty for the demo); `Demo@1234` fails the password policy; no AI key means applicants cannot apply; demo accounts could change passwords and delete vacancies; a stray empty file `usually` in the repo root.

## Phase 1: demo-safe app

| Change | Where | Why |
|---|---|---|
| `DEMO_MODE` switch, demo accounts, password, upload limit | `config/demo.php` | One place for every demo setting; off by default |
| Blue banner on every page | `resources/views/app.blade.php` | "Demo version of i-HR. All data is fictional and resets regularly. Uploaded résumés are temporary…" |
| "Try the demo" box with one-click **Log in as Applicant / HR staff** | `resources/js/Pages/Auth/Login.vue`, shared `demo` prop | Visitors need no typing; Google sign-in is hidden (not configured in the demo) |
| Demo accounts | `applicant@demo.ihr`, `hr@demo.ihr`, password `Demo@Ihr2026!` | Meets the real password policy, so the demo shows the real rules |
| Fictional data | `database/seeders/DemoSeeder.php` | 6 vacancies (5 open, 1 closed), 19 applicants + the demo applicant, 25 applications at every stage, 10 interviews, panel ratings, 2 hires, about 50 status changes and 110 audit entries, each recorded as the person who would do it |
| Sample résumés | `database/demo/resumes/*.pdf` | Three generated PDFs, each marked "FICTIONAL SAMPLE RÉSUMÉ" on the first line |
| Option B résumé reading | `app/Services/DemoResumeParser.php`, `app/Jobs/ParseResume.php` | Without an AI key, the demo matches skills against the vacancies' required skills and a common list; years and education use the existing rule-based extractor. Results carry `demo: true` and the résumé page labels them "Demo analysis (no AI in the public demo)". Outside demo mode a missing key fails exactly as before. |
| Upload limit in the demo | `ResumeController`, résumé page | PDF only, 2 MB (normally PDF/DOC/DOCX, 5 MB) |
| Protections | `app/Http/Middleware/ProtectDemo.php` | Deleting vacancies is refused with a visible message (archive and close still work); demo account passwords cannot be reset. Login (5 tries) and upload (20/hour) rate limits already existed. |
| Reset command | `php artisan demo:reset` | Deletes uploads and rebuilds the database with `DemoSeeder`; refuses to run unless `DEMO_MODE=true` |
| Tests | `tests/Feature/DemoModeTest.php` (10 tests) | Off by default; reset refuses outside demo mode; seeder and both accounts; banner and login shortcuts; delete and password protections; PDF-only uploads; Option B reading, and the unchanged behaviour without demo mode |
| Clean-up | removed `usually` | An empty file committed by mistake |

### Problems found and fixed while checking in a browser

- **"Vacancy deleted successfully" for a delete the demo had blocked.** The vacancies page treats any non-error answer as success. The guard now returns an error, and the page shows the guard's message.
- **The blocked delete then showed nothing at all.** The guard answers before Inertia's middleware, which normally turns a redirect after `DELETE` into `303 See Other`; with a plain `302` the browser repeated the `DELETE` on the list page (`405`). The guard now sends `303` itself, and a test checks it.

### Verified

- All 72 tests pass (62 existing + 10 new).
- In a browser with `DEMO_MODE=true` and no AI key: the banner and demo box show, Google is hidden, both one-click logins work, the demo applicant has an interview invitation to answer, a new PDF upload is read and labelled "Demo analysis", and a vacancy delete is refused with the message. No console errors.

### Noticed, not changed (existing behaviour)

- The HR dashboard counts every vacancy as "open" (it shows 6 with one closed) and shows fixed trend badges such as "+12%".

### Settings for the demo host (Phase 4)

```
DEMO_MODE=true
APP_ENV=production
APP_DEBUG=false
MAIL_MAILER=log
QUEUE_CONNECTION=sync
UPLOADS_DISK=local
IP_ALLOWLIST=            # empty: visitors must reach the HR demo
AI_API_KEY=              # empty: Option B
```

## Phase 2: container

The existing two-stage `Dockerfile` already matched the plan (Node 20 builds the Vite assets; PHP 8.2 with `pdo_pgsql`, `gd` and `zip` runs the app), so it was adapted rather than replaced.

| Change | Where | Why |
|---|---|---|
| Demo start-up: `demo:reset` on every start when `DEMO_MODE=true` | `docker/start.sh` | The free host wipes its disk on every restart and wake-up; resetting the database at the same time keeps records and files in step, and gives every visitor a fresh demo |
| PHP's built-in server, run directly from `public/` with `PHP_CLI_SERVER_WORKERS` (default 4) on `$PORT` | `docker/start.sh` | One process, no Nginx to configure; replaces `artisan serve` so the host's environment variables reach the app unchanged. Fine for demo traffic; a real launch would use Nginx + PHP-FPM or FrankenPHP |
| Package list rebuilt inside the image | `Dockerfile`, `.dockerignore` | **Bug found by the first real build:** the developer's cached `bootstrap/cache/packages.php` listed dev-only packages (Laravel Pail), so the production image crashed on start. Railway would have hit this too |
| Tests, test output, local uploads and CI files left out of the image | `.dockerignore` | Smaller image; no local data shipped |

**Web server choice:** PHP's built-in server with worker processes. It is one container process listening on Render's `$PORT`, needs no extra configuration, and handles a demo's traffic. It is not meant for heavy production load.

### Verified (Docker 29.9.2, image 951 MB)

Built locally and run against a throwaway `postgres:15` container (standing in for Neon), with the Phase 1 demo settings and `PORT=10000` as Render sets it:

- Start-up resets the demo (about 5 s of seeding), then serves: `/up`, `/login`, `/jobs` return 200.
- `/.env` and unknown pages return 404 with no stack trace (`APP_DEBUG=false`).
- In a browser: both one-click logins; the demo applicant confirms the waiting interview; a PDF upload is read and labelled "Demo analysis"; the résumé preview link serves the PDF; HR sees the AI ranking; a vacancy delete is refused with the demo message. No console errors.
- `docker restart` brings the demo back to its starting state (the confirmed interview is pending again; the extra upload is gone).

## Phase 3: database (Neon)

Yazid created the Neon project (free plan, Singapore) on 17 Nov 2026. The connection string is kept out of the repo and goes only into Render as `DB_URL`.

**Use the direct (non-pooled) connection string.** i-HR's audit triggers learn who made a change from a per-connection database setting (`set_config('app.user_id', …)`, set by `SetAuditUser` on every request). Neon's pooled endpoint shares database connections between requests, so a change could be credited to the wrong person. The demo needs only a handful of connections, well within the free plan.

## Phase 4: Render (prepared)

| Change | Where | Why |
|---|---|---|
| Blueprint: one free Docker web service `ihr-demo`, Singapore, `live-demo` branch, health check `/up`, auto-deploy | `render.yaml` | Everything except secrets is in the repo; `APP_KEY` and `DB_URL` are `sync: false`, so Render asks for them and they never touch git |
| App URL falls back to `RENDER_EXTERNAL_URL` | `config/app.php` | Render sets it automatically, so links and signed résumé URLs use the real address without a manual `APP_URL` |
| `SESSION_SECURE_COOKIE=true` | `render.yaml` | Render serves HTTPS; sign-in cookies are sent over HTTPS only |

Keeping the data fresh: the container runs `demo:reset` on every start (Phase 2), and Render's free service restarts on every deploy and after each idle sleep, so no cron job is needed.
