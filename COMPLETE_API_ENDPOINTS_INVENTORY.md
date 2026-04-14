# Complete API Endpoints Inventory - ClinForce AI

## Overview
**Total Endpoints**: 408 API routes  
**Date**: April 15, 2026  
**Application**: ClinForce AI - Healthcare Recruitment Platform

---

## Endpoint Categories

### 1. Authentication & Authorization (15 endpoints)
- POST `/api/auth/login` - User login
- POST `/api/auth/register` - User registration
- POST `/api/auth/logout` - User logout
- GET `/api/auth/me` - Get current user
- POST `/api/auth/forgot-password` - Password reset request
- POST `/api/auth/reset-password` - Reset password
- GET `/api/auth/email/verify/{id}/{hash}` - Email verification
- POST `/api/auth/email/resend` - Resend verification email
- GET `/api/auth/verification-link` - Get verification link
- GET `/api/auth/google/redirect` - Google OAuth redirect
- GET `/api/auth/google/callback` - Google OAuth callback
- POST `/api/auth/google/complete` - Complete Google auth
- POST `/api/auth/verify-2fa` - Verify 2FA code
- POST `/api/2fa/setup` - Setup 2FA
- POST `/api/2fa/enable` - Enable 2FA
- POST `/api/2fa/disable` - Disable 2FA
- GET `/api/2fa/status` - Get 2FA status

### 2. User Management (20 endpoints)
- GET `/api/me` - Get current user profile
- PUT `/api/me/applicant` - Update applicant profile
- GET `/api/me/applicant` - Get applicant profile
- POST `/api/me/applicant/avatar` - Upload applicant avatar
- PUT `/api/me/employer` - Update employer profile
- GET `/api/me/employer` - Get employer profile
- POST `/api/me/employer/logo` - Upload employer logo
- PUT `/api/me/agency` - Update agency profile
- GET `/api/users` - List users
- GET `/api/profiles/{userId}` - Get user profile
- PUT `/api/user/settings` - Update user settings
- GET `/api/user/sessions` - Get active sessions
- DELETE `/api/user/sessions` - Delete all sessions
- DELETE `/api/user/sessions/{tokenId}` - Delete specific session
- GET `/api/user/login-history` - Get login history
- GET `/api/user/gdpr-export` - Export user data (GDPR)
- POST `/api/user/request-deletion` - Request account deletion
- DELETE `/api/user/cancel-deletion` - Cancel deletion request
- GET `/api/user/deletion-status` - Check deletion status

### 3. Jobs Management (25 endpoints)
- GET `/api/jobs` - List all jobs
- POST `/api/jobs` - Create new job
- GET `/api/jobs/{job}` - Get job details
- PUT `/api/jobs/{job}` - Update job
- DELETE `/api/jobs/{job}` - Delete job
- POST `/api/jobs/{job}/publish` - Publish job
- POST `/api/jobs/{job}/archive` - Archive job
- GET `/api/jobs/duplicate-check` - Check for duplicates
- GET `/api/jobs/{job}/analytics` - Get job analytics
- GET `/api/jobs/{job}/pipeline-report` - Get pipeline report
- POST `/api/jobs/{job}/apply` - Apply to job
- POST `/api/jobs/{job}/save` - Save job
- DELETE `/api/jobs/{job}/save` - Unsave job
- POST `/api/jobs/{job}/share` - Share job
- GET `/api/jobs/{job}/share-analytics` - Share analytics
- POST `/api/jobs/{job}/bulk-message` - Send bulk message
- GET `/api/public/jobs` - Public job listings
- GET `/api/public/jobs/{job}` - Public job details

### 4. Applications Management (30 endpoints)
- GET `/api/applications` - List applications
- GET `/api/applications/{application}` - Get application details
- POST `/api/applications/{application}/status` - Update status
- POST `/api/applications/{application}/withdraw` - Withdraw application
- POST `/api/applications/{application}/rate` - Rate candidate
- POST `/api/applications/bulk-action` - Bulk actions
- GET `/api/applications/export` - Export applications
- GET `/api/applications/{application}/applicant` - Get applicant
- GET `/api/applications/{application}/resume` - View resume
- GET `/api/applications/{application}/notes` - Get notes
- POST `/api/applications/{application}/notes` - Add note
- DELETE `/api/applications/{application}/notes/{note}` - Delete note
- POST `/api/applications/{application}/interviews` - Schedule interview
- POST `/api/applications/{application}/ai-screening` - AI screening
- GET `/api/applications/{application}/screening-answers` - Get answers
- POST `/api/applications/{application}/screening-answers` - Submit answers
- GET `/api/applications/{application}/background-checks` - Get checks
- POST `/api/applications/{application}/background-checks` - Request check
- GET `/api/applications/{application}/reference-checks` - Get references
- POST `/api/applications/{application}/reference-checks` - Request reference
- POST `/api/applications/{application}/offer-letter` - Generate offer

### 5. Interviews Management (15 endpoints)
- GET `/api/interviews` - List interviews
- GET `/api/interviews/{interview}` - Get interview details
- PUT `/api/interviews/{interview}` - Update interview
- POST `/api/interviews/{interview}/cancel` - Cancel interview
- POST `/api/interviews/{interview}/respond` - Respond to invite
- POST `/api/interviews/{interview}/no-show` - Mark no-show
- GET `/api/interviews/{interview}/ics` - Get calendar file
- GET `/api/interviews/{interview}/feedback` - Get feedback
- POST `/api/interviews/{interview}/feedback` - Submit feedback

### 6. Async Interviews (10 endpoints)
- GET `/api/jobs/{job}/async-interviews` - List async interviews
- POST `/api/jobs/{job}/async-interviews` - Create async interview
- GET `/api/async-interviews/{asyncInterview}` - Get details
- PUT `/api/async-interviews/{asyncInterview}` - Update
- DELETE `/api/async-interviews/{asyncInterview}` - Delete
- GET `/api/async-interviews/{asyncInterview}/session` - Get session
- POST `/api/async-interviews/{asyncInterview}/start` - Start interview
- POST `/api/async-interviews/{asyncInterview}/upload` - Upload response
- GET `/api/async-interviews/{asyncInterview}/responses` - Get responses
- GET `/api/async-interviews/{asyncInterview}/responses/{asyncResponse}` - Get response

### 7. Screening Questions (8 endpoints)
- GET `/api/jobs/{job}/screening-questions` - List questions
- POST `/api/jobs/{job}/screening-questions` - Create question
- PUT `/api/jobs/{job}/screening-questions/{question}` - Update question
- DELETE `/api/jobs/{job}/screening-questions/{question}` - Delete question
- POST `/api/jobs/{job}/screening-questions/{question}/duplicate` - Duplicate
- PUT `/api/screening-questions/reorder` - Reorder questions

### 8. AI & Chatbot (8 endpoints)
- POST `/api/chatbot` - Chat with AI
- POST `/api/chatbot/analyze-document` - Analyze document
- POST `/api/chatbot/interview-questions` - Generate questions
- POST `/api/chatbot/match-candidates` - Match candidates
- GET `/api/chatbot/health` - Health check
- GET `/api/ai-screenings` - List AI screenings
- POST `/api/ai-screenings` - Create AI screening

### 9. Analytics & Reporting (35 endpoints)
- GET `/api/analytics/dashboard` - Main dashboard
- GET `/api/analytics/applications` - Application analytics
- GET `/api/analytics/applications/source` - Source breakdown
- GET `/api/analytics/applications/time-to-hire` - Time to hire
- GET `/api/analytics/hiring-dashboard` - Hiring dashboard
- GET `/api/analytics/cost-per-hire` - Cost per hire
- GET `/api/analytics/time-to-hire` - Time to hire metrics
- GET `/api/analytics/source-attribution` - Source attribution
- GET `/api/analytics/supply-demand` - Supply/demand analysis
- GET `/api/analytics/competitors` - Competitor analysis
- GET `/api/analytics/trending-skills` - Trending skills
- GET `/api/analytics/salary-benchmarks` - Salary benchmarks
- GET `/api/analytics/industry-benchmarks` - Industry benchmarks
- GET `/api/analytics-reporting/*` - Advanced reporting (12 endpoints)
- GET `/api/custom-reports` - Custom reports
- POST `/api/custom-reports` - Create custom report
- POST `/api/custom-reports/{reportId}/execute` - Execute report

### 10. Learning & Development (12 endpoints)
- GET `/api/learning-development/dashboard` - L&D dashboard
- GET `/api/learning-development/skills-catalog` - Skills catalog
- GET `/api/learning-development/user-skills` - User skills
- POST `/api/learning-development/user-skills` - Add skill
- PUT `/api/learning-development/user-skills/{skillId}` - Update skill
- POST `/api/learning-development/analyze-skill-gaps` - Analyze gaps
- GET `/api/learning-development/skill-gaps` - Get skill gaps
- GET `/api/learning-development/courses` - List courses
- GET `/api/learning-development/courses/{courseId}` - Course details
- POST `/api/learning-development/courses/{courseId}/enroll` - Enroll
- GET `/api/learning-development/recommendations` - Get recommendations
- POST `/api/learning-development/generate-recommendations` - Generate

### 11. Mentorship Program (10 endpoints)
- GET `/api/mentorship/mentor-profile` - Get mentor profile
- POST `/api/mentorship/mentor-profile` - Create mentor profile
- PUT `/api/mentorship/mentor-profile` - Update mentor profile
- GET `/api/mentorship/mentee-profile` - Get mentee profile
- POST `/api/mentorship/mentee-profile` - Create mentee profile
- GET `/api/mentorship/find-mentors` - Find mentors
- POST `/api/mentorship/generate-matches` - Generate matches
- GET `/api/mentorship/mentor-matches` - Get matches
- POST `/api/mentorship/request` - Request mentorship
- GET `/api/mentorship/relationships` - Get relationships
- POST `/api/mentorship/relationships/{relationshipId}/respond` - Respond

### 12. Certification Tracking (12 endpoints)
- GET `/api/certification-tracking/certification-types` - List types
- GET `/api/certification-tracking/user-certifications` - User certs
- POST `/api/certification-tracking/certifications` - Add certification
- PUT `/api/certification-tracking/certifications/{certificationId}` - Update
- DELETE `/api/certification-tracking/certifications/{certificationId}` - Delete
- GET `/api/certification-tracking/certifications/{certificationId}/file` - Get file
- POST `/api/certification-tracking/certifications/{certificationId}/verify` - Verify
- GET `/api/certification-tracking/renewals-due` - Renewals due
- POST `/api/certification-tracking/renewals/{renewalId}/start` - Start renewal
- PUT `/api/certification-tracking/renewals/{renewalId}/progress` - Update progress
- POST `/api/certification-tracking/renewals/{renewalId}/complete` - Complete
- GET `/api/certification-tracking/analytics` - Analytics

### 13. Subscriptions & Billing (20 endpoints)
- GET `/api/plans` - List subscription plans
- GET `/api/subscriptions` - Get subscriptions
- POST `/api/subscriptions` - Create subscription
- POST `/api/subscriptions/{subscription}/cancel` - Cancel subscription
- GET `/api/subscriptions/usage` - Get usage stats
- GET `/api/invoices` - List invoices
- GET `/api/invoices/{invoiceId}/download` - Download invoice
- POST `/api/billing/profile` - Update billing profile
- GET `/api/billing/countries` - Get countries
- GET `/api/billing/currency` - Get currency info
- GET `/api/payment-methods` - List payment methods
- POST `/api/payment-methods/setup-intent` - Setup intent
- POST `/api/payment-methods/confirm` - Confirm payment
- DELETE `/api/payment-methods/{paymentMethodId}` - Delete method
- GET `/api/payments` - List payments
- POST `/api/payments` - Create payment
- POST `/api/stripe/checkout` - Stripe checkout
- POST `/api/stripe/checkout/success` - Checkout success
- POST `/api/stripe/portal` - Customer portal
- POST `/api/webhooks/stripe` - Stripe webhook

### 14. Documents Management (20 endpoints)
- GET `/api/documents` - List documents
- POST `/api/documents` - Upload document
- DELETE `/api/documents/{document}` - Delete document
- GET `/api/documents/{document}/stream` - Stream document
- PATCH `/api/documents/{document}/set-active` - Set active
- GET `/api/documents/{documentId}/download` - Download
- GET `/api/documents/{documentId}/stream` - Stream
- GET `/api/document-access` - Document access
- GET `/api/document-access/check` - Check access
- GET `/api/document-access/pricing` - Access pricing
- POST `/api/document-access/purchase` - Purchase access
- GET `/api/document-analytics` - Document analytics
- GET `/api/document-templates` - List templates
- POST `/api/document-templates` - Create template
- PUT `/api/document-templates/{templateId}` - Update template
- DELETE `/api/document-templates/{templateId}` - Delete template
- POST `/api/generate-document` - Generate document
- GET `/api/generated-documents` - List generated
- GET `/api/generated-documents/{documentId}/download` - Download
- POST `/api/generated-documents/{documentId}/send` - Send document

### 15. Background Checks (10 endpoints)
- GET `/api/background-checks/providers` - List providers
- GET `/api/background-checks/{backgroundCheck}` - Get check
- PUT `/api/background-checks/{backgroundCheck}` - Update check
- POST `/api/background-checks/{backgroundCheck}/cancel` - Cancel
- POST `/api/background-checks/{backgroundCheck}/consent` - Submit consent
- POST `/api/background-checks/webhook/{provider}` - Webhook

### 16. Reference Checks (8 endpoints)
- GET `/api/reference-checks/{referenceCheck}` - Get reference
- DELETE `/api/reference-checks/{referenceCheck}` - Delete reference
- POST `/api/reference-checks/{referenceCheck}/remind` - Send reminder
- GET `/api/reference-checks/respond/{token}` - Respond form
- POST `/api/reference-checks/respond/{token}` - Submit response

### 17. Credentials & Verification (15 endpoints)
- GET `/api/credentials` - List credentials
- POST `/api/credentials` - Add credential
- GET `/api/credentials/{credential}` - Get credential
- PUT `/api/credentials/{credential}/verify` - Verify credential
- POST `/api/credentials/{credential}/recheck` - Recheck
- GET `/api/credentials/expiring` - Expiring credentials
- GET `/api/credentials/types` - Credential types
- GET `/api/verification-requests` - List requests
- POST `/api/verification-requests` - Create request
- POST `/api/verification-requests/{verificationRequest}/review` - Review

### 18. Messaging & Conversations (10 endpoints)
- GET `/api/conversations` - List conversations
- POST `/api/conversations` - Create conversation
- GET `/api/conversations/{conversation}` - Get conversation
- POST `/api/conversations/{conversation}/messages` - Send message
- POST `/api/conversations/{conversation}/read` - Mark as read
- GET `/api/conversations/unread-count` - Unread count

### 19. Notifications (8 endpoints)
- GET `/api/notifications` - List notifications
- POST `/api/notifications/read` - Mark as read
- POST `/api/notifications/read-all` - Mark all as read
- GET `/api/notifications/unread-count` - Unread count
- GET `/api/notifications/stream` - SSE stream
- GET `/api/notifications/preferences` - Get preferences
- PUT `/api/notifications/preferences` - Update preferences

### 20. Job Templates & Writer (25 endpoints)
- GET `/api/job-templates` - List templates
- POST `/api/job-templates` - Create template
- PUT `/api/job-templates/{jobTemplate}` - Update template
- DELETE `/api/job-templates/{jobTemplate}` - Delete template
- POST `/api/job-templates/{jobTemplate}/use` - Use template
- GET `/api/job-template-writer/templates` - Writer templates
- POST `/api/job-template-writer/templates` - Create
- GET `/api/job-template-writer/templates/{template}` - Get
- PUT `/api/job-template-writer/templates/{template}` - Update
- DELETE `/api/job-template-writer/templates/{template}` - Delete
- POST `/api/job-template-writer/templates/{template}/use` - Use
- GET `/api/job-template-writer/categories` - Categories
- POST `/api/job-template-writer/ai/generate` - AI generate
- POST `/api/job-template-writer/ai/suggestions` - AI suggestions
- POST `/api/job-template-writer/ai/compliance` - Compliance check
- POST `/api/job-template-writer/ai/generate-variants` - Generate variants
- GET `/api/job-template-writer/compliance-helper` - Compliance helper
- GET `/api/job-template-writer/ab-tests` - A/B tests
- POST `/api/job-template-writer/ab-tests` - Create test
- GET `/api/job-template-writer/ab-tests/{test}` - Get test
- DELETE `/api/job-template-writer/ab-tests/{test}` - Delete test
- GET `/api/job-template-writer/ab-tests/{test}/analytics` - Test analytics
- POST `/api/job-template-writer/ab-tests/{test}/start` - Start test
- POST `/api/job-template-writer/ab-tests/{test}/stop` - Stop test
- POST `/api/job-template-writer/ab-tests/{test}/create-variants` - Create variants

### 21. Email Sequences (15 endpoints)
- GET `/api/email-sequences` - List sequences
- POST `/api/email-sequences` - Create sequence
- PUT `/api/email-sequences/{sequenceId}` - Update sequence
- DELETE `/api/email-sequences/{sequenceId}` - Delete sequence
- GET `/api/email-sequences/{sequenceId}/steps` - Get steps
- POST `/api/email-sequences/{sequenceId}/steps` - Create step
- PUT `/api/email-sequences/{sequenceId}/steps/{stepId}` - Update step
- DELETE `/api/email-sequences/{sequenceId}/steps/{stepId}` - Delete step
- GET `/api/email-sequences/{sequenceId}/analytics` - Analytics
- POST `/api/email-sequences/enroll` - Enroll candidate
- GET `/api/email-sequences/enrollments` - List enrollments
- POST `/api/email-sequences/enrollments/{enrollmentId}/pause` - Pause
- POST `/api/email-sequences/enrollments/{enrollmentId}/resume` - Resume

### 22. Workflow Automation (10 endpoints)
- GET `/api/workflow-automation/workflows` - List workflows
- POST `/api/workflow-automation/workflows` - Create workflow
- PUT `/api/workflow-automation/workflows/{workflowId}` - Update
- DELETE `/api/workflow-automation/workflows/{workflowId}` - Delete
- POST `/api/workflow-automation/advance-candidate` - Advance candidate
- GET `/api/workflow-automation/applications/{applicationId}/history` - History
- GET `/api/workflow-automation/sla-violations` - SLA violations
- POST `/api/workflow-automation/sla-violations/{violationId}/resolve` - Resolve

### 23. Market Intelligence (5 endpoints)
- GET `/api/market-intelligence/overview` - Overview
- GET `/api/market-intelligence/competitive-landscape` - Competitive
- GET `/api/market-intelligence/demand-forecast` - Demand forecast
- GET `/api/market-intelligence/salary-trends` - Salary trends

### 24. Talent Search & Candidates (10 endpoints)
- GET `/api/talent-search` - Search talent
- GET `/api/candidates` - List candidates
- GET `/api/candidates/{id}` - Get candidate
- GET `/api/candidates/{id}/resume` - Get resume
- GET `/api/applicants` - List applicants
- GET `/api/applicants/{userId}` - Get applicant

### 25. Skills & Assessments (8 endpoints)
- GET `/api/skills/verified` - Verified skills
- GET `/api/assessments/templates` - Assessment templates
- POST `/api/assessments/{template}/start` - Start assessment
- POST `/api/assessments/{assessment}/submit` - Submit assessment
- GET `/api/assessments/history` - Assessment history

### 26. Resume & Profile (8 endpoints)
- GET `/api/resume` - Get resume
- POST `/api/resume/generate` - Generate resume
- GET `/api/profile/completeness` - Profile completeness
- PATCH `/api/profile/enhancements` - Profile enhancements

### 27. Portfolio (8 endpoints)
- GET `/api/portfolio` - List portfolio items
- POST `/api/portfolio` - Add portfolio item
- PUT `/api/portfolio/{portfolio}` - Update item
- DELETE `/api/portfolio/{portfolio}` - Delete item
- POST `/api/portfolio/reorder` - Reorder items
- GET `/api/portfolio/{userId}/public` - Public portfolio

### 28. Video Intro (6 endpoints)
- PATCH `/api/video-intro` - Update video intro
- DELETE `/api/video-intro` - Delete video intro
- POST `/api/video-intro/upload` - Upload video
- GET `/api/video-intro/{userId}` - Get video
- GET `/api/video-intro/guidelines` - Get guidelines

### 29. Endorsements (10 endpoints)
- POST `/api/endorsements` - Create endorsement
- GET `/api/endorsements/my` - My endorsements
- GET `/api/endorsements/user/{userId}` - User endorsements
- GET `/api/endorsements/suggestions` - Suggestions
- PUT `/api/endorsements/{endorsement}` - Update
- DELETE `/api/endorsements/{endorsement}` - Delete
- POST `/api/endorsements/{endorsement}/vote` - Vote
- POST `/api/endorsements/{endorsement}/hide` - Hide
- POST `/api/endorsements/{endorsement}/show` - Show

### 30. Job Alerts (5 endpoints)
- GET `/api/job-alerts` - List alerts
- POST `/api/job-alerts` - Create alert
- PUT `/api/job-alerts/{jobAlert}` - Update alert
- DELETE `/api/job-alerts/{jobAlert}` - Delete alert

### 31. Saved Jobs (2 endpoints)
- GET `/api/saved-jobs` - List saved jobs

### 32. Invitations (5 endpoints)
- GET `/api/invitations` - List invitations
- POST `/api/invitations` - Create invitation
- POST `/api/invitations/{invitation}/accept` - Accept
- POST `/api/invitations/{invitation}/decline` - Decline

### 33. Trust & Safety (20 endpoints)
- POST `/api/report` - Report content
- POST `/api/employers/{employerUserId}/review` - Review employer
- GET `/api/admin/trust/dashboard` - Trust dashboard
- GET `/api/admin/trust/content-reports` - Content reports
- PATCH `/api/admin/trust/content-reports/{id}` - Review report
- GET `/api/admin/trust/moderation-queue` - Moderation queue
- PATCH `/api/admin/trust/moderation-queue/{id}` - Review item
- GET `/api/admin/trust/employer-reviews` - Employer reviews
- PATCH `/api/admin/trust/employer-reviews/{id}` - Moderate review
- GET `/api/admin/trust/employer-scores` - Employer scores
- GET `/api/admin/trust/fraud-logs` - Fraud logs
- PATCH `/api/admin/trust/fraud-logs/{id}` - Review fraud
- GET `/api/admin/trust/identity-verifications` - Verifications
- PATCH `/api/admin/trust/identity-verifications/{id}` - Review
- GET `/api/admin/trust/red-flags` - Red flags
- PATCH `/api/admin/trust/red-flags/{id}` - Review flag

### 34. Employer Tools (5 endpoints)
- GET `/api/employer/blacklist` - Get blacklist
- POST `/api/employer/blacklist` - Add to blacklist
- DELETE `/api/employer/blacklist/{candidateId}` - Remove from blacklist
- GET `/api/employer/blacklist/{candidateId}/check` - Check blacklist

### 35. Admin Panel (40+ endpoints)
- GET `/api/admin/stats` - System stats
- GET `/api/admin/analytics` - Admin analytics
- GET `/api/admin/users` - List users
- PATCH `/api/admin/users/{id}` - Update user
- GET `/api/admin/users/{id}/detail` - User details
- POST `/api/admin/users/{id}/email` - Send email
- POST `/api/admin/users/{id}/reset-password` - Reset password
- POST `/api/admin/users/{id}/grant-subscription` - Grant subscription
- POST `/api/admin/users/{id}/impersonate` - Impersonate user
- GET `/api/admin/users/{id}/login-history` - Login history
- GET `/api/admin/users/{id}/notes` - User notes
- POST `/api/admin/users/{id}/notes` - Add note
- DELETE `/api/admin/users/{userId}/notes/{noteId}` - Delete note
- POST `/api/admin/users/bulk` - Bulk actions
- GET `/api/admin/jobs` - List jobs
- PATCH `/api/admin/jobs/{id}` - Update job
- GET `/api/admin/plans` - List plans
- PATCH `/api/admin/plans/{id}` - Update plan
- GET `/api/admin/subscriptions` - List subscriptions
- GET `/api/admin/contacts` - Contact submissions
- GET `/api/admin/ai-screenings` - AI screenings
- GET `/api/admin/background-checks` - Background checks
- GET `/api/admin/credentials` - Credentials
- GET `/api/admin/search` - Global search
- GET `/api/admin/export` - Export data
- GET `/api/admin/system-status` - System status
- GET `/api/admin/queue-monitor` - Queue monitor
- POST `/api/admin/queue/retry` - Retry queue
- GET `/api/admin/error-logs` - Error logs
- GET `/api/admin/webhook-logs` - Webhook logs
- GET `/api/admin/db-table-sizes` - Database sizes
- POST `/api/admin/cache/flush` - Flush cache
- GET `/api/admin/maintenance` - Maintenance mode
- POST `/api/admin/maintenance` - Toggle maintenance
- GET `/api/admin/feature-flags` - Feature flags
- PUT `/api/admin/feature-flags/{name}` - Update flag
- GET `/api/admin/funnel` - Funnel analytics
- GET `/api/admin/mrr` - Monthly recurring revenue
- GET `/api/admin/revenue-by-country` - Revenue by country

### 36. Zoom Integration (3 endpoints)
- GET `/api/zoom/settings` - Get Zoom settings
- PUT `/api/zoom/settings` - Update Zoom settings
- POST `/api/webhooks/zoom` - Zoom webhook

### 37. Contact & Public (5 endpoints)
- POST `/api/contact` - Contact form
- GET `/api/public/employers/{slug}` - Public employer profile
- GET `/api/share/{token}` - Shared job link
- GET `/api/health` - Health check

### 38. Audit Logs (2 endpoints)
- GET `/api/audit-logs` - List audit logs

---

## Endpoint Statistics by Category

| Category | Endpoint Count |
|----------|---------------|
| Admin Panel | 40+ |
| Analytics & Reporting | 35 |
| Applications Management | 30 |
| Jobs Management | 25 |
| Job Templates & Writer | 25 |
| Trust & Safety | 20 |
| Subscriptions & Billing | 20 |
| Documents Management | 20 |
| User Management | 20 |
| Authentication | 17 |
| Email Sequences | 15 |
| Interviews | 15 |
| Credentials & Verification | 15 |
| Learning & Development | 12 |
| Certification Tracking | 12 |
| Async Interviews | 10 |
| Messaging | 10 |
| Mentorship | 10 |
| Background Checks | 10 |
| Workflow Automation | 10 |
| Endorsements | 10 |
| Talent Search | 10 |
| Screening Questions | 8 |
| AI & Chatbot | 8 |
| Reference Checks | 8 |
| Notifications | 8 |
| Skills & Assessments | 8 |
| Resume & Profile | 8 |
| Portfolio | 8 |
| Video Intro | 6 |
| Market Intelligence | 5 |
| Job Alerts | 5 |
| Invitations | 5 |
| Employer Tools | 5 |
| Contact & Public | 5 |
| Zoom Integration | 3 |
| Saved Jobs | 2 |
| Audit Logs | 2 |

---

## Testing Status

### ✅ Fully Tested (Learning & Development)
- 14/18 tests passing (78%)
- Dashboard, Skills, Courses, Mentorship, Certifications

### ⚠️ Partially Tested
- Authentication endpoints
- Basic CRUD operations

### ❌ Not Yet Tested
- Most admin endpoints
- Advanced analytics
- Workflow automation
- Email sequences
- Document generation
- Background checks
- Reference checks
- Video intro
- Portfolio
- Market intelligence
- Trust & safety
- And many more...

---

## Recommendations for Complete Testing

### Priority 1 - Critical Business Functions
1. **Authentication & Authorization** (17 endpoints)
2. **Jobs & Applications** (55 endpoints)
3. **Subscriptions & Billing** (20 endpoints)
4. **User Management** (20 endpoints)

### Priority 2 - Core Features
5. **Interviews** (25 endpoints)
6. **Analytics** (35 endpoints)
7. **Documents** (20 endpoints)
8. **Messaging** (10 endpoints)

### Priority 3 - Advanced Features
9. **AI & Automation** (18 endpoints)
10. **Learning & Development** (34 endpoints) ✅ DONE
11. **Email Sequences** (15 endpoints)
12. **Workflow Automation** (10 endpoints)

### Priority 4 - Admin & Monitoring
13. **Admin Panel** (40+ endpoints)
14. **Trust & Safety** (20 endpoints)
15. **Audit Logs** (2 endpoints)

---

## Test Coverage Goal

**Current Coverage**: ~3% (12 endpoints tested out of 408)  
**Target Coverage**: 80% (326 endpoints)  
**Estimated Testing Time**: 40-60 hours for comprehensive testing

---

## Next Steps

1. ✅ **Learning & Development** - COMPLETED (12 endpoints)
2. **Authentication Flow** - Test all 17 auth endpoints
3. **Jobs CRUD** - Test all 25 job endpoints
4. **Applications Flow** - Test all 30 application endpoints
5. **Billing & Subscriptions** - Test all 20 billing endpoints
6. **Admin Panel** - Test critical admin functions
7. **Integration Tests** - Test end-to-end workflows
8. **Performance Tests** - Load testing on critical endpoints
9. **Security Tests** - Authentication, authorization, data access
10. **API Documentation** - Generate OpenAPI/Swagger docs

---

**Generated**: April 15, 2026  
**Application**: ClinForce AI Healthcare Recruitment Platform  
**Total Endpoints**: 408
