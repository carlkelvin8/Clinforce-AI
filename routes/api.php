<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\PlansController;
use App\Http\Controllers\Api\ProfilesController;
use App\Http\Controllers\Api\DocumentsController;
use App\Http\Controllers\Api\JobsController;
use App\Http\Controllers\Api\JobApplicationsController;
use App\Http\Controllers\Api\InterviewsController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\PaymentsController;
use App\Http\Controllers\Api\VerificationRequestsController;
use App\Http\Controllers\Api\AiScreeningsController;
use App\Http\Controllers\Api\AuditLogsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MessagesController;
use App\Http\Controllers\Api\UsersController;
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\TalentSearchController;
use App\Http\Controllers\Api\NotificationsController;
use App\Http\Controllers\Api\StripeCheckoutController;
use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\CandidatesController;
use App\Http\Controllers\Api\ApplicantsController;
use App\Http\Controllers\Api\SecureDocumentController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ZoomWebhookController;
use App\Http\Controllers\Api\ZoomSettingsController;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/
Route::get('/health', [HealthController::class, 'index']);
Route::get('/plans', [PlansController::class, 'index']);
Route::post('/contact', [ContactController::class, 'store']);

// Public job browsing
Route::get('/public/jobs', [JobsController::class, 'publicIndex']);
Route::get('/public/jobs/{job}', [JobsController::class, 'publicShow']);

// Public employer profiles
Route::get('/public/employers/{slug}', [\App\Http\Controllers\Api\PublicEmployerController::class, 'show']);
Route::get('/employers/{slug}', [\App\Http\Controllers\Api\PublicEmployerController::class, 'show']);

// Share tracking redirect (web)
Route::get('/share/{token}', [\App\Http\Controllers\Api\JobShareController::class, 'track']);

// Stripe webhook (must be outside auth middleware)
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle']);
Route::post('/webhooks/zoom', [ZoomWebhookController::class, 'handle']);

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    // Rate limited auth endpoints — relaxed in testing
    Route::middleware('throttle:' . (app()->environment('testing', 'local') ? '1000,1' : '5,1'))->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login',    [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    });
    
    // Email Verification
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed'])
        ->name('verification.verify');
    Route::post('/email/resend', [AuthController::class, 'resendVerificationEmail'])
        ->middleware(['auth:sanctum', 'throttle:3,1']);
    
    // Google OAuth
    Route::get('/google/redirect', [AuthController::class, 'googleRedirect']);
    Route::get('/google/callback', [AuthController::class, 'googleCallback']);
    Route::post('/google/complete', [AuthController::class, 'googleCompleteRegistration']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me',      [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/verification-link', [AuthController::class, 'verificationLink']);
    });
});

/*
|--------------------------------------------------------------------------
| Protected (MUST be auth:sanctum so $request->user() works)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Profile (me)
    Route::get('/me', [ProfilesController::class, 'me']);
    Route::get('/profiles/{userId}', [ProfilesController::class, 'show']);
    Route::put('/user/settings', [UsersController::class, 'updateSettings']);
    // Session management
    Route::get('/user/sessions', [UsersController::class, 'sessions']);
    Route::delete('/user/sessions/{tokenId}', [UsersController::class, 'revokeSession']);
    Route::delete('/user/sessions', [UsersController::class, 'revokeAllSessions']);
    Route::get('/user/login-history', [UsersController::class, 'loginHistory']);
    Route::get('/user/gdpr-export', [UsersController::class, 'gdprExport']);
    Route::post('/user/request-deletion', [UsersController::class, 'requestDeletion']);
    Route::delete('/user/cancel-deletion', [UsersController::class, 'cancelDeletion']);
    Route::get('/user/deletion-status', [UsersController::class, 'deletionStatus']);
    Route::put('/me/applicant', [ProfilesController::class, 'upsertApplicant']);
    Route::get('/me/applicant', [ProfilesController::class, 'meApplicant']);
    Route::post('/me/applicant/avatar', [ProfilesController::class, 'uploadApplicantAvatar']);
    Route::put('/me/employer', [ProfilesController::class, 'upsertEmployer']);
    Route::put('/me/agency', [ProfilesController::class, 'upsertAgency']);
    Route::get('/me/employer', [ProfilesController::class, 'meEmployer']);
    Route::post('/me/employer/logo', [ProfilesController::class, 'uploadEmployerLogo']);

    // Zoom Settings
    Route::get('/zoom/settings', [ZoomSettingsController::class, 'show']);
    Route::put('/zoom/settings', [ZoomSettingsController::class, 'update']);

    // Documents
    Route::get('/documents', [DocumentsController::class, 'index']);
    Route::post('/documents', [DocumentsController::class, 'store']);
    Route::delete('/documents/{document}', [DocumentsController::class, 'destroy']);
    Route::get('/documents/{document}/stream', [DocumentsController::class, 'stream']);
    Route::patch('/documents/{document}/set-active', [DocumentsController::class, 'setActive']);

    // Jobs (owner) — posting requires active subscription
    Route::get('/jobs', [JobsController::class, 'index']);
    Route::get('/jobs/duplicate-check', [JobsController::class, 'duplicateCheck']);
    Route::get('/jobs/{job}', [JobsController::class, 'show']);
    Route::get('/jobs/{job}/pipeline-report', [JobsController::class, 'pipelineReport']);
    Route::middleware('subscription:jobs')->group(function () {
        Route::post('/jobs', [JobsController::class, 'store']);
        Route::put('/jobs/{job}', [JobsController::class, 'update']);
        Route::post('/jobs/{job}/publish', [JobsController::class, 'publish']);
        Route::post('/jobs/{job}/archive', [JobsController::class, 'archive']);
        Route::delete('/jobs/{job}', [JobsController::class, 'destroy']);
    });

    // Job templates
    Route::get('/job-templates', [\App\Http\Controllers\Api\JobTemplatesController::class, 'index']);
    Route::post('/job-templates', [\App\Http\Controllers\Api\JobTemplatesController::class, 'store']);
    Route::put('/job-templates/{jobTemplate}', [\App\Http\Controllers\Api\JobTemplatesController::class, 'update']);
    Route::delete('/job-templates/{jobTemplate}', [\App\Http\Controllers\Api\JobTemplatesController::class, 'destroy']);
    Route::post('/job-templates/{jobTemplate}/use', [\App\Http\Controllers\Api\JobTemplatesController::class, 'useTemplate']);

    // Applications
    Route::post('/jobs/{job}/apply', [JobApplicationsController::class, 'apply']);
    Route::get('/applications', [JobApplicationsController::class, 'index']);
    Route::get('/applications/{application}', [JobApplicationsController::class, 'show']);
    Route::post('/applications/{application}/status', [JobApplicationsController::class, 'updateStatus']);
    Route::post('/applications/{application}/withdraw', [JobApplicationsController::class, 'withdraw']);
    Route::post('/applications/{application}/rate', [JobApplicationsController::class, 'rateCandidate']);
    Route::get('/applications/export', [JobApplicationsController::class, 'exportCsv']);
    Route::get('/applications/{application}/resume', [JobApplicationsController::class, 'viewResume']);

    // Bulk application actions
    Route::post('/applications/bulk-action', [\App\Http\Controllers\Api\BulkApplicationsController::class, 'bulkAction']);

    // Application notes
    Route::get('/applications/{application}/notes', [\App\Http\Controllers\Api\ApplicationNotesController::class, 'index']);
    Route::post('/applications/{application}/notes', [\App\Http\Controllers\Api\ApplicationNotesController::class, 'store']);
    Route::delete('/applications/{application}/notes/{note}', [\App\Http\Controllers\Api\ApplicationNotesController::class, 'destroy']);

    // Talent Search — requires subscription
    Route::middleware('subscription')->group(function () {
        Route::get('/talent-search', [TalentSearchController::class, 'index']);
    });

    // Invitations — requires active subscription
    Route::get('/invitations', [App\Http\Controllers\Api\InvitationController::class, 'index']);
    Route::post('/invitations/{invitation}/accept', [App\Http\Controllers\Api\InvitationController::class, 'accept']);
    Route::post('/invitations/{invitation}/decline', [App\Http\Controllers\Api\InvitationController::class, 'decline']);
    Route::middleware('subscription:invite')->group(function () {
        Route::post('/invitations', [App\Http\Controllers\Api\InvitationController::class, 'store']);
    });

    // Interviews — listing + write actions require active trial/subscription
    Route::middleware(['require.sub'])->group(function () {
        Route::get('/interviews', [InterviewsController::class, 'index']);
        Route::post('/applications/{application}/interviews', [InterviewsController::class, 'store']);
        Route::put('/interviews/{interview}', [InterviewsController::class, 'update']);
        Route::post('/interviews/{interview}/cancel', [InterviewsController::class, 'cancel']);
    });
    Route::get('/interviews/{interview}', [InterviewsController::class, 'show']);
    Route::get('/interviews/{interview}/ics', [InterviewsController::class, 'exportIcs']);
    Route::post('/interviews/{interview}/respond', [InterviewsController::class, 'respond']);
    Route::post('/interviews/{interview}/no-show', [InterviewsController::class, 'markNoShow']);

    // Interview feedback
    Route::get('/interviews/{interview}/feedback', [\App\Http\Controllers\Api\InterviewFeedbackController::class, 'show']);
    Route::post('/interviews/{interview}/feedback', [\App\Http\Controllers\Api\InterviewFeedbackController::class, 'store']);

    // Subscriptions / Payments
    Route::get('/subscriptions', [SubscriptionController::class, 'index']);
    Route::get('/subscriptions/usage', [SubscriptionController::class, 'usage']);
    
    Route::get('/invoices', [SubscriptionController::class, 'invoices']);
    Route::get('/invoices/{invoiceId}/download', [SubscriptionController::class, 'downloadInvoice']);
    
    // Document Access (separate from subscription)
    Route::get('/document-access/check', [App\Http\Controllers\Api\DocumentAccessController::class, 'checkAccess']);
    Route::get('/document-access/pricing', [App\Http\Controllers\Api\DocumentAccessController::class, 'pricing']);
    Route::post('/document-access/purchase', [App\Http\Controllers\Api\DocumentAccessController::class, 'purchase']);
    Route::get('/document-access', [App\Http\Controllers\Api\DocumentAccessController::class, 'index']);
    
    // AI Chatbot - Enterprise Grade
    Route::post('/chatbot', [ChatbotController::class, 'chat']);
    Route::get('/chatbot/health', [ChatbotController::class, 'health']);
    Route::post('/chatbot/analyze-document', [ChatbotController::class, 'analyzeDocument']);
    Route::post('/chatbot/match-candidates', [ChatbotController::class, 'matchCandidates']);
    Route::post('/chatbot/interview-questions', [ChatbotController::class, 'generateInterviewQuestions']);
    Route::post('/subscriptions', [SubscriptionController::class, 'store']);
    Route::post('/subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel']);

    Route::get('/payments', [PaymentsController::class, 'index']);
    Route::post('/payments', [PaymentsController::class, 'store']);

    // Verification requests
    Route::get('/verification-requests', [VerificationRequestsController::class, 'index']);
    Route::post('/verification-requests', [VerificationRequestsController::class, 'store']);
    Route::post('/verification-requests/{verificationRequest}/review', [VerificationRequestsController::class, 'review']);

    Route::middleware(['require.sub'])->group(function () {
        Route::get('/conversations/unread-count', [MessagesController::class, 'unreadCount']);
        Route::get('/conversations', [MessagesController::class, 'index']);
        Route::post('/conversations', [MessagesController::class, 'store']);
        Route::get('/conversations/{conversation}', [MessagesController::class, 'show']);
        Route::post('/conversations/{conversation}/messages', [MessagesController::class, 'send']);
        Route::post('/conversations/{conversation}/read', [MessagesController::class, 'markRead']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/users', [UsersController::class, 'index']);
    });
    // AI screenings — read requires subscription, trigger requires AI feature
    Route::middleware('subscription')->group(function () {
        Route::get('/ai-screenings', [AiScreeningsController::class, 'index']);
    });
    Route::middleware('subscription:ai')->group(function () {
        Route::post('/applications/{application}/ai-screening', [AiScreeningsController::class, 'store']);
        Route::post('/ai-screenings', [AiScreeningsController::class, 'store']);
    });

    // Audit logs (admin-only)
    Route::get('/audit-logs', [AuditLogsController::class, 'index']);

    // Billing currency
    Route::get('/billing/currency', [BillingController::class, 'currency']);
    Route::get('/billing/countries', [BillingController::class, 'countries']);
    Route::post('/billing/profile', [BillingController::class, 'updateProfile']);

    // Payment Methods
    Route::post('/payment-methods/setup-intent', [PaymentMethodController::class, 'createSetupIntent']);
    Route::get('/payment-methods', [PaymentMethodController::class, 'index']);
    Route::post('/payment-methods/confirm', [PaymentMethodController::class, 'confirm']);
    Route::delete('/payment-methods/{paymentMethodId}', [PaymentMethodController::class, 'destroy']);

    // Stripe Checkout & Billing Portal
    Route::post('/stripe/checkout', [StripeCheckoutController::class, 'createCheckoutSession']);
    Route::post('/stripe/checkout/success', [StripeCheckoutController::class, 'handleSuccess']);
    Route::post('/stripe/portal', [StripeCheckoutController::class, 'createPortalSession']);

    // Candidates - List (preview mode, no subscription required)
    Route::get('/candidates', [CandidatesController::class, 'index']);
    
    // Candidates - Protected (requires active subscription)
    Route::middleware(['require.sub', 'throttle:60,1'])->group(function () {
        Route::get('/candidates/{id}', [CandidatesController::class, 'show']);
        Route::get('/candidates/{id}/resume', [CandidatesController::class, 'downloadResume']);
    });

    // Applicants (with subscription gates for employers)
    Route::get('/applicants', [ApplicantsController::class, 'index']);
    Route::get('/applicants/{userId}', [ApplicantsController::class, 'show']);
    Route::get('/applications/{applicationId}/applicant', [ApplicantsController::class, 'showFromApplication']);

    // Secure document downloads (with subscription gates)
    Route::get('/documents/{documentId}/download', [SecureDocumentController::class, 'download']);
    Route::get('/documents/{documentId}/stream', [SecureDocumentController::class, 'stream']);

    // Saved Jobs (candidates)
    Route::get('/saved-jobs', [\App\Http\Controllers\Api\SavedJobsController::class, 'index']);
    Route::post('/jobs/{job}/save', [\App\Http\Controllers\Api\SavedJobsController::class, 'store']);
    Route::delete('/jobs/{job}/save', [\App\Http\Controllers\Api\SavedJobsController::class, 'destroy']);

    // Job share / referral tracking
    Route::post('/jobs/{job}/share', [\App\Http\Controllers\Api\JobShareController::class, 'store']);
    Route::get('/jobs/{job}/share-analytics', [\App\Http\Controllers\Api\JobShareController::class, 'analytics']);

    // Job alerts
    Route::get('/job-alerts', [\App\Http\Controllers\Api\JobAlertsController::class, 'index']);
    Route::post('/job-alerts', [\App\Http\Controllers\Api\JobAlertsController::class, 'store']);
    Route::put('/job-alerts/{jobAlert}', [\App\Http\Controllers\Api\JobAlertsController::class, 'update']);
    Route::delete('/job-alerts/{jobAlert}', [\App\Http\Controllers\Api\JobAlertsController::class, 'destroy']);

    // ═══════════════════════════════════════════════════════════
    // CANDIDATE PROFILE ENHANCEMENTS
    // ═══════════════════════════════════════════════════════════
    
    // AI Resume Generator
    Route::post('/resume/generate', [\App\Http\Controllers\Api\ResumeController::class, 'generate']);
    Route::get('/resume', [\App\Http\Controllers\Api\ResumeController::class, 'show']);
    Route::get('/profile/completeness', [\App\Http\Controllers\Api\ResumeController::class, 'completeness']);
    Route::patch('/profile/enhancements', [\App\Http\Controllers\Api\ResumeController::class, 'updateProfile']);
    
    // Video Introduction
    Route::post('/video-intro/upload', [\App\Http\Controllers\Api\VideoIntroController::class, 'upload']);
    Route::patch('/video-intro', [\App\Http\Controllers\Api\VideoIntroController::class, 'update']);
    Route::delete('/video-intro', [\App\Http\Controllers\Api\VideoIntroController::class, 'destroy']);
    Route::get('/video-intro/{userId}', [\App\Http\Controllers\Api\VideoIntroController::class, 'show']);
    Route::get('/video-intro/guidelines', [\App\Http\Controllers\Api\VideoIntroController::class, 'guidelines']);
    
    // Portfolio
    Route::get('/portfolio', [\App\Http\Controllers\Api\PortfolioController::class, 'index']);
    Route::post('/portfolio', [\App\Http\Controllers\Api\PortfolioController::class, 'store']);
    Route::put('/portfolio/{portfolio}', [\App\Http\Controllers\Api\PortfolioController::class, 'update']);
    Route::delete('/portfolio/{portfolio}', [\App\Http\Controllers\Api\PortfolioController::class, 'destroy']);
    Route::post('/portfolio/reorder', [\App\Http\Controllers\Api\PortfolioController::class, 'reorder']);
    Route::get('/portfolio/{userId}/public', [\App\Http\Controllers\Api\PortfolioController::class, 'showPublic']);
    
    // Skills Assessments
    Route::get('/assessments/templates', [\App\Http\Controllers\Api\SkillsAssessmentController::class, 'templates']);
    Route::post('/assessments/{template}/start', [\App\Http\Controllers\Api\SkillsAssessmentController::class, 'start']);
    Route::post('/assessments/{assessment}/submit', [\App\Http\Controllers\Api\SkillsAssessmentController::class, 'submit']);
    Route::get('/assessments/history', [\App\Http\Controllers\Api\SkillsAssessmentController::class, 'history']);
    Route::get('/skills/verified', [\App\Http\Controllers\Api\SkillsAssessmentController::class, 'verifiedSkills']);
    
    // Endorsements
    Route::get('/endorsements/my', [\App\Http\Controllers\Api\EndorsementController::class, 'myEndorsements']);
    Route::get('/endorsements/user/{userId}', [\App\Http\Controllers\Api\EndorsementController::class, 'showUserEndorsements']);
    Route::post('/endorsements', [\App\Http\Controllers\Api\EndorsementController::class, 'store']);
    Route::put('/endorsements/{endorsement}', [\App\Http\Controllers\Api\EndorsementController::class, 'update']);
    Route::delete('/endorsements/{endorsement}', [\App\Http\Controllers\Api\EndorsementController::class, 'destroy']);
    Route::post('/endorsements/{endorsement}/hide', [\App\Http\Controllers\Api\EndorsementController::class, 'hide']);
    Route::post('/endorsements/{endorsement}/show', [\App\Http\Controllers\Api\EndorsementController::class, 'show']);
    Route::post('/endorsements/{endorsement}/vote', [\App\Http\Controllers\Api\EndorsementController::class, 'vote']);
    Route::get('/endorsements/suggestions', [\App\Http\Controllers\Api\EndorsementController::class, 'suggestEndorsees']);

    // Analytics
    Route::get('/analytics/dashboard', [\App\Http\Controllers\Api\AnalyticsController::class, 'dashboard']);

    // Employer tools
    Route::get('/employer/blacklist', [\App\Http\Controllers\Api\EmployerToolsController::class, 'blacklistIndex']);
    Route::post('/employer/blacklist', [\App\Http\Controllers\Api\EmployerToolsController::class, 'blacklistAdd']);
    Route::delete('/employer/blacklist/{candidateId}', [\App\Http\Controllers\Api\EmployerToolsController::class, 'blacklistRemove']);
    Route::get('/employer/blacklist/{candidateId}/check', [\App\Http\Controllers\Api\EmployerToolsController::class, 'isBlacklisted']);
    Route::get('/jobs/{job}/analytics', [\App\Http\Controllers\Api\EmployerToolsController::class, 'jobAnalytics']);
    Route::post('/jobs/{job}/bulk-message', [\App\Http\Controllers\Api\EmployerToolsController::class, 'bulkMessage']);
    Route::post('/applications/{application}/offer-letter', [\App\Http\Controllers\Api\EmployerToolsController::class, 'offerLetter']);

    // 2FA
    Route::get('/2fa/status', [\App\Http\Controllers\Api\TwoFactorController::class, 'status']);
    Route::post('/2fa/setup', [\App\Http\Controllers\Api\TwoFactorController::class, 'setup']);
    Route::post('/2fa/enable', [\App\Http\Controllers\Api\TwoFactorController::class, 'enable']);
    Route::post('/2fa/disable', [\App\Http\Controllers\Api\TwoFactorController::class, 'disable']);
    Route::post('/auth/verify-2fa', [\App\Http\Controllers\Api\TwoFactorController::class, 'verify']);

    // Notifications
    Route::get('/notifications', [NotificationsController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [NotificationsController::class, 'unreadCount'])->name('notifications.unreadCount');
    Route::post('/notifications/read', [NotificationsController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationsController::class, 'markAllRead'])->name('notifications.readAll');
    Route::get('/notifications/stream', [NotificationsController::class, 'stream'])->name('notifications.stream');
    Route::get('/notifications/preferences', [NotificationsController::class, 'preferencesGet'])->name('notifications.preferences.get');
    Route::put('/notifications/preferences', [NotificationsController::class, 'preferencesUpdate'])->name('notifications.preferences.update');

    // ── Admin ────────────────────────────────────────────────────────────
    Route::prefix('admin')->group(function () {
        Route::get('/stats',                    [\App\Http\Controllers\Api\AdminController::class, 'stats']);
        Route::get('/analytics',                [\App\Http\Controllers\Api\AdminController::class, 'analytics']);
        Route::get('/mrr',                      [\App\Http\Controllers\Api\AdminController::class, 'mrrStats']);
        Route::get('/search',                   [\App\Http\Controllers\Api\AdminController::class, 'globalSearch']);
        Route::get('/users',                    [\App\Http\Controllers\Api\AdminController::class, 'users']);
        Route::patch('/users/{id}',             [\App\Http\Controllers\Api\AdminController::class, 'updateUser']);
        Route::post('/users/{id}/reset-password', [\App\Http\Controllers\Api\AdminController::class, 'resetUserPassword']);
        Route::get('/users/{id}/detail',        [\App\Http\Controllers\Api\AdminController::class, 'userDetail']);
        Route::post('/users/{id}/email',        [\App\Http\Controllers\Api\AdminController::class, 'emailUser']);
        Route::post('/users/{id}/impersonate',  [\App\Http\Controllers\Api\AdminController::class, 'impersonate']);
        Route::post('/users/bulk',              [\App\Http\Controllers\Api\AdminController::class, 'bulkUsers']);
        Route::get('/jobs',                     [\App\Http\Controllers\Api\AdminController::class, 'jobs']);
        Route::patch('/jobs/{id}',              [\App\Http\Controllers\Api\AdminController::class, 'updateJob']);
        Route::get('/subscriptions',            [\App\Http\Controllers\Api\AdminController::class, 'subscriptions']);
        Route::get('/plans',                    [\App\Http\Controllers\Api\AdminController::class, 'plans']);
        Route::patch('/plans/{id}',             [\App\Http\Controllers\Api\AdminController::class, 'updatePlan']);
        Route::get('/system-status',            [\App\Http\Controllers\Api\AdminController::class, 'systemStatus']);
        Route::get('/error-logs',               [\App\Http\Controllers\Api\AdminController::class, 'errorLogs']);
        Route::get('/contacts',                 [\App\Http\Controllers\Api\AdminController::class, 'contacts']);
        Route::get('/ai-screenings',            [\App\Http\Controllers\Api\AdminController::class, 'aiScreenings']);
        Route::get('/export',                   [\App\Http\Controllers\Api\AdminController::class, 'exportCsv']);
        // New routes
        Route::get('/users/{id}/notes',         [\App\Http\Controllers\Api\AdminController::class, 'userNotes']);
        Route::post('/users/{id}/notes',        [\App\Http\Controllers\Api\AdminController::class, 'addUserNote']);
        Route::delete('/users/{userId}/notes/{noteId}', [\App\Http\Controllers\Api\AdminController::class, 'deleteUserNote']);
        Route::get('/users/{id}/login-history', [\App\Http\Controllers\Api\AdminController::class, 'userLoginHistory']);
        Route::post('/users/{id}/grant-subscription', [\App\Http\Controllers\Api\AdminController::class, 'grantSubscription']);
        Route::get('/feature-flags',            [\App\Http\Controllers\Api\AdminController::class, 'featureFlags']);
        Route::put('/feature-flags/{name}',     [\App\Http\Controllers\Api\AdminController::class, 'updateFeatureFlag']);
        Route::get('/maintenance',              [\App\Http\Controllers\Api\AdminController::class, 'maintenanceStatus']);
        Route::post('/maintenance',             [\App\Http\Controllers\Api\AdminController::class, 'setMaintenance']);
        Route::post('/cache/flush',             [\App\Http\Controllers\Api\AdminController::class, 'flushCache']);
        Route::get('/queue-monitor',            [\App\Http\Controllers\Api\AdminController::class, 'queueMonitor']);
        Route::post('/queue/retry',             [\App\Http\Controllers\Api\AdminController::class, 'retryJob']);
        Route::get('/webhook-logs',             [\App\Http\Controllers\Api\AdminController::class, 'webhookLogs']);
        Route::get('/funnel',                   [\App\Http\Controllers\Api\AdminController::class, 'applicationFunnel']);
        Route::get('/revenue-by-country',       [\App\Http\Controllers\Api\AdminController::class, 'revenueByCountry']);
        Route::get('/db-table-sizes',           [\App\Http\Controllers\Api\AdminController::class, 'dbTableSizes']);
    });
});
