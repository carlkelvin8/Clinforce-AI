<?php

namespace App\Http\Requests\Api;

use App\Models\Job;
use App\Models\JobApplication;

class JobApplyRequest extends ApiRequest
{
    public function authorize(): bool
    {
        $u = $this->user();
        return (bool)$u && ($u->role === 'applicant' || $u->role === 'admin');
    }

    public function rules(): array
    {
        return [
            'cover_letter' => ['nullable','string','max:8000'],
            'resume' => ['nullable','file','max:10240'],
            'cover_letter_file' => ['nullable','file','max:10240'],
            'other_docs' => ['nullable','array'],
            'other_docs.*' => ['file','max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'cover_letter.string' => 'Cover letter must be text.',
            'cover_letter.max' => 'Cover letter must be 8000 characters or fewer.',
            'resume.file' => 'Resume must be a file.',
            'resume.max' => 'Resume file size must not exceed 10MB.',
            'cover_letter_file.file' => 'Cover letter must be a file.',
            'cover_letter_file.max' => 'Cover letter file size must not exceed 10MB.',
        ];
    }

    protected function validateFiles($validator): void
    {
        $allowedExtensions = ['pdf', 'doc', 'docx'];
        $allowedMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        // Validate resume
        if ($this->hasFile('resume')) {
            $file = $this->file('resume');
            $extension = strtolower($file->getClientOriginalExtension());
            $mimeType = $file->getMimeType();
            
            if (!in_array($extension, $allowedExtensions) && !in_array($mimeType, $allowedMimes)) {
                $validator->errors()->add('resume', 'Resume must be a PDF or Word document (PDF/DOC/DOCX). Detected: ' . $extension . ' (' . $mimeType . ')');
            }
        }

        // Validate cover letter file
        if ($this->hasFile('cover_letter_file')) {
            $file = $this->file('cover_letter_file');
            $extension = strtolower($file->getClientOriginalExtension());
            $mimeType = $file->getMimeType();
            
            if (!in_array($extension, $allowedExtensions) && !in_array($mimeType, $allowedMimes)) {
                $validator->errors()->add('cover_letter_file', 'Cover letter file must be a PDF or Word document (PDF/DOC/DOCX). Detected: ' . $extension . ' (' . $mimeType . ')');
            }
        }
    }

    public function withValidator($validator): void
    {
        // Validate file types
        $this->validateFiles($validator);
        
        $validator->after(function ($validator) {
            $u = $this->user();
            /** @var Job|null $job */
            $job = $this->route('job');

            if (!$u || !$job) return;

            if ($u->role !== 'admin' && $u->role !== 'applicant') {
                $validator->errors()->add('role', 'Only applicants can apply.');
                return;
            }

            if ($job->status !== 'published') {
                $validator->errors()->add('job', 'Job is not open for applications.');
                return;
            }

            if ($job->owner_user_id === $u->id) {
                $validator->errors()->add('job', 'Owner cannot apply to own job.');
                return;
            }

            $dup = JobApplication::query()
                ->where('job_id', $job->id)
                ->where('applicant_user_id', $u->id)
                ->exists();

            if ($dup) {
                $validator->errors()->add('job', 'Already applied to this job.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('cover_letter')) {
            $this->merge(['cover_letter' => trim((string)$this->input('cover_letter'))]);
        }
    }
}
