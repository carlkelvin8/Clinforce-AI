<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\VerificationRequest;
use App\Models\AuditLog;
use App\Models\Contact;
use App\Models\AiScreening;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends ApiController
{
    private function requireAdmin(): User
    {
        $u = $this->requireAuth();
        if ($u->role !== 'admin') abort(403, 'Admin only');
        return $u;
    }

    // ── Dashboard Stats ──────────────────────────────────────────────────
    public function stats(): JsonResponse
    {
        $this->requireAdmin();

        $totalUsers       = User::count();
        $totalEmployers   = User::where('role', 'employer')->count();
        $totalCandidates  = User::where('role', 'applicant')->count();
        $totalJobs        = Job::count();
        $activeJobs       = Job::where('status', 'published')->count();
        $totalApps        = JobApplication::count();
        $totalSubs        = Subscription::where('status', 'active')->count();
        $pendingVerifs    = VerificationRequest::where('status', 'pending')->count();

        $revenue = Subscription::where('status', 'active')
            ->sum('amount_cents') / 100;

        $recentUsers = User::orderByDesc('created_at')->limit(5)
            ->get(['id', 'role', 'email', 'phone', 'status', 'created_at']);

        $driver = DB::connection()->getDriverName();
        $monthSql = $driver === 'sqlite' ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')";

        $userGrowth = User::selectRaw("$monthSql as month, COUNT(*) as count")
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return $this->ok([
            'total_users'       => $totalUsers,
            'total_employers'   => $totalEmployers,
            'total_candidates'  => $totalCandidates,
            'total_jobs'        => $totalJobs,
            'active_jobs'       => $activeJobs,
            'total_applications'=> $totalApps,
            'active_subscriptions' => $totalSubs,
            'pending_verifications' => $pendingVerifs,
            'total_revenue'     => round((float)$revenue, 2),
            'recent_users'      => $recentUsers,
            'user_growth'       => $userGrowth,
        ]);
    }

    // ── Users ────────────────────────────────────────────────────────────
    public function users(Request $request): JsonResponse
    {
        $this->requireAdmin();

        $q     = trim((string) $request->query('q', ''));
        $role  = $request->query('role');
        $status = $request->query('status');

        $query = User::query()->orderByDesc('id');

        if ($role)   $query->where('role', $role);
        if ($status) $query->where('status', $status);
        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('email', 'like', "%{$q}%")
                   ->orWhere('phone', 'like', "%{$q}%");
                if (ctype_digit($q)) $qq->orWhere('id', (int)$q);
            });
        }

        return $this->ok($query->paginate(20));
    }

    public function updateUser(Request $request, int $id): JsonResponse
    {
        $this->requireAdmin();

        $user = User::findOrFail($id);

        $data = $request->validate([
            'status' => 'sometimes|in:active,suspended,banned',
            'role'   => 'sometimes|in:admin,employer,agency,applicant',
        ]);

        $user->fill($data)->save();

        return $this->ok(['message' => 'User updated.', 'user' => $user]);
    }

    public function resetUserPassword(Request $request, int $id): JsonResponse
    {
        $this->requireAdmin();

        $request->validate(['password' => 'required|string|min:8']);

        $user = User::findOrFail($id);
        $user->password_hash = Hash::make($request->input('password'));
        $user->save();

        return $this->ok(['message' => 'Password reset.']);
    }

    // ── Jobs ─────────────────────────────────────────────────────────────
    public function jobs(Request $request): JsonResponse
    {
        $this->requireAdmin();

        $q      = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        $query = Job::query()->with('owner:id,email,role')->orderByDesc('id');

        if ($status) $query->where('status', $status);
        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('title', 'like', "%{$q}%");
            });
        }

        return $this->ok($query->paginate(20));
    }

    public function updateJob(Request $request, int $id): JsonResponse
    {
        $this->requireAdmin();

        $job = Job::findOrFail($id);
        $data = $request->validate(['status' => 'required|in:draft,published,archived,closed']);
        $job->fill($data)->save();

        return $this->ok(['message' => 'Job updated.']);
    }

    // ── Subscriptions ────────────────────────────────────────────────────
    public function subscriptions(Request $request): JsonResponse
    {
        $this->requireAdmin();

        $status = $request->query('status');
        $q      = trim((string) $request->query('q', ''));

        $query = Subscription::query()
            ->with(['user:id,email,role', 'plan:id,name,price_cents,currency'])
            ->orderByDesc('id');

        if ($status) $query->where('status', $status);
        if ($q !== '') {
            $query->whereHas('user', fn($uq) => $uq->where('email', 'like', "%{$q}%"));
        }

        return $this->ok($query->paginate(20));
    }

    // ── Analytics ────────────────────────────────────────────────────────
    public function analytics(Request $request): JsonResponse
    {
        $this->requireAdmin();

        $period = $request->query('period', 'monthly');

        // Cache admin analytics for 10 minutes per period
        $cacheKey = "admin_analytics:{$period}";
        $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
        if ($cached) return $this->ok($cached);

        $driver = DB::connection()->getDriverName();

        switch ($period) {
            case 'weekly':
                $format = $driver === 'sqlite' ? "strftime('%Y-%W', created_at)" : "DATE_FORMAT(created_at, '%Y-%u')";
                $revenue = Subscription::selectRaw("$format as period, SUM(amount_cents)/100 as revenue, COUNT(*) as count")
                    ->where('created_at', '>=', now()->subWeeks(12))
                    ->groupBy('period')->orderBy('period')->get();
                $users = User::selectRaw("$format as period, COUNT(*) as count")
                    ->where('created_at', '>=', now()->subWeeks(12))
                    ->groupBy('period')->orderBy('period')->get();
                break;
            case 'yearly':
                $format = $driver === 'sqlite' ? "strftime('%Y', created_at)" : "DATE_FORMAT(created_at, '%Y')";
                $revenue = Subscription::selectRaw("$format as period, SUM(amount_cents)/100 as revenue, COUNT(*) as count")
                    ->where('created_at', '>=', now()->subYears(5))
                    ->groupBy('period')->orderBy('period')->get();
                $users = User::selectRaw("$format as period, COUNT(*) as count")
                    ->where('created_at', '>=', now()->subYears(5))
                    ->groupBy('period')->orderBy('period')->get();
                break;
            default: // monthly
                $format = $driver === 'sqlite' ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')";
                $revenue = Subscription::selectRaw("$format as period, SUM(amount_cents)/100 as revenue, COUNT(*) as count")
                    ->where('created_at', '>=', now()->subMonths(12))
                    ->groupBy('period')->orderBy('period')->get();
                $users = User::selectRaw("$format as period, COUNT(*) as count")
                    ->where('created_at', '>=', now()->subMonths(12))
                    ->groupBy('period')->orderBy('period')->get();
        }

        // Revenue by plan
        $byPlan = Subscription::selectRaw('plans.name as plan_name, SUM(subscriptions.amount_cents)/100 as revenue, COUNT(*) as count')
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->groupBy('plans.id', 'plans.name')
            ->orderByDesc('revenue')
            ->get();

        // Top employers by job count
        $topEmployers = Job::selectRaw('owner_user_id, COUNT(*) as job_count')
            ->with('owner:id,email')
            ->groupBy('owner_user_id')
            ->orderByDesc('job_count')
            ->limit(5)
            ->get();

        $result = [
            'revenue'             => $revenue,
            'users'               => $users,
            'by_plan'             => $byPlan,
            'top_employers'       => $topEmployers,
            'role_distribution'   => \App\Models\User::selectRaw('role, COUNT(*) as count')->groupBy('role')->get(),
            'subscription_status' => \App\Models\Subscription::selectRaw('status, COUNT(*) as count')->groupBy('status')->get(),
        ];

        \Illuminate\Support\Facades\Cache::put($cacheKey, $result, now()->addMinutes(10));

        return $this->ok($result);
    }

    // ── Plans ────────────────────────────────────────────────────────────
    public function plans(): JsonResponse
    {
        $this->requireAdmin();
        return $this->ok(Plan::orderBy('price_cents')->get());
    }

    public function updatePlan(Request $request, int $id): JsonResponse
    {
        $this->requireAdmin();

        $plan = Plan::findOrFail($id);
        $data = $request->validate([
            'name'                 => 'sometimes|string|max:100',
            'price_cents'          => 'sometimes|integer|min:0',
            'job_post_limit'       => 'sometimes|integer|min:0',
            'ai_screening_enabled' => 'sometimes|boolean',
            'analytics_enabled'    => 'sometimes|boolean',
            'is_active'            => 'sometimes|boolean',
        ]);

        $plan->fill($data)->save();
        return $this->ok(['message' => 'Plan updated.', 'plan' => $plan]);
    }

    // ── Global Search ────────────────────────────────────────────────────
    public function globalSearch(Request $request): JsonResponse
    {
        $this->requireAdmin();
        $q = trim((string) $request->query('q', ''));
        if (strlen($q) < 2) return $this->ok(['users' => [], 'jobs' => [], 'subscriptions' => []]);

        $users = User::where('email', 'like', "%{$q}%")
            ->orWhere('phone', 'like', "%{$q}%")
            ->limit(5)->get(['id', 'email', 'phone', 'role', 'status']);

        $jobs = Job::where('title', 'like', "%{$q}%")
            ->limit(5)->get(['id', 'title', 'status']);

        $subscriptions = Subscription::whereHas('user', fn($uq) => $uq->where('email', 'like', "%{$q}%"))
            ->with('user:id,email', 'plan:id,name')
            ->limit(5)->get(['id', 'user_id', 'plan_id', 'status', 'created_at']);

        return $this->ok(compact('users', 'jobs', 'subscriptions'));
    }

    // ── User Detail ──────────────────────────────────────────────────────
    public function userDetail(int $id): JsonResponse
    {
        $this->requireAdmin();
        $user = User::findOrFail($id);

        $subscriptions = Subscription::where('user_id', $id)
            ->with('plan:id,name,price_cents,currency')
            ->orderByDesc('id')->get();

        $jobs = Job::where('owner_user_id', $id)
            ->orderByDesc('id')->limit(10)
            ->get(['id', 'title', 'status', 'created_at']);

        $applications = JobApplication::where('applicant_user_id', $id)
            ->with('job:id,title')
            ->orderByDesc('id')->limit(10)->get();

        return $this->ok(compact('user', 'subscriptions', 'jobs', 'applications'));
    }

    // ── Bulk Users ───────────────────────────────────────────────────────
    public function bulkUsers(Request $request): JsonResponse
    {
        $this->requireAdmin();
        $data = $request->validate([
            'ids'    => 'required|array',
            'ids.*'  => 'integer',
            'action' => 'required|in:suspend,ban,activate,export',
        ]);

        if ($data['action'] === 'export') {
            $users = User::whereIn('id', $data['ids'])
                ->get(['id', 'email', 'phone', 'role', 'status', 'created_at']);
            return $this->ok(['export' => $users]);
        }

        $statusMap = ['suspend' => 'suspended', 'ban' => 'banned', 'activate' => 'active'];
        User::whereIn('id', $data['ids'])->update(['status' => $statusMap[$data['action']]]);

        return $this->ok(['message' => 'Bulk action applied.', 'count' => count($data['ids'])]);
    }

    // ── Email User ───────────────────────────────────────────────────────
    public function emailUser(Request $request, int $id): JsonResponse
    {
        $this->requireAdmin();
        $data = $request->validate([
            'subject' => 'required|string|max:200',
            'body'    => 'required|string|max:5000',
        ]);

        $user = User::findOrFail($id);
        if (!$user->email) return $this->fail('User has no email address.', null, 422);

        try {
            Mail::raw($data['body'], function ($msg) use ($user, $data) {
                $msg->to($user->email)->subject($data['subject']);
            });
        } catch (\Exception $e) {
            Log::error('Admin email failed: ' . $e->getMessage());
            return $this->fail('Failed to send email.', null, 500);
        }

        return $this->ok(['message' => 'Email sent.']);
    }

    // ── Impersonate ──────────────────────────────────────────────────────
    public function impersonate(int $id): JsonResponse
    {
        $this->requireAdmin();
        $user = User::findOrFail($id);

        // Revoke old impersonation tokens, create a fresh one
        $user->tokens()->where('name', 'impersonation')->delete();
        $token = $user->createToken('impersonation')->plainTextToken;

        return $this->ok(['token' => $token, 'user' => $user]);
    }

    // ── MRR Stats ────────────────────────────────────────────────────────
    public function mrrStats(): JsonResponse
    {
        $this->requireAdmin();

        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        $mrr = Subscription::where('status', 'active')
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->sum('plans.price_cents') / 100;

        $newThisMonth = Subscription::where('status', 'active')
            ->where('created_at', '>=', $thisMonth)->count();

        $cancelledThisMonth = Subscription::where('status', 'cancelled')
            ->where('updated_at', '>=', $thisMonth)->count();

        $cancelledLastMonth = Subscription::where('status', 'cancelled')
            ->whereBetween('updated_at', [$lastMonth, $lastMonthEnd])->count();

        $activeLastMonth = Subscription::where('status', 'active')
            ->where('created_at', '<', $thisMonth)->count();

        $churnRate = $activeLastMonth > 0
            ? round(($cancelledThisMonth / $activeLastMonth) * 100, 1)
            : 0;

        $churnLastMonth = $activeLastMonth > 0
            ? round(($cancelledLastMonth / max($activeLastMonth, 1)) * 100, 1)
            : 0;

        // Monthly revenue trend (last 6 months)
        $driver = DB::connection()->getDriverName();
        $format = $driver === 'sqlite' ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')";

        $trend = Subscription::selectRaw("$format as month, SUM(amount_cents)/100 as revenue")
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')->orderBy('month')->get();

        return $this->ok([
            'mrr'                  => round((float)$mrr, 2),
            'new_this_month'       => $newThisMonth,
            'cancelled_this_month' => $cancelledThisMonth,
            'churn_rate'           => $churnRate,
            'churn_last_month'     => $churnLastMonth,
            'revenue_trend'        => $trend,
        ]);
    }

    // ── System Status ────────────────────────────────────────────────────
    public function systemStatus(): JsonResponse
    {
        $this->requireAdmin();

        // DB check
        $dbOk = false;
        try { DB::select('SELECT 1'); $dbOk = true; } catch (\Exception $e) {}

        // Storage check
        $storageOk = false;
        $storageUsed = 0;
        try {
            $storageOk = true;
            $storagePath = storage_path('app');
            if (is_dir($storagePath)) {
                $storageUsed = $this->dirSize($storagePath);
            }
        } catch (\Exception $e) {}

        // Queue check (failed jobs table)
        $failedJobs = 0;
        try {
            if (DB::getSchemaBuilder()->hasTable('failed_jobs')) {
                $failedJobs = DB::table('failed_jobs')->count();
            }
        } catch (\Exception $e) {}

        // Last cron run (check cache or log)
        $lastCron = null;
        try {
            $lastCron = \Illuminate\Support\Facades\Cache::get('last_cron_run');
        } catch (\Exception $e) {}

        return $this->ok([
            'database'      => ['ok' => $dbOk, 'driver' => DB::connection()->getDriverName()],
            'storage'       => ['ok' => $storageOk, 'used_bytes' => $storageUsed],
            'failed_jobs'   => $failedJobs,
            'last_cron'     => $lastCron,
            'php_version'   => PHP_VERSION,
            'laravel_version' => app()->version(),
            'env'           => app()->environment(),
        ]);
    }

    private function dirSize(string $path): int
    {
        $size = 0;
        try {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)) as $file) {
                $size += $file->getSize();
            }
        } catch (\Exception $e) {}
        return $size;
    }

    // ── Error Logs ───────────────────────────────────────────────────────
    public function errorLogs(): JsonResponse
    {
        $this->requireAdmin();

        $logPath = storage_path('logs/laravel.log');
        $lines = [];

        if (file_exists($logPath)) {
            // Read last 1000 lines to avoid memory exhaustion on large files
            $file = new \SplFileObject($logPath, 'r');
            $file->seek(PHP_INT_MAX);
            $totalLines = $file->key();
            $startLine = max(0, $totalLines - 1000);
            $file->seek($startLine);

            $rawLines = [];
            while (!$file->eof()) {
                $l = trim($file->fgets());
                if ($l) $rawLines[] = $l;
            }

            $rawLines = array_reverse($rawLines);
            $count = 0;
            foreach ($rawLines as $line) {
                if (str_contains($line, '.ERROR') || str_contains($line, '.CRITICAL') || str_contains($line, '.WARNING')) {
                    $lines[] = $line;
                    if (++$count >= 50) break;
                }
            }
        }

        return $this->ok(['lines' => $lines, 'count' => count($lines)]);
    }

    // ── Contacts ─────────────────────────────────────────────────────────
    public function contacts(Request $request): JsonResponse
    {
        $this->requireAdmin();
        $q = trim((string) $request->query('q', ''));
        $query = Contact::orderByDesc('id');
        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('email', 'like', "%{$q}%")
                   ->orWhere('name', 'like', "%{$q}%")
                   ->orWhere('message', 'like', "%{$q}%");
            });
        }
        return $this->ok($query->paginate(20));
    }

    // ── AI Screenings ────────────────────────────────────────────────────
    public function aiScreenings(Request $request): JsonResponse
    {
        $this->requireAdmin();
        $q = trim((string) $request->query('q', ''));
        $query = AiScreening::with(['application.job:id,title', 'application.applicant:id,email'])
            ->orderByDesc('id');
        if ($q !== '') {
            $query->whereHas('application.applicant', fn($uq) => $uq->where('email', 'like', "%{$q}%"));
        }
        return $this->ok($query->paginate(20));
    }

    // ── User Notes ───────────────────────────────────────────────────────
    public function userNotes(int $id): JsonResponse
    {
        $this->requireAdmin();
        $notes = DB::table('admin_user_notes')->where('user_id', $id)->orderByDesc('id')->get();
        return $this->ok($notes);
    }

    public function addUserNote(Request $request, int $id): JsonResponse
    {
        $this->requireAdmin();
        $admin = $this->requireAuth();
        $data = $request->validate(['note' => 'required|string|max:2000']);
        User::findOrFail($id);
        $note = DB::table('admin_user_notes')->insertGetId([
            'user_id'    => $id,
            'admin_id'   => $admin->id,
            'note'       => $data['note'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return $this->ok(DB::table('admin_user_notes')->find($note));
    }

    public function deleteUserNote(int $userId, int $noteId): JsonResponse
    {
        $this->requireAdmin();
        DB::table('admin_user_notes')->where('id', $noteId)->where('user_id', $userId)->delete();
        return $this->ok(['message' => 'Deleted']);
    }

    // ── Login History ────────────────────────────────────────────────────
    public function userLoginHistory(int $id): JsonResponse
    {
        $this->requireAdmin();
        User::findOrFail($id);
        $logs = AuditLog::where('actor_user_id', $id)
            ->whereIn('action', ['login', 'logout'])
            ->orderByDesc('id')->limit(20)
            ->get(['id', 'action', 'ip_address', 'created_at', 'metadata']);
        return $this->ok($logs);
    }

    // ── Manual Subscription Override ─────────────────────────────────────
    public function grantSubscription(Request $request, int $userId): JsonResponse
    {
        $this->requireAdmin();
        $data = $request->validate([
            'plan_id'    => 'required|exists:plans,id',
            'days'       => 'required|integer|min:1|max:3650',
            'note'       => 'nullable|string|max:500',
        ]);
        $user = User::findOrFail($userId);
        $plan = Plan::findOrFail($data['plan_id']);

        $sub = Subscription::create([
            'user_id'      => $userId,
            'plan_id'      => $data['plan_id'],
            'status'       => 'active',
            'amount_cents' => 0,
            'currency_code'=> 'USD',
            'start_at'     => now(),
            'end_at'       => now()->addDays($data['days']),
            'notes'        => $data['note'] ?? 'Admin grant',
        ]);

        AuditLog::create([
            'actor_user_id' => $this->requireAuth()->id,
            'action'        => 'admin_subscription_grant',
            'entity_type'   => 'subscription',
            'entity_id'     => $sub->id,
            'metadata'      => json_encode(['plan' => $plan->name, 'days' => $data['days'], 'user_id' => $userId]),
        ]);

        return $this->ok(['message' => 'Subscription granted.', 'subscription' => $sub]);
    }

    // ── Feature Flags ────────────────────────────────────────────────────
    public function featureFlags(): JsonResponse
    {
        $this->requireAdmin();
        $flags = DB::table('feature_flags')->orderBy('name')->get();
        return $this->ok($flags);
    }

    public function updateFeatureFlag(Request $request, string $name): JsonResponse
    {
        $this->requireAdmin();
        $data = $request->validate(['enabled' => 'required|boolean', 'description' => 'nullable|string|max:500']);
        DB::table('feature_flags')->updateOrInsert(
            ['name' => $name],
            ['enabled' => $data['enabled'], 'description' => $data['description'] ?? null, 'updated_at' => now(), 'created_at' => now()]
        );
        return $this->ok(['message' => 'Flag updated.']);
    }

    // ── Maintenance Mode ─────────────────────────────────────────────────
    public function maintenanceStatus(): JsonResponse
    {
        $this->requireAdmin();
        return $this->ok([
            'maintenance' => \Illuminate\Support\Facades\Cache::get('maintenance_mode', false),
            'message'     => \Illuminate\Support\Facades\Cache::get('maintenance_message', ''),
            'announcement'=> \Illuminate\Support\Facades\Cache::get('site_announcement', ''),
        ]);
    }

    public function setMaintenance(Request $request): JsonResponse
    {
        $this->requireAdmin();
        $data = $request->validate([
            'maintenance'  => 'required|boolean',
            'message'      => 'nullable|string|max:500',
            'announcement' => 'nullable|string|max:500',
        ]);
        \Illuminate\Support\Facades\Cache::put('maintenance_mode', $data['maintenance'], now()->addDays(7));
        if (isset($data['message']))      \Illuminate\Support\Facades\Cache::put('maintenance_message', $data['message'], now()->addDays(7));
        if (isset($data['announcement'])) \Illuminate\Support\Facades\Cache::put('site_announcement', $data['announcement'], now()->addDays(7));
        return $this->ok(['message' => 'Updated.']);
    }

    // ── Cache Flush ──────────────────────────────────────────────────────
    public function flushCache(): JsonResponse
    {
        $this->requireAdmin();
        \Illuminate\Support\Facades\Cache::flush();
        return $this->ok(['message' => 'Cache flushed.']);
    }

    // ── Queue Monitor ────────────────────────────────────────────────────
    public function queueMonitor(): JsonResponse
    {
        $this->requireAdmin();
        $failed = [];
        try {
            if (DB::getSchemaBuilder()->hasTable('failed_jobs')) {
                $failed = DB::table('failed_jobs')->orderByDesc('failed_at')->limit(20)->get()->map(function ($j) {
                    $payload = json_decode($j->payload, true);
                    return [
                        'id'         => $j->id,
                        'uuid'       => $j->uuid ?? null,
                        'connection' => $j->connection,
                        'queue'      => $j->queue,
                        'job'        => $payload['displayName'] ?? ($payload['job'] ?? 'Unknown'),
                        'failed_at'  => $j->failed_at,
                        'exception'  => substr($j->exception ?? '', 0, 300),
                    ];
                });
            }
        } catch (\Exception $e) {}

        return $this->ok(['failed' => $failed, 'count' => count($failed)]);
    }

    public function retryJob(Request $request): JsonResponse
    {
        $this->requireAdmin();
        $data = $request->validate(['uuid' => 'required|string']);
        try {
            \Illuminate\Support\Facades\Artisan::call('queue:retry', ['id' => [$data['uuid']]]);
            return $this->ok(['message' => 'Job queued for retry.']);
        } catch (\Exception $e) {
            return $this->fail('Failed: ' . $e->getMessage(), null, 500);
        }
    }

    // ── Webhook Log ──────────────────────────────────────────────────────
    public function webhookLogs(Request $request): JsonResponse
    {
        $this->requireAdmin();
        $logs = [];
        try {
            if (DB::getSchemaBuilder()->hasTable('webhook_logs')) {
                $q = DB::table('webhook_logs')->orderByDesc('id');
                if ($src = $request->query('source')) $q->where('source', $src);
                $logs = $q->limit(50)->get();
            }
        } catch (\Exception $e) {}
        return $this->ok($logs);
    }

    // ── Application Funnel ───────────────────────────────────────────────
    public function applicationFunnel(): JsonResponse
    {
        $this->requireAdmin();
        $total      = JobApplication::count();
        $reviewed   = JobApplication::whereNotIn('status', ['pending'])->count();
        $interviewed= JobApplication::whereIn('status', ['interview', 'offer', 'hired'])->count();
        $hired      = JobApplication::where('status', 'hired')->count();
        $offered    = JobApplication::where('status', 'offer')->count();

        return $this->ok([
            ['stage' => 'Applied',     'count' => $total],
            ['stage' => 'Reviewed',    'count' => $reviewed],
            ['stage' => 'Interviewed', 'count' => $interviewed],
            ['stage' => 'Offered',     'count' => $offered],
            ['stage' => 'Hired',       'count' => $hired],
        ]);
    }

    // ── Revenue by Country ───────────────────────────────────────────────
    public function revenueByCountry(): JsonResponse
    {
        $this->requireAdmin();
        $data = Subscription::selectRaw('billing_country as country, SUM(amount_cents)/100 as revenue, COUNT(*) as count')
            ->whereNotNull('billing_country')
            ->groupBy('billing_country')
            ->orderByDesc('revenue')
            ->limit(15)
            ->get();
        return $this->ok($data);
    }

    // ── DB Table Sizes ───────────────────────────────────────────────────
    public function dbTableSizes(): JsonResponse
    {
        $this->requireAdmin();
        try {
            $driver = DB::connection()->getDriverName();
            if ($driver === 'sqlite') {
                $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table'");
                $rows = [];
                foreach ($tables as $t) {
                    $count = DB::table($t->name)->count();
                    $rows[] = ['table' => $t->name, 'size_mb' => 0.01, 'row_count' => $count];
                }
                return $this->ok($rows);
            }

            $db = config('database.connections.' . config('database.default') . '.database');
            $rows = DB::select("
                SELECT table_name as `table`,
                       ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb,
                       table_rows as row_count
                FROM information_schema.TABLES
                WHERE table_schema = ?
                ORDER BY (data_length + index_length) DESC
                LIMIT 20
            ", [$db]);
            return $this->ok($rows);
        } catch (\Exception $e) {
            return $this->ok([]);
        }
    }

    // ── Export CSV ───────────────────────────────────────────────────────
    public function exportCsv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse    {
        $this->requireAdmin();
        $type = $request->query('type', 'users');

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$type}-export.csv\"",
        ];

        return response()->stream(function () use ($type) {
            $handle = fopen('php://output', 'w');

            if ($type === 'users') {
                fputcsv($handle, ['ID', 'Email', 'Phone', 'Role', 'Status', 'Created At']);
                User::orderByDesc('id')->chunk(500, function ($rows) use ($handle) {
                    foreach ($rows as $r) {
                        fputcsv($handle, [$r->id, $r->email, $r->phone, $r->role, $r->status, $r->created_at]);
                    }
                });
            } elseif ($type === 'subscriptions') {
                fputcsv($handle, ['ID', 'User Email', 'Plan', 'Status', 'Amount', 'Currency', 'Created At']);
                Subscription::with('user:id,email', 'plan:id,name,currency')
                    ->orderByDesc('id')->chunk(500, function ($rows) use ($handle) {
                        foreach ($rows as $r) {
                            fputcsv($handle, [
                                $r->id, $r->user?->email, $r->plan?->name,
                                $r->status, $r->amount_cents / 100, $r->plan?->currency, $r->created_at,
                            ]);
                        }
                    });
            } elseif ($type === 'revenue') {
                fputcsv($handle, ['Month', 'Revenue', 'Subscriptions']);
                $driver = DB::connection()->getDriverName();
                $format = $driver === 'sqlite' ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')";
                $rows = Subscription::selectRaw("$format as month, SUM(amount_cents)/100 as revenue, COUNT(*) as count")
                    ->groupBy('month')->orderBy('month')->get();
                foreach ($rows as $r) {
                    fputcsv($handle, [$r->month, $r->revenue, $r->count]);
                }
            }

            fclose($handle);
        }, 200, $headers);
    }
}
