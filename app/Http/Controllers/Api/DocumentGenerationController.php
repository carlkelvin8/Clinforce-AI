<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DocumentGenerationController extends ApiController
{
    private function requireEmployer(): User
    {
        $u = $this->requireAuth();
        if (!in_array($u->role, ['employer', 'agency', 'admin'], true)) {
            abort(403, 'Employer only');
        }
        return $u;
    }

    // ── Document Templates ───────────────────────────────────────────────
    public function getTemplates(Request $request): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $type = $request->query('type');
        
        $query = DB::table('document_templates')
            ->where('employer_user_id', $u->id)
            ->where('is_active', true);

        if ($type) {
            $query->where('type', $type);
        }

        $templates = $query
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return $this->ok($templates);
    }

    public function createTemplate(Request $request): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:offer_letter,employment_contract,reference_letter,onboarding_packet,termination_letter,custom',
            'template_content' => 'required|string',
            'required_fields' => 'nullable|array',
            'optional_fields' => 'nullable|array',
            'file_format' => 'in:pdf,docx,html',
            'is_default' => 'boolean',
            'letterhead_url' => 'nullable|url',
            'styling_options' => 'nullable|array',
        ]);

        // If setting as default, unset other defaults for this type
        if ($data['is_default'] ?? false) {
            DB::table('document_templates')
                ->where('employer_user_id', $u->id)
                ->where('type', $data['type'])
                ->update(['is_default' => false]);
        }

        $id = DB::table('document_templates')->insertGetId([
            'employer_user_id' => $u->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'template_content' => $data['template_content'],
            'required_fields' => isset($data['required_fields']) ? json_encode($data['required_fields']) : null,
            'optional_fields' => isset($data['optional_fields']) ? json_encode($data['optional_fields']) : null,
            'file_format' => $data['file_format'] ?? 'pdf',
            'is_default' => $data['is_default'] ?? false,
            'is_active' => true,
            'letterhead_url' => $data['letterhead_url'] ?? null,
            'styling_options' => isset($data['styling_options']) ? json_encode($data['styling_options']) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->ok(['id' => $id], 'Document template created', 201);
    }

    public function updateTemplate(Request $request, int $templateId): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $template = DB::table('document_templates')
            ->where('id', $templateId)
            ->where('employer_user_id', $u->id)
            ->first();

        if (!$template) return $this->fail('Document template not found', null, 404);

        $data = $request->validate([
            'name' => 'string|max:200',
            'description' => 'nullable|string|max:1000',
            'template_content' => 'string',
            'required_fields' => 'nullable|array',
            'optional_fields' => 'nullable|array',
            'file_format' => 'in:pdf,docx,html',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'letterhead_url' => 'nullable|url',
            'styling_options' => 'nullable|array',
        ]);

        // If setting as default, unset other defaults for this type
        if (isset($data['is_default']) && $data['is_default']) {
            DB::table('document_templates')
                ->where('employer_user_id', $u->id)
                ->where('type', $template->type)
                ->where('id', '!=', $templateId)
                ->update(['is_default' => false]);
        }

        $updateData = array_filter([
            'name' => $data['name'] ?? null,
            'description' => $data['description'] ?? null,
            'template_content' => $data['template_content'] ?? null,
            'required_fields' => isset($data['required_fields']) ? json_encode($data['required_fields']) : null,
            'optional_fields' => isset($data['optional_fields']) ? json_encode($data['optional_fields']) : null,
            'file_format' => $data['file_format'] ?? null,
            'is_default' => $data['is_default'] ?? null,
            'is_active' => $data['is_active'] ?? null,
            'letterhead_url' => $data['letterhead_url'] ?? null,
            'styling_options' => isset($data['styling_options']) ? json_encode($data['styling_options']) : null,
            'updated_at' => now(),
        ], fn($value) => $value !== null);

        DB::table('document_templates')->where('id', $templateId)->update($updateData);

        return $this->ok(null, 'Document template updated');
    }

    public function deleteTemplate(int $templateId): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $deleted = DB::table('document_templates')
            ->where('id', $templateId)
            ->where('employer_user_id', $u->id)
            ->delete();

        if (!$deleted) return $this->fail('Document template not found', null, 404);

        return $this->ok(null, 'Document template deleted');
    }

    // ── Document Generation ──────────────────────────────────────────────
    public function generateDocument(Request $request): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $data = $request->validate([
            'template_id' => 'required|exists:document_templates,id',
            'application_id' => 'nullable|exists:job_applications,id',
            'recipient_user_id' => 'nullable|exists:users,id',
            'document_name' => 'required|string|max:200',
            'field_values' => 'required|array',
        ]);

        // Verify template ownership
        $template = DB::table('document_templates')
            ->where('id', $data['template_id'])
            ->where('employer_user_id', $u->id)
            ->first();

        if (!$template) return $this->fail('Document template not found', null, 404);

        // If application_id provided, verify ownership
        if ($data['application_id']) {
            $application = DB::table('job_applications')
                ->join('jobs_table', 'jobs_table.id', '=', 'job_applications.job_id')
                ->where('job_applications.id', $data['application_id'])
                ->where('jobs_table.owner_user_id', $u->id)
                ->first();

            if (!$application) return $this->fail('Application not found', null, 404);
        }

        // Generate document content by replacing placeholders
        $content = $this->replacePlaceholders($template->template_content, $data['field_values']);
        
        // Generate file path
        $fileName = $this->sanitizeFileName($data['document_name']) . '.' . $template->file_format;
        $filePath = 'documents/' . $u->id . '/' . date('Y/m/') . $fileName;
        
        // For now, we'll store as HTML/text. In production, you'd use a PDF library
        Storage::put($filePath, $content);
        $fileUrl = Storage::url($filePath);
        $fileSize = Storage::size($filePath);

        $id = DB::table('generated_documents')->insertGetId([
            'template_id' => $data['template_id'],
            'application_id' => $data['application_id'] ?? null,
            'generated_by_user_id' => $u->id,
            'recipient_user_id' => $data['recipient_user_id'] ?? null,
            'document_name' => $data['document_name'],
            'status' => 'generated',
            'field_values' => json_encode($data['field_values']),
            'file_path' => $filePath,
            'file_url' => $fileUrl,
            'file_format' => $template->file_format,
            'file_size_bytes' => $fileSize,
            'version' => '1.0',
            'generated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Log automation
        DB::table('workflow_automation_logs')->insert([
            'automation_type' => 'document_generation',
            'entity_type' => 'Document',
            'entity_id' => $id,
            'triggered_by_user_id' => $u->id,
            'action_taken' => "Generated {$template->type}: {$data['document_name']}",
            'status' => 'success',
            'executed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->ok([
            'id' => $id,
            'file_url' => $fileUrl,
            'file_size' => $fileSize,
        ], 'Document generated successfully', 201);
    }

    public function getGeneratedDocuments(Request $request): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $applicationId = $request->query('application_id');
        $status = $request->query('status');
        $type = $request->query('type');

        $query = DB::table('generated_documents')
            ->join('document_templates', 'document_templates.id', '=', 'generated_documents.template_id')
            ->leftJoin('users as recipients', 'recipients.id', '=', 'generated_documents.recipient_user_id')
            ->where('generated_documents.generated_by_user_id', $u->id);

        if ($applicationId) {
            $query->where('generated_documents.application_id', $applicationId);
        }

        if ($status) {
            $query->where('generated_documents.status', $status);
        }

        if ($type) {
            $query->where('document_templates.type', $type);
        }

        $documents = $query
            ->select([
                'generated_documents.*',
                'document_templates.type as template_type',
                'document_templates.name as template_name',
                'recipients.name as recipient_name',
                'recipients.email as recipient_email'
            ])
            ->orderByDesc('generated_documents.generated_at')
            ->get();

        return $this->ok($documents);
    }

    public function sendDocument(Request $request, int $documentId): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $data = $request->validate([
            'recipient_email' => 'required|email',
            'subject' => 'nullable|string|max:200',
            'message' => 'nullable|string|max:1000',
        ]);

        // Verify document ownership
        $document = DB::table('generated_documents')
            ->where('id', $documentId)
            ->where('generated_by_user_id', $u->id)
            ->first();

        if (!$document) return $this->fail('Document not found', null, 404);

        // TODO: Implement actual email sending
        // For now, just update the status
        DB::table('generated_documents')
            ->where('id', $documentId)
            ->update([
                'status' => 'sent',
                'sent_at' => now(),
                'updated_at' => now(),
            ]);

        return $this->ok(null, 'Document sent successfully');
    }

    public function downloadDocument(int $documentId): JsonResponse
    {
        $u = $this->requireEmployer();
        
        // Verify document ownership or recipient access
        $document = DB::table('generated_documents')
            ->where('id', $documentId)
            ->where(function($query) use ($u) {
                $query->where('generated_by_user_id', $u->id)
                      ->orWhere('recipient_user_id', $u->id);
            })
            ->first();

        if (!$document) return $this->fail('Document not found', null, 404);

        // Log access
        DB::table('document_access_logs')->insert([
            'document_id' => $documentId,
            'accessed_by_user_id' => $u->id,
            'access_type' => 'download',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'accessed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->ok([
            'download_url' => $document->file_url,
            'file_name' => basename($document->file_path),
            'file_size' => $document->file_size_bytes,
        ]);
    }

    // ── Helper Methods ───────────────────────────────────────────────────
    private function replacePlaceholders(string $template, array $values): string
    {
        $content = $template;
        
        foreach ($values as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $content = str_replace($placeholder, $value, $content);
        }
        
        // Add current date if not provided
        $content = str_replace('{{current_date}}', now()->format('F j, Y'), $content);
        
        return $content;
    }

    private function sanitizeFileName(string $name): string
    {
        // Remove special characters and spaces
        $name = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $name);
        $name = preg_replace('/_+/', '_', $name);
        return trim($name, '_');
    }

    // ── Document Analytics ───────────────────────────────────────────────
    public function getDocumentAnalytics(Request $request): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $days = (int) $request->query('days', 30);
        $startDate = now()->subDays($days);

        // Document generation stats
        $stats = DB::table('generated_documents')
            ->where('generated_by_user_id', $u->id)
            ->where('generated_at', '>=', $startDate)
            ->selectRaw('
                COUNT(*) as total_documents,
                SUM(CASE WHEN status = "generated" THEN 1 ELSE 0 END) as generated_count,
                SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent_count,
                SUM(CASE WHEN status = "signed" THEN 1 ELSE 0 END) as signed_count
            ')
            ->first();

        // Documents by type
        $byType = DB::table('generated_documents')
            ->join('document_templates', 'document_templates.id', '=', 'generated_documents.template_id')
            ->where('generated_documents.generated_by_user_id', $u->id)
            ->where('generated_documents.generated_at', '>=', $startDate)
            ->selectRaw('document_templates.type, COUNT(*) as count')
            ->groupBy('document_templates.type')
            ->orderByDesc('count')
            ->get();

        // Recent activity
        $recentActivity = DB::table('generated_documents')
            ->join('document_templates', 'document_templates.id', '=', 'generated_documents.template_id')
            ->leftJoin('users as recipients', 'recipients.id', '=', 'generated_documents.recipient_user_id')
            ->where('generated_documents.generated_by_user_id', $u->id)
            ->select([
                'generated_documents.document_name',
                'generated_documents.status',
                'generated_documents.generated_at',
                'document_templates.type',
                'recipients.name as recipient_name'
            ])
            ->orderByDesc('generated_documents.generated_at')
            ->limit(10)
            ->get();

        return $this->ok([
            'stats' => $stats,
            'by_type' => $byType,
            'recent_activity' => $recentActivity,
            'period_days' => $days,
        ]);
    }
}