<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InvitationController extends Controller
{
    /**
     * Display a listing of invitations.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Invitation::query();

        if ($user->role === 'employer' || $user->role === 'agency') {
            $query->where('employer_id', $user->id)
                  ->with(['candidate', 'candidateProfile']);
        } else {
            $query->where('candidate_id', $user->id)
                  ->with(['employer.employerProfile']);
        }

        $invitations = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($invitations);
    }

    /**
     * Store a newly created invitation in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'candidate_id' => 'required|exists:users,id',
            'message' => 'nullable|string'
        ]);

        $user = $request->user();

        // Check if already invited
        $exists = Invitation::where('employer_id', $user->id)
                            ->where('candidate_id', $request->candidate_id)
                            ->exists();
        
        if ($exists) {
            return response()->json(['message' => 'You have already invited this candidate.'], 409);
        }

        $invitation = Invitation::create([
            'employer_id' => $user->id,
            'candidate_id' => $request->candidate_id,
            'status' => 'pending',
            'message' => $request->message
        ]);

        return response()->json([
            'message' => 'Invitation sent successfully.',
            'data' => $invitation
        ], 201);
    }

    public function accept(Request $request, Invitation $invitation): JsonResponse
    {
        $user = $request->user();

        if ($user->id !== $invitation->candidate_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($invitation->status === 'accepted') {
            return response()->json(['data' => $invitation]);
        }

        $invitation->status = 'accepted';
        $invitation->save();

        return response()->json(['data' => $invitation]);
    }
}
