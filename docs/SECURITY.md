# Security

What protects i-HR, what changed in the October 2026 security review, and what is still open. The network side (HR area limited to the office/VPN IP) is in [DEPLOYMENT.md §4](DEPLOYMENT.md#4-internal-access-office-ips-and-vpn); this document covers the application.

## 1. Summary of the October 2026 changes

| # | Change | Risk it removes | Where |
|---|---|---|---|
| 1 | Applicant text is escaped on the *Filter Résumés* page | **Critical.** An applicant could run JavaScript in an HR user's browser | `resources/js/Pages/HR/FilterResumes.vue` |
| 2 | One strong password policy for register, reset and change | Easily guessed applicant and HR passwords | `AppServiceProvider`, auth controllers |
| 3 | Browser security headers, including a Content-Security-Policy | Clickjacking, content sniffing, injected scripts, version disclosure | `app/Http/Middleware/SecurityHeaders.php` |
| 4 | Secure, encrypted sessions; HR signed out after 30 idle minutes | Session theft over HTTP, unattended office laptops | `config/session.php`, `LogoutIdleHrUsers` |
| 5 | Accounts can be disabled without being deleted | Staff who leave keep access | `is_active` column, `EnsureAccountIsActive`, `php artisan ihr:account` |
| 6 | Google sign-in keeps its CSRF check outside local development | Login CSRF (being signed in to an attacker's account) | `SocialAuthController` |
| 7 | Production PHP settings in Docker | Error details and PHP version shown to visitors | `Dockerfile` |
| 8 | Removed an unused duplicate `LoginRequest` class | Confusion over which login code runs | `app/Http/Controllers/Auth/LoginRequest.php` (deleted) |

All are covered by `tests/Feature/SecurityTest.php`, except 1 (front-end, checked by hand, see §2) and 7 (Docker build not run yet).

## 2. Filter Résumés: stored cross-site scripting (fixed)

**The problem.** The page highlighted search matches by building HTML from the candidate's name, the job, the department and a résumé snippet, and displayed it with `v-html` without escaping. An applicant who registered with a name such as `<img src=x onerror="…">` would have their script run the moment HR opened the page: inside HR's signed-in browser, on the allowed office/VPN IP. The IP allowlist cannot stop this, because the requests come from HR's own browser.

**The fix.** `highlight()` now splits the text around the search term, HTML-escapes every piece, and only then wraps the matches in `<mark>`. The other `v-html` uses in the app only insert the app's own SVG icons, and no Blade `{!! !!}` or `innerHTML` takes user data.

**Checked:** with the input `<img src=x onerror=alert(1)> Ali & "Co"`, the output is plain escaped text, with only the search term highlighted, including when the search term is itself `<img`.

**To check yourself:** register an applicant named `<b>bold</b>` and apply. On *Filter Résumés* the name must show the literal tags, not bold text.

## 3. Passwords

| Rule | Applies to |
|---|---|
| 12–128 characters, upper **and** lower case, at least one number | registration, password reset, password change |
| Not in a known data breach ([Have I Been Pwned](https://haveibeenpwned.com/Passwords)) | production only |
| `HR_ADMIN_PASSWORD` of 12+ characters, else seeding refuses | staging and production seeding |

- The breach check sends only the first 5 characters of the password's SHA-1 hash (k-anonymity). The password itself never leaves the server. It is off locally and in tests so they work offline.
- Existing passwords keep working. The rule applies the next time a password is set.
- Login is still limited to 5 failed attempts per login name and IP, then a short lock-out.
- The register form shows the rule under the password field, and the reset page lists it.

## 4. Browser security headers

Sent on every response by `SecurityHeaders`:

| Header | Value | Why |
|---|---|---|
| `Content-Security-Policy` | see below | the browser refuses scripts and frames from anywhere else |
| `X-Frame-Options` | `SAMEORIGIN` | other sites cannot embed i-HR to trick clicks |
| `X-Content-Type-Options` | `nosniff` | an uploaded file can't be run as a script |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | full URLs (with IDs) are not leaked to other sites |
| `Permissions-Policy` | camera, microphone, location, payment, USB off | not used by i-HR |
| `Cross-Origin-Opener-Policy` | `same-origin` | isolates the window from pages it opens |
| `Strict-Transport-Security` | `max-age=31536000` (HTTPS only) | browsers always use HTTPS for a year |
| `X-Powered-By` | removed | doesn't advertise the PHP version |

**Content-Security-Policy** (production):

```
default-src 'self'; script-src 'self';
style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;
font-src 'self' https://fonts.gstatic.com data:;
img-src 'self' data: blob: https:;              # Google profile photos, bucket
connect-src 'self';
frame-src 'self' https://<bucket endpoint> https://*.<bucket endpoint>;   # résumé preview
object-src 'none'; base-uri 'self';
form-action 'self' https://accounts.google.com; frame-ancestors 'self';
upgrade-insecure-requests
```

- Scripts may only come from i-HR itself. Even if another injection bug appears, an injected `<script>` or `onerror=` will not run. The built bundles contain no `eval`.
- The bucket host for résumé previews is read from `AWS_ENDPOINT` / `AWS_URL`, so no change is needed when the bucket changes.
- During `npm run dev`, the Vite dev server is allowed automatically.
- `'unsafe-inline'` for styles is needed by the Vue components and the staging banner. Inline styles cannot run code.

**Checked:** login, register and jobs render fully in headless Chrome with no CSP violations.

**Not yet checked in a browser:** the signed-in HR and applicant pages. Please click through them once (§9). If anything looks broken, set `SECURITY_CSP=report`. The browser console then lists what *would* be blocked without blocking it, and the policy can be adjusted.

| Variable | Default | Meaning |
|---|---|---|
| `SECURITY_CSP` | `enforce` | `enforce`, `report` (log only) or `off` |
| `SECURITY_HSTS_MAX_AGE` | `31536000` | seconds; `0` disables HSTS |

## 5. Sessions

| Setting | Now | Before |
|---|---|---|
| Secure cookie (HTTPS only) | on automatically when `APP_URL` is `https://` | off unless set |
| Session data encrypted in the database | on (`SESSION_ENCRYPT=true`) | off |
| HTTP-only cookie, `SameSite=lax` | unchanged | — |
| HR idle timeout | **30 minutes** (`HR_IDLE_MINUTES`) | 120, same as applicants |
| Applicant session | 120 minutes (`SESSION_LIFETIME`) | unchanged |

An HR user who leaves the laptop for 30 minutes is signed out on the next click: *"You were signed out after 30 minutes of inactivity."* Any activity resets the timer.

When `SESSION_ENCRYPT` is first turned on, sessions that are already open become unreadable. Everyone is signed out once.

## 6. Disabling accounts

New column `users.is_active` (migration `2026_10_05_000001`). A disabled account:

- cannot sign in, with a password or with Google: *"This account has been disabled. Please contact HR."*
- is signed out immediately: its open sessions are deleted and its "remember me" token is cleared. Any session that slips through is ended on its next request.
- keeps its data and its full audit history. The change itself appears in **Activity** (table `users`).

```bash
php artisan ihr:account list                          # HR accounts and whether they are active
php artisan ihr:account disable someone@company.com   # e-mail or username
php artisan ihr:account enable  someone@company.com
```

The last active HR account cannot be disabled without `--force`, so nobody locks HR out by accident. `is_active` cannot be set through any form; only this command changes it.

On Railway, run these in the `web` service shell.

## 7. Google sign-in

When Google's `state` check failed, the callback used to retry in "stateless" mode, which turns off the flow's CSRF protection. Now it only does that in local development, where a cookie-domain mismatch commonly breaks the check. Elsewhere the user sees *"Google sign-in expired. Please try again."* Disabled accounts are refused here too.

## 8. Production PHP (Docker)

The image now uses PHP's `php.ini-production` (errors are logged, never displayed) plus `expose_php=Off`, `upload_max_filesize=6M` and `post_max_size=8M`. Résumés are limited to 5 MB and avatars to 2 MB by validation.

**Not yet verified:** the Docker build has not been run since this change, because Docker isn't installed on the development Mac. Railway runs it on the next deploy; check that the build log shows no error at that step.

## 9. How to review

1. `php artisan test`: 62 tests pass, 12 of them in `SecurityTest`.
2. Headers: `curl -sI https://<site>/login`. Check that the headers in §4 are present and `X-Powered-By` is absent.
3. Browser: sign in as HR and open each tab (Dashboard, Vacancies, AI ranking, Filter Résumés, Interviews and an evaluation with a résumé preview, Employees, Activity). Then do the same as an applicant (Jobs, Résumé upload, Applications, Interviews, Profile with a photo). The browser console (F12) must show no *Content-Security-Policy* errors.
4. The XSS check in §2.
5. Idle timeout: set `HR_IDLE_MINUTES=1`, wait just over a minute, click: you're signed out. Set it back.
6. `php artisan ihr:account disable <test HR user>`: their open browser is signed out on the next click.

## 10. Still open (recommended next)

| Priority | Item | Why |
|---|---|---|
| High | **Two-factor sign-in for HR** (authenticator app) | if an HR password leaks, the IP allowlist is the only remaining barrier |
| High | **PDPA 2010**: privacy notice and consent at registration, retention (e.g. delete unsuccessful applicants' résumés after 12 months), account deletion | the system stores applicants' personal data |
| Medium | Enforce e-mail verification for applicants | anyone can currently register with someone else's e-mail |
| Medium | Résumé uploads: accept only PDF/DOCX (drop `.doc`, which can carry macros), check the real file type, signed links valid 5–10 minutes | files are opened by HR |
| Medium | Railway Postgres backups, and one test restore | recovery after mistakes or data loss |
| Low | `composer audit` and `npm audit` in CI; GitHub Dependabot | known-vulnerable packages are flagged automatically |

## 11. Already in place before this review

- HR area limited to allowlisted IPs, failing closed if the proxy header is missing ([DEPLOYMENT.md §4](DEPLOYMENT.md#4-internal-access-office-ips-and-vpn))
- Role checks on every HR route
- CSRF protection on all forms
- Rate limits on login, registration, password reset and uploads
- Résumés and photos in a private bucket, shown through signed, expiring links
- Database-level audit log of every change, with the acting user and old and new values; passwords are never logged
- Debug routes exist only in local development
- `.env` is never committed; secret rotation is in [DEPLOYMENT.md §3](DEPLOYMENT.md#3-rotating-secrets)
