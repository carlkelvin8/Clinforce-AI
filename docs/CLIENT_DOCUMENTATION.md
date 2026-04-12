# ClinForce AI — Complete Client Documentation

**Platform:** aiclinforce.com
**Version:** 1.0
**Prepared for:** Client
**Date:** April 2026
**Prepared by:** ClinForce AI Development Team

---

> This document is written in plain language so that any member of your team — technical or non-technical — can fully understand how the ClinForce AI platform works, what it does, and how to use it.

---

## Table of Contents

1. [Executive Summary — What is ClinForce AI?](#1-executive-summary)
2. [How to Access the Platform](#2-how-to-access-the-platform)
3. [User Roles Explained](#3-user-roles-explained)
4. [The Candidate Journey — Step by Step](#4-the-candidate-journey)
5. [The Employer Journey — Step by Step](#5-the-employer-journey)
6. [Admin Panel — Every Page Explained](#6-admin-panel)
7. [Billing & Subscriptions](#7-billing--subscriptions)
8. [Messaging System](#8-messaging-system)
9. [Notifications](#9-notifications)
10. [AI Features](#10-ai-features)
11. [Landing Page Features](#11-landing-page-features)
12. [Security Features](#12-security-features)
13. [Privacy & GDPR Compliance](#13-privacy--gdpr-compliance)
14. [Automated Background Tasks](#14-automated-background-tasks)
15. [Staging Test Accounts](#15-staging-test-accounts)
16. [Frequently Asked Questions](#16-frequently-asked-questions)
17. [Glossary of Terms](#17-glossary-of-terms)

---

## 1. Executive Summary

### What is ClinForce AI?

ClinForce AI is a **healthcare recruitment platform** that connects hospitals, clinics, and staffing agencies with qualified clinical professionals (nurses, doctors, allied health workers, and other healthcare staff).

Think of it as a specialized job board — but much more powerful. Instead of just listing jobs and collecting CVs, ClinForce AI automates the entire hiring process from start to finish:

- Employers post jobs and the platform automatically screens applicants using AI
- Candidates get a personalized dashboard showing how well they match each job
- Interviews are scheduled with automatic Zoom links and calendar invites
- Offer letters are generated with one click after a candidate is hired
- Everything is tracked, logged, and reportable

### Who is it for?

| User Type | Who They Are |
|-----------|-------------|
| **Employers** | Hospitals, clinics, healthcare companies hiring clinical staff |
| **Agencies** | Staffing agencies placing healthcare workers |
| **Candidates** | Nurses, doctors, allied health professionals looking for work |
| **Admins** | Your team managing the platform |

### What problems does it solve?

**For Employers:**
- No more manually sorting through hundreds of CVs — AI ranks candidates automatically
- No more back-and-forth emails to schedule interviews — the platform handles it
- No more losing track of where each candidate is in the process — the Kanban board shows everything at a glance

**For Candidates:**
- No more applying blindly — they see a match score before applying
- No more wondering what happened to their application — a visual timeline shows every status change
- No more missing interview invitations — they get email + in-app notifications

### Platform URL

| Environment | URL |
|-------------|-----|
| Live Platform | https://aiclinforce.com |
| Admin Panel | https://aiclinforce.com/admin |

---

## 2. How to Access the Platform

### For New Users (Registration)

1. Go to **https://aiclinforce.com**
2. Click **"Get Started"** or **"Register"** in the navigation
3. Choose your account type: **Employer** or **Clinician (Candidate)**
4. Fill in your name, email address, and a strong password
5. Your account is immediately active — no waiting for an email verification link

**Alternative: Sign in with Google**
- Click the **"Continue with Google"** button on the login or register page
- Authorize ClinForce AI to access your Google account
- You are logged in instantly — no password needed

### For Existing Users (Login)

1. Go to **https://aiclinforce.com**
2. Click **"Login"** in the top navigation
3. Enter your email and password
4. Click **"Sign In"**

### Password Requirements

Passwords must be at least 8 characters and include:
- At least one uppercase letter (e.g. A, B, C)
- At least one number (e.g. 1, 2, 3)
- At least one special character (e.g. !, @, #)

**Example of a valid password:** `Hospital@2026`

### Forgot Password

1. Click **"Forgot Password"** on the login page
2. Enter your email address
3. Check your inbox for a reset link
4. Click the link and enter a new password

### Two-Factor Authentication (2FA)

For extra security, users can enable 2FA:
1. Go to **Settings → Security**
2. Click **"Enable Two-Factor Authentication"**
3. Scan the QR code with Google Authenticator or Authy (free apps)
4. Enter the 6-digit code to confirm
5. From now on, every login requires both your password and the 6-digit code from the app

---

## 3. User Roles Explained

The platform has four types of accounts. Each type sees a different version of the platform.

### Admin
- **Who:** Your internal team managing the platform
- **What they can do:** Everything — see all users, all jobs, all subscriptions, all analytics, manage plans, toggle features, view system health
- **How to access:** Login at https://aiclinforce.com/admin

### Employer
- **Who:** A hospital, clinic, or healthcare company
- **What they can do:** Post jobs, manage applications, schedule interviews, subscribe to a plan, generate offer letters, message candidates
- **Dashboard:** Shows their jobs, candidates, interviews, and analytics

### Agency
- **Who:** A staffing agency placing healthcare workers
- **What they can do:** Same as Employer — post jobs, manage candidates, subscribe
- **Note:** Agencies can represent multiple clients and manage placements across different facilities

### Candidate (Clinician / Applicant)
- **Who:** A nurse, doctor, or other healthcare professional looking for work
- **What they can do:** Build a profile, browse jobs, apply, track applications, schedule interviews, receive offers
- **Dashboard:** Shows their applications, upcoming interviews, and job recommendations

---

## 4. The Candidate Journey

This section walks through everything a candidate experiences from the moment they register to the moment they are hired.

### Step 1: Register

1. Go to https://aiclinforce.com
2. Click **"I'm a Clinician"** on the hero section, or click **"Register"**
3. Select **"Candidate / Clinician"** as the account type
4. Enter name, email, password
5. Account is created and the candidate is taken to their dashboard

### Step 2: Build a Profile

A complete profile is essential — it determines how well the candidate matches jobs and how attractive they appear to employers.

**Profile fields:**
- **First name and last name** — Note: employers only see the first name and last initial (e.g. "Maria S.") for privacy
- **Headline** — A short professional title (e.g. "Registered Nurse · ICU Specialist")
- **Summary** — A paragraph about their background and goals
- **Years of experience** — Number of years in healthcare
- **Location** — City, state, country
- **Profile photo** — Upload a photo with a built-in crop tool
- **Portfolio links** — Links to LinkedIn, personal website, etc.

**Resume Upload:**
- Upload a PDF or Word document resume
- Multiple resumes can be stored (e.g. one for nursing, one for management roles)
- One resume is marked as "active" and auto-attached to applications
- Employers must pay a one-time document access fee to download the resume

**Open to Work Toggle:**
- Candidates can turn on an "Open to Work" badge on their profile
- This signals to employers in the talent search that they are actively looking
- Can be turned off at any time (e.g. once they accept a job)

**Profile Completeness:**
- The dashboard shows a percentage (e.g. "Profile 70% complete")
- Specific tips are shown: "Add a headline to get 3x more views", "Upload your resume to unlock applications"
- Completing the profile increases visibility in employer talent searches

### Step 3: Browse Jobs

1. Click **"Browse Jobs"** in the navigation
2. The job listings page shows all published jobs
3. Each job card shows:
   - Job title and employer name
   - Location and work mode (on-site / remote / hybrid)
   - Employment type (full-time / part-time / contract)
   - Salary range
   - **Match score** — a percentage showing how well the candidate matches this job (e.g. "82% match")
   - Date posted

**Filtering Jobs:**
- Filter by location (country, city)
- Filter by employment type
- Filter by work mode
- Filter by salary range

**Saving Jobs:**
- Click the bookmark icon on any job to save it
- Saved jobs appear in the **"Saved Jobs"** section of the dashboard

**Job Alerts:**
- Set up keyword and location alerts
- When a new job matching the alert is posted, the candidate receives an email notification
- Manage alerts in **Settings → Job Alerts**

### Step 4: Apply for a Job

1. Click on a job to view the full description
2. Click **"Apply Now"**
3. Write a cover letter — or choose from 3 pre-written templates:
   - General Application
   - Experienced Professional
   - Career Change
4. The active resume is automatically attached
5. Click **"Submit Application"**

The employer is notified immediately of the new application.

### Step 5: Track the Application

After applying, the candidate can track every application from their dashboard.

**Application Status Timeline:**
Each application shows a visual progress bar with 4 stages:

```
Applied → Reviewed → Interview → Decision
```

- Each stage is highlighted as the employer moves the application forward
- The date and time of each status change is recorded
- If the employer adds a note when changing the status, the candidate can see it

**Withdrawal:**
- If the candidate no longer wants to be considered, they can withdraw the application
- They must provide a reason (e.g. "Accepted another offer", "No longer interested in this role")
- The reason is sent to the employer

### Step 6: Interview

When an employer schedules an interview:

1. The candidate receives an **email notification** and an **in-app notification**
2. The notification includes: date, time, interview type (video/phone/in-person), and a Zoom link (if video)
3. The candidate must **confirm or decline** the interview from their dashboard
4. A **.ics calendar file** can be downloaded to add the interview to Google Calendar, Outlook, or Apple Calendar
5. **Reminder emails** are sent 24 hours and 1 hour before the interview

**After the Interview:**
- The employer can leave feedback (rating, recommendation, notes)
- The candidate can view this feedback from their **Applications** page
- This helps candidates understand how they performed and improve for future interviews

### Step 7: Receive an Offer

If the employer marks the candidate as "Hired":
1. The employer generates an offer letter with one click
2. The offer letter includes: job title, start date, salary, and any additional notes
3. The candidate is notified

### Candidate Dashboard Summary

The dashboard is the candidate's home base. It shows:

| Section | What it shows |
|---------|--------------|
| Stats bar | Active applications, upcoming interviews, profile completeness, AI match score |
| Next steps | Smart action cards (e.g. "2 interviews awaiting confirmation") |
| Profile tips | Specific suggestions to improve the profile |
| Application pipeline | Visual chart of applications by status |
| Recommended jobs | Jobs that match the candidate's profile |
| Recent activity | Latest status changes and notifications |

### Candidate Settings

From **Settings**, candidates can:
- Update email, phone, password
- Change profile photo
- Set notification preferences (which types of emails to receive and how often)
- View and revoke active login sessions (e.g. remove access from an old device)
- Download all their data (GDPR export)
- Schedule account deletion (30-day grace period)

---

## 5. The Employer Journey

This section walks through everything an employer experiences from registration to hiring a candidate.

### Step 1: Register

1. Go to https://aiclinforce.com
2. Click **"Start Hiring"** or **"Register"**
3. Select **"Employer"** or **"Agency"** as the account type
4. Enter company name, email, password
5. Account is created and the employer is taken to their dashboard

### Step 2: Complete Company Profile

Before posting jobs, employers should complete their company profile:
- **Business name** — the name shown to candidates
- **Business type** — hospital, clinic, agency, etc.
- **Website** — company website URL
- **Address** — city, state, country
- **Tax ID** — for billing purposes
- **Billing currency** — automatically set based on country (PHP for Philippines, USD for international)

**Verification:**
- Employers can submit a verification request to get a "Verified" badge on their profile
- This increases candidate trust
- Admin reviews and approves/rejects verification requests

### Step 3: Subscribe to a Plan

Most employer features require an active subscription.

**To subscribe:**
1. Go to **Billing** in the navigation
2. The platform detects your country and shows prices in the correct currency
3. Choose a plan (Starter, Growth, or Pro)
4. Enter payment details (credit/debit card via Stripe — secure, no card details stored on our servers)
5. Subscription is activated immediately
6. A confirmation email and invoice are sent

**What requires a subscription:**
- Messaging candidates
- Scheduling interviews
- Accessing the hiring pipeline
- Generating offer letters
- Advanced analytics

**What is free:**
- Posting jobs (up to the plan limit)
- Viewing applications
- Basic candidate profiles

### Step 4: Post a Job

1. Go to **Jobs → Create New Job**
2. Fill in the job details:

| Field | Description |
|-------|-------------|
| Job Title | e.g. "Registered Nurse — ICU" |
| Description | Full job description with responsibilities and requirements |
| Employment Type | Full-time, Part-time, Contract, Temporary |
| Work Mode | On-site, Remote, Hybrid |
| Location | City, State, Country |
| Salary Range | Minimum and maximum salary |
| Application Deadline | The closing date for applications |

3. **Template Auto-fill:** If the employer has posted similar jobs before, they can select a previous job as a template to pre-fill the form
4. **Duplicate Detection:** If the title is very similar to an existing active job, a warning is shown
5. Click **"Publish"** — the job is immediately visible to candidates

### Step 5: Manage Applications

When candidates apply, they appear in the **Applications** section.

**Candidate List View:**
- See all applicants for a job in a table
- Each row shows: name (last name hidden), headline, status, rating, applied date
- Filter by status (Applied, Reviewed, Shortlisted, Interview, Hired, Rejected)
- Search by name

**Actions per candidate:**
- View full application (cover letter, resume, status history)
- Change status (move to next stage)
- Add internal notes (only visible to your team)
- Rate the candidate (1–5 stars)
- Schedule an interview
- Message the candidate
- Add to blacklist (do not contact)
- Reject with a reason (sends a templated email to the candidate)

**Bulk Actions:**
- Select multiple candidates
- Move all to a new status at once
- Bulk reject with a message
- Export selected candidates to CSV

**Kanban Board (Pipeline View):**
- Visual drag-and-drop board with columns: Applied, Reviewed, Shortlisted, Interview, Hired, Rejected
- Drag a candidate card from one column to another to update their status
- Bulk select mode: select multiple cards and move them all at once
- Filter the board by job

### Step 6: AI Screening

For any application, the employer can trigger an AI screening:
1. Open the candidate's application
2. Click **"Run AI Screening"**
3. The AI analyzes the candidate's profile, resume, and cover letter against the job requirements
4. Within seconds, a result appears showing:
   - **Match score** (0–100%)
   - **Recommendation** (Strong Match / Possible Match / Not Recommended)
   - **Detailed summary** explaining the reasoning

This saves hours of manual CV review.

### Step 7: Schedule an Interview

1. Open the candidate's application
2. Click **"Schedule Interview"**
3. Fill in:
   - Interview date and time
   - Interview type (Video / Phone / In-person)
   - Duration
   - Notes for the candidate
4. If Video is selected, a **Zoom meeting link is automatically generated**
5. The candidate receives an email and in-app notification
6. Both parties can download a **.ics calendar file**

**After the Interview:**
- Mark the interview as completed
- Leave feedback: rating (1–5), recommendation (hire / maybe / no), and notes
- If the candidate didn't show up, mark as **"No Show"** — a badge appears on their card

### Step 8: Hire a Candidate

1. Move the candidate to **"Hired"** status
2. Click **"Generate Offer Letter"**
3. Fill in: start date, salary, and any additional notes
4. The offer letter is generated as a printable document
5. The candidate is notified

### Step 9: Analytics & Reporting

**Job Analytics (per job):**
- Total views (how many candidates viewed the job)
- Total applications
- Conversion rate (views → applications)
- Daily trend chart
- Breakdown by status

**Pipeline Report:**
- Download a CSV with per-job funnel stats: applied / shortlisted / interviewed / hired / rejected counts

**Analytics Dashboard:**
- Application activity bar chart (new / in-process / hired)
- Pipeline donut chart
- KPIs: open roles, total candidates, upcoming interviews, new applications
- Filter by date range: 7 / 30 / 60 / 90 days

### Employer Dashboard Summary

| Section | What it shows |
|---------|--------------|
| KPI cards | Open roles, total candidates, upcoming interviews, new applications |
| Activity chart | Applications over time |
| Pipeline chart | Candidates by stage |
| Recent applications | Latest applicants across all jobs |
| Upcoming interviews | Next scheduled interviews |

### Employer Settings

From **Settings**, employers can:
- Update company profile
- Set billing region and currency
- Configure data retention (how long to keep rejected applications)
- Set notification preferences
- Enable 2FA
- View and revoke active sessions

---

## 6. Admin Panel

The admin panel is the control center for managing the entire platform. It is only accessible to users with the **Admin** role.

**URL:** https://aiclinforce.com/admin

### How to Access

1. Log in with an admin account at https://aiclinforce.com
2. You are automatically redirected to the admin panel
3. Or navigate directly to https://aiclinforce.com/admin

### Admin Dashboard

The first page you see when entering the admin panel. It gives a complete overview of the platform at a glance.

**Stats cards (top row):**
- Total Users
- Total Employers
- Total Candidates
- Active Jobs
- Total Applications
- Active Subscriptions
- Total Revenue (this month)

**Charts:**
- **User Growth** — a line chart showing new registrations over the past 6 months
- **Role Breakdown** — a donut chart showing the split between admins, employers, and candidates

**Recent Registrations:**
- A table showing the last 10 users who registered, with their email, role, and registration date

**Quick Actions:**
- Links to common tasks: View Users, View Jobs, View Subscriptions, System Status

---

### Users Page

This page lists every registered user on the platform.

**What you can see:**
- User ID, email, phone number, role (admin/employer/candidate), status (active/suspended/banned), registration date

**Search and Filter:**
- Search by email address, phone number, or user ID
- Filter by role (admin, employer, agency, applicant)
- Filter by status (active, suspended, banned)

**Actions per user:**
- **View details** — opens a side panel with 3 tabs:
  - *Overview:* their subscriptions, jobs posted, applications submitted
  - *Notes:* internal admin notes about this user (not visible to the user)
  - *Login History:* every login with IP address, country, city, device, and timestamp
- **Suspend** — temporarily disables the account (user cannot log in)
- **Ban** — permanently disables the account
- **Activate** — re-enables a suspended or banned account
- **Reset Password** — sends a password reset email to the user
- **Email** — opens a compose window to send a direct email
- **Impersonate** — logs in as this user to see exactly what they see (an amber banner is shown at the top of the screen while impersonating)
- **Grant Subscription** — manually give the user an active subscription without payment

**Bulk Actions:**
- Select multiple users with checkboxes
- Bulk activate, suspend, or ban
- Export selected users to CSV

---

### Jobs Page

Lists every job posted on the platform across all employers.

**What you can see:**
- Job title, employer name, status (draft/published/archived), number of applications, posted date, closing date

**Search and Filter:**
- Search by job title
- Filter by status

**Actions:**
- Update job status (publish, archive, delete)
- View the full job details

---

### Subscriptions Page

Lists every subscription on the platform.

**What you can see:**
- User email, plan name, status (active/expired/cancelled), amount paid, start date, end date

**Search:**
- Search by user email

**Actions:**
- View subscription details
- Manually expire or cancel a subscription

---

### Plans Page

Manage the subscription plans available on the platform.

**What you can see:**
- Plan name, duration, price (PHP and USD), status (active/inactive), features

**Actions:**
- **Edit a plan** — update the name, price, duration, job post limit, and features
- **Toggle active/inactive** — hide a plan from the public pricing page without deleting it

**Current Plans:**

Philippines (PHP):
| Plan | Duration | Price |
|------|----------|-------|
| PH Starter | 3 months | ₱2,999.99 |
| PH Growth | 6 months | ₱4,999.99 |
| PH Pro | 1 year | ₱7,999.99 |

International (USD):
| Plan | Duration | Price |
|------|----------|-------|
| International Starter | 3 months | $49.99 |
| International Growth | 6 months | $79.99 |
| International Pro | 1 year | $129.99 |
| 7-Day Trial | 7 days | Free |

---

### Verifications Page

When employers or agencies want to get a "Verified" badge on their profile, they submit a verification request. This page is where admins review those requests.

**What you can see:**
- Employer name, business type, submission date, status (pending/approved/rejected)

**Actions:**
- **Approve** — grants the verified badge to the employer's profile
- **Reject** — rejects the request with a note explaining why

---

### Contacts Page

Every message submitted through the contact form on the landing page appears here.

**What you can see:**
- Sender name, email, subject, message, submission date

**Actions:**
- View the full message
- Reply via email

---

### AI Screenings Page

A log of every AI screening that has been run on the platform.

**What you can see:**
- Candidate name, job title, employer, match score, recommendation, date

**Actions:**
- View the full AI screening result including the detailed summary

---

### Audit Logs Page

A complete record of every significant action taken on the platform. This is useful for accountability and troubleshooting.

**What you can see:**
- Action type (e.g. "user.suspended", "job.published", "subscription.created")
- Who performed the action (user email)
- When it happened (date and time)
- What changed (metadata — e.g. old status vs new status)

**Filter:**
- Filter by action type
- Filter by user
- Filter by date range

**Actions:**
- Click any log entry to see the full metadata in a dialog

---

### System Status Page

A real-time health check of the entire platform. This is the first place to look if something seems wrong.

**Sections:**

**Health Indicators:**
- Database connection: Connected / Disconnected
- Storage usage: how much disk space is being used
- Failed jobs: number of background tasks that failed
- PHP version and Laravel version
- Environment: production or local

**Recent Error Logs:**
- The last 50 WARNING, ERROR, and CRITICAL messages from the server logs
- Useful for diagnosing issues without needing server access

**Queue Monitor:**
- Lists all failed background jobs (e.g. an email that failed to send)
- Each failed job shows: job name, error message, failed at timestamp
- **Retry button** — re-queues the failed job to try again

**Webhook Logs:**
- History of all incoming webhooks from Stripe (payment events) and Zoom (meeting events)
- Shows: event type, status (success/failed), timestamp, payload

**Database Table Sizes:**
- Lists every database table with its row count and size in MB
- Useful for monitoring data growth

**Cache Flush:**
- A button to clear all cached data
- Use this if you notice stale data being displayed (e.g. old analytics numbers)

**Maintenance Mode:**
- Toggle the platform into maintenance mode
- While in maintenance mode, visitors see a custom message instead of the platform
- Useful during deployments or emergency fixes

**Announcement Banner:**
- Set a site-wide announcement that appears at the top of every page
- Example: "We are performing scheduled maintenance on Saturday 10pm–12am"

---

### Feature Flags Page

Feature flags allow you to turn specific platform features on or off without any code changes or deployments.

**Example use cases:**
- Turn off AI screening temporarily if the OpenAI API is having issues
- Disable new registrations during a maintenance window
- Enable a beta feature for testing before rolling it out to everyone

Each flag has a name, description, and an on/off toggle.

---

### Analytics Page

Detailed charts and reports about platform performance.

**Charts available:**
- **Revenue over time** — line chart showing daily/weekly/monthly revenue
- **User registrations over time** — line chart showing new signups
- **Revenue by plan** — donut chart showing which plans generate the most revenue
- **User role distribution** — donut chart showing the split between employers and candidates
- **Subscription status breakdown** — bar chart showing active vs expired vs cancelled subscriptions
- **Top employers by job count** — table showing the most active employers

**Period filter:**
- Weekly, Monthly, or Yearly view

**Export buttons:**
- Export users list as CSV
- Export revenue report as CSV
- Export subscriptions list as CSV

---

### Global Search

From any page in the admin panel, press **Cmd+K** (Mac) or **Ctrl+K** (Windows) to open the global search.

Search across:
- Users (by email or name)
- Jobs (by title)
- Subscriptions (by user email)

Results appear instantly as you type.

---

## 7. Billing & Subscriptions

### Overview

ClinForce AI uses a subscription model for employers. Candidates use the platform for free. Employers pay a recurring subscription to access the full hiring workflow.

Payments are processed securely through **Stripe** — one of the world's most trusted payment processors. ClinForce AI never stores card numbers on its servers.

### Currency Detection

When an employer visits the billing page, the platform automatically detects their country and shows prices in the appropriate currency:
- **Philippines** → prices shown in Philippine Peso (₱ PHP)
- **All other countries** → prices shown in US Dollars ($ USD)

Employers can also manually change their billing currency in Settings.

### Subscription Plans

**Philippines Plans (PHP):**

| Plan | Duration | Price | Job Posts | AI Screening | Analytics | Priority Support |
|------|----------|-------|-----------|--------------|-----------|-----------------|
| PH Starter | 3 months | ₱2,999.99 | Up to 20 | ✓ | Basic | — |
| PH Growth | 6 months | ₱4,999.99 | Up to 50 | ✓ | Advanced | — |
| PH Pro | 1 year | ₱7,999.99 | Unlimited | ✓ | Advanced | ✓ |

**International Plans (USD):**

| Plan | Duration | Price | Job Posts | AI Screening | Analytics | Priority Support |
|------|----------|-------|-----------|--------------|-----------|-----------------|
| International Starter | 3 months | $49.99 | Up to 20 | ✓ | Basic | — |
| International Growth | 6 months | $79.99 | Up to 50 | ✓ | Advanced | — |
| International Pro | 1 year | $129.99 | Unlimited | ✓ | Advanced | ✓ |
| 7-Day Trial | 7 days | Free | Up to 5 | ✓ | Basic | — |

### How to Subscribe

1. Log in as an employer
2. Click **"Billing"** in the navigation
3. The platform shows your detected currency and available plans
4. Click **"Subscribe"** on your chosen plan
5. Enter your card details in the secure Stripe form
6. Click **"Pay"**
7. Your subscription is activated immediately
8. A confirmation email is sent with your invoice attached

### Invoices

Every payment generates an invoice. To view and download invoices:
1. Go to **Billing → Invoices**
2. All past invoices are listed with: plan name, billing period, amount, status (paid/unpaid)
3. Click **"Download"** on any invoice
4. The invoice opens as a printable page — use your browser's print function to save as PDF

**Invoice contents:**
- Invoice number
- Billing date
- Plan name and duration
- Amount paid
- Payment status
- Employer business name and address

### Renewal Reminders

The platform automatically sends reminder emails before a subscription expires:
- **7 days before expiry** — "Your subscription expires in 7 days"
- **1 day before expiry** — "Your subscription expires tomorrow"

These emails include a direct link to renew.

### What Happens When a Subscription Expires

- The employer can no longer message candidates
- The employer can no longer schedule new interviews
- Existing jobs remain visible but new job posts are blocked
- The employer can still view their existing applications
- A banner is shown in the dashboard prompting renewal

### Document Access (Separate from Subscription)

Viewing a candidate's resume and contact details requires a separate one-time payment per candidate. This is independent of the subscription.

**What document access unlocks:**
- Resume download (PDF)
- All uploaded documents
- Full contact details (email, phone number)

**How to purchase:**
1. Open a candidate's application
2. Click **"Unlock Documents"**
3. Complete the one-time payment
4. Documents are immediately accessible

---

## 8. Messaging System

### Overview

The messaging system allows employers and candidates to communicate directly within the platform — no need to share personal email addresses until both parties are ready.

### How Messaging Works

**For Employers:**
- Message any candidate who has applied to one of your jobs
- Message candidates you have invited from the talent search
- Go to **Messages** in the navigation to see all conversations
- Click **"New Message"** or open a conversation from a candidate's application page

**For Candidates:**
- Receive messages from employers
- Reply directly from the **Messages** section
- Cannot initiate a conversation with an employer (employers must message first)

### Conversation View

- Messages are displayed in a chat-style interface (newest at the bottom)
- Conversations load the most recent 30 messages
- Click **"Load older messages"** to see earlier messages
- Unread message count is shown as a badge in the navigation bar

### Notifications

When a new message arrives:
- An in-app notification badge appears on the Messages icon
- An email notification is sent (based on the user's notification preferences)

### Privacy

- Candidate last names are hidden in the messaging interface (first name and last initial only)
- Candidate email and phone are not shown in messages — they must be unlocked via document access

---

## 9. Notifications

### Overview

ClinForce AI keeps all users informed through two channels: **in-app notifications** (shown inside the platform) and **email notifications** (sent to the user's email address).

### In-App Notifications

These appear as a bell icon in the navigation bar. Clicking it shows a dropdown list of recent notifications.

**Employers receive in-app notifications for:**
- New application received
- Candidate confirmed or declined an interview
- New message received

**Candidates receive in-app notifications for:**
- Application status changed (e.g. moved to "Shortlisted")
- Interview scheduled
- New message received
- Job alert match found

### Email Notifications

**Employers receive emails for:**
- New application received
- Candidate confirmed or declined an interview

**Candidates receive emails for:**
- Application status updated
- Interview scheduled
- Interview reminder (24 hours before)
- Interview reminder (1 hour before)
- Job alert — new matching jobs found
- Subscription confirmation (after subscribing)
- Invoice issued
- Subscription renewal reminder (7 days before expiry)
- Subscription renewal reminder (1 day before expiry)
- Account deletion confirmation (when deletion is scheduled)

**Admins receive emails for:**
- Suspicious login detected (a user logged in from a new country)

### Notification Preferences

Users can customize which notifications they receive and how often.

**To manage preferences:**
1. Go to **Settings → Notifications**
2. Set the **frequency**:
   - **Immediate** — receive an email as soon as the event happens
   - **Daily digest** — receive one email per day summarizing all notifications
   - **Weekly summary** — receive one email per week
3. Toggle individual notification types on or off:
   - Status change notifications
   - Interview notifications
   - Message notifications
   - Job alert notifications
   - Invitation notifications

---

## 10. AI Features

### Overview

ClinForce AI uses artificial intelligence in three ways: candidate screening, job matching, and a help chatbot.

### AI Candidate Screening

**What it does:**
When an employer runs an AI screening on a candidate, the AI reads the candidate's profile, resume, and cover letter, then compares them against the job requirements. It produces a detailed report in seconds.

**What the report includes:**
- **Match score** — a number from 0 to 100 representing how well the candidate fits the job
- **Recommendation** — one of three verdicts:
  - *Strong Match* — the candidate is a very good fit
  - *Possible Match* — the candidate has some relevant experience but may not meet all requirements
  - *Not Recommended* — the candidate does not appear to be a good fit for this role
- **Detailed summary** — a paragraph explaining the reasoning, highlighting strengths and gaps

**How to use it:**
1. Open a candidate's application
2. Click **"Run AI Screening"**
3. Wait a few seconds
4. The result appears on the page

**Important note:** AI screening is a tool to assist decision-making, not replace it. Always review the candidate's full profile before making a final decision.

### Job Match Score (for Candidates)

**What it does:**
When a candidate browses the job listings, each job shows a match score — a percentage indicating how well the candidate's profile matches that job.

**How it is calculated:**
- Keyword overlap between the candidate's headline and the job title/description
- Location match (same city = higher score, same country = moderate score)
- Experience level alignment

**Color coding:**
- **Green** (80% and above) — excellent match
- **Blue** (60–79%) — good match
- **Grey** (below 60%) — partial match

This helps candidates prioritize which jobs to apply for.

### AI Chatbot (for Employers)

**What it does:**
A chat assistant available to employers in their dashboard. It can answer questions about:
- How to use the platform
- Hiring best practices
- Candidate management tips
- General healthcare recruitment advice

**How to use it:**
1. Click the chat bubble icon in the employer dashboard
2. Type your question
3. The AI responds instantly

---

## 11. Landing Page Features

The landing page at https://aiclinforce.com is the public face of the platform. It is designed to convert visitors into registered users.

### Sections (top to bottom)

**Announcement Bar**
A dismissable banner at the very top of the page. Can be used for promotions, maintenance notices, or new feature announcements. Managed from the Admin Panel → System Status → Announcement Banner.

**Navigation**
- Links: Features, How it Works, Pricing, Testimonials, FAQ, Contact
- Dark mode toggle (sun/moon icon)
- Login and Register buttons

**Hero Section**
The main headline and call-to-action. Features two distinct paths:
- **For Employers:** "Start Hiring Now" card with employer-specific value proposition
- **For Clinicians:** "Find Your Next Role" card with candidate-specific value proposition
- "No credit card required" copy to reduce friction

**Stats Counter**
Animated numbers that count up when scrolled into view:
- 5,000+ Clinicians
- 200+ Employers
- 48h Average Time to Hire
- 1,200+ Interviews Conducted

**Live Job Ticker**
A scrolling strip showing recently posted jobs pulled from the platform's public API. This proves the platform is active and has real job listings. Falls back to placeholder jobs if the API is unavailable.

**Trusted By**
Logos of healthcare organizations that use the platform. Builds credibility and trust.

**Banner Section**
Two large image cards highlighting the platform's two core strengths:
- Clinical Excellence
- Professional Staffing

**Features Section**
Three feature cards with hover animations:
- AI-Powered Screening
- Smart Matching
- Seamless Hiring

**How It Works**
A 3-step visual process:
1. Post a job
2. AI screens candidates
3. Hire the best fit

**For Employers / For Clinicians**
A split section showing the value proposition for each user type side by side.

**Testimonials**
Three testimonial cards from healthcare professionals and employers, with avatar photos.

**Pricing Section**
A full pricing table with a PHP/USD toggle. Shows all plans with features listed. The most popular plan is highlighted with a badge.

**FAQ Section**
Six accordion-style questions and answers covering common concerns:
- Is it free for candidates?
- How does AI screening work?
- Can I cancel my subscription?
- Is my data secure?
- How do I post a job?
- What types of healthcare roles can I post?

**CTA Banner**
A gradient call-to-action section with:
- Security badges (SSL, data encryption)
- "No credit card required" copy
- Register button

**Contact Form**
A form for visitors to send a message. Fields: name, email, subject, message. Submissions are stored in the database and an email is sent to the admin.

**Footer**
- Navigation links
- Social media icons
- Security badges
- Privacy Policy and Terms of Service links

**Sticky CTA Bar**
After scrolling 600px down the page, a slim bar slides up from the bottom of the screen with a "Start Hiring Free →" button. This keeps the conversion path visible at all times without being intrusive.

---

## 12. Security Features

This section explains the security measures in place to protect the platform and its users, written in plain language.

### Password Security

All passwords are stored using **bcrypt hashing** — this means the actual password is never stored anywhere. Even if someone gained access to the database, they would only see a scrambled version of the password that cannot be reversed.

Password requirements enforce a minimum level of security: at least 8 characters, one uppercase letter, one number, and one special character.

### Login Rate Limiting

If someone tries to guess a password, the system blocks them after **5 failed attempts per minute** from the same IP address. This prevents automated brute-force attacks.

### Two-Factor Authentication (2FA)

Users can enable 2FA for an extra layer of security. Even if someone steals a password, they cannot log in without also having access to the user's phone (where the 6-digit code is generated).

### Suspicious Login Alerts

Every login is recorded with the IP address, country, city, and device. If a user logs in from a country they have never logged in from before, an alert email is sent to the admin. This helps detect account takeovers early.

### Session Management

Users can see a list of all devices currently logged into their account. If they see a device they don't recognize, they can revoke that session with one click — immediately logging out that device.

### Admin Impersonation

Admins can log in as any user to troubleshoot issues. When impersonating, a clearly visible **amber banner** is shown at the top of every page saying "You are impersonating [user email]". This ensures impersonation is always transparent and auditable. All impersonation actions are logged in the audit trail.

### HTTPS Encryption

All data transmitted between the user's browser and the server is encrypted using HTTPS (SSL/TLS). This means no one can intercept login credentials, messages, or personal data in transit.

### CORS Protection

The API only accepts requests from the official domain (aiclinforce.com). This prevents other websites from making unauthorized requests to the API on behalf of users.

### Token-Based Authentication

After logging in, users receive a secure token that expires after 7 days. This token is required for all API requests. If a token is stolen, it can be revoked from the sessions management page.

### Audit Trail

Every significant action on the platform is logged: who did it, what they did, when, and what changed. This provides a complete accountability trail for compliance and troubleshooting.

---

## 13. Privacy & GDPR Compliance

### Overview

ClinForce AI is designed with privacy in mind. The platform includes several features specifically to comply with data protection regulations including GDPR (General Data Protection Regulation).

### Candidate Privacy Protections

**Last name masking:**
Across all employer-facing pages, candidate last names are hidden. Employers see only the first name and last initial (e.g. "Maria S." instead of "Maria Santos"). This protects candidate identity until the employer has a legitimate reason to know their full name.

**Contact details hidden:**
A candidate's email address and phone number are not visible to employers by default. Employers must purchase document access to see contact details. This prevents unsolicited contact and protects candidate privacy.

**Interview page privacy:**
In the interview scheduling modal, candidate contact information is not displayed. Employers communicate through the platform's messaging system.

### GDPR Rights

**Right to Access (Data Export):**
Candidates can download a complete copy of all their data stored on the platform:
1. Go to **Settings → Privacy**
2. Click **"Download My Data"**
3. A JSON file is generated containing: profile information, all applications, all messages, all documents, notification history
4. The file downloads immediately

**Right to Erasure (Account Deletion):**
Candidates can request deletion of their account and all associated data:
1. Go to **Settings → Privacy**
2. Click **"Delete My Account"**
3. A confirmation email is sent
4. A **30-day grace period** begins — the account is deactivated but not yet deleted
5. During the grace period, the user can cancel the deletion by logging in and clicking "Cancel Deletion"
6. After 30 days, the account and all data are permanently deleted

**Data Retention Policy:**
Employers can set how long rejected application data is retained:
1. Go to **Settings → Data Retention**
2. Set the number of days (default: 90 days)
3. Rejected applications older than this limit are automatically deleted by a daily background task

### Data Storage

- All data is stored on servers in a secure hosting environment
- Database backups are performed regularly
- No personal data is shared with third parties except as required for payment processing (Stripe) and video interviews (Zoom)

### Third-Party Services

| Service | Purpose | Data Shared |
|---------|---------|-------------|
| Stripe | Payment processing | Billing name, email, card details (Stripe handles card data directly — we never see card numbers) |
| Zoom | Video interviews | Meeting host details, meeting time |
| OpenAI | AI screening | Candidate profile text, job description text |
| Google OAuth | Login | Email address, name |
| Gmail SMTP | Email delivery | Recipient email, email content |

---

## 14. Automated Background Tasks

The platform runs several automated tasks in the background without any manual intervention. These run on a schedule managed by the server.

### How It Works

A cron job on the server runs every minute and checks if any scheduled tasks are due. This is standard practice for web applications.

### Task Schedule

| Task | When It Runs | What It Does |
|------|-------------|-------------|
| Expire subscriptions | Every hour | Checks all subscriptions and marks any that have passed their end date as "expired" |
| Sync exchange rates | Every 6 hours | Fetches the latest currency exchange rates so billing amounts are accurate |
| Clean up expired tokens | Once per day | Removes old login tokens from the database to keep it clean |
| Send interview reminders | Every 30 minutes | Checks for interviews happening in the next 24 hours or 1 hour and sends reminder emails |
| Delete old rejected applications | Daily at 2am | Removes rejected applications that are older than each employer's data retention setting |
| Archive expired jobs | Every hour | Automatically archives job posts that have passed their application deadline |
| Send job alert emails | Every 6 hours | Checks all job alerts and emails candidates about new matching jobs |
| Send renewal reminders | Daily at 9am | Emails employers whose subscriptions expire in 7 days or 1 day |
| Process email queue | Every minute | Sends any queued emails (ensures emails don't slow down the website) |

### Email Queue

All emails are sent through a background queue. This means:
- When an action triggers an email (e.g. a candidate applies), the email is added to a queue
- The website responds immediately without waiting for the email to send
- The email is sent within seconds by the background worker
- This keeps the platform fast and responsive

### What Happens If a Task Fails

If a background task fails (e.g. an email couldn't be sent because the mail server was temporarily unavailable):
- The failed task is logged in the **Failed Jobs** list
- Admins can see this in the Admin Panel → System Status → Queue Monitor
- Admins can retry the failed task with one click

---

## 15. Staging Test Accounts

The following test accounts are available on the staging/production environment for testing and demonstration purposes.

> **Important:** These accounts are for testing only. Do not use them for real hiring or real candidate applications.

### Test Account Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@aiclinforce.com | Admin@Staging2026! |
| Employer | employer@aiclinforce.com | Employer@Staging2026! |
| Candidate | candidate@aiclinforce.com | Candidate@Staging2026! |

### What Each Account Has

**Admin Account (admin@aiclinforce.com)**
- Full access to the admin panel at https://aiclinforce.com/admin
- Can manage all users, jobs, subscriptions, and system settings
- Can view all analytics and audit logs

**Employer Account (employer@aiclinforce.com)**
- Business name: ClinForce Demo Hospital
- Business type: Hospital
- Location: Manila, NCR, Philippines
- Currency: PHP
- Verification status: Verified
- Use this account to test: job posting, candidate management, interviews, billing, analytics

**Candidate Account (candidate@aiclinforce.com)**
- Name: Demo Candidate
- Headline: Registered Nurse · ICU Specialist
- Location: Quezon City, NCR, Philippines
- Open to work: Yes
- Use this account to test: job browsing, applying, tracking applications, interview responses

### How to Set Up Test Accounts on a New Server

Run the following command on the server:

```bash
php artisan db:seed --class=StagingSeeder
```

This creates all three accounts (or updates them if they already exist).

---

## 16. Frequently Asked Questions

### For Candidates

**Q: Is it free to use ClinForce AI as a candidate?**
A: Yes, completely free. Candidates can register, build a profile, browse jobs, apply, track applications, and communicate with employers at no cost.

**Q: Why can't employers see my full name?**
A: For your privacy, employers only see your first name and last initial (e.g. "Maria S.") until they have a legitimate reason to know your full identity. Your contact details (email and phone) are also hidden until an employer purchases document access.

**Q: How does the job match score work?**
A: The match score is calculated based on how well your profile matches the job. It considers your headline keywords, your location, and your years of experience. A higher score means you are a stronger fit for that role.

**Q: Can I apply to multiple jobs at once?**
A: Yes. You can apply to as many jobs as you like. Each application is tracked separately in your dashboard.

**Q: What happens after I apply?**
A: You will see your application in the "Applied" stage on your dashboard. As the employer reviews your application and moves it through their process, you will receive notifications and the status timeline will update.

**Q: Can I withdraw an application?**
A: Yes. Open the application from your dashboard and click "Withdraw". You will be asked to provide a reason, which is shared with the employer.

**Q: How do I confirm or decline an interview?**
A: When an interview is scheduled, you will receive an email and an in-app notification. Open the notification or go to your Interviews page and click "Confirm" or "Decline".

**Q: Can I download my data?**
A: Yes. Go to Settings → Privacy → Download My Data. A JSON file containing all your data will be downloaded immediately.

**Q: How do I delete my account?**
A: Go to Settings → Privacy → Delete My Account. A 30-day grace period begins. You can cancel the deletion at any time during those 30 days by logging in.

---

### For Employers

**Q: Do I need a subscription to post jobs?**
A: You can post jobs without a subscription, but you need a subscription to access the full hiring workflow (messaging, interviews, offer letters, advanced analytics).

**Q: How does AI screening work?**
A: Open any candidate's application and click "Run AI Screening". The AI reads the candidate's profile, resume, and cover letter and compares them to your job requirements. It returns a match score, a recommendation, and a detailed explanation within seconds.

**Q: Can I try the platform before paying?**
A: Yes. There is a free 7-day trial plan available for international users. No credit card is required for the trial.

**Q: How do I generate an offer letter?**
A: Move the candidate to "Hired" status, then click "Generate Offer Letter". Fill in the start date, salary, and any notes. The offer letter is generated as a printable document.

**Q: What is document access?**
A: Document access is a one-time payment per candidate that unlocks their resume download, all uploaded documents, and their full contact details (email and phone). This is separate from the subscription.

**Q: Can I export my candidate data?**
A: Yes. From the candidates list, select the candidates you want to export and click "Export CSV". You can also download a pipeline report from the Analytics section.

**Q: What happens when my subscription expires?**
A: You will receive reminder emails 7 days and 1 day before expiry. After expiry, you can still view existing applications but cannot message candidates, schedule new interviews, or post new jobs. Renew from the Billing page.

**Q: How do I get my company verified?**
A: Go to Settings → Company Profile → Request Verification. Submit your request and the admin team will review it. Approved companies get a "Verified" badge on their profile, which increases candidate trust.

---

### General

**Q: Is my data secure?**
A: Yes. All data is transmitted over HTTPS (encrypted). Passwords are never stored in plain text. Payments are processed by Stripe — we never see or store card numbers. The platform includes rate limiting, 2FA, session management, and a full audit trail.

**Q: What browsers are supported?**
A: ClinForce AI works on all modern browsers: Chrome, Firefox, Safari, Edge. It is also mobile-responsive and works on smartphones and tablets.

**Q: How do I contact support?**
A: Email us at aiclinforce@gmail.com or use the contact form at https://aiclinforce.com/#contact.

**Q: Is there a mobile app?**
A: Not currently. The platform is fully mobile-responsive and works well in mobile browsers.

---

## 17. Glossary of Terms

| Term | Definition |
|------|-----------|
| **AI Screening** | An automated analysis of a candidate's profile and resume against a job's requirements, producing a match score and recommendation |
| **Application Deadline** | The closing date for a job post. After this date, the job is automatically archived and no new applications are accepted |
| **Audit Log** | A complete record of every significant action taken on the platform, including who did it and when |
| **Blacklist** | An employer's private list of candidates marked as "do not contact" across all their jobs |
| **Bulk Action** | Performing the same action on multiple items at once (e.g. rejecting 10 candidates simultaneously) |
| **Candidate** | A healthcare professional (nurse, doctor, allied health worker) looking for employment |
| **Cover Letter** | A written message from a candidate explaining why they are applying for a specific job |
| **CORS** | Cross-Origin Resource Sharing — a security measure that restricts which websites can make requests to the API |
| **Data Export (GDPR)** | A downloadable file containing all of a user's personal data stored on the platform |
| **Document Access** | A one-time payment that unlocks a candidate's resume, documents, and contact details for an employer |
| **Employer** | A hospital, clinic, or healthcare company that posts jobs and hires candidates |
| **Feature Flag** | A switch that turns a platform feature on or off without requiring a code deployment |
| **GDPR** | General Data Protection Regulation — a European data privacy law that gives users rights over their personal data |
| **Grace Period** | A 30-day window after requesting account deletion during which the user can cancel the deletion |
| **Impersonation** | An admin feature that allows logging in as another user to troubleshoot issues |
| **Invoice** | A document showing the details of a payment made for a subscription |
| **Job Alert** | A saved search that emails a candidate when new jobs matching their criteria are posted |
| **Kanban Board** | A visual board with columns representing stages in the hiring process. Candidate cards are dragged between columns to update their status |
| **Match Score** | A percentage (0–100%) indicating how well a candidate's profile matches a job's requirements |
| **No-Show** | When a candidate does not attend a scheduled interview. Can be marked by the employer |
| **Offer Letter** | A formal document generated by the employer after marking a candidate as hired, containing job title, start date, and salary |
| **Open to Work** | A toggle on a candidate's profile that signals to employers they are actively looking for a new role |
| **Pipeline** | The stages a candidate moves through during the hiring process: Applied → Reviewed → Shortlisted → Interview → Hired / Rejected |
| **Plan** | A subscription tier with specific features and limits (e.g. PH Starter, International Pro) |
| **Profile Completeness** | A percentage showing how much of a candidate's profile has been filled in |
| **Queue** | A list of background tasks (like emails) waiting to be processed |
| **Rate Limiting** | A security measure that blocks excessive requests from the same IP address |
| **Sanctum** | Laravel's authentication system used to generate and validate login tokens |
| **Session** | An active login instance. Each device/browser where a user is logged in creates a session |
| **Staging** | A test environment that mirrors production, used for testing before deploying changes |
| **Stripe** | The payment processor used by ClinForce AI to handle credit/debit card payments |
| **Subscription** | A recurring payment plan that gives employers access to the full hiring workflow |
| **2FA (Two-Factor Authentication)** | A security feature requiring both a password and a one-time code from an authenticator app to log in |
| **Verification** | A process where employers submit proof of their business identity to receive a "Verified" badge |
| **Webhook** | An automatic notification sent from one system to another when an event occurs (e.g. Stripe notifies ClinForce AI when a payment succeeds) |
| **Zoom** | The video conferencing platform integrated with ClinForce AI for automatic meeting link generation |

---

## Support & Contact

| Channel | Details |
|---------|---------|
| Email | aiclinforce@gmail.com |
| Platform | https://aiclinforce.com |
| Admin Panel | https://aiclinforce.com/admin |
| Contact Form | https://aiclinforce.com/#contact |

---

*This document covers the complete feature set of ClinForce AI as of April 2026.*
*For technical integration details, API credentials, or server access, contact the development team.*


