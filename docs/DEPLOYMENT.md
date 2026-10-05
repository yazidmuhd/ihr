# Deploying i-HR

i-HR runs in three environments. Code moves in one direction only:

```
feature branch ──PR──▶ staging ──PR──▶ main
   (local)           (staging site)   (production site)
```

| | Local | Staging | Production |
|---|---|---|---|
| Purpose | Development | **Public** test site: testers try releases with demo data | **Internal** system with real data |
| Who can open it | you | anyone with the link | applicant portal: anyone · HR area: office and VPN IPs only (`IP_ALLOWLIST`) |
| Git branch | any | `staging` (auto-deploys) | `main` (auto-deploys) |
| `APP_ENV` / `APP_DEBUG` | `local` / `true` | `staging` / `false` | `production` / `false` |
| Database | local PostgreSQL `ihr` | its own Railway Postgres | its own Railway Postgres |
| Uploads (`UPLOADS_DISK`) | `public` (local folder) | `s3` — staging bucket | `s3` — production bucket |
| Queue | `sync` (no worker) | `database` + worker service | `database` + worker service |
| Mail | `log` | `log` or a test inbox (never real applicants) | real mail service |
| AI key | own key, small limit | own key, small limit | own key, monthly limit |
| Demo data | yes | yes, reset weekly (`ihr:reset-demo`) | no |
| Search engines | — | blocked (`X-Robots-Tag: noindex`) + yellow "Test environment" banner | normal |

Never share a database, bucket, or API key between staging and production. A bug or test in staging must not be able to touch real data or spend the production budget.

---

## 1. Railway setup (one time)

Railway keeps both environments in **one project**. Each environment gets its own copy of every service.

### Production environment (created by default)

1. **New Project → Deploy from GitHub repo** → select this repository. Rename the service to **`web`**.
2. **+ New → Database → PostgreSQL**.
3. **+ New → Bucket** (Railway Storage Bucket). Open its **Credentials** tab for the S3 values.
4. On **`web`** → **Variables**, add:

   | Variable | Value |
   |---|---|
   | `APP_NAME` | `i-HR` |
   | `APP_ENV` | `production` |
   | `APP_DEBUG` | `false` |
   | `APP_KEY` | output of `php artisan key:generate --show` (new, never reuse local) |
   | `APP_URL` | `https://<your production domain>` |
   | `LOG_LEVEL` | `warning` |
   | `DATABASE_URL` | `${{Postgres.DATABASE_URL}}` (reference variable) |
   | `DB_CONNECTION` | `pgsql` |
   | `DB_SSLMODE` | `prefer` (private network) |
   | `SESSION_DRIVER` / `CACHE_STORE` | `database` |
   | `SESSION_SECURE_COOKIE` | `true` |
   | `QUEUE_CONNECTION` | `database` |
   | `CONTAINER_ROLE` | `web` |
   | `UPLOADS_DISK` | `s3` |
   | `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET` | from the bucket's Credentials tab |
   | `AWS_ENDPOINT` | `https://storage.railway.app` |
   | `AWS_DEFAULT_REGION` | `auto` |
   | `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` | your mail service |
   | `AI_API_KEY` | production OpenRouter key |
   | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` | production OAuth client |
   | `GOOGLE_REDIRECT_URI` | `https://<your production domain>/auth/google/callback` |
   | `CLIENT_IP_HEADER` | `X-Real-IP` (see section 4) |
   | `IP_ALLOWLIST` | office + VPN egress IPs, e.g. `203.0.113.10, 198.51.100.0/28` |
   | `IP_ALLOWLIST_SCOPE` | `hr` — HR internal, applicant portal public (the default) |
   | `HR_ADMIN_USERNAME`, `HR_ADMIN_EMAIL`, `HR_ADMIN_PASSWORD` | first HR account (password ≥ 12 characters) |

5. **`web` → Settings**: branch **`main`**, **Healthcheck Path** `/up`, generate a public domain.
6. **Worker**: **+ New → GitHub repo** (same repository) → name it **`worker`**. Copy the `web` variables (Railway's *Raw editor* makes this quick) and change `CONTAINER_ROLE=worker`. Settings: branch `main`, **no** health check, **no** public domain. The worker also runs the scheduled tasks in `routes/console.php` (interview reminders, cleanup).
7. Deploy. The `web` container runs the migrations on start (`docker/start.sh`).
8. Create the first HR account once:
   ```bash
   railway ssh --service web          # Railway CLI
   php artisan db:seed --force        # departments + HR account from HR_ADMIN_* (no demo data in production)
   php artisan ihr:check              # every line should be PASS
   ```

### Staging environment

1. Project → environment dropdown → **New Environment** → **Duplicate "production"** → name it `staging`.
2. Railway creates separate copies of Postgres, the bucket, `web`, and `worker`.
3. In **staging**, change on both `web` and `worker`:
   - branch → `staging`
   - `APP_ENV=staging`, `APP_URL` → the staging domain, new `APP_KEY`
   - `AI_API_KEY` → the staging key (low credit limit — public testers trigger AI calls); `MAIL_MAILER=log`
   - **remove** `IP_ALLOWLIST` (staging is public); keep `CLIENT_IP_HEADER=X-Real-IP` so rate limits work per visitor
   - bucket credentials → the **staging** bucket's values
   - `GOOGLE_REDIRECT_URI` → `https://<staging domain>/auth/google/callback`
4. Create the `staging` branch once: `git checkout -b staging && git push -u origin staging`.
5. Seed staging with demo data: `php artisan db:seed --force` (APP_ENV=staging adds the demo vacancy).
6. Weekly reset happens automatically: the staging worker's scheduler runs `php artisan ihr:reset-demo` every Monday 02:00 (Malaysia time). It never runs in production.
7. Give testers the staging link plus a test HR login (from `HR_ADMIN_*`). They can register as applicants themselves.

---

## 2. Releasing a change

1. Work on a feature branch; open a PR into `staging`. GitHub Actions runs the test suite on PostgreSQL (`.github/workflows/tests.yml`).
2. Merge when green → staging deploys automatically. Click through the change on the staging site.
3. Open a PR `staging → main`, merge → production deploys.
4. After each production deploy: `php artisan ihr:check` in the `web` shell.

**Database changes:** always add a *new* migration; never edit one that has already run in staging or production. Before a risky migration, take a backup (Postgres service → Backups, or `pg_dump`).

**Rollback:** Railway → service → Deployments → previous deployment → *Redeploy*. A rollback does not undo migrations, so keep migrations backward compatible (add columns first, remove them in a later release).

---

## 3. Rotating secrets

Rotate a secret when it may have been exposed (pasted in a chat, shown on screen, committed, a team member leaves) and at least yearly. Order: **create the new one → switch the app → verify → revoke the old one**, so the site never breaks.

| Secret | Create the new one | Verify | Revoke the old one |
|---|---|---|---|
| `APP_KEY` | `php artisan key:generate --show` → set in the environment. Signs everyone out; nothing in the database is encrypted with it | `php artisan ihr:check --only=app` | automatic |
| `AI_API_KEY` | openrouter.ai → **Keys** → *Create Key* (one per environment, set a credit limit) | `php artisan ihr:check --only=ai` | delete the old key in OpenRouter |
| `GOOGLE_CLIENT_SECRET` | console.cloud.google.com → APIs & Services → **Credentials** → OAuth client → *Add secret* | `ihr:check --only=google`, then sign in with Google | *Disable*, then delete the old secret on the same page |
| `MAIL_PASSWORD` | Gmail: myaccount.google.com → Security → **App passwords** (needs 2-Step Verification). Better for production: a transactional mail service with your own domain | `php artisan ihr:check --only=mail` (logs in, sends nothing) | remove the old app password |
| Bucket keys | Bucket → Credentials → regenerate | `php artisan ihr:check --only=uploads` | automatic on regenerate |
| Database password | Railway manages it (`DATABASE_URL` updates automatically) | `ihr:check --only=database` | — |

Locally, edit `.env` directly. Never paste secret values into chats, tickets, or commits. `.env` is git-ignored, and must not be included in zip files you hand in or share.

---

## 4. Internal access: office IPs and VPN

In production the **HR side is internal**: `/hr/*` pages, and anything done while signed in as an HR account, only work from the networks in `IP_ALLOWLIST`. Applicants register, apply and answer interview invitations from anywhere. An HR user outside the allowed networks gets a page that shows the IP the server saw and tells them to connect to the VPN.

**Where the fixed IP comes from**

Until the office's IT provides a static office IP or VPN, HR connects through our own **exit node**: a small cloud server with a static IP running Tailscale (`deploy/exit-node/setup.sh`, steps in [GO-LIVE.md](GO-LIVE.md#3-the-fixed-exit-ip-exit-node)). HR turns on the Tailscale app, and their traffic leaves from that server's IP. When IT later provides an office IP, add it to the list; both can be allowed at once.

**What to put in the list**

- The office's public IP (what `curl ifconfig.me` shows from an office computer).
- The VPN's **egress** IP(s): the address your traffic leaves the VPN from, not the VPN's internal 10.x pool. Ask IT, or connect to the VPN and run `curl ifconfig.me`. The VPN needs a static egress IP for this to work.
- CIDR ranges and IPv6 are supported: `IP_ALLOWLIST="203.0.113.10, 198.51.100.0/28, 2001:db8:abcd::/48"`.
- After a change, redeploy and run `php artisan ihr:check --only=network`.

**Scope: `all` or `hr`**

| `IP_ALLOWLIST_SCOPE` | Effect | Use when |
|---|---|---|
| `hr` (default, used in production) | Only `/hr/*` pages and HR accounts need an allowed IP; register / apply stay public | External candidates apply online, HR works only from the office / VPN |
| `all` | Every page needs an allowed IP | Candidates are internal too (staff transfers) |

The platform health check `/up` is never blocked.

**VPN-friendly details**

- Use a **full-tunnel** VPN, or add this site's domain to the VPN's routed domains. With split tunnelling the browser may reach the site directly from home, and the block page will show the home IP instead of the VPN's.
- Sessions are not tied to an IP, so reconnecting the VPN does not sign anyone out.
- Résumé links are signed S3 links that expire after 60 minutes. They open without the VPN for that hour; that's by design, so a link opened by HR keeps working.

**How the app knows the visitor's IP**

Railway's edge proxy sets `X-Real-IP` to the connecting address and overwrites any value a visitor sends. With `CLIENT_IP_HEADER=X-Real-IP` the app uses that header (and nothing else) as the visitor's IP; login rate limits use it too. If the header is missing the request is blocked. Never set `CLIENT_IP_HEADER` on a host where visitors can reach the app without going through that proxy.

Verify it once on staging after deploying (with `IP_ALLOWLIST` temporarily set to your own IP):

```bash
curl -s -o /dev/null -w "%{http_code}\n" https://<staging-domain>/login                          # 200 from your IP
curl -s -o /dev/null -w "%{http_code}\n" -H "X-Real-IP: 1.2.3.4" https://<staging-domain>/login  # must still be 200: your spoofed header was replaced
```

Then test from a phone on mobile data (not on the VPN): it must get the "only available on the company network" page.

**Stronger options (if requirements grow)**

- **Cloudflare Access** in front of the custom domain: staff sign in with their company account (free up to 50 users), no VPN needed, and Railway receives the real IP through `Cf-Connecting-IP`.
- **Tailscale** ([Railway guide](https://docs.railway.com/guides/set-up-a-tailscale-subnet-router)): remove the public domain entirely; the app is reachable only from devices on the company tailnet.

---

## 5. Running the queue worker locally (optional)

Local development uses `QUEUE_CONNECTION=sync`, so no worker is needed. To test the production setup:

```bash
QUEUE_CONNECTION=database php artisan serve
php artisan queue:work --queue=high,default      # in a second terminal
```

Résumé parsing runs on the `high` queue; e-mail notifications on `default`.
