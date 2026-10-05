# Go-live checklist

Work through these in order. Each step ends with a check, so you know it worked before moving on. Allow about half a day the first time.

**Target setup**

```
Applicants (anywhere) ──────────────────────────────▶  i-HR production (Railway)
                                                          ├─ applicant portal: public
HR (laptop + Tailscale) ──▶ exit node (fixed IP) ──────▶  └─ HR area: only from the exit node's IP

Testers (anywhere) ─────────────────────────────────▶  i-HR staging (Railway): public test site, demo data
```

## 1. Accounts you need

| Account | Used for | Cost |
|---|---|---|
| GitHub (push access to the repository) | Railway deploys from it | free |
| [Railway](https://railway.com) Hobby | production + staging | from USD 5 / month (includes USD 5 usage) |
| Cloud server provider (DigitalOcean, Vultr or AWS Lightsail) | the exit node with a fixed IP | about USD 4–6 / month |
| [Tailscale](https://tailscale.com) Personal | connects HR devices to the exit node | free up to 6 users |
| [OpenRouter](https://openrouter.ai) | AI résumé parsing | pay per use; set a credit limit per key |
| Google Cloud | "Sign in with Google" | free |
| Gmail with 2-Step Verification (for now) | interview e-mails | free |

## 2. Rotate the secrets

The OpenRouter key and Google client secret were exposed during development, and the Gmail app password no longer works (interview e-mails currently fail). Follow [DEPLOYMENT.md §3](DEPLOYMENT.md#3-rotating-secrets).

**Check:** locally, `php artisan ihr:check` shows PASS for *AI API key*, *Mail* and *Google sign-in*.

## 3. The fixed exit IP (exit node)

1. Create the server at your provider:
   - Region **Singapore** (closest to Malaysia), image **Ubuntu 24.04**, the smallest plan **with a public IPv4** (avoid IPv6-only plans).
   - Make sure the IP never changes: DigitalOcean and Vultr keep it for the server's lifetime (don't destroy and recreate the server). On **Lightsail**, attach a **Static IP** (free while attached), or it changes when the server restarts.
2. Create a Tailscale account (sign in with Google or Microsoft). This is your *tailnet*.
3. SSH into the server and run the setup script:
   ```bash
   sudo bash setup.sh        # deploy/exit-node/setup.sh from this repository
   ```
   Open the login link it prints. At the end it prints the server's **public IPv4 / IPv6** and the exact `IP_ALLOWLIST` line.
4. Tailscale admin console → **Machines** → `ihr-exit`:
   - **Edit route settings** → tick **Use as exit node**.
   - **Disable key expiry**, so the server never drops out of the tailnet.
5. On your laptop: install Tailscale, sign in to the same tailnet, then **Exit node → ihr-exit**.

**Check:** with the exit node on, https://ifconfig.me shows the server's IPv4. With it off, it shows your normal IP (e.g. TIME dotCom).

## 4. Production on Railway

Follow [DEPLOYMENT.md §1](DEPLOYMENT.md#1-railway-setup-one-time) (production). Network variables on **both** `web` and `worker`:

```
CLIENT_IP_HEADER=X-Real-IP
IP_ALLOWLIST=<exit node IPv4>, <exit node IPv6>
IP_ALLOWLIST_SCOPE=hr
```

Then, in the `web` service shell: `php artisan db:seed --force` (creates the departments and the HR account from `HR_ADMIN_*`).

**Check:** `php artisan ihr:check` in the `web` shell: every line PASS.

## 5. Staging (public test site) on Railway

Follow [DEPLOYMENT.md §1](DEPLOYMENT.md#staging-environment). No `IP_ALLOWLIST`, `MAIL_MAILER=log`, its own AI key with a small credit limit. Seed it with demo data.

**Check:** the staging site shows the yellow "Test environment" banner.

## 6. Final verification (15 minutes)

| # | Test | Expected |
|---|---|---|
| 1 | Laptop, exit node **on** → `https://<prod>/login` as HR | HR dashboard opens |
| 2 | Laptop, exit node **off** → reload the HR dashboard | "only available on the company network" page showing your home IP |
| 3 | Phone on mobile data → `https://<prod>/register`, register, upload a résumé, apply | works (applicant side is public) |
| 4 | Phone → `https://<prod>/hr/dashboard` | blocked page |
| 5 | Exit node on → HR invites the phone applicant to an interview | invitation e-mail arrives |
| 6 | Spoofing test, exit node **off**: `curl -s -o /dev/null -w "%{http_code}" -H "X-Real-IP: <exit node IPv4>" https://<prod>/hr/dashboard` | `403` (Railway replaced the fake header). `302` would mean the header can be faked: stop and remove `CLIENT_IP_HEADER` |
| 7 | HR → **Activity** | every step above is listed with who did it |

When all seven pass, the system is ready to show.

## 7. Demo script for HR (about 15 minutes)

Prepare: a sample PDF résumé on your phone, the HR account on your laptop with the exit node on.

1. **Security first (1 min).** Turn the exit node off: the HR area refuses access. Turn it on: it opens. *"HR data is only reachable from the company network; applicants can still apply from anywhere."*
2. **Post a vacancy (2 min).** Vacancies → Create: title, department, required skills, experience range, education.
3. **Apply as a candidate (3 min).** On the phone (mobile data): register, upload the résumé (the AI reads it in a few seconds), apply. Show that it works without the VPN.
4. **AI ranking (2 min).** Back on the laptop: the vacancy's AI ranking board shows the match score and the per-skill breakdown → shortlist.
5. **Interview (3 min).** Schedule an online interview → the candidate gets the invitation and confirms on the phone → mark it done → add two panel members with ratings → finalize (combined score).
6. **Hire (1 min).** Convert to employee → the employee record with an employee number and department.
7. **Accountability (2 min).** Employee page → *Hiring timeline*; then **Activity**: every action, who did it, and old → new values. Nothing can be changed without leaving a trace.
8. **Questions (1 min).** Point testers to the public test site for hands-on trials.

## 8. When the office's IT takes over

- **Office static IP or company VPN:** add its egress IP(s) to `IP_ALLOWLIST` next to the exit node's. When everyone uses the office network, remove the exit node's IP and shut the server down.
- **Keeping Tailscale instead:** invite each HR staff member to the tailnet (free up to 6 users; beyond that, Tailscale's paid plans). In the Tailscale policy, allow only an `hr` group to use the exit node.
- Hand IT [DEPLOYMENT.md](DEPLOYMENT.md): environments, release flow, secret rotation and access control are all documented there.
