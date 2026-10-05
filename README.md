# i-HR

Recruitment system for HR teams: post vacancies, receive applications with AI-parsed résumés, rank candidates by match score, run panel interviews, and convert hires into employee records — with a full audit trail of who changed what.

**Stack:** Laravel 12 · Inertia + Vue 3 · PostgreSQL · S3-compatible storage · OpenRouter (AI résumé parsing)

## Local setup

Requirements: PHP 8.2+, Composer, Node 20, PostgreSQL 15+.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
createdb ihr                      # then set DB_USERNAME / DB_PASSWORD in .env
php artisan migrate --seed        # departments, HR account (humanres / admin01), demo vacancy
npm run build                     # or `npm run dev` while editing the frontend
php artisan serve
```

`php artisan ihr:check` verifies the database, storage, queue, AI key, mail and Google sign-in configuration without printing any secrets.

## Tests

```bash
createdb ihr_test
php artisan test
```

Tests run on PostgreSQL because the database triggers (audit log, application status history) and CHECK constraints are part of what is tested. GitHub Actions runs them on every push and pull request.

## Deployment

Staging and production run on Railway, each with its own database, storage bucket, web service and queue worker. See [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) for setup, the release flow, access control and how to rotate secrets, and [docs/GO-LIVE.md](docs/GO-LIVE.md) for the step-by-step launch checklist and HR demo script.

**Access:** the applicant portal is public; the HR area only accepts connections from approved IPs (office / VPN exit node), configured with `IP_ALLOWLIST`.
