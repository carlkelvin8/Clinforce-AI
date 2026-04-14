<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CertificationTrackingController extends ApiController
{
    public function getCertificationTypes(Request $request)
    {
        $types = DB::table('certification_types')
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $types]);
    }

    public function getUserCertifications(Request $request)
    {
        $user = $this->requireAuth();
        
        $certifications = DB::table('user_certifications')
            ->join('certification_types', 'user_certifications.certification_type_id', '=', 'certification_types.id')
            ->where('user_certifications.user_id', $user->id)
            ->select([
                'user_certifications.*',
                'certification_types.name as certification_name',
                'certification_types.abbreviation',
                'certification_types.category',
                'certification_types.issuing_organization',
                'certification_types.requires_renewal',
                'certification_types.renewal_months'
            ])
            ->orderBy('user_certifications.expiration_date')
            ->get();

        return response()->json(['data' => $certifications]);
    }

    public function addCertification(Request $request)
    {
        $user = $this->requireAuth();
        
        $validated = $request->validate([
            'certification_type_id' => 'required|exists:certification_types,id',
            'certification_number' => 'nullable|string|max:100',
            'issued_date' => 'required|date',
            'expiration_date' => 'nullable|date|after:issued_date',
            'issuing_authority' => 'nullable|string|max:200',
            'verification_url' => 'nullable|url',
            'certificate_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'notes' => 'nullable|string|max:1000'
        ]);

        $certificationData = [
            'user_id' => $user->id,
            'certification_type_id' => $validated['certification_type_id'],
            'certification_number' => $validated['certification_number'],
            'issued_date' => $validated['issued_date'],
            'expiration_date' => $validated['expiration_date'],
            'issuing_authority' => $validated['issuing_authority'],
            'verification_url' => $validated['verification_url'],
            'notes' => $validated['notes'],
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Handle file upload
        if ($request->hasFile('certificate_file')) {
            $file = $request->file('certificate_file');
            $path = $file->store('certifications/' . $user->id, 'private');
            $certificationData['certificate_file_path'] = $path;
        }

        $certificationId = DB::table('user_certifications')->insertGetId($certificationData);

        // Create renewal tracking if certification requires renewal
        $certificationType = DB::table('certification_types')->find($validated['certification_type_id']);
        if ($certificationType->requires_renewal && $validated['expiration_date']) {
            $this->createRenewalTracking($certificationId, $validated['expiration_date'], $certificationType);
        }

        return response()->json(['message' => 'Certification added successfully', 'id' => $certificationId]);
    }

    public function updateCertification(Request $request, $certificationId)
    {
        $user = $this->requireAuth();
        
        $validated = $request->validate([
            'certification_number' => 'nullable|string|max:100',
            'issued_date' => 'date',
            'expiration_date' => 'nullable|date|after:issued_date',
            'status' => 'in:active,expired,suspended,revoked,pending_renewal',
            'issuing_authority' => 'nullable|string|max:200',
            'verification_url' => 'nullable|url',
            'certificate_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'notes' => 'nullable|string|max:1000'
        ]);

        $certification = DB::table('user_certifications')
            ->where('id', $certificationId)
            ->where('user_id', $user->id)
            ->first();

        if (!$certification) {
            return response()->json(['error' => 'Certification not found'], 404);
        }

        $updateData = array_filter($validated, function($value, $key) {
            return $value !== null && $key !== 'certificate_file';
        }, ARRAY_FILTER_USE_BOTH);

        // Handle file upload
        if ($request->hasFile('certificate_file')) {
            // Delete old file if exists
            if ($certification->certificate_file_path) {
                Storage::disk('private')->delete($certification->certificate_file_path);
            }
            
            $file = $request->file('certificate_file');
            $path = $file->store('certifications/' . $user->id, 'private');
            $updateData['certificate_file_path'] = $path;
        }

        $updateData['updated_at'] = now();

        DB::table('user_certifications')->where('id', $certificationId)->update($updateData);

        return response()->json(['message' => 'Certification updated successfully']);
    }

    public function deleteCertification(Request $request, $certificationId)
    {
        $user = $this->requireAuth();
        
        $certification = DB::table('user_certifications')
            ->where('id', $certificationId)
            ->where('user_id', $user->id)
            ->first();

        if (!$certification) {
            return response()->json(['error' => 'Certification not found'], 404);
        }

        // Delete certificate file if exists
        if ($certification->certificate_file_path) {
            Storage::disk('private')->delete($certification->certificate_file_path);
        }

        // Delete renewal tracking
        DB::table('certification_renewals')->where('user_certification_id', $certificationId)->delete();

        // Delete certification
        DB::table('user_certifications')->where('id', $certificationId)->delete();

        return response()->json(['message' => 'Certification deleted successfully']);
    }

    public function getRenewalsDue(Request $request)
    {
        $user = $this->requireAuth();
        
        $renewals = DB::table('certification_renewals')
            ->join('user_certifications', 'certification_renewals.user_certification_id', '=', 'user_certifications.id')
            ->join('certification_types', 'user_certifications.certification_type_id', '=', 'certification_types.id')
            ->where('user_certifications.user_id', $user->id)
            ->where('certification_renewals.status', '!=', 'completed')
            ->where('certification_renewals.renewal_due_date', '<=', now()->addMonths(6))
            ->select([
                'certification_renewals.*',
                'certification_types.name as certification_name',
                'certification_types.abbreviation',
                'user_certifications.certification_number'
            ])
            ->orderBy('certification_renewals.renewal_due_date')
            ->get();

        return response()->json(['data' => $renewals]);
    }

    public function startRenewal(Request $request, $renewalId)
    {
        $user = $this->requireAuth();
        
        $renewal = DB::table('certification_renewals')
            ->join('user_certifications', 'certification_renewals.user_certification_id', '=', 'user_certifications.id')
            ->where('certification_renewals.id', $renewalId)
            ->where('user_certifications.user_id', $user->id)
            ->first();

        if (!$renewal) {
            return response()->json(['error' => 'Renewal not found'], 404);
        }

        DB::table('certification_renewals')
            ->where('id', $renewalId)
            ->update([
                'status' => 'in_progress',
                'updated_at' => now()
            ]);

        return response()->json(['message' => 'Renewal process started']);
    }

    public function updateRenewalProgress(Request $request, $renewalId)
    {
        $user = $this->requireAuth();
        
        $validated = $request->validate([
            'completed_requirements' => 'array',
            'ceu_completed' => 'numeric|min:0',
            'notes' => 'nullable|string|max:1000'
        ]);

        $renewal = DB::table('certification_renewals')
            ->join('user_certifications', 'certification_renewals.user_certification_id', '=', 'user_certifications.id')
            ->where('certification_renewals.id', $renewalId)
            ->where('user_certifications.user_id', $user->id)
            ->first();

        if (!$renewal) {
            return response()->json(['error' => 'Renewal not found'], 404);
        }

        $updateData = [
            'updated_at' => now()
        ];

        if (isset($validated['completed_requirements'])) {
            $updateData['completed_requirements'] = json_encode($validated['completed_requirements']);
            
            // Calculate completion percentage
            $totalRequirements = count(json_decode($renewal->requirements_checklist, true) ?? []);
            $completedCount = count($validated['completed_requirements']);
            $updateData['completion_percentage'] = $totalRequirements > 0 ? ($completedCount / $totalRequirements) * 100 : 0;
        }

        if (isset($validated['ceu_completed'])) {
            $updateData['ceu_completed'] = $validated['ceu_completed'];
        }

        if (isset($validated['notes'])) {
            $updateData['notes'] = $validated['notes'];
        }

        DB::table('certification_renewals')->where('id', $renewalId)->update($updateData);

        return response()->json(['message' => 'Renewal progress updated']);
    }

    public function completeRenewal(Request $request, $renewalId)
    {
        $user = $this->requireAuth();
        
        $validated = $request->validate([
            'renewal_confirmation_number' => 'nullable|string|max:100',
            'renewal_fee_paid' => 'nullable|numeric|min:0',
            'new_expiration_date' => 'required|date|after:today'
        ]);

        $renewal = DB::table('certification_renewals')
            ->join('user_certifications', 'certification_renewals.user_certification_id', '=', 'user_certifications.id')
            ->where('certification_renewals.id', $renewalId)
            ->where('user_certifications.user_id', $user->id)
            ->first();

        if (!$renewal) {
            return response()->json(['error' => 'Renewal not found'], 404);
        }

        // Update renewal record
        DB::table('certification_renewals')
            ->where('id', $renewalId)
            ->update([
                'status' => 'completed',
                'completion_percentage' => 100,
                'renewal_submitted_date' => now(),
                'renewal_approved_date' => now(),
                'renewal_confirmation_number' => $validated['renewal_confirmation_number'],
                'renewal_fee_paid' => $validated['renewal_fee_paid'],
                'updated_at' => now()
            ]);

        // Update certification expiration date and status
        DB::table('user_certifications')
            ->where('id', $renewal->user_certification_id)
            ->update([
                'expiration_date' => $validated['new_expiration_date'],
                'status' => 'active',
                'updated_at' => now()
            ]);

        // Create next renewal tracking
        $certificationType = DB::table('certification_types')->find($renewal->certification_type_id);
        if ($certificationType->requires_renewal) {
            $this->createRenewalTracking(
                $renewal->user_certification_id, 
                $validated['new_expiration_date'], 
                $certificationType
            );
        }

        return response()->json(['message' => 'Certification renewal completed successfully']);
    }

    public function verifyCertification(Request $request, $certificationId)
    {
        $user = $this->requireAuth();
        
        $certification = DB::table('user_certifications')
            ->where('id', $certificationId)
            ->where('user_id', $user->id)
            ->first();

        if (!$certification) {
            return response()->json(['error' => 'Certification not found'], 404);
        }

        // Mock verification process
        $verificationData = [
            'verified' => true,
            'verification_date' => now(),
            'verification_source' => 'automated_check'
        ];

        DB::table('user_certifications')
            ->where('id', $certificationId)
            ->update([
                'verification_data' => json_encode($verificationData),
                'last_verified_at' => now(),
                'updated_at' => now()
            ]);

        return response()->json(['message' => 'Certification verified successfully', 'data' => $verificationData]);
    }

    public function getCertificationFile(Request $request, $certificationId)
    {
        $user = $this->requireAuth();
        
        $certification = DB::table('user_certifications')
            ->where('id', $certificationId)
            ->where('user_id', $user->id)
            ->first();

        if (!$certification || !$certification->certificate_file_path) {
            return response()->json(['error' => 'Certificate file not found'], 404);
        }

        if (!Storage::disk('private')->exists($certification->certificate_file_path)) {
            return response()->json(['error' => 'Certificate file not found on disk'], 404);
        }

        return Storage::disk('private')->download($certification->certificate_file_path);
    }

    public function getCertificationAnalytics(Request $request)
    {
        $user = $this->requireAuth();
        $userId = $user->id;

        $analytics = [
            'total_certifications' => DB::table('user_certifications')->where('user_id', $userId)->count(),
            'active_certifications' => DB::table('user_certifications')->where('user_id', $userId)->where('status', 'active')->count(),
            'expiring_soon' => DB::table('user_certifications')
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->where('expiration_date', '<=', now()->addMonths(6))
                ->count(),
            'renewals_due' => DB::table('certification_renewals')
                ->join('user_certifications', 'certification_renewals.user_certification_id', '=', 'user_certifications.id')
                ->where('user_certifications.user_id', $userId)
                ->where('certification_renewals.status', '!=', 'completed')
                ->where('certification_renewals.renewal_due_date', '<=', now()->addMonths(3))
                ->count(),
            'certifications_by_category' => DB::table('user_certifications')
                ->join('certification_types', 'user_certifications.certification_type_id', '=', 'certification_types.id')
                ->where('user_certifications.user_id', $userId)
                ->select('certification_types.category', DB::raw('count(*) as count'))
                ->groupBy('certification_types.category')
                ->get(),
            'renewal_completion_rate' => $this->calculateRenewalCompletionRate($userId)
        ];

        return response()->json(['data' => $analytics]);
    }

    private function createRenewalTracking($certificationId, $expirationDate, $certificationType)
    {
        $renewalDueDate = date('Y-m-d', strtotime($expirationDate . ' -' . ($certificationType->renewal_months ?? 24) . ' months'));

        $requirements = [];
        if ($certificationType->requires_ceu) {
            $requirements[] = 'Complete ' . $certificationType->required_ceu_hours . ' CEU hours';
        }
        $requirements[] = 'Submit renewal application';
        $requirements[] = 'Pay renewal fee';

        DB::table('certification_renewals')->insert([
            'user_certification_id' => $certificationId,
            'renewal_due_date' => $renewalDueDate,
            'status' => 'upcoming',
            'requirements_checklist' => json_encode($requirements),
            'ceu_required' => $certificationType->required_ceu_hours,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function calculateRenewalCompletionRate($userId)
    {
        $totalRenewals = DB::table('certification_renewals')
            ->join('user_certifications', 'certification_renewals.user_certification_id', '=', 'user_certifications.id')
            ->where('user_certifications.user_id', $userId)
            ->count();

        if ($totalRenewals === 0) {
            return 100;
        }

        $completedRenewals = DB::table('certification_renewals')
            ->join('user_certifications', 'certification_renewals.user_certification_id', '=', 'user_certifications.id')
            ->where('user_certifications.user_id', $userId)
            ->where('certification_renewals.status', 'completed')
            ->count();

        return round(($completedRenewals / $totalRenewals) * 100, 2);
    }
}