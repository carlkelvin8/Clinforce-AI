<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogger
{
    public function log(?User $actor, string $action, ?string $entityType = null, ?int $entityId = null, array $metadata = [], ?Request $request = null): void
    {
        AuditLog::query()->create([
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => $metadata ?: null,
            'ip_address' => $request?->ip(),
            'created_at' => now(),
        ]);
    }
}

