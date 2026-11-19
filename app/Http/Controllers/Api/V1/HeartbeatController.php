<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HeartbeatController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $token = $user->currentAccessToken();

        $device = Device::where('user_id', $user->id)
            ->where('token_id', $token->id)
            ->first();

        if (! $device) {
            return response()->json(['wipe' => true, 'revoked' => true], 401);
        }

        if ($device->isRevoked()) {
            $token->delete();

            return response()->json(['wipe' => true, 'revoked' => true], 401);
        }

        $wipe = $device->needsWipe();

        if ($wipe) {
            $device->update(['revoked_at' => now()]);
            $token->delete();
        } else {
            $device->update(['last_synced_at' => now()]);
        }

        return response()->json([
            'wipe' => $wipe,
            'server_time' => now()->toIso8601String(),
        ]);
    }
}
