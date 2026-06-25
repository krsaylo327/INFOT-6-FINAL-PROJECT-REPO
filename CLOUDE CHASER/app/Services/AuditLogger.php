<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request as RequestFacade;

class AuditLogger
{
    /**
     * Log an auditable action.
     *
     * @param  string       $action    dot-notation verb (e.g. 'approval.approved')
     * @param  Model|null   $subject   the model this action is about (nullable)
     * @param  array        $metadata  any extra payload (will be JSON-encoded)
     * @param  int|null     $userId    actor; defaults to auth()->id()
     */
    public static function log(
        string $action,
        ?Model $subject = null,
        array $metadata = [],
        ?int $userId = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id'        => $userId ?? auth()->id(),
            'action'         => $action,
            'auditable_type' => $subject ? $subject::class : null,
            'auditable_id'   => $subject?->getKey(),
            'metadata'       => $metadata ?: null,
            'ip_address'     => self::safeIp(),
        ]);
    }

    private static function safeIp(): ?string
    {
        try {
            return RequestFacade::ip();
        } catch (\Throwable) {
            return null;
        }
    }
}
