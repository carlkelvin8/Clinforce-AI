<?php

namespace App\Http\Controllers\Api;

use App\Models\Document;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecureDocumentController extends ApiController
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Download document with subscription check
     */
    public function download(Request $request, int $documentId)
    {
        $user = $this->requireAuth();
        
        $document = Document::findOrFail($documentId);

        // Check access permissions
        $canAccess = $this->canAccessDocument($user, $document);

        if (!$canAccess) {
            return response()->json([
                'error' => 'access_denied',
                'message' => 'Document access payment required. Please unlock this applicant\'s documents first.',
                'subscription_status' => $this->subscriptionService->getSubscriptionStatus($user->id),
            ], 403);
        }

        // Get file path from URL
        $filePath = $this->getFilePathFromUrl($document->file_url);

        if (!Storage::disk('public')->exists($filePath)) {
            return response()->json([
                'error' => 'file_not_found',
                'message' => 'Document file not found.',
            ], 404);
        }

        return Storage::disk('public')->download($filePath, $document->file_name);
    }

    /**
     * Stream document (for preview)
     */
    public function stream(Request $request, int $documentId): StreamedResponse
    {
        $user = $this->requireAuth();
        
        $document = Document::findOrFail($documentId);

        // Check access permissions
        $canAccess = $this->canAccessDocument($user, $document);

        if (!$canAccess) {
            abort(403, 'Document access payment required');
        }

        // Get file path from URL
        $filePath = $this->getFilePathFromUrl($document->file_url);

        if (!Storage::disk('public')->exists($filePath)) {
            abort(404, 'File not found');
        }

        return Storage::disk('public')->response($filePath, $document->file_name, [
            'Content-Type' => $document->mime_type,
        ]);
    }

    /**
     * Check if user can access document
     */
    protected function canAccessDocument($user, Document $document): bool
    {
        // Owner can always access their own documents
        if ($document->user_id === $user->id) {
            return true;
        }

        // Admin can access all
        if ($user->role === 'admin') {
            return true;
        }

        // Employers need active subscription OR document access payment
        if ($user->role === 'employer') {
            $documentOwner = $document->user;
            
            // Only check for applicant documents
            if ($documentOwner && $documentOwner->role === 'applicant') {
                // Check if has active subscription
                if ($this->subscriptionService->hasActiveSubscription($user->id)) {
                    return true;
                }
                
                // Check if has paid for document access for this specific applicant
                $hasDocumentAccess = \App\Models\DocumentAccessPayment::hasAccess(
                    $user->id, 
                    $document->user_id
                );
                
                return $hasDocumentAccess;
            }
        }

        // Agencies can access if they have relationship
        if ($user->role === 'agency') {
            // Check document access payment for agencies too
            $documentOwner = $document->user;
            if ($documentOwner && $documentOwner->role === 'applicant') {
                return \App\Models\DocumentAccessPayment::hasAccess(
                    $user->id, 
                    $document->user_id
                );
            }
            return true;
        }

        return false;
    }

    /**
     * Extract file path from storage URL
     */
    protected function getFilePathFromUrl(string $url): string
    {
        // Remove domain and /storage/ prefix
        $path = parse_url($url, PHP_URL_PATH);
        $path = str_replace('/storage/', '', $path);
        
        return $path;
    }
}
