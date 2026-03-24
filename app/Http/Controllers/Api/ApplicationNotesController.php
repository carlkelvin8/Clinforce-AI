<?php

namespace App\Http\Controllers\Api;

use App\Models\ApplicationNote;
use App\Models\JobApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationNotesController extends ApiController
{
    public function index(JobApplication $application): JsonResponse
    {
        $u = $this->requireAuth();
        $this->assertCanAccess($u, $application);

        $notes = ApplicationNote::where('application_id', $application->id)
            ->with('author:id,email')
            ->orderByDesc('created_at')
            ->get();

        return $this->ok($notes);
    }

    public function store(Request $request, JobApplication $application): JsonResponse
    {
        $u = $this->requireAuth();
        $this->assertCanAccess($u, $application);

        $request->validate(['note' => ['required', 'string', 'max:5000']]);

        $note = ApplicationNote::create([
            'application_id' => $application->id,
            'user_id' => $u->id,
            'content' => $request->note,
        ]);

        return $this->ok($note->load('author:id,email'), 'Note added', 201);
    }

    public function destroy(JobApplication $application, ApplicationNote $note): JsonResponse
    {
        $u = $this->requireAuth();
        $this->assertCanAccess($u, $application);

        if ($note->application_id !== $application->id) abort(404);
        if ($note->user_id !== $u->id && $u->role !== 'admin') abort(403);

        $note->delete();
        return $this->ok(null, 'Note deleted');
    }

    private function assertCanAccess($u, JobApplication $application): void
    {
        if ($u->role === 'admin') return;

        $application->loadMissing('job');

        $isOwner = $application->job
            && in_array($u->role, ['employer', 'agency'], true)
            && $application->job->owner_user_id === $u->id
            && $application->job->owner_type === $u->role;

        if (!$isOwner) abort(403);
    }
}
