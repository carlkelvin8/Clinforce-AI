<?php

namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Command;

class FixDocumentUrls extends Command
{
    protected $signature = 'fix:document-urls';
    protected $description = 'Fix document URLs to use relative paths instead of full URLs';

    public function handle()
    {
        $this->info('Fixing document URLs...');

        $documents = Document::all();
        $fixed = 0;

        foreach ($documents as $doc) {
            $originalUrl = $doc->file_url;
            
            // Skip if already a relative path
            if (!str_starts_with($originalUrl, 'http://') && !str_starts_with($originalUrl, 'https://')) {
                continue;
            }

            // Extract path from URL
            $parsed = parse_url($originalUrl);
            $path = $parsed['path'] ?? '';
            
            // Remove /storage/ prefix to get the actual storage path
            $relativePath = preg_replace('#^/storage/#', '', $path);
            
            $doc->file_url = $relativePath;
            $doc->save();
            
            $this->line("Fixed Doc #{$doc->id}: {$originalUrl} -> {$relativePath}");
            $fixed++;
        }

        $this->info("\nFixed {$fixed} document URLs");

        return 0;
    }
}
