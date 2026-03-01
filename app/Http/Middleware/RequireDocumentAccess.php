<?php

namespace App\Http\Middleware;

use App\Models\DocumentAccessPayment;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireDocumentAccess
{
    /**
     * Handle an incoming request.
     * 
     * This middleware checks if employer has paid for document access to view applicant's resume/documents
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Must be logged in
        if (!$user) {
            return $this->respondUnauthorized($request);
        }

        // Only employers need document access payments
        if (!in_array($user->role, ['employer', 'agency'])) {
            return $next($request);
        }

        // Get applicant ID from route parameter or request
        $applicantId = $request->route('application')?->applicant_user_id 
                    ?? $request->route('applicant_user_id')
                    ?? $request->input('applicant_user_id');

        if (!$applicantId) {
            return $this->respondBadRequest($request, 'Applicant ID required');
        }

        // Check if employer has paid for document access
        $hasAccess = DocumentAccessPayment::hasAccess($user->id, $applicantId);

        if (!$hasAccess) {
            return $this->respondDocumentAccessRequired($request);
        }

        return $next($request);
    }

    protected function respondUnauthorized(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHORIZED',
                'message' => 'Authentication required.',
            ], 401);
        }

        return redirect()->route('login');
    }

    protected function respondBadRequest(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'code' => 'BAD_REQUEST',
                'message' => $message,
            ], 400);
        }

        return back()->with('error', $message);
    }

    protected function respondDocumentAccessRequired(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'code' => 'DOCUMENT_ACCESS_REQUIRED',
                'message' => 'Document access payment required to view resume and documents.',
                'action' => 'purchase_document_access',
            ], 402);
        }

        return redirect('/billing/document-access')->with('doc_access_required', 1);
    }
}
