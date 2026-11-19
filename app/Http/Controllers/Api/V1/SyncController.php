<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DuesPayment;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\Member;
use App\Models\Setting;
use App\Models\Task;
use App\Services\BlindIndexService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $since = $request->input('since');

        $memberQuery = Member::query()
            ->select([
                'id', 'org_unit_id', 'membership_no', 'first_name', 'middle_name',
                'last_name', 'gender', 'date_of_birth', 'status', 'category',
                'joined_date', 'phone', 'email', 'sync_version', 'created_at', 'updated_at',
            ]);

        if ($since) {
            $memberQuery->where('updated_at', '>', $since);
        }

        $members = $memberQuery->orderBy('updated_at')->limit(500)->get();

        $eventQuery = Event::query()
            ->select(['id', 'org_unit_id', 'title', 'type', 'starts_at', 'ends_at', 'location_text', 'updated_at']);
        if ($since) {
            $eventQuery->where('updated_at', '>', $since);
        }
        $events = $eventQuery->orderBy('updated_at')->limit(200)->get();

        $taskQuery = Task::query()
            ->where('assignee_id', $user->id)
            ->select(['id', 'org_unit_id', 'title', 'details', 'assignee_id', 'due_at', 'status', 'updated_at']);
        if ($since) {
            $taskQuery->where('updated_at', '>', $since);
        }
        $tasks = $taskQuery->orderBy('updated_at')->limit(200)->get();

        $appSettings = [
            'party_name' => Setting::get('party_name'),
            'party_short_name' => Setting::get('party_short_name'),
            'currency' => Setting::get('currency'),
            'locales' => explode(',', Setting::get('locales', 'en')),
            'default_locale' => Setting::get('default_locale', 'en'),
        ];

        if ($deviceName = $request->input('device_name')) {
            Device::updateOrCreate(
                ['user_id' => $user->id, 'name' => $deviceName],
                [
                    'platform' => $request->input('platform', 'unknown'),
                    'last_synced_at' => now(),
                ]
            );
        }

        return response()->json([
            'data' => $members,
            'events' => $events,
            'tasks' => $tasks,
            'settings' => $appSettings,
            'meta' => [
                'server_time' => now()->toIso8601String(),
                'count' => $members->count(),
            ],
        ]);
    }

    public function push(Request $request): JsonResponse
    {
        $request->validate([
            'operations' => ['required', 'array', 'max:100'],
            'operations.*.client_uuid' => ['required', 'uuid'],
            'operations.*.type' => ['required', 'string', 'in:create_member,update_member,record_payment,event_attendance,update_task'],
            'operations.*.payload' => ['required', 'array'],
            'operations.*.client_created_at' => ['required', 'date'],
        ]);

        $user = $request->user();
        $results = [];

        foreach ($request->operations as $op) {
            $clientUuid = $op['client_uuid'];

            try {
                $result = match ($op['type']) {
                    'create_member' => $this->handleCreateMember($op, $user),
                    'update_member' => $this->handleUpdateMember($op, $user),
                    'record_payment' => $this->handleRecordPayment($op, $user),
                    'event_attendance' => $this->handleEventAttendance($op, $user),
                    'update_task' => $this->handleUpdateTask($op, $user),
                };

                $results[] = array_merge(['client_uuid' => $clientUuid, 'status' => 'accepted'], $result);
            } catch (\Throwable $e) {
                $results[] = [
                    'client_uuid' => $clientUuid,
                    'status' => 'rejected',
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return response()->json(['results' => $results]);
    }

    private function handleCreateMember(array $op, $user): array
    {
        $payload = $op['payload'];
        $clientUuid = $op['client_uuid'];

        // Idempotency: if a member with this client_uuid already exists, return it
        $existing = Member::where('id', $clientUuid)->first();
        if ($existing) {
            return ['id' => $existing->id, 'idempotent' => true];
        }

        $blindIndex = app(BlindIndexService::class);

        $member = Member::create([
            'id' => $clientUuid,
            'org_unit_id' => $payload['org_unit_id'],
            'first_name' => $payload['first_name'],
            'middle_name' => $payload['middle_name'] ?? null,
            'last_name' => $payload['last_name'],
            'gender' => $payload['gender'] ?? 'undisclosed',
            'date_of_birth' => $payload['date_of_birth'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'phone_hash' => isset($payload['phone']) ? $blindIndex->hash($payload['phone']) : null,
            'email' => $payload['email'] ?? null,
            'email_hash' => isset($payload['email']) ? $blindIndex->hash($payload['email']) : null,
            'category' => $payload['category'] ?? 'member',
            'status' => 'applicant',
            'consent_at' => $payload['consent_at'] ?? now(),
            'consent_version' => $payload['consent_version'] ?? '1.0',
            'consent_channel' => 'phone_app',
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'client_created_at' => $op['client_created_at'],
            'sync_version' => 1,
        ]);

        return ['id' => $member->id];
    }

    private function handleUpdateMember(array $op, $user): array
    {
        $payload = $op['payload'];
        $member = Member::findOrFail($payload['id']);

        $allowed = ['first_name', 'middle_name', 'last_name', 'phone', 'email', 'gender', 'date_of_birth', 'preferred_language'];
        $updates = array_intersect_key($payload, array_flip($allowed));
        $updates['updated_by'] = $user->id;
        $updates['sync_version'] = DB::raw('sync_version + 1');

        $blindIndex = app(BlindIndexService::class);
        if (isset($updates['phone'])) {
            $updates['phone_hash'] = $blindIndex->hash($updates['phone']);
        }
        if (isset($updates['email'])) {
            $updates['email_hash'] = $blindIndex->hash($updates['email']);
        }

        $member->update($updates);

        return ['id' => $member->id, 'sync_version' => $member->fresh()->sync_version];
    }

    private function handleRecordPayment(array $op, $user): array
    {
        $payload = $op['payload'];
        $clientUuid = $op['client_uuid'];

        $existing = DuesPayment::where('client_uuid', $clientUuid)->first();
        if ($existing) {
            return ['id' => $existing->id, 'idempotent' => true];
        }

        $payment = DuesPayment::create([
            'member_id' => $payload['member_id'],
            'dues_plan_id' => $payload['dues_plan_id'] ?? null,
            'amount' => $payload['amount'],
            'currency' => $payload['currency'],
            'method' => $payload['method'],
            'reference' => $payload['reference'] ?? null,
            'receipt_no' => $payload['receipt_no'] ?? null,
            'period_from' => $payload['period_from'] ?? null,
            'period_to' => $payload['period_to'] ?? null,
            'status' => 'recorded',
            'recorded_by' => $user->id,
            'client_uuid' => $clientUuid,
        ]);

        return ['id' => $payment->id];
    }

    private function handleEventAttendance(array $op, $user): array
    {
        $payload = $op['payload'];
        $clientUuid = $op['client_uuid'];

        $existing = EventAttendance::where('client_uuid', $clientUuid)->first();
        if ($existing) {
            return ['id' => $existing->id, 'idempotent' => true];
        }

        $attendance = EventAttendance::updateOrCreate(
            ['event_id' => $payload['event_id'], 'member_id' => $payload['member_id']],
            ['status' => 'attended', 'client_uuid' => $clientUuid]
        );

        return ['id' => $attendance->id];
    }

    private function handleUpdateTask(array $op, $user): array
    {
        $payload = $op['payload'];
        $task = Task::findOrFail($payload['id']);

        $allowed = ['status', 'details'];
        $updates = array_intersect_key($payload, array_flip($allowed));
        $task->update($updates);

        return ['id' => $task->id];
    }
}
