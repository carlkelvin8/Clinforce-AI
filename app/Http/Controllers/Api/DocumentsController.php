<?php
// app/Http/Controllers/Api/DocumentsController.php
// app/Http/Controllers/Api/DocumentsController.php
namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\DocumentStoreRequest;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class DocumentsController extends ApiController
{
    public function index(): JsonResponse
    {
        $u = $this->requireAuth();

        $docs = Document::query()
            ->where('user_id', $u->id)
            ->orderByDesc('id')
            ->get()
            ->map(function (Document $d) {
                return [
                    'id' => $d->id,
                    'doc_type' => $d->doc_type,
                    'file_url' => $d->file_url,
                    'file_name' => $d->file_name,
                    'mime_type' => $d->mime_type,
                    'file_size_bytes' => $d->file_size_bytes,
                    'status' => $d->status,
                    'created_at' => optional($d->created_at)->toISOString(),
                ];
            });

        return $this->ok($docs);
    }

    public function store(DocumentStoreRequest $request): JsonResponse
    {
        $u = $this->requireAuth();
        $v = $request->validated();

        // Role-guard doc types
        if (in_array($u->role, ['employer', 'agency'], true)) {
            $allowed = ['business_permit', 'accreditation', 'id_document', 'other'];
            if (!in_array($v['doc_type'], $allowed, true)) {
                return $this->fail('Invalid doc_type for this role', ['doc_type' => ['Not allowed']], 422);
            }
        }

        if ($u->role === 'applicant') {
            $allowed = ['resume', 'license', 'certificate', 'id_document', 'other'];
            if (!in_array($v['doc_type'], $allowed, true)) {
                return $this->fail('Invalid doc_type for this role', ['doc_type' => ['Not allowed']], 422);
            }
        }

        $file = $request->file('file');
        if (!$file) {
            return $this->fail('Please choose a file to upload.', ['file' => ['Please choose a file to upload.']], 422);
        }

        $dir = "documents/{$u->id}";
        $path = $file->store($dir, 'public'); // storage/app/public/...
        $url  = Storage::disk('public')->url($path);

        $doc = Document::query()->create([
            'user_id' => $u->id,
            'doc_type' => $v['doc_type'],
            'file_url' => $url,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size_bytes' => $file->getSize(),
            'status' => 'active',
            'created_at' => now(),
        ]);

        return $this->ok([
            'id' => $doc->id,
            'doc_type' => $doc->doc_type,
            'file_url' => $doc->file_url,
            'file_name' => $doc->file_name,
            'mime_type' => $doc->mime_type,
            'file_size_bytes' => $doc->file_size_bytes,
            'status' => $doc->status,
            'created_at' => optional($doc->created_at)->toISOString(),
        ], 'Uploaded', 201);
    }

    public function destroy(Document $document): JsonResponse
    {
        $u = $this->requireAuth();

        if ($u->role !== 'admin' && $document->user_id !== $u->id) {
            return $this->fail('Forbidden', null, 403);
        }

        // best-effort delete stored file (only if it is a public disk URL)
        if ($document->file_url) {
            $prefix = Storage::disk('public')->url('/');
            if (str_starts_with($document->file_url, $prefix)) {
                $relative = ltrim(str_replace($prefix, '', $document->file_url), '/');
                Storage::disk('public')->delete($relative);
            }
        }

        $document->delete();
        return $this->ok(null, 'Deleted');
    }

    public function setActive(Document $document): JsonResponse
    {
        $u = $this->requireAuth();

        if ($document->user_id !== $u->id) {
            return $this->fail('Forbidden', null, 403);
        }

        // Deactivate all other resumes, activate this one
        Document::query()
            ->where('user_id', $u->id)
            ->where('doc_type', $document->doc_type)
            ->update(['status' => 'inactive']);

        $document->status = 'active';
        $document->save();

        return $this->ok($document, 'Set as active');
    }

    public function stream(Document $document): \Symfony\Component\HttpFoundation\Response
    {
        $u = $this->requireAuth();

        // Only owner or admin can stream
        if ($u->role !== 'admin' && $document->user_id !== $u->id) {
            // Also allow employers to view if they have access to the application
            // But for simplicity in the profile page, we check owner first.
            abort(403, 'Forbidden');
        }

        $filePath = $document->file_url;
        
        // Fix for production /build/ prefix or full URLs
        if (str_contains($filePath, '/build/')) {
            $filePath = str_replace('/build/', '/', $filePath);
        }

        // If it's a full URL, we need to extract the relative path after /storage/
        if (str_contains($filePath, '/storage/')) {
            $pos = strpos($filePath, '/storage/');
            $filePath = substr($filePath, $pos + 9); // length of "/storage/"
        } else {
            // Remove leading slash and public prefix if present
            $filePath = ltrim($filePath, '/');
            if (str_starts_with($filePath, 'public/')) {
                $filePath = substr($filePath, 7);
            }
        }

        if (!Storage::disk('public')->exists($filePath)) {
            abort(404, 'File not found');
        }

        $fullPath = Storage::disk('public')->path($filePath);
        
        return response()->file($fullPath, [
            'Content-Type' => $document->mime_type ?: 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $document->file_name . '"'
        ]);
    }
}
