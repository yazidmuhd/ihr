# i-HR: Intelligent Human Resource Recruitment System

i-HR is a web system that helps an HR team hire people. It covers everything from posting a job to turning the chosen candidate into an employee record. Its main helper is AI: it reads every applicant's résumé and shows HR which candidates fit the job best.

i-HR was developed as a final year project for **CSP650** at **Universiti Teknologi MARA (UiTM)**, with **BASF PETRONAS Chemicals Sdn. Bhd.** as the case study organisation. The requirements and design are documented in the [project documents](#project-documents) below.

> **In one sentence:** applicants apply online with their résumé, the system reads and scores each one against the job, and HR interviews, rates and hires the best candidates in one place, with a record of every decision.

---

## The problem it solves

Hiring by e-mail and spreadsheets is slow and hard to keep fair:

- HR opens and reads every résumé by hand, even for applicants who clearly don't fit.
- Candidates are compared by gut feeling, and the reasons are rarely written down.
- Interview schedules, panel ratings and decisions end up spread across e-mails and files.
- Later, nobody can easily answer "who rejected this applicant, and when?"

i-HR puts the whole process in one system. It does the first round of reading for HR, and it keeps a trail of every step.

## Who uses it

| User | What they do in i-HR |
|---|---|
| **Applicants** (the public) | Browse open jobs, create an account (or sign in with Google), upload a résumé, apply, and confirm or decline interview invitations |
| **HR staff** | Post vacancies, see AI-ranked candidates, shortlist, schedule interviews, record panel ratings, hire, and review the activity log |

The applicant side is open to anyone on the internet. The HR side can only be opened from the company's network or VPN (see [Security](#security-in-plain-words)).

## How it works: one hire, step by step

```
 HR                         Applicant                       i-HR does automatically
 ──                         ─────────                       ───────────────────────
 1. Posts a vacancy  ───▶   2. Finds the job, registers,
    (skills, experience,       uploads a résumé (PDF/DOCX)  ──▶  3. Reads the résumé with AI:
     education needed)                                               skills, years of experience,
                            4. Applies                               education
                                                            ──▶  5. Scores the match (0–100)
 6. Opens the AI ranking board: best matches first,
    with the reason for each score → shortlists
 7. Schedules the interview  ─▶ 8. Gets a notification     ──▶  Sends reminders before the
                                   and confirms                  interview
 9. Marks it done, each panel member rates 1–5
10. Finalises: final score = 60% résumé match + 40% interview
11. Hires → an employee record is created
                                                            ──▶  Every step above is logged:
                                                                 who did it, when, old → new value
```

### 1. Post a vacancy
HR describes the job: title, department, employment type, required skills, years of experience and education.

### 2–4. Apply
Applicants browse the job list without signing in. To apply they create an account and upload a résumé. Each applicant can keep several résumés and choose which one is active.

### 3. The AI reads the résumé
A few seconds after upload, the system pulls the text out of the PDF or Word file and asks an AI model (GPT-4o mini, through OpenRouter) to pick out the important facts:

- name, e-mail and phone
- skills
- years of experience
- education

Simple built-in rules double-check the result. For example, if the AI misses the years of experience, the system looks for phrases like "5 years" in the résumé. Skill names are tidied up too, so "lockout tagout" and "LOTO" count as the same skill.

### 5–6. Match score and ranking
The system compares each applicant with the vacancy and gives a **match score from 0 to 100**. The score is built from:

| Part | Typical weight |
|---|---|
| Skills the job asks for | ~45% |
| Years of experience | ~25–30% |
| Education level | ~15–20% |
| Certifications and tools | the rest |

The exact weights depend on the type of job. HR sees candidates sorted by score, with a **breakdown** of which required skills each person has and which they're missing. HR can understand and question the score, not just trust it.

> **The AI assists; HR decides.** The score puts the best matches at the top, but every shortlist, rejection and hire is a decision made by a person.

### 7–8. Interviews
HR schedules an interview: on-site, online, by phone or hybrid. The applicant gets an in-app notification and an e-mail invitation, and confirms or declines. When the interview is less than three days away, the system adds a reminder to the applicant's notifications.

### 9–10. Panel evaluation
After the interview, HR marks it as done. Each panel member gives a rating from 1 to 5, with notes. When HR finalises, the system combines the two views of the candidate:

**final score = 60% résumé match + 40% interview rating**

### 11. Hire
One click turns the successful candidate into an **employee record** with their department. The application is marked as hired, and the same candidate can't be hired twice by accident.

### Accountability
The **Activity** page and each employee's **hiring timeline** show every step: who moved an application from *submitted* to *shortlisted* to *hired*, who changed a vacancy, and what the old and new values were. The database records this by itself, so it is written even if someone forgets to.

## Security in plain words

HR data is personal data, so the system is built to protect it:

- **The HR area only opens from the office network or VPN.** From anywhere else, HR pages refuse to load, even with the right password. Applicants can still apply from anywhere.
- **Strong passwords** are required, and repeated wrong passwords are slowed down.
- **HR is signed out after 30 minutes of inactivity**, in case a laptop is left open.
- **Résumés are stored privately.** They're only shown through short-lived links, never as public files.
- **Accounts of staff who leave can be disabled.** Their history stays in the activity log.
- **Every change is recorded:** who did it and when, with the old and new values.

Details for IT: [docs/SECURITY.md](docs/SECURITY.md).

## What it doesn't do (yet)

Being clear about the limits:

- **Scanned résumés** (a photo or scan saved as PDF) can't be read, because there's no text recognition (OCR). The applicant is asked to upload a text-based PDF or a Word (.docx) file.
- **The AI can misread a résumé**, especially one with an unusual layout. That's why HR always sees the breakdown and makes the final call.
- **Résumé text is sent to the AI provider** (OpenRouter → OpenAI) to be read. Applicants should be told this in the privacy notice (Malaysia PDPA 2010).
- Planned next: two-factor sign-in for HR, a PDPA consent and data-retention policy, and e-mail verification for applicants.

---

## Project documents

| Document | What it covers |
|---|---|
| [System Requirements Specification (SRS)](docs/SRS_iHR.pdf) | What the system must do: scope, users, the 16 use cases (UCD001–UCD016) with their steps, performance and security requirements, activity, class and sequence diagrams |
| [Software Design Document (SDD)](docs/SDD_iHR.pdf), version 2.0 | How it is built: three-tier architecture, database design and data dictionary, package diagram, controller pseudocode, screen designs and the requirements traceability matrix |

Prepared by Muhammad Yazid, supervised by Dr. Nur Suhailayani Binti Suhaimi; submitted 30 January 2026.

### Use cases in the system

| Use case | Who | Where in i-HR |
|---|---|---|
| UCD001 Create Job Vacancy, UCD002 Manage Job Vacancy | HR | Vacancies |
| UCD003 Create Applicant, UCD004 Manage Applicant | Applicant | Register, Profile |
| UCD005 View Job Vacancies, UCD006 Apply Job Vacancies | Applicant | Jobs, My Applications |
| UCD007 Upload Resume, UCD008 Delete Resume | Applicant | My Resumes |
| UCD009 View Application Status | Applicant | My Applications |
| UCD010 Filter Resume | HR | Vacancies → AI ranking (star icon) |
| UCD011 Manage Shortlisted Candidate | HR | Shortlist |
| UCD012 Manage Interview Session, UCD013 Notification to Candidates | HR | Interviews |
| UCD014 View Interview Notification, UCD015 Interview Confirmation | Applicant | Notifications, Interviews |
| UCD016 Create Employee | HR | Interview evaluation → Convert to Employee, Employees |

### Added after the documents

The documents describe the system as submitted in January 2026. These parts were added later and are not in them:

- Applicants can browse jobs without signing in (the SRS required sign-in first).
- Interview reminders three days ahead.
- Activity log and hiring timeline (who changed what, recorded by the database).
- Security for live use: HR area limited to the office/VPN, stronger passwords, HR idle timeout, account disabling, browser security headers ([docs/SECURITY.md](docs/SECURITY.md)).
- Production setup: separate production and test sites, private file storage, background worker, automated tests ([docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)).

---

## For developers

### Built with

| Part | Technology |
|---|---|
| Backend | Laravel 12 (PHP 8.2+) |
| Frontend | Vue 3 with Inertia.js, Tailwind CSS, built with Vite |
| Database | PostgreSQL 15+. Triggers keep the audit log and status history; constraints keep data valid |
| AI résumé reading | OpenRouter API (default model `openai/gpt-4o-mini`), `smalot/pdfparser` for PDFs |
| File storage | Local disk in development; a private S3-compatible bucket in production |
| Background work | Laravel queue (résumé parsing) and scheduler (interview reminders) |
| Sign-in | E-mail or username + password, or Google |
| Hosting | Railway: separate production and staging (public test site) environments |

### Where things are

```
app/Jobs/ParseResume.php            résumé → text → AI → stored profile
app/Services/AiClient.php           the AI request and the prompt
app/Services/MatchScorer.php        résumé vs vacancy → match score + breakdown
app/Http/Controllers/HR/            vacancies, ranking, interviews, employees, activity
app/Http/Controllers/Applicant/     jobs, applications, résumés, interviews, profile
database/migrations/                tables, constraints, audit and history triggers
resources/js/Pages/                 the Vue screens (HR/, Applicant/, Auth/, Public/)
tests/                              feature and unit tests (PHPUnit, on PostgreSQL)
docs/                               deployment, go-live checklist, security
```

### Run it on your computer

You need PHP 8.2+, Composer, Node 20 and PostgreSQL 15+.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
createdb ihr                      # then set DB_USERNAME / DB_PASSWORD in .env
php artisan migrate --seed        # departments, an HR account and a demo vacancy
npm run build                     # or `npm run dev` while editing the frontend
php artisan serve                 # http://127.0.0.1:8000
php artisan queue:work            # in a second terminal: reads uploaded résumés
```

- **Local HR login:** `humanres` / `admin01`. This is only for local use; staging and production refuse to seed without a strong password set in `HR_ADMIN_PASSWORD`.
- **AI key:** résumé reading needs an OpenRouter key in `AI_API_KEY`. Without it, uploads still work but end with an "error" status.
- **Check the setup:** `php artisan ihr:check` tests the database, storage, queue, AI key, mail and Google sign-in, without printing any secrets.

### Tests

```bash
createdb ihr_test
php artisan test
```

The tests use PostgreSQL rather than an in-memory database, because the database triggers and constraints are part of what they test. GitHub Actions runs them on every push and pull request.

### Deployment and operations

| Document | What's in it |
|---|---|
| [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) | Railway setup (production and staging), releasing a change, rotating secrets, the office/VPN IP allowlist |
| [docs/GO-LIVE.md](docs/GO-LIVE.md) | Step-by-step launch checklist, final verification and a 15-minute HR demo script |
| [docs/SECURITY.md](docs/SECURITY.md) | Security measures, how to review them, and what's still open |

Useful commands:

```bash
php artisan ihr:check                         # are all services configured?
php artisan ihr:account list                  # HR accounts
php artisan ihr:account disable <e-mail>      # someone left: block access, keep history
php artisan ihr:reset-demo                    # staging only: reset the demo data
```

---

*i-HR was developed as a final year project for CSP650, Universiti Teknologi MARA.*
