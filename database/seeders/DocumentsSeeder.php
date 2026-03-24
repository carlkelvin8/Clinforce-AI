<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Document;
use App\Models\User;

class DocumentsSeeder extends Seeder
{
    public function run(): void
    {
        $applicants = User::where('role', 'applicant')->get();

        foreach ($applicants as $applicant) {
            $docs = [
                ['doc_type' => 'resume', 'file_name' => 'resume.pdf', 'mime_type' => 'application/pdf', 'file_size_bytes' => 204800],
                ['doc_type' => 'license', 'file_name' => 'nursing_license.pdf', 'mime_type' => 'application/pdf', 'file_size_bytes' => 102400],
            ];

            foreach ($docs as $doc) {
                Document::firstOrCreate(
                    ['user_id' => $applicant->id, 'doc_type' => $doc['doc_type']],
                    [
                        'file_url' => '/uploads/documents/demo/' . $doc['file_name'],
                        'file_name' => $doc['file_name'],
                        'mime_type' => $doc['mime_type'],
                        'file_size_bytes' => $doc['file_size_bytes'],
                        'status' => 'active',
                        'created_at' => now()->subDays(rand(1, 30)),
                    ]
                );
            }
        }
    }
}
