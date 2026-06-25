<?php
/**
 * Verify that the `approvals:escalate` command wrote the audit log row.
 * Usage:  php artisan tinker scripts/verify_escalation_audit.php
 */

use App\Models\AuditLog;

$logs = AuditLog::where('action', 'approval.escalated')
    ->orderByDesc('id')
    ->limit(5)
    ->get();

if ($logs->isEmpty()) {
    echo "NO approval.escalated audit rows found.\n";
    return;
}

echo "Found {$logs->count()} approval.escalated audit entries:\n";
foreach ($logs as $log) {
    echo sprintf(
        "  #%d  action=%s  auditable=%s:%d  metadata=%s\n",
        $log->id,
        $log->action,
        class_basename($log->auditable_type),
        $log->auditable_id,
        json_encode($log->metadata),
    );
}
