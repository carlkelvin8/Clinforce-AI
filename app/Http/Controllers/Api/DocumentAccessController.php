<?php

namespace App\Http\Controllers\Api;

use App\Models\DocumentAccessPayment;
use App\Models\User;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentAccessController extends ApiController
{
    private CurrencyService $currency;

    public function __construct(CurrencyService $currency)
    {
        $this->currency = $currency;
    }

    /**
     * Check if employer has document access for an applicant
     */
    public function checkAccess(Request $request): JsonResponse
    {
        $u = $this->requireAuth();

        if (!in_array($u->role, ['employer', 'agency'], true)) {
            return $this->fail('Only employers can check document access', null, 403);
        }

        $applicantId = $request->input('applicant_id');
        if (!$applicantId) {
            return $this->fail('Applicant ID required', ['applicant_id' => ['Required']], 422);
        }

        $hasAccess = DocumentAccessPayment::hasAccess($u->id, $applicantId);

        return $this->ok([
            'has_access' => $hasAccess,
            'applicant_id' => $applicantId,
        ]);
    }

    /**
     * Get pricing for document access
     */
    public function pricing(Request $request): JsonResponse
    {
        $u = $this->requireAuth();

        if (!in_array($u->role, ['employer', 'agency'], true)) {
            return $this->fail('Only employers can view pricing', null, 403);
        }

        // Base price in USD
        $perApplicantUSD = 999; // $9.99 per applicant

        // Get employer's currency context
        $ctx = $this->currency->getEmployerCurrencyContext($u instanceof User ? $u : User::find($u->id));

        // Convert to employer's currency
        $converted = $this->currency->convertAmount($perApplicantUSD, 'USD', $ctx['currency_code']);

        return $this->ok([
            'per_applicant' => [
                'amount_cents' => $converted,
                'currency_code' => $ctx['currency_code'],
                'currency_symbol' => $ctx['currency_symbol'],
                'formatted' => $ctx['currency_symbol'] . number_format($converted / 100, 2),
            ],
            'description' => 'One-time payment per applicant to unlock resume and all documents',
        ]);
    }

    /**
     * Purchase document access for an applicant
     */
    public function purchase(Request $request): JsonResponse
    {
        $u = $this->requireAuth();

        if (!in_array($u->role, ['employer', 'agency'], true)) {
            return $this->fail('Only employers can purchase document access', null, 403);
        }

        $v = $request->validate([
            'applicant_id' => ['required', 'integer', 'exists:users,id'],
            'application_id' => ['nullable', 'integer', 'exists:job_applications,id'],
        ]);

        // Check if already has access
        $existing = DocumentAccessPayment::query()
            ->where('employer_user_id', $u->id)
            ->where('applicant_user_id', $v['applicant_id'])
            ->where('status', 'paid')
            ->first();

        if ($existing) {
            return $this->ok($existing, 'You already have access to this applicant\'s documents');
        }

        // Get pricing
        $perApplicantUSD = 999; // $9.99
        $ctx = $this->currency->getEmployerCurrencyContext($u instanceof User ? $u : User::find($u->id));
        $amountCents = $this->currency->convertAmount($perApplicantUSD, 'USD', $ctx['currency_code']);

        // Create payment record as pending, then charge
        $payment = DocumentAccessPayment::create([
            'employer_user_id' => $u->id,
            'applicant_user_id' => $v['applicant_id'],
            'application_id' => $v['application_id'] ?? null,
            'access_type' => 'per_applicant',
            'amount_cents' => $amountCents,
            'currency_code' => $ctx['currency_code'],
            'status' => 'pending',
            'provider' => 'stripe',
            'paid_at' => null,
        ]);

        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            $paymentMethods = \Stripe\PaymentMethod::all([
                'customer' => $u->stripe_customer_id,
                'type' => 'card',
            ]);

            if (!$u->stripe_customer_id || count($paymentMethods->data) === 0) {
                $payment->delete();
                return $this->fail('Please add a payment method before purchasing document access.', null, 422);
            }

            $intent = \Stripe\PaymentIntent::create([
                'amount' => $amountCents,
                'currency' => strtolower($ctx['currency_code']),
                'customer' => $u->stripe_customer_id,
                'payment_method' => $paymentMethods->data[0]->id,
                'confirm' => true,
                'off_session' => true,
                'description' => 'Document access: applicant #' . $v['applicant_id'],
                'metadata' => [
                    'employer_user_id' => $u->id,
                    'applicant_user_id' => $v['applicant_id'],
                ],
            ]);

            if ($intent->status !== 'succeeded') {
                $payment->delete();
                return $this->fail('Payment did not succeed. Status: ' . $intent->status, null, 402);
            }

            $payment->update([
                'status' => 'paid',
                'provider_ref' => $intent->id,
                'paid_at' => now(),
            ]);

        } catch (\Stripe\Exception\CardException $e) {
            $payment->delete();
            return $this->fail('Card declined: ' . $e->getMessage(), ['stripe' => $e->getMessage()], 402);
        } catch (\Exception $e) {
            $payment->delete();
            \Log::error('Document access payment failed', ['error' => $e->getMessage()]);
            return $this->fail('Payment failed: ' . $e->getMessage(), null, 500);
        }

        return $this->ok($payment->fresh(), 'Document access granted', 201);
    }

    /**
     * List all document access payments for employer
     */
    public function index(): JsonResponse
    {
        $u = $this->requireAuth();

        if (!in_array($u->role, ['employer', 'agency'], true)) {
            return $this->fail('Only employers can view document access history', null, 403);
        }

        $payments = DocumentAccessPayment::query()
            ->with(['applicant.applicantProfile'])
            ->where('employer_user_id', $u->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return $this->ok($payments);
    }
}
