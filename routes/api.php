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
/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/
Route::get('/health', [HealthController::class, 'index']);
Route::get('/plans', [PlansController::class, 'index']);

// Public job browsing
Route::get('/public/jobs', [JobsController::class, 'publicIndex']);
Route::get('/public/jobs/{job}', [JobsController::class, 'publicShow']);

// Stripe webhook (must be outside auth middleware)
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle']);

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

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
    Route::get('/debug/whoami', function (\Illuminate\Http\Request $request) {
        $u = $request->user();
        return response()->json([
            'authed' => (bool) $u,
            'id' => $u?->id,
            'role' => $u?->role,
            'email' => $u?->email,
            'token_id' => $u?->currentAccessToken()?->id,
            'token_name' => $u?->currentAccessToken()?->name,
        ]);
    });
    
    // Profile (me)
    Route::get('/me', [ProfilesController::class, 'me']);
    Route::get('/profiles/{userId}', [ProfilesController::class, 'show']);
    Route::put('/user/settings', [UsersController::class, 'updateSettings']);
    Route::put('/me/applicant', [ProfilesController::class, 'upsertApplicant']);
    Route::put('/me/employer', [ProfilesController::class, 'upsertEmployer']);
    Route::put('/me/agency', [ProfilesController::class, 'upsertAgency']);
    Route::get('/me/employer', [ProfilesController::class, 'meEmployer']);
    Route::post('/me/employer/logo', [ProfilesController::class, 'uploadEmployerLogo']);

    // Documents
    Route::get('/documents', [DocumentsController::class, 'index']);
    Route::post('/documents', [DocumentsController::class, 'store']);
    Route::delete('/documents/{document}', [DocumentsController::class, 'destroy']);

    // Jobs (owner)
    Route::get('/jobs', [JobsController::class, 'index']);
    Route::post('/jobs', [JobsController::class, 'store']);
    Route::get('/jobs/{job}', [JobsController::class, 'show']);
    Route::put('/jobs/{job}', [JobsController::class, 'update']);
    Route::post('/jobs/{job}/publish', [JobsController::class, 'publish']);
    Route::post('/jobs/{job}/archive', [JobsController::class, 'archive']);
    Route::delete('/jobs/{job}', [JobsController::class, 'destroy']);

    // Applications
    Route::post('/jobs/{job}/apply', [JobApplicationsController::class, 'apply']);
    Route::get('/applications', [JobApplicationsController::class, 'index']);
    Route::get('/applications/{application}', [JobApplicationsController::class, 'show']);
    Route::post('/applications/{application}/status', [JobApplicationsController::class, 'updateStatus']);
    Route::get('/applications/{application}/resume', [JobApplicationsController::class, 'viewResume']);

    // Talent Search
    Route::get('/talent-search', [TalentSearchController::class, 'index']);

    // Invitations
    Route::get('/invitations', [App\Http\Controllers\Api\InvitationController::class, 'index']);
    Route::post('/invitations', [App\Http\Controllers\Api\InvitationController::class, 'store']);
    Route::post('/invitations/{invitation}/accept', [App\Http\Controllers\Api\InvitationController::class, 'accept']);

    // Interviews
 // routes/api.php
        Route::get('/interviews', [InterviewsController::class, 'index']);
        Route::post('/applications/{application}/interviews', [InterviewsController::class, 'store']);
        Route::put('/interviews/{interview}', [InterviewsController::class, 'update']);
        Route::post('/interviews/{interview}/cancel', [InterviewsController::class, 'cancel']);

    // Subscriptions / Payments
    Route::get('/subscriptions/debug', function() {
        $user = auth('sanctum')->user();
        return response()->json([
            'authenticated' => $user ? true : false,
            'user' => $user ? $user->only(['id', 'email', 'role']) : null,
            'subscriptions' => $user ? \App\Models\Subscription::where('user_id', $user->id)->with('plan')->get() : [],
        ]);
    });
    
    Route::get('/billing/test-currency', function() {
        $user = \App\Models\User::find(9006);
        $service = app(\App\Services\CurrencyService::class);
        $ctx = $service->getEmployerCurrencyContext($user);
        $plan = \App\Models\Plan::find(4);
        $conversion = $service->convertPlanPriceForUser($plan, $ctx);
        
        return response()->json([
            'context' => $ctx,
            'plan' => $plan->only(['id', 'name', 'price_cents', 'currency']),
            'conversion' => $conversion,
        ]);
    });
    
    Route::get('/subscriptions', [SubscriptionController::class, 'index']);
    
    Route::get('/invoices', [SubscriptionController::class, 'invoices']);
    
    // Document Access (separate from subscription)
    Route::get('/document-access/check', [App\Http\Controllers\Api\DocumentAccessController::class, 'checkAccess']);
    Route::get('/document-access/pricing', [App\Http\Controllers\Api\DocumentAccessController::class, 'pricing']);
    Route::post('/document-access/purchase', [App\Http\Controllers\Api\DocumentAccessController::class, 'purchase']);
    Route::get('/document-access', [App\Http\Controllers\Api\DocumentAccessController::class, 'index']);
    
    // AI Chatbot
    Route::post('/chatbot', [ChatbotController::class, 'chat']);
    Route::post('/subscriptions', [SubscriptionController::class, 'store']);
    Route::post('/subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel']);

    Route::get('/payments', [PaymentsController::class, 'index']);
    Route::post('/payments', [PaymentsController::class, 'store']);

    // Verification requests
    Route::get('/verification-requests', [VerificationRequestsController::class, 'index']);
    Route::post('/verification-requests', [VerificationRequestsController::class, 'store']);
    Route::post('/verification-requests/{verificationRequest}/review', [VerificationRequestsController::class, 'review']);

        Route::get('/conversations', [MessagesController::class, 'index']);
    Route::post('/conversations', [MessagesController::class, 'store']);
    Route::get('/conversations/{conversation}', [MessagesController::class, 'show']);
    Route::post('/conversations/{conversation}/messages', [MessagesController::class, 'send']);
    Route::post('/conversations/{conversation}/read', [MessagesController::class, 'markRead']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/users', [UsersController::class, 'index']);
    });
    // AI screenings (read-only)
    Route::get('/ai-screenings', [AiScreeningsController::class, 'index']);

    // Audit logs (admin-only)
    Route::get('/audit-logs', [AuditLogsController::class, 'index']);

    // Billing currency
    Route::get('/billing/currency', [BillingController::class, 'currency']);
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
    Route::get('/applicants/test', function() {
        return response()->json([
            'message' => 'Applicants endpoint is working',
            'user' => auth('sanctum')->user() ? auth('sanctum')->user()->only(['id', 'email', 'role']) : null,
        ]);
    });
    Route::get('/applicants', [ApplicantsController::class, 'index']);
    Route::get('/applicants/{userId}', [ApplicantsController::class, 'show']);
    Route::get('/applications/{applicationId}/applicant', [ApplicantsController::class, 'showFromApplication']);

    // Secure document downloads (with subscription gates)
    Route::get('/documents/{documentId}/download', [SecureDocumentController::class, 'download']);
    Route::get('/documents/{documentId}/stream', [SecureDocumentController::class, 'stream']);

    // Notifications
    Route::get('/notifications', [NotificationsController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [NotificationsController::class, 'unreadCount'])->name('notifications.unreadCount');
    Route::post('/notifications/read', [NotificationsController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationsController::class, 'markAllRead'])->name('notifications.readAll');
    Route::get('/notifications/stream', [NotificationsController::class, 'stream'])->name('notifications.stream');
    Route::get('/notifications/preferences', [NotificationsController::class, 'preferencesGet'])->name('notifications.preferences.get');
    Route::put('/notifications/preferences', [NotificationsController::class, 'preferencesUpdate'])->name('notifications.preferences.update');
});




Route::get('/debug/whoami', function (\Illuminate\Http\Request $request) {
    $u = $request->user();
    return response()->json([
        'id' => $u?->id,
        'role' => $u?->role,
        'email' => $u?->email,
        'x_user_id' => $request->header('X-User-Id'),
    ]);
});
