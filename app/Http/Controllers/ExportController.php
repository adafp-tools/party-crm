<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Export;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function members(Request $request): StreamedResponse
    {
        Gate::authorize('exportMembers');

        $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $members = Member::query()
            ->with('orgUnit:id,name')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get([
                'id', 'org_unit_id', 'membership_no', 'first_name', 'last_name',
                'gender', 'status', 'category', 'joined_date', 'created_at',
            ]);

        $rowCount = $members->count();

        Export::create([
            'user_id' => auth()->id(),
            'type' => 'members',
            'row_count' => $rowCount,
            'reason' => $request->input('reason'),
            'created_at' => now(),
        ]);

        AuditLog::record('members.exported', null, ['row_count' => $rowCount]);

        $filename = 'members-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($members): void {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for correct Excel rendering
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                __('Membership No'), __('First Name'), __('Last Name'),
                __('Gender'), __('Status'), __('Category'),
                __('Unit'), __('Joined Date'), __('Created At'),
            ]);

            foreach ($members as $member) {
                fputcsv($handle, [
                    $member->membership_no ?? '',
                    $member->first_name,
                    $member->last_name,
                    $member->gender ?? '',
                    $member->status,
                    $member->category,
                    $member->orgUnit?->name ?? '',
                    $member->joined_date?->format('Y-m-d') ?? '',
                    $member->created_at->format('Y-m-d'),
                ]);
            }

            // Mandatory footer row
            fputcsv($handle, [
                __('Exported by').' '.auth()->user()->name.' '.__('on').' '.now()->format('Y-m-d H:i').' UTC.',
                __('Contains personal data. Handle in accordance with applicable data protection law.'),
            ]);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
