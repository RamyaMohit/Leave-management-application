<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    public static function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        $oldValue = null,
        $newValue = null,
        ?int $userId = null
    ): AuditLog {
        $effectiveUserId = $userId ?? Auth::id();

        return AuditLog::create([
            'user_id' => $effectiveUserId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_value' => $oldValue ? (array) $oldValue : null,
            'new_value' => $newValue ? (array) $newValue : null,
        ]);
    }
}
