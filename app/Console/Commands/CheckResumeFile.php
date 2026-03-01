<?php

namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CheckResumeFile extends Command
{
    protected $signature = 'check:resume-file {doc_id}';
    protected $description = 'Check if resume file exists';

    public function handle()
    {
        $docId = $this->argument('doc_id');
        $doc = Document::find($docId);

        if (!$doc) {
            $this->error("Document not found: {$docId}");
            return 1;
        }

        $this->info("Document #{$doc->id}");
        $this->info("User ID: {$doc->user_id}");
        $this->info("Type: {$doc->doc_type}");
        $this->info("File URL: {$doc->file_url}");
        $this->info("File Name: {$doc->file_name}");

        // Try different path variations
        $filePath = $doc->file_url;
        
        $this->line("\nChecking file paths:");
        
        // Original path
        $this->line("1. Original: {$filePath}");
        $exists1 = Storage::disk('public')->exists($filePath);
        $this->line("   Exists: " . ($exists1 ? 'YES' : 'NO'));
        
        // Remove leading slash
        $path2 = ltrim($filePath, '/');
        $this->line("2. No leading slash: {$path2}");
        $exists2 = Storage::disk('public')->exists($path2);
        $this->line("   Exists: " . ($exists2 ? 'YES' : 'NO'));
        
        // Remove public/ prefix
        if (str_starts_with($path2, 'public/')) {
            $path3 = substr($path2, 7);
            $this->line("3. No public/ prefix: {$path3}");
            $exists3 = Storage::disk('public')->exists($path3);
            $this->line("   Exists: " . ($exists3 ? 'YES' : 'NO'));
        }
        
        // Remove storage/ prefix
        if (str_starts_with($path2, 'storage/')) {
            $path4 = substr($path2, 8);
            $this->line("4. No storage/ prefix: {$path4}");
            $exists4 = Storage::disk('public')->exists($path4);
            $this->line("   Exists: " . ($exists4 ? 'YES' : 'NO'));
        }

        // Check actual storage path
        $storagePath = storage_path('app/public');
        $this->line("\nStorage path: {$storagePath}");
        
        return 0;
    }
}
