<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function pull(Request $request): JsonResponse
    {
        $request->validate([
            'since' => ['nullable', 'date'],
            'device_name' => ['nullable', 'string', 'max:150'],
            'platform' => ['nullable', 'string', 'max:30'],
        ]);

        $user = $request->user();

        $device = Device::firstOrCreate(
            [
                'user_id' => $user->id,
                'name' => $request->input('device_name', 'unknown'),
                'platform' => $request->input('platform', 'unknown'),
            ],
        );

        $query = Member::query()
            ->select([
                'id', 'org_unit_id', 'membership_no', 'first_name', 'middle_name',
                'last_name', 'gender', 'date_of_birth', 'status', 'category',
                'joined_date', 'sync_version', 'created_at', 'updated_at',
            ]);

        if ($since = $request->input('since')) {
            $query->where('updated_at', '>', $since);
        }

        $members = $query->orderBy('updated_at')->get();

        $device->update(['last_synced_at' => now()]);

        return response()->json([
            'data' => $members,
            'meta' => [
                'server_time' => now()->toIso8601String(),
                'count' => $members->count(),
            ],
        ]);
    }
}
