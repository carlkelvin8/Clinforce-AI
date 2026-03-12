# Database Schema Documentation

## Overview

All models have corresponding database tables with migrations. The database is fully set up and ready for production.

## Tables by Category

### User Management
- **users** - Core user accounts (Laravel default)
- **personal_access_tokens** - API tokens for authentication

### User Profiles
- **applicant_profiles** - Job applicant profiles
- **employer_profiles** - Employer/company profiles
- **agency_profiles** - Recruitment agency profiles

### Job Management
- **jobs_table** - Job postings
- **job_applications** - Applications to jobs
- **application_status_history** - Track application status changes
- **interviews** - Interview scheduling

### Documents & Files
- **documents** - User documents (resume, certificates, etc.)
- **document_access_payments** - Payments for document access

### Screening & Verification
- **ai_screenings** - AI-powered candidate screening results
- **verification_requests** - User verification requests

### Payments & Billing
- **plans** - Subscription plans
- **subscriptions** - User subscriptions
- **payments** - Payment transactions
- **invoices** - Invoice records
- **countries** - Country and currency data
- **exchange_rates** - Currency exchange rates

### Communication
- **conversations** - Message conversations
- **conversation_participants** - Participants in conversations
- **messages** - Individual messages
- **notifications** - User notifications
- **notification_preferences** - User notification settings

### Zoom Integration
- **zoom_filter_settings** - Zoom privacy and filtering settings

### System
- **audit_logs** - System audit trail
- **invitations** - User invitations
- **trial_identities** - Trial user tracking
- **access_logs** - Document access logs
- **cache** - Cache table (Laravel)
- **jobs** - Queue jobs table (Laravel)

## Model to Table Mapping

| Model | Table | Status |
|-------|-------|--------|
| User | users | ✅ Created |
| AgencyProfile | agency_profiles | ✅ Created |
| AiScreening | ai_screenings | ✅ Created |
| ApplicantProfile | applicant_profiles | ✅ Created |
| ApplicationStatusHistory | application_status_history | ✅ Created |
| AuditLog | audit_logs | ✅ Created |
| Contact | contacts | ✅ Created |
| Conversation | conversations | ✅ Created |
| ConversationParticipant | conversation_participants | ✅ Created |
| Country | countries | ✅ Created |
| Document | documents | ✅ Created |
| DocumentAccessPayment | document_access_payments | ✅ Created |
| EmployerProfile | employer_profiles | ✅ Created |
| ExchangeRate | exchange_rates | ✅ Created |
| Interview | interviews | ✅ Created |
| Invitation | invitations | ✅ Created |
| Invoice | invoices | ✅ Created |
| Job | jobs_table | ✅ Created |
| JobApplication | job_applications | ✅ Created |
| Message | messages | ✅ Created |
| Notification | notifications | ✅ Created |
| NotificationPreference | notification_preferences | ✅ Created |
| Payment | payments | ✅ Created |
| Plan | plans | ✅ Created |
| Subscription | subscriptions | ✅ Created |
| TrialIdentity | trial_identities | ✅ Created |
| VerificationRequest | verification_requests | ✅ Created |
| ZoomFilterSetting | zoom_filter_settings | ✅ Created |

## Migration Files

### Core Migrations
1. **0001_01_01_000000_create_users_table.php** - Users table
2. **0001_01_01_000001_create_cache_table.php** - Cache table
3. **0001_01_01_000002_create_jobs_table.php** - Queue jobs table
4. **2026_01_13_081845_create_personal_access_tokens_table.php** - API tokens

### Profile Migrations
5. **2026_01_20_000000_create_profile_tables.php** - Applicant, Employer, Agency profiles

### Core Tables
6. **2026_01_21_000000_create_core_tables.php** - Plans, Subscriptions, Jobs, Applications, Documents, Interviews, Payments, etc.

### Billing & Currency
7. **2026_02_16_000300_add_billing_currency_tables_and_columns.php** - Countries, Exchange Rates, Invoices
8. **2026_02_16_000201_create_exchange_rates_table.php** - Exchange rates seeding

### Profile Enhancements
9. **2026_02_15_000200_add_logo_to_employer_profiles_table.php** - Logo field
10. **2026_02_16_000400_add_state_to_employer_profiles.php** - State field
11. **2026_02_16_020000_add_zip_and_tax_id_to_employer_profiles.php** - Zip and Tax ID
12. **2026_02_21_000500_add_state_to_applicant_profiles.php** - State field for applicants
13. **2026_03_05_031500_make_business_name_nullable_in_employer_profiles.php** - Business name field
14. **2026_03_08_010021_change_country_code_to_country_in_profiles.php** - Country field updates
15. **2026_03_08_023412_increase_country_column_length_in_profiles.php** - Increase country column

### Application & Document Management
16. **2026_02_21_011500_add_application_document_ids.php** - Document IDs for applications
17. **2026_03_01_034512_create_document_access_payments_table.php** - Document access payments

### Notifications & Preferences
18. **2026_02_21_120000_create_notifications_tables.php** - Notifications and preferences
19. **2026_03_08_023947_create_notification_preferences_table.php** - Notification preferences
20. **2026_03_09_024652_ensure_notification_preferences_schema.php** - Ensure schema

### Payments & Stripe
21. **2026_02_28_000001_add_stripe_fields_to_subscriptions.php** - Stripe fields
22. **2026_02_28_100001_add_stripe_customer_id_to_users.php** - Stripe customer ID
23. **2026_02_28_100002_modify_subscriptions_defaults.php** - Subscription defaults
24. **2026_02_28_100003_create_access_logs_table.php** - Access logs

### Trial & Security
25. **2026_03_08_014357_add_trial_columns_to_users_table.php** - Trial fields
26. **2026_03_09_022823_add_trial_security_fields_to_users_table.php** - Trial security
27. **2026_03_09_022834_create_trial_identities_table.php** - Trial identities

### Zoom Integration
28. **2026_03_09_013058_create_zoom_filter_settings_table.php** - Zoom settings
29. **2026_03_09_014939_add_audio_and_lock_to_zoom_settings.php** - Audio and lock settings
30. **2026_03_12_000001_add_privacy_filtering_to_zoom_filter_settings.php** - Privacy filtering

### Job & Country Updates
31. **2026_03_08_011044_update_country_code_in_jobs_table.php** - Country code in jobs
32. **2026_03_08_023634_increase_country_column_length_in_jobs.php** - Increase country column
33. **2026_03_01_031121_add_currency_fields_to_countries_table.php** - Currency fields

### Invitations & Contacts
34. **2026_02_28_100000_fix_invitations_table.php** - Fix invitations
35. **2026_03_06_144053_create_contacts_table.php** - Contact form submissions

### Billing Currency
36. **2026_02_16_010500_update_billing_currency_on_employer_profiles.php** - Billing currency

## Key Relationships

### User Relationships
- User → ApplicantProfile (1:1)
- User → EmployerProfile (1:1)
- User → AgencyProfile (1:1)
- User → Documents (1:many)
- User → Subscriptions (1:many)
- User → Payments (1:many)
- User → Invoices (1:many)

### Job Relationships
- Job → JobApplications (1:many)
- JobApplication → Interviews (1:many)
- JobApplication → AiScreening (1:1)
- JobApplication → ApplicationStatusHistory (1:many)

### Subscription Relationships
- Subscription → Plan (many:1)
- Subscription → Payments (1:many)
- Subscription → Invoices (1:many)

### Communication Relationships
- Conversation → ConversationParticipants (1:many)
- Conversation → Messages (1:many)
- ConversationParticipant → User (many:1)
- Message → User (many:1)

## Database Constraints

### Foreign Keys
- All foreign keys use `onDelete('cascade')` for data integrity
- User deletions cascade to all related records

### Unique Constraints
- `personal_access_tokens.token` - Unique API tokens
- `invitations.token` - Unique invitation tokens
- `conversation_participants` - Unique (conversation_id, user_id)
- `exchange_rates` - Unique (base_currency, quote_currency)

### Indexes
- All primary keys are indexed
- Foreign keys are indexed for performance
- Unique constraints create indexes

## Data Types

### Common Patterns
- **IDs**: `unsignedBigInteger` (primary), `foreignId` (foreign)
- **Amounts**: `integer` (cents to avoid floating point issues)
- **Currencies**: `string(3)` (ISO 4217 codes)
- **Status**: `string(50)` (enum-like values)
- **Timestamps**: `timestamp` with `useCurrent()` or `timestamps()`
- **JSON**: `json` for flexible data storage

## Migration Best Practices Used

1. **Idempotent Migrations** - All migrations check if tables/columns exist
2. **Database Agnostic** - Works with SQLite (dev) and MySQL (production)
3. **Proper Rollback** - All migrations have proper `down()` methods
4. **Foreign Keys** - All relationships properly constrained
5. **Timestamps** - All tables have created_at/updated_at
6. **Nullable Fields** - Properly marked for optional data

## Running Migrations

### Fresh Database
```bash
php artisan migrate:fresh
```

### Run Pending Migrations
```bash
php artisan migrate
```

### Rollback Last Batch
```bash
php artisan migrate:rollback
```

### Rollback All
```bash
php artisan migrate:reset
```

### Refresh (Reset + Migrate)
```bash
php artisan migrate:refresh
```

## Production Deployment

On Railway, migrations run automatically during deployment:
```bash
php artisan migrate --force
```

The `--force` flag allows migrations to run in production without confirmation.

## Verification

To verify all tables are created:
```bash
php artisan tinker
>>> Schema::getTables()
```

To check a specific table:
```bash
php artisan tinker
>>> Schema::getColumns('users')
```

## Summary

✅ **All 28 models have corresponding database tables**
✅ **All 36 migration files are in place**
✅ **Database is fully normalized and optimized**
✅ **Ready for production deployment**
