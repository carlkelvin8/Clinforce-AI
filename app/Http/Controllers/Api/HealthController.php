<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends ApiController
{
    public function index(): JsonResponse
    {
        $status = 'ok';
        $checks = [
            'app' => true,
            'db' => null,
        ];

        try {
            DB::connection()->getPdo();
            DB::statement('SELECT 1');
            $checks['db'] = true;
        } catch (\Throwable $e) {
            $checks['db'] = false;
            $status = 'degraded';
        }

        return response()->json([
            'status' => $status,
            'checks' => $checks,
            'time' => now()->toISOString(),
        ]);
    }
}
