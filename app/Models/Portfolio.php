<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Portfolio extends Model
{
    protected $table = 'portfolios';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'type',
        'media_url',
        'thumbnail_url',
        'embed_url',
        'external_url',
        'domain',
        'category',
        'tags',
        'project_details',
        'completed_at',
        'is_public',
        'is_featured',
        'display_order',
        'views',
        'attachments',
    ];

    protected $casts = [
        'tags' => 'array',
        'project_details' => 'array',
        'attachments' => 'array',
        'completed_at' => 'date',
        'is_public' => 'boolean',
        'is_featured' => 'boolean',
        'display_order' => 'integer',
        'views' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'is_public' => true,
        'is_featured' => false,
        'display_order' => 0,
        'views' => 0,
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scopes
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('created_at', 'desc');
    }

    /**
     * Mutators
     */
    public function setExternalUrlAttribute($value)
    {
        $this->attributes['external_url'] = $value;
        
        if ($value) {
            $parsed = parse_url($value);
            $this->attributes['domain'] = $parsed['host'] ?? null;
        }
    }

    /**
     * Helper Methods
     */
    public function incrementViews(): void
    {
        $this->increment('views');
    }

    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    public function isImage(): bool
    {
        return $this->type === 'image';
    }

    public function isExternalLink(): bool
    {
        return $this->type === 'link';
    }

    public function getEmbedCode(): ?string
    {
        if (!$this->embed_url) {
            return null;
        }

        // YouTube embed
        if (str_contains($this->embed_url, 'youtube.com') || str_contains($this->embed_url, 'youtu.be')) {
            $videoId = $this->extractYouTubeVideoId();
            return $videoId ? "https://www.youtube.com/embed/{$videoId}" : null;
        }

        // Vimeo embed
        if (str_contains($this->embed_url, 'vimeo.com')) {
            $videoId = basename(parse_url($this->embed_url, PHP_URL_PATH));
            return "https://player.vimeo.com/video/{$videoId}";
        }

        return $this->embed_url;
    }

    private function extractYouTubeVideoId(): ?string
    {
        preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $this->embed_url, $matches);
        return $matches[1] ?? null;
    }

    /**
     * Get display icon based on type
     */
    public function getTypeIcon(): string
    {
        return match ($this->type) {
            'image' => '🖼️',
            'video' => '🎥',
            'link' => '🔗',
            'document' => '📄',
            'project' => '💼',
            default => '📎',
        };
    }
}
