# ClinForce AI — System Documentation
**Version:** 1.0  
**Platform:** aiclinforce.com  
**Prepared for:** Client  
**Date:** April 5, 2026

---

## Table of Contents

1. [Platform Overview](#1-platform-overview)
2. [Technology Stack](#2-technology-stack)
3. [User Roles](#3-user-roles)
4. [Authentication & Security](#4-authentication--security)
5. [Candidate Features](#5-candidate-features)
6. [Employer Features](#6-employer-features)
7. [Admin Panel](#7-admin-panel)
8. [Billing & Subscriptions](#8-billing--subscriptions)
9. [Messaging System](#9-messaging-system)
10. [Notifications](#10-notifications)
11. [AI Features](#11-ai-features)
12. [Landing Page](#12-landing-page)
13. [Performance & Infrastructure](#13-performance--infrastructure)
14. [Privacy & Compliance](#14-privacy--compliance)
15. [Automated Tasks (Scheduler)](#15-automated-tasks-scheduler)
16. [API Overview](#16-api-overview)

---

## 1. Platform Overview

ClinForce AI is a healthcare recruitment platform that connects employers (hospitals, clinics, agencies) with qualified clinical professionals. The platform automates the hiring workflow from job posting through to offer letter generation, with AI-powered candidate screening and matching.

**Live URL:** https://aiclinforce.com  
**Admin Panel:** https://aiclinforce.com/admin

---

## 2. Technology Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 11 (PHP 8.2) |
| Frontend | Vue 3 + Vite + Tailwind CSS v4 |
| UI Components | PrimeVue (Aura theme) |
| Database | MySQL 8 |
| Authentication | Laravel Sanctum (token-based) |
| Payments | Stripe (test mode) |
| Video Interviews | Zoom API (Server-to-Server OAuth) |
| AI Screening | OpenAI API |
| Email | Gmail SMTP |
| Hosting | Hostinger Shared Hosting |
| Testing | Playwright (66 tests, 0 failures) |

---

## 3. User Roles

The platform has four user roles:

| Role | Description |
|------|-------------|
| **Admin** | Full platform access — manages users, subscriptions, analytics, system settings |
| **Employer** | Posts jobs, manages applications, schedules interviews, subscribes to plans |
| **Agency** | Same as Employer — represents staffing agencies |
| **Candidate (Applicant)** | Browses jobs, applies, tracks applications, manages profile |

---

## 4. Authentication & Security

### Registration & Login
- Email or phone number registration
- Google OAuth (one-click sign-in)
- Strong password enforcement (uppercase, number, min 8 chars)
- Auto-verification on first login (no more expired link lockouts)
- Re-registration allowed if email was never verified

### Security Features
- **Rate limiting** — 5 login attempts per minute per IP
- **Two-factor authentication (2FA)** — TOTP via Google Authenticator / Authy
- **Session management** — users can view and revoke all active sessions
- **IP-based suspicious login alerts** — admin is emailed when a user logs in from a new country
- **Login location tracking** — all logins stored with IP, country, city, user agent
- **Impersonation** — admins can log in as any user; a visible amber banner shows when impersonating
- **Account deletion** — 30-day grace period with email confirmation

### Email Verification
- Verification link valid for 7 days
- Clicking an expired link redirects to the SPA with a clear error message
- Auto-redirect to login after successful verification (3-second countdown)

---

## 5. Candidate Features

### Profile
- First name, last name, headline, summary, years of experience
- City, state, country
- Profile photo with crop tool
- Portfolio links
- Resume upload (PDF/DOC) with version history — multiple resumes, one marked "active"
- **Open to work toggle** — signals availability to employers in talent search
- Profile completeness percentage with actionable tips

### Job Search
- Browse all published jobs with filters: location, employment type, work mode, salary range
- **Job match score** — each listing shows a % match based on the candidate's headline, location, and experience
- Save jobs (bookmark)
- Job alerts — set keyword/location alerts and receive email when matching jobs are posted

### Applications
- Apply with cover letter (3 pre-written templates available)
- Resume auto-attached from profile
- **Application status timeline** — visual stepper: Applied → Reviewed → Interview → Decision
- Withdraw application with reason (reason is sent to employer)
- View full status history with timestamps and notes

### Interviews
- View all scheduled interviews
- **Confirm or decline** interview invitations
- Download `.ics` calendar file
- **View employer feedback** after completed interviews (rating, recommendation, notes)

### Dashboard
- Stats: active applications, upcoming interviews, profile completeness, AI match score
- **Next steps prompts** — smart action cards (e.g. "2 interviews awaiting confirmation", "Complete your profile")
- **Profile strength tips** — specific suggestions with benefit descriptions
- Application pipeline visualization
- Recommended jobs
- Recent activity feed

### Settings
- Update email, phone, password
- Profile photo with crop
- **Notification preferences** — frequency (immediate/daily/weekly) + per-type toggles (status changes, interviews, messages, job alerts, invitations)
- Active sessions management
- **GDPR data export** — download all data as JSON
- **Account deletion** — schedule with 30-day grace period

---

## 6. Employer Features

### Job Management
- Create, edit, publish, archive, delete job posts
- Fields: title, description, employment type, work mode, location, salary range, **application deadline**
- **Application deadline** — jobs auto-archive after the closing date
- **Duplicate job detection** — warning shown when posting a title similar to an existing active job
- **Job template auto-fill** — select a previous job to pre-fill the form
- Copy shareable job link
- **Job post analytics** — views, total applications, conversion rate, daily trend, breakdown by status
- **Pipeline report** — download CSV with per-job funnel stats

### Candidate Management
- Candidates list with search, status filter, bulk actions
- **Bulk status update** — select multiple candidates, move to any stage at once
- **Quick reject with reason** — bulk reject with a pre-written or custom message emailed to candidates
- **Export candidates to CSV** — all applications with name, job, status, rating, email, phone
- **Candidate rating** — 1–5 star rating on each application (list and profile)
- **Candidate blacklist** — mark candidates as "do not contact" across all jobs

### Applicant Profile
- Full application details: cover letter, status history, interview details, notes
- **Offer letter generation** — after marking hired, generate an HTML offer letter with start date, salary, notes
- Internal notes (private, team-only)
- Resume preview and download (requires document access payment)
- Message candidate directly

### Pipeline Board (Kanban)
- Drag-and-drop cards between stages
- **Bulk selection mode** — select multiple cards, move all at once
- Filter by job
- Load more (paginated, 50 per page)

### Interviews
- Schedule interviews with Zoom auto-link generation
- Calendar view and list view
- **No-show tracking** — mark a candidate as no-show; badge displayed on the card
- Interview feedback (rating, recommendation, notes)
- Download `.ics` calendar file
- Reminder emails sent 24h and 1h before

### Talent Search
- Browse all candidate profiles
- Search by keyword, location
- **Open to work filter** — show only candidates actively looking
- Bulk invite candidates
- Match score displayed per candidate

### Analytics Dashboard
- Application activity bar chart (stacked: new / in-process / hired)
- Pipeline donut chart
- KPIs: open roles, total candidates, upcoming interviews, new applications
- Date range filter (7 / 30 / 60 / 90 days)

### Candidate Comparison
- Compare up to 3 candidates side-by-side
- Rows: status, headline, experience, location, applied date, cover letter, resume, **notes**
- Notes column is editable per candidate during the session

### Bulk Messaging
- Send one message to all shortlisted (or interview-stage) candidates for a job at once
- Creates individual conversations per candidate

### Settings
- Company profile: name, type, website, address, tax ID
- Billing region and currency
- **Data retention** — set how many days before rejected applications are auto-deleted (default: 90 days)
- Notification frequency
- 2FA setup
- Active sessions management

---

## 7. Admin Panel

The admin panel is accessible at `/admin` and is only available to users with the `admin` role.

### Dashboard
- Platform stats: total users, employers, candidates, active jobs, applications, subscriptions, revenue
- MRR, churn rate, new subscriptions this month
- User growth chart (6 months)
- Role breakdown donut chart
- Recent registrations table
- Quick action links

### Users
- Paginated user list with search (email, phone, ID), role filter, status filter
- Actions per user: suspend, ban, activate, reset password, email, impersonate, grant subscription
- Bulk actions: activate, suspend, ban, export CSV
- User detail drawer with 3 tabs:
  - Overview: subscriptions, jobs posted, applications
  - Notes: internal admin notes
  - Login History: IP, country, timestamp

### Jobs
- All jobs across all employers
- Search by title, filter by status
- Update job status

### Subscriptions
- All subscriptions with user, plan, status, amount
- Search by user email

### Plans
- View and edit all subscription plans
- Toggle active/inactive, update pricing, features, limits

### Verifications
- Review employer/agency verification requests
- Approve or reject with notes

### Contacts
- All contact form submissions from the landing page

### AI Screenings
- All AI screening results across the platform
- View score, recommendation, and AI summary per candidate

### Audit Logs
- Full audit trail of all significant actions
- Filter by action type, user, date range
- View metadata in expandable dialog

### System Status
- Database connection status
- Storage usage
- Failed jobs count
- PHP and Laravel versions
- Environment (production/local)
- Recent error logs (WARNING, ERROR, CRITICAL)
- **Queue monitor** — failed jobs list with retry button
- **Webhook logs** — Stripe and Zoom webhook history
- **DB table sizes** — all tables with row count and size in MB
- **Cache flush** button
- **Maintenance mode** toggle with custom message
- **Announcement banner** — set a site-wide message

### Feature Flags
- Toggle platform features on/off without code deployment

### Analytics
- Revenue over time (line chart)
- User registrations over time (line chart)
- Revenue by plan (donut chart)
- User role distribution (donut chart)
- Subscription status breakdown (bar chart)
- Top employers by job count
- Period filter: weekly / monthly / yearly
- Export buttons: users CSV, revenue CSV, subscriptions CSV

### Global Search
- Search users, jobs, and subscriptions from the header (Cmd+K)

---

## 8. Billing & Subscriptions

### Plans

**Philippines (PHP)**
| Plan | Duration | Price |
|------|----------|-------|
| PH Starter | 3 months | ₱2,999.99 |
| PH Growth | 6 months | ₱4,999.99 |
| PH Pro | 1 year | ₱7,999.99 |

**International (USD)**
| Plan | Duration | Price |
|------|----------|-------|
| International Starter | 3 months | $49.99 |
| International Growth | 6 months | $79.99 |
| International Pro | 1 year | $129.99 |
| 7-Day Trial | 7 days | Free |

### Features per Plan
- Job post limits (20 / 50 / unlimited)
- AI candidate screening
- Advanced analytics
- Priority support
- Custom branding (Pro only)

### Billing Flow
1. Employer selects billing country → currency auto-set (PHP for Philippines, USD otherwise)
2. Add payment method (Stripe card)
3. Select plan → checkout → Stripe PaymentIntent charged
4. Subscription created, invoice generated, confirmation email sent

### Invoices
- All invoices listed in the Billing page
- Download as printable HTML (auto-triggers browser print dialog)
- Invoice includes: plan name, billing period, amount, status

### Renewal Reminders
- Email sent 7 days before expiry
- Email sent 1 day before expiry

### Document Access
- Separate from subscription — one-time payment per candidate
- Unlocks: resume download, all attachments, full contact details
- Subscription unlocks: messaging, interviews, hiring workflows

---

## 9. Messaging System

- Employers can message candidates they invited or who applied to their jobs
- Candidates can message employers
- Conversations are paginated (30 messages per page, load-more button)
- Unread count shown in the navigation bar
- Real-time unread count polling

---

## 10. Notifications

### In-App
- New application received (employer)
- Application status changed (candidate)
- Interview scheduled (candidate)
- New message received

### Email
- Application status updates
- Interview scheduled
- Interview reminders (24h and 1h before)
- Job alerts (matching new jobs)
- Subscription confirmation
- Invoice issued
- Subscription renewal reminders (7d and 1d)
- Suspicious login from new country (admin)
- Account deletion confirmation

### Preferences
- Frequency: immediate, daily digest, weekly summary
- Per-type toggles: status changes, interviews, messages, job alerts, invitations

---

## 11. AI Features

### AI Candidate Screening
- Triggered per application by employer
- Analyzes candidate profile, resume, and cover letter against job requirements
- Returns: match score (0–100%), recommendation, detailed summary
- Results visible to employer in the application view
- Admin can view all screenings across the platform

### AI Chatbot
- Available to employers in the dashboard
- Answers questions about the platform, hiring best practices, and candidate management

### Job Match Score
- Shown to candidates on the job listings page
- Computed from: headline keyword overlap, city match, country match
- Color-coded: green (≥80%), blue (≥60%), grey (below)

---

## 12. Landing Page

### Sections
1. **Announcement bar** — dismissable, links to register
2. **Navigation** — Features, How it works, Pricing, Testimonials, FAQ, Contact + dark mode toggle
3. **Hero** — headline, two-path CTA cards (Employer / Clinician), "No credit card required" copy
4. **Stats counter** — animated on scroll: 5,000+ clinicians, 200+ employers, 48h avg hire time, 1,200+ interviews
5. **Live job ticker** — scrolling strip of recently posted jobs (real API + fallback)
6. **Trusted by** — 6 healthcare organization logos
7. **Banner section** — two large image cards (Clinical Excellence, Professional Staffing)
8. **Features** — 3 feature cards with hover animations
9. **How it works** — 3-step process
10. **For Employers / For Clinicians** — split value proposition
11. **Testimonials** — 3 cards with real avatars (DiceBear)
12. **Pricing** — PHP/USD toggle, 3 plans per region, "Most Popular" badge
13. **FAQ** — 6 accordion questions
14. **CTA banner** — gradient with security badges and "No credit card required"
15. **Contact form** — name, email, subject, message → stored in DB + email to admin
16. **Footer** — links, social icons, security badges, Privacy/Terms links
17. **Sticky CTA bar** — appears after scrolling 600px, slides up from bottom

---

## 13. Performance & Infrastructure

### Caching
- `/public/jobs` — 5-minute cache per page (unfiltered requests only)
- `/billing/currency` — 10-minute cache per currency code
- `/analytics/dashboard` — 5-minute cache per user + days parameter
- `/admin/analytics` — 10-minute cache per period

### Bundle Splitting (Vite)
- `vendor-vue` — Vue core
- `vendor-router` — Vue Router
- `vendor-primevue` — PrimeVue components
- `vendor-charts` — Chart.js
- `vendor-swal` — SweetAlert2
- `pages-admin` — Admin pages (only loaded on /admin)
- `pages-candidate` — Candidate pages
- `pages-employer` — Employer pages
- `pages-auth` — Auth pages

### Background Queue
- Production uses `database` queue driver
- All emails dispatched to queue (non-blocking HTTP responses)
- Queue processed every minute via scheduler (`queue:work --stop-when-empty`)

### Image Optimization
- Banner images use `loading="lazy"` (not loaded until scrolled into view)
- Testimonial avatars lazy-loaded

### Message Pagination
- Conversations load 30 messages per page
- "Load older messages" button prepends older messages

---

## 14. Privacy & Compliance

### GDPR
- **Data export** — candidates can download all their data (profile, applications, messages, documents) as a JSON file
- **Account deletion** — 30-day grace period; confirmation email sent; cancellable before deadline
- **Data retention** — employers can set how long rejected applications are kept (default: 90 days); auto-deleted by daily command

### Candidate Privacy
- Last names masked to initial only across all employer-facing pages (e.g. "Maria S.")
- Email and phone hidden until document access is purchased
- Candidate contact info hidden in interview scheduling modal

### Security
- All passwords hashed with bcrypt
- Tokens expire after 7 days (configurable)
- HTTPS enforced in production
- CORS restricted to `aiclinforce.com`

---

## 15. Automated Tasks (Scheduler)

All commands run via `php artisan schedule:run` (cron every minute on the server).

| Command | Schedule | Description |
|---------|----------|-------------|
| `subscriptions:expire` | Hourly | Marks expired subscriptions as expired |
| `exchange-rates:sync` | Every 6 hours | Syncs currency exchange rates |
| `sanctum:prune-expired` | Daily | Removes expired API tokens |
| `interviews:reminders` | Every 30 min | Sends 24h and 1h interview reminder emails |
| `applications:prune-rejected` | Daily at 2am | Deletes rejected applications per retention settings |
| `jobs:archive-expired` | Hourly | Archives jobs past their closing date |
| `job-alerts:send` | Every 6 hours | Emails candidates about new matching jobs |
| `subscriptions:renewal-reminders` | Daily at 9am | Sends 7-day and 1-day renewal reminder emails |
| `queue:work --stop-when-empty` | Every minute | Processes queued emails and jobs |

---

## 16. API Overview

All API endpoints are prefixed with `/api/`. Authentication uses Bearer tokens via `Authorization: Bearer {token}` header.

### Public Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/health` | Health check |
| GET | `/plans` | List all active plans |
| GET | `/public/jobs` | Browse published jobs |
| GET | `/public/jobs/{id}` | View single job (increments view count) |
| POST | `/contact` | Submit contact form |
| POST | `/auth/register` | Register new account |
| POST | `/auth/login` | Login |
| POST | `/auth/forgot-password` | Request password reset |
| POST | `/auth/reset-password` | Reset password |
| GET | `/auth/google/redirect` | Google OAuth redirect |
| GET | `/auth/google/callback` | Google OAuth callback |

### Authenticated Endpoints (selection)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/auth/me` | Get current user |
| POST | `/auth/logout` | Logout |
| GET | `/me` | Get full profile |
| PUT | `/me/applicant` | Update candidate profile |
| PUT | `/me/employer` | Update employer profile |
| GET | `/jobs` | List own jobs |
| POST | `/jobs` | Create job |
| GET | `/applications` | List applications |
| POST | `/jobs/{job}/apply` | Apply to job |
| POST | `/applications/{id}/status` | Update application status |
| POST | `/applications/{id}/withdraw` | Withdraw application |
| POST | `/applications/{id}/rate` | Rate a candidate (1–5 stars) |
| POST | `/applications/{id}/offer-letter` | Generate offer letter |
| GET | `/interviews` | List interviews |
| POST | `/applications/{id}/interviews` | Schedule interview |
| POST | `/interviews/{id}/respond` | Confirm/decline interview |
| POST | `/interviews/{id}/no-show` | Mark as no-show |
| GET | `/conversations` | List conversations |
| POST | `/conversations` | Start conversation |
| GET | `/notifications` | List notifications |
| GET | `/billing/currency` | Get currency context + plans |
| POST | `/subscriptions` | Subscribe to plan |
| GET | `/invoices` | List invoices |
| GET | `/invoices/{id}/download` | Download invoice HTML |
| GET | `/user/sessions` | List active sessions |
| DELETE | `/user/sessions/{id}` | Revoke session |
| GET | `/user/gdpr-export` | Download GDPR data export |
| POST | `/user/request-deletion` | Schedule account deletion |
| GET | `/employer/blacklist` | List blacklisted candidates |
| POST | `/employer/blacklist` | Add to blacklist |
| GET | `/jobs/{id}/analytics` | Job post analytics |
| POST | `/jobs/{id}/bulk-message` | Bulk message shortlisted candidates |

### Admin Endpoints (admin role only)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/stats` | Platform statistics |
| GET | `/admin/analytics` | Revenue and user analytics |
| GET | `/admin/users` | List all users |
| PATCH | `/admin/users/{id}` | Update user status/role |
| POST | `/admin/users/{id}/impersonate` | Impersonate user |
| GET | `/admin/subscriptions` | All subscriptions |
| GET | `/admin/system-status` | System health |
| GET | `/admin/queue-monitor` | Failed jobs |
| POST | `/admin/cache/flush` | Flush all cache |
| GET | `/admin/feature-flags` | Feature flags |
| POST | `/admin/maintenance` | Set maintenance mode |

---

## Support

**Email:** aiclinforce@gmail.com  
**Platform:** https://aiclinforce.com  
**Admin Panel:** https://aiclinforce.com/admin

---

*This document covers the complete feature set of ClinForce AI as of April 2026. For technical integration details or API credentials, contact the development team.*
