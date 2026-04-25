<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ReferralProgramController extends ApiController
{
    // ========== REFERRAL PROGRAMS ==========
    
    public function getPrograms(Request $request)
    {
        $user = $request->user();
        
        $programs = DB::table('referral_programs')
            ->where('employer_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($program) {
                $program->bonus_tiers = json_decode($program->bonus_tiers);
                $program->eligible_job_types = json_decode($program->eligible_job_types);
                
                // Get referral count
                $program->total_referrals = DB::table('employee_referrals')
                    ->where('referral_program_id', $program->id)
                    ->count();
                
                $program->successful_hires = DB::table('employee_referrals')
                    ->where('referral_program_id', $program->id)
                    ->where('status', 'hired')
                    ->count();
                
                return $program;
            });
        
        return response()->json(['data' => $programs]);
    }
    
    public function createProgram(Request $request)
    {
        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'bonus_amount' => 'required|numeric|min:0',
            'bonus_currency' => 'nullable|string|size:3',
            'bonus_type' => 'required|in:fixed,percentage,tiered',
            'days_until_eligible' => 'nullable|integer|min:0',
            'allow_external_referrals' => 'nullable|boolean',
        ]);
        
        if ($v->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $v->errors()], 422);
        }
        
        $user = $request->user();
        
        $programId = DB::table('referral_programs')->insertGetId([
            'employer_id' => $user->id,
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => true,
            'bonus_amount' => $request->bonus_amount,
            'bonus_currency' => $request->bonus_currency ?? 'USD',
            'bonus_type' => $request->bonus_type,
            'bonus_tiers' => json_encode($request->bonus_tiers ?? []),
            'days_until_eligible' => $request->days_until_eligible ?? 90,
            'allow_external_referrals' => $request->allow_external_referrals ?? true,
            'max_referrals_per_employee' => $request->max_referrals_per_employee,
            'eligible_job_types' => json_encode($request->eligible_job_types ?? []),
            'program_start_date' => $request->program_start_date ?? now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $program = DB::table('referral_programs')->find($programId);
        
        return response()->json(['message' => 'Referral program created', 'data' => $program], 201);
    }
    
    public function updateProgram(Request $request, $id)
    {
        $user = $request->user();
        
        $program = DB::table('referral_programs')
            ->where('id', $id)
            ->where('employer_id', $user->id)
            ->first();
        
        if (!$program) {
            return response()->json(['message' => 'Program not found'], 404);
        }
        
        $updateData = ['updated_at' => now()];
        
        if ($request->has('name')) $updateData['name'] = $request->name;
        if ($request->has('description')) $updateData['description'] = $request->description;
        if ($request->has('is_active')) $updateData['is_active'] = $request->is_active;
        if ($request->has('bonus_amount')) $updateData['bonus_amount'] = $request->bonus_amount;
        if ($request->has('days_until_eligible')) $updateData['days_until_eligible'] = $request->days_until_eligible;
        
        DB::table('referral_programs')->where('id', $id)->update($updateData);
        
        return response()->json(['message' => 'Program updated']);
    }
    
    // ========== REFERRALS ==========
    
    public function submitReferral(Request $request)
    {
        $v = Validator::make($request->all(), [
            'referral_program_id' => 'required|exists:referral_programs,id',
            'job_id' => 'nullable|exists:jobs_table,id',
            'candidate_first_name' => 'required|string|max:100',
            'candidate_last_name' => 'required|string|max:100',
            'candidate_email' => 'required|email|max:190',
            'candidate_phone' => 'nullable|string|max:30',
            'candidate_linkedin' => 'nullable|url',
            'relationship_description' => 'nullable|string',
            'why_good_fit' => 'nullable|string',
            'years_known' => 'nullable|integer|min:0',
            'resume' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ]);
        
        if ($v->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $v->errors()], 422);
        }
        
        $user = $request->user();
        
        // Check if candidate already referred
        $existing = DB::table('employee_referrals')
            ->where('candidate_email', $request->candidate_email)
            ->where('referrer_id', $user->id)
            ->whereIn('status', ['submitted', 'reviewed', 'interviewing'])
            ->exists();
        
        if ($existing) {
            return response()->json(['message' => 'You have already referred this candidate'], 409);
        }
        
        // Handle resume upload
        $resumePath = null;
        if ($request->hasFile('resume')) {
            $file = $request->file('resume');
            $filename = 'referral_' . time() . '_' . $file->getClientOriginalName();
            $resumePath = $file->storeAs('referrals/resumes', $filename, 'public');
        }
        
        $referralId = DB::table('employee_referrals')->insertGetId([
            'referral_program_id' => $request->referral_program_id,
            'referrer_id' => $user->id,
            'job_id' => $request->job_id,
            'candidate_first_name' => $request->candidate_first_name,
            'candidate_last_name' => $request->candidate_last_name,
            'candidate_email' => $request->candidate_email,
            'candidate_phone' => $request->candidate_phone,
            'candidate_resume_path' => $resumePath,
            'candidate_linkedin' => $request->candidate_linkedin,
            'relationship_description' => $request->relationship_description,
            'why_good_fit' => $request->why_good_fit,
            'years_known' => $request->years_known,
            'referral_source' => 'employee',
            'status' => 'submitted',
            'submitted_at' => now(),
            'bonus_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Log status history
        DB::table('referral_status_history')->insert([
            'employee_referral_id' => $referralId,
            'old_status' => null,
            'new_status' => 'submitted',
            'notes' => 'Referral submitted',
            'changed_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Create notification
        $this->createNotification($referralId, $user->id, 'status_update', 
            'Referral Submitted', 
            'Your referral for ' . $request->candidate_first_name . ' ' . $request->candidate_last_name . ' has been submitted successfully.'
        );
        
        return response()->json(['message' => 'Referral submitted successfully', 'data' => ['id' => $referralId]], 201);
    }
    
    public function getMyReferrals(Request $request)
    {
        $user = $request->user();
        
        $referrals = DB::table('employee_referrals as er')
            ->leftJoin('jobs_table as j', 'er.job_id', '=', 'j.id')
            ->leftJoin('referral_programs as rp', 'er.referral_program_id', '=', 'rp.id')
            ->where('er.referrer_id', $user->id)
            ->select(
                'er.*',
                'j.title as job_title',
                'rp.name as program_name',
                'rp.bonus_amount',
                'rp.bonus_currency'
            )
            ->orderBy('er.submitted_at', 'desc')
            ->get();
        
        return response()->json(['data' => $referrals]);
    }
    
    public function getAllReferrals(Request $request)
    {
        $user = $request->user();
        $status = $request->query('status');
        
        $query = DB::table('employee_referrals as er')
            ->join('users as u', 'er.referrer_id', '=', 'u.id')
            ->leftJoin('jobs_table as j', 'er.job_id', '=', 'j.id')
            ->leftJoin('referral_programs as rp', 'er.referral_program_id', '=', 'rp.id')
            ->where('rp.employer_id', $user->id)
            ->select(
                'er.*',
                'u.email as referrer_email',
                'j.title as job_title',
                'rp.name as program_name'
            );
        
        if ($status) {
            $query->where('er.status', $status);
        }
        
        $referrals = $query->orderBy('er.submitted_at', 'desc')->get();
        
        return response()->json(['data' => $referrals]);
    }
    
    public function updateReferralStatus(Request $request, $id)
    {
        $v = Validator::make($request->all(), [
            'status' => 'required|in:submitted,reviewed,interviewing,hired,rejected,withdrawn',
            'notes' => 'nullable|string',
            'rejection_reason' => 'nullable|string',
        ]);
        
        if ($v->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $v->errors()], 422);
        }
        
        $user = $request->user();
        
        $referral = DB::table('employee_referrals as er')
            ->join('referral_programs as rp', 'er.referral_program_id', '=', 'rp.id')
            ->where('er.id', $id)
            ->where('rp.employer_id', $user->id)
            ->select('er.*')
            ->first();
        
        if (!$referral) {
            return response()->json(['message' => 'Referral not found'], 404);
        }
        
        $updateData = [
            'status' => $request->status,
            'updated_at' => now(),
        ];
        
        if ($request->status === 'reviewed') {
            $updateData['reviewed_at'] = now();
        } elseif ($request->status === 'hired') {
            $updateData['hired_at'] = now();
            
            // Calculate bonus eligible date
            $program = DB::table('referral_programs')->find($referral->referral_program_id);
            $updateData['bonus_eligible_date'] = now()->addDays($program->days_until_eligible);
            $updateData['bonus_amount'] = $program->bonus_amount;
            $updateData['bonus_status'] = 'eligible';
        } elseif ($request->status === 'rejected') {
            $updateData['rejected_at'] = now();
            $updateData['rejection_reason'] = $request->rejection_reason;
            $updateData['bonus_status'] = 'forfeited';
        }
        
        DB::table('employee_referrals')->where('id', $id)->update($updateData);
        
        // Log status history
        DB::table('referral_status_history')->insert([
            'employee_referral_id' => $id,
            'old_status' => $referral->status,
            'new_status' => $request->status,
            'notes' => $request->notes,
            'changed_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Create notification
        $statusMessages = [
            'reviewed' => 'Your referral is being reviewed',
            'interviewing' => 'Your referral is interviewing',
            'hired' => 'Congratulations! Your referral was hired',
            'rejected' => 'Your referral was not selected',
        ];
        
        if (isset($statusMessages[$request->status])) {
            $this->createNotification($id, $referral->referrer_id, 'status_update',
                'Referral Status Update',
                $statusMessages[$request->status]
            );
        }
        
        return response()->json(['message' => 'Referral status updated']);
    }
    
    // ========== BONUSES ==========
    
    public function approveBonus(Request $request, $referralId)
    {
        $user = $request->user();
        
        $referral = DB::table('employee_referrals as er')
            ->join('referral_programs as rp', 'er.referral_program_id', '=', 'rp.id')
            ->where('er.id', $referralId)
            ->where('rp.employer_id', $user->id)
            ->where('er.status', 'hired')
            ->where('er.bonus_status', 'eligible')
            ->select('er.*', 'rp.bonus_amount', 'rp.bonus_currency')
            ->first();
        
        if (!$referral) {
            return response()->json(['message' => 'Referral not eligible for bonus'], 404);
        }
        
        // Update referral bonus status
        DB::table('employee_referrals')
            ->where('id', $referralId)
            ->update([
                'bonus_status' => 'approved',
                'bonus_approved_at' => now(),
                'approved_by' => $user->id,
                'updated_at' => now(),
            ]);
        
        // Create bonus record
        DB::table('referral_bonuses')->insert([
            'employee_referral_id' => $referralId,
            'referrer_id' => $referral->referrer_id,
            'amount' => $referral->bonus_amount,
            'currency' => $referral->bonus_currency,
            'status' => 'pending',
            'scheduled_payment_date' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Create notification
        $this->createNotification($referralId, $referral->referrer_id, 'bonus_eligible',
            'Bonus Approved!',
            'Your referral bonus of ' . $referral->bonus_currency . ' ' . number_format($referral->bonus_amount, 2) . ' has been approved!'
        );
        
        return response()->json(['message' => 'Bonus approved']);
    }
    
    public function markBonusPaid(Request $request, $bonusId)
    {
        $v = Validator::make($request->all(), [
            'payment_method' => 'required|in:payroll,check,direct_deposit,gift_card',
            'payment_reference' => 'nullable|string',
        ]);
        
        if ($v->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $v->errors()], 422);
        }
        
        $user = $request->user();
        
        $bonus = DB::table('referral_bonuses as rb')
            ->join('employee_referrals as er', 'rb.employee_referral_id', '=', 'er.id')
            ->join('referral_programs as rp', 'er.referral_program_id', '=', 'rp.id')
            ->where('rb.id', $bonusId)
            ->where('rp.employer_id', $user->id)
            ->select('rb.*', 'er.referrer_id')
            ->first();
        
        if (!$bonus) {
            return response()->json(['message' => 'Bonus not found'], 404);
        }
        
        DB::table('referral_bonuses')
            ->where('id', $bonusId)
            ->update([
                'status' => 'paid',
                'payment_method' => $request->payment_method,
                'payment_reference' => $request->payment_reference,
                'paid_at' => now(),
                'updated_at' => now(),
            ]);
        
        DB::table('employee_referrals')
            ->where('id', $bonus->employee_referral_id)
            ->update([
                'bonus_status' => 'paid',
                'bonus_paid_at' => now(),
            ]);
        
        // Create notification
        $this->createNotification($bonus->employee_referral_id, $bonus->referrer_id, 'bonus_paid',
            'Bonus Paid!',
            'Your referral bonus has been paid via ' . $request->payment_method
        );
        
        return response()->json(['message' => 'Bonus marked as paid']);
    }
    
    public function getPendingBonuses(Request $request)
    {
        $user = $request->user();
        
        $bonuses = DB::table('referral_bonuses as rb')
            ->join('employee_referrals as er', 'rb.employee_referral_id', '=', 'er.id')
            ->join('referral_programs as rp', 'er.referral_program_id', '=', 'rp.id')
            ->join('users as u', 'rb.referrer_id', '=', 'u.id')
            ->where('rp.employer_id', $user->id)
            ->where('rb.status', 'pending')
            ->select(
                'rb.*',
                'u.email as referrer_email',
                'er.candidate_first_name',
                'er.candidate_last_name'
            )
            ->orderBy('rb.scheduled_payment_date', 'asc')
            ->get();
        
        return response()->json(['data' => $bonuses]);
    }
    
    // ========== LEADERBOARD ==========
    
    public function getLeaderboard(Request $request)
    {
        $user = $request->user();
        $period = $request->query('period', 'all_time');
        $limit = $request->query('limit', 10);
        
        $query = DB::table('referral_leaderboard as rl')
            ->join('users as u', 'rl.referrer_id', '=', 'u.id')
            ->leftJoin('applicant_profiles as ap', 'u.id', '=', 'ap.user_id')
            ->where('rl.employer_id', $user->id)
            ->where('rl.period', $period);
        
        if ($period === 'year') {
            $query->where('rl.year', now()->year);
        } elseif ($period === 'quarter') {
            $query->where('rl.year', now()->year)
                  ->where('rl.quarter', now()->quarter);
        } elseif ($period === 'month') {
            $query->where('rl.year', now()->year)
                  ->where('rl.month', now()->month);
        }
        
        $leaderboard = $query->select(
                'rl.*',
                'u.email',
                'ap.first_name',
                'ap.last_name',
                'ap.avatar'
            )
            ->orderBy('rl.rank', 'asc')
            ->limit($limit)
            ->get();
        
        return response()->json(['data' => $leaderboard]);
    }
    
    public function refreshLeaderboard(Request $request)
    {
        $user = $request->user();
        
        // Get all referrers
        $referrers = DB::table('employee_referrals as er')
            ->join('referral_programs as rp', 'er.referral_program_id', '=', 'rp.id')
            ->where('rp.employer_id', $user->id)
            ->select('er.referrer_id')
            ->distinct()
            ->pluck('referrer_id');
        
        foreach ($referrers as $referrerId) {
            $this->updateLeaderboardForReferrer($user->id, $referrerId);
        }
        
        return response()->json(['message' => 'Leaderboard refreshed']);
    }
    
    private function updateLeaderboardForReferrer($employerId, $referrerId)
    {
        $stats = DB::table('employee_referrals as er')
            ->join('referral_programs as rp', 'er.referral_program_id', '=', 'rp.id')
            ->where('rp.employer_id', $employerId)
            ->where('er.referrer_id', $referrerId)
            ->selectRaw('
                COUNT(*) as total_referrals,
                SUM(CASE WHEN er.status = "hired" THEN 1 ELSE 0 END) as successful_hires,
                SUM(CASE WHEN er.status IN ("submitted", "reviewed", "interviewing") THEN 1 ELSE 0 END) as pending_referrals,
                COALESCE(SUM(CASE WHEN er.bonus_status IN ("approved", "paid") THEN er.bonus_amount ELSE 0 END), 0) as total_bonuses_earned,
                COALESCE(SUM(CASE WHEN er.bonus_status = "paid" THEN er.bonus_amount ELSE 0 END), 0) as total_bonuses_paid
            ')
            ->first();
        
        $successRate = $stats->total_referrals > 0 
            ? round(($stats->successful_hires / $stats->total_referrals) * 100, 2) 
            : 0;
        
        DB::table('referral_leaderboard')->updateOrInsert(
            [
                'employer_id' => $employerId,
                'referrer_id' => $referrerId,
                'period' => 'all_time',
                'year' => null,
                'quarter' => null,
                'month' => null,
            ],
            [
                'total_referrals' => $stats->total_referrals,
                'successful_hires' => $stats->successful_hires,
                'pending_referrals' => $stats->pending_referrals,
                'total_bonuses_earned' => $stats->total_bonuses_earned,
                'total_bonuses_paid' => $stats->total_bonuses_paid,
                'success_rate' => $successRate,
                'last_updated_at' => now(),
                'updated_at' => now(),
            ]
        );
        
        // Update ranks
        $this->updateRanks($employerId, 'all_time');
    }
    
    private function updateRanks($employerId, $period)
    {
        $leaderboard = DB::table('referral_leaderboard')
            ->where('employer_id', $employerId)
            ->where('period', $period)
            ->orderBy('successful_hires', 'desc')
            ->orderBy('total_bonuses_earned', 'desc')
            ->get();
        
        $rank = 1;
        foreach ($leaderboard as $entry) {
            DB::table('referral_leaderboard')
                ->where('id', $entry->id)
                ->update(['rank' => $rank]);
            $rank++;
        }
    }
    
    // ========== ANALYTICS ==========
    
    public function getAnalytics(Request $request)
    {
        $user = $request->user();
        $days = $request->query('days', 30);
        
        $startDate = Carbon::now()->subDays($days);
        
        $stats = DB::table('employee_referrals as er')
            ->join('referral_programs as rp', 'er.referral_program_id', '=', 'rp.id')
            ->where('rp.employer_id', $user->id)
            ->where('er.submitted_at', '>=', $startDate)
            ->selectRaw('
                COUNT(*) as total_referrals,
                SUM(CASE WHEN er.status = "hired" THEN 1 ELSE 0 END) as hired_count,
                SUM(CASE WHEN er.status = "rejected" THEN 1 ELSE 0 END) as rejected_count,
                SUM(CASE WHEN er.status IN ("submitted", "reviewed", "interviewing") THEN 1 ELSE 0 END) as pending_count,
                COALESCE(SUM(CASE WHEN er.bonus_status = "approved" THEN er.bonus_amount ELSE 0 END), 0) as bonuses_approved,
                COALESCE(SUM(CASE WHEN er.bonus_status = "paid" THEN er.bonus_amount ELSE 0 END), 0) as bonuses_paid
            ')
            ->first();
        
        $conversionRate = $stats->total_referrals > 0 
            ? round(($stats->hired_count / $stats->total_referrals) * 100, 2) 
            : 0;
        
        // Referrals by status
        $byStatus = DB::table('employee_referrals as er')
            ->join('referral_programs as rp', 'er.referral_program_id', '=', 'rp.id')
            ->where('rp.employer_id', $user->id)
            ->select('er.status', DB::raw('COUNT(*) as count'))
            ->groupBy('er.status')
            ->get();
        
        // Top referrers
        $topReferrers = DB::table('employee_referrals as er')
            ->join('referral_programs as rp', 'er.referral_program_id', '=', 'rp.id')
            ->join('users as u', 'er.referrer_id', '=', 'u.id')
            ->where('rp.employer_id', $user->id)
            ->where('er.submitted_at', '>=', $startDate)
            ->select('u.email', DB::raw('COUNT(*) as referral_count'))
            ->groupBy('u.id', 'u.email')
            ->orderBy('referral_count', 'desc')
            ->limit(5)
            ->get();
        
        return response()->json([
            'data' => [
                'total_referrals' => $stats->total_referrals,
                'hired_count' => $stats->hired_count,
                'rejected_count' => $stats->rejected_count,
                'pending_count' => $stats->pending_count,
                'conversion_rate' => $conversionRate,
                'bonuses_approved' => $stats->bonuses_approved,
                'bonuses_paid' => $stats->bonuses_paid,
                'by_status' => $byStatus,
                'top_referrers' => $topReferrers,
            ]
        ]);
    }
    
    // ========== NOTIFICATIONS ==========
    
    public function getNotifications(Request $request)
    {
        $user = $request->user();
        
        $notifications = DB::table('referral_notifications')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
        
        return response()->json(['data' => $notifications]);
    }
    
    public function markNotificationRead(Request $request, $id)
    {
        $user = $request->user();
        
        DB::table('referral_notifications')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->update([
                'is_read' => true,
                'read_at' => now(),
                'updated_at' => now(),
            ]);
        
        return response()->json(['message' => 'Notification marked as read']);
    }
    
    private function createNotification($referralId, $userId, $type, $title, $message)
    {
        DB::table('referral_notifications')->insert([
            'employee_referral_id' => $referralId,
            'user_id' => $userId,
            'notification_type' => $type,
            'title' => $title,
            'message' => $message,
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
