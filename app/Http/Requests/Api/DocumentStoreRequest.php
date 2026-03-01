<?php
/// app/Http/Requests/Api/DocumentStoreRequest.php
namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class DocumentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'doc_type' => ['bail','required','string','max:60'],
            'file' => ['bail','required','file','max:10240'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->hasFile('file')) {
                $file = $this->file('file');
                $extension = strtolower($file->getClientOriginalExtension());
                $mimeType = $file->getMimeType();
                
                $allowedExtensions = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'];
                $allowedMimes = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'image/png',
                    'image/jpeg',
                    'image/jpg',
                ];
                
                if (!in_array($extension, $allowedExtensions) && !in_array($mimeType, $allowedMimes)) {
                    $validator->errors()->add('file', 'File must be PDF, DOC, DOCX, PNG, JPG, or JPEG. Detected: ' . $extension . ' (' . $mimeType . ')');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'doc_type.required' => 'doc_type is required.',
            'file.required' => 'Please choose a file to upload.',
            'file.file' => 'The file field must be a file.',
            'file.mimes' => 'Allowed: pdf, doc, docx, png, jpg, jpeg.',
            'file.max' => 'Max file size is 10MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('doc_type')) {
            $this->merge([
                'doc_type' => strtolower(trim((string)$this->input('doc_type'))),
            ]);
        }
    }
}

