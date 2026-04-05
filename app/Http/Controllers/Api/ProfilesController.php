<?php
// app/Http/Controllers/Api/ProfilesController.php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\AgencyProfileUpsertRequest;
use App\Http\Requests\Api\ApplicantProfileUpsertRequest;
use App\Http\Requests\Api\EmployerProfileUpsertRequest;
use App\Models\AgencyProfile;
use App\Models\ApplicantProfile;
use App\Models\EmployerProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfilesController extends ApiController
{
    public function show(int $userId): JsonResponse
    {
        $u = $this->requireAuth();
        // Only staff can view candidate profiles
        if (!in_array($u->role, ['admin', 'employer', 'agency'], true)) {
            return $this->fail('Forbidden', null, 403);
        }

        $target = User::with('applicantProfile')->find($userId);
        if (!$target || $target->role !== 'applicant') {
            return $this->fail('Candidate not found', null, 404);
        }

        $p = $target->applicantProfile;

        $avatarUrl = null;
        if ($p?->avatar) {
            $path = ltrim($p->avatar, '/');
            $avatarUrl = str_starts_with($path, 'uploads/')
                ? '/' . $path
                : '/storage/' . $path;
        }

        $data = [
            'id' => $target->id,
            'name' => trim($p->first_name . ' ' . $p->last_name) ?: ($p->public_display_name ?: 'Candidate'),
            'first_name' => $p?->first_name,
            'last_name' => $p?->last_name, 
            'headline' => $p?->headline,
            'summary' => $p?->summary,
            'city' => $p?->city,
            'state' => $p?->state,
            'country' => $p?->country,
            'years_experience' => $p?->years_experience,
            'bio' => $p?->bio, // or summary?
            'avatar' => $avatarUrl,
            'email' => $target->email,
            'phone' => $target->phone,
            'updated_at' => $p?->updated_at,
        ];

        return $this->ok($data);
    }

    public function meApplicant(): JsonResponse
    {
        $u = $this->requireAuth();
        $p = ApplicantProfile::query()->where('user_id', $u->id)->first();
        return $this->ok($p);
    }

    public function meEmployer(): JsonResponse
    {
        $u = $this->requireAuth();
        if ($u->role !== 'employer' && $u->role !== 'admin') {
            return $this->fail('Only employers can view employer profile', null, 403);
        }

        $p = EmployerProfile::query()->where('user_id', $u->id)->first();
        if (!$p) {
            return $this->ok(null);
        }

        $logo = asset('assets/brand/default-hospital-logo.svg');

        $data = [
            'user_id' => $p->user_id,
            'logo' => $logo,
            'logo_path' => null,
            'business_name' => $p->business_name,
            'business_type' => $p->business_type,
            'country' => $p->country,
            'billing_currency_code' => $p->billing_currency_code,
            'state' => $p->state,
            'city' => $p->city,
            'zip_code' => $p->zip_code,
            'tax_id' => $p->tax_id,
            'address_line' => $p->address_line,
            'website_url' => $p->website_url,
            'verification_status' => $p->verification_status,
            'data_retention_days' => $p->data_retention_days,
            'updated_at' => optional($p->updated_at)->toISOString(),
        ];

        return $this->ok($data);
    }

    public function uploadEmployerLogo(Request $request): JsonResponse
    {
        $u = $this->requireAuth();
        if ($u->role !== 'employer' && $u->role !== 'admin') {
            return $this->fail('Only employers can upload logo', null, 403);
        }

        $profile = EmployerProfile::query()->where('user_id', $u->id)->first();
        if (!$profile) {
            $profile = new EmployerProfile();
            $profile->user_id = $u->id;
            $profile->business_name = $request->input('business_name')
                ?: (is_string($u->email) && strpos($u->email, '@') !== false ? explode('@', $u->email)[0] : 'Company');
            $profile->business_type = $request->input('business_type') ?: ($profile->business_type ?: 'clinic');
            $profile->save();
        }

        $url = asset('assets/brand/default-hospital-logo.svg');

        return $this->ok([
            'logo' => $url,
            'logo_path' => null,
            'profile' => $profile,
        ], 'Logo uploaded');
    }

    public function me(): JsonResponse
    {
        $u = $this->requireAuth();
        $u->load(['applicantProfile', 'employerProfile', 'agencyProfile']);

        $data = $u->toArray();
        $avatarUrl = $u->avatar_url;
        $data['avatar'] = $avatarUrl;
        $data['avatar_url'] = $avatarUrl;

        return $this->ok($data);
    }

    public function upsertApplicant(ApplicantProfileUpsertRequest $request): JsonResponse
    {
        $u = $this->requireAuth();
        if ($u->role !== 'applicant') return $this->fail('Only applicants can update applicant profile', null, 403);

        $v = $request->validated();

        $existing = ApplicantProfile::query()->where('user_id', $u->id)->first();

        // Only update public_display_name when name fields are provided
        $extra = [];
        if (isset($v['first_name']) && isset($v['last_name'])) {
            $extra['public_display_name'] = $v['first_name'] . ' ' . $v['last_name'];
        }

        if ($existing) {
            $existing->update(array_merge($v, $extra));
            $profile = $existing->fresh();
        } else {
            $profile = ApplicantProfile::query()->create(
                array_merge(['user_id' => $u->id], $v, $extra)
            );
        }

        return $this->ok($profile, 'Profile saved');
    }

    public function upsertEmployer(EmployerProfileUpsertRequest $request): JsonResponse
    {
        $u = $this->requireAuth();
        if ($u->role !== 'employer') return $this->fail('Only employers can update employer profile', null, 403);

        $existing = EmployerProfile::query()->where('user_id', $u->id)->first();

        $data = $request->validated();
        $country = $data['country'] ?? ($existing?->country ?? null);

        $billingCurrency = ($country && strtolower($country) === 'philippines') ? 'PHP' : 'USD';

        $payload = array_merge(
            $data,
            [
                'country' => $country,
                'billing_currency_code' => $billingCurrency,
            ]
        );

        $profile = EmployerProfile::query()->updateOrCreate(
            ['user_id' => $u->id],
            $payload
        );

        return $this->ok($profile, 'Profile saved');
    }

    public function upsertAgency(AgencyProfileUpsertRequest $request): JsonResponse
    {
        $u = $this->requireAuth();
        if ($u->role !== 'agency') return $this->fail('Only agencies can update agency profile', null, 403);

        $profile = AgencyProfile::query()->updateOrCreate(
            ['user_id' => $u->id],
            $request->validated()
        );

        return $this->ok($profile, 'Profile saved');
    }
}
