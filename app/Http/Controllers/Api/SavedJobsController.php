<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\SavedJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavedJobsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $jobs = SavedJob::query()
            ->where('user_id', $request->user()->id)
            ->with('job')
            ->orderByDesc('created_at')
            ->get()
            ->pluck('job')
            ->filter();

        return response()->json(['data' => $jobs->values()]);
    }

    public function store(Request $request, Job $job): JsonResponse
    {
        SavedJob::firstOrCreate([
            'user_id' => $request->user()->id,
            'job_id'  => $job->id,
        ]);

        return response()->json(['message' => 'Job saved.'], 201);
    }

    public function destroy(Request $request, Job $job): JsonResponse
    {
        SavedJob::where('user_id', $request->user()->id)
            ->where('job_id', $job->id)
            ->delete();

        return response()->json(['message' => 'Job removed from saved.']);
    }
}
