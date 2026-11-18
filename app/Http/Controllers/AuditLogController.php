<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function export(): StreamedResponse
    {
        // Only national_admin may export audit logs.
        abort_unless(auth()->user()->hasRole('national_admin'), 403);

        AuditLog::record('audit_log.exported');

        $filename = 'audit-log-'.now()->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['id', 'user_id', 'action', 'subject_type', 'subject_id', 'changed_fields', 'ip_hash', 'created_at']);

            AuditLog::query()
                ->orderBy('created_at')
                ->cursor()
                ->each(function (AuditLog $log) use ($handle) {
                    fputcsv($handle, [
                        $log->id,
                        $log->user_id ?? '',
                        $log->action,
                        $log->subject_type ?? '',
                        $log->subject_id ?? '',
                        $log->changed_fields ? json_encode($log->changed_fields) : '',
                        $log->ip_hash ?? '',
                        $log->created_at->toIso8601String(),
                    ]);
                });

            fclose($handle);
        }, 200, $headers);
    }
}
