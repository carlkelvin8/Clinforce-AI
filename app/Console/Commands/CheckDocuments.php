<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\JobApplication;
use Illuminate\Console\Command;

class CheckDocuments extends Command
{
    protected $signature = 'check:documents {application_id?}';
    protected $description = 'Check documents for an application';

    public function handle()
    {
        $appId = $this->argument('application_id');

        if ($appId) {
            $app = JobApplication::find($appId);
            if (!$app) {
                $this->error("Application not found: {$appId}");
                return 1;
            }

            $this->info("Application #{$app->id}");
            $this->info("Applicant User ID: {$app->applicant_user_id}");

            $docs = Document::where('user_id', $app->applicant_user_id)->get();
            $this->info("Total documents: " . $docs->count());

            foreach ($docs as $doc) {
                $this->line("- Doc #{$doc->id}: {$doc->doc_type} | Status: " . ($doc->status ?? 'NULL') . " | File: {$doc->file_name}");
            }

            $resumes = Document::where('user_id', $app->applicant_user_id)
                ->where('doc_type', 'resume')
                ->get();
            
            $this->info("\nResumes found: " . $resumes->count());
            foreach ($resumes as $resume) {
                $this->line("- Resume #{$resume->id}: Status: " . ($resume->status ?? 'NULL') . " | File: {$resume->file_name}");
            }
        } else {
            $this->info("All documents:");
            $docs = Document::all();
            foreach ($docs as $doc) {
                $this->line("Doc #{$doc->id}: User {$doc->user_id} | Type: {$doc->doc_type} | Status: " . ($doc->status ?? 'NULL'));
            }
        }

        return 0;
    }
}
