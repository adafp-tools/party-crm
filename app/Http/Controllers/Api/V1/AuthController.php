<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:150'],
            'platform' => ['nullable', 'string', 'max:30'],
            'totp_code' => ['nullable', 'string', 'digits:6'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('These credentials do not match our records.')],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => [__('This account has been deactivated.')],
            ]);
        }

        // Check if 2FA is required for this user's roles and enabled
        $requiresTwoFactor = $user->requiresTwoFactor() && $user->hasTotpEnabled();

        if ($requiresTwoFactor) {
            if (! $request->totp_code) {
                return response()->json([
                    'two_factor_required' => true,
                    'message' => __('Two-factor authentication code required.'),
                ], 403);
            }

            $google2fa = new Google2FA;
            if (! $google2fa->verifyKey($user->totp_secret, $request->totp_code)) {
                throw ValidationException::withMessages([
                    'totp_code' => [__('Invalid two-factor authentication code.')],
                ]);
            }
        }

        // Revoke old tokens for this device name to prevent accumulation
        $existingDevice = Device::where('user_id', $user->id)
            ->where('name', $request->device_name)
            ->first();

        if ($existingDevice && $existingDevice->token_id) {
            $user->tokens()->where('id', $existingDevice->token_id)->delete();
        }

        $token = $user->createToken($request->device_name, ['*'], now()->addDays(30));

        $device = Device::updateOrCreate(
            ['user_id' => $user->id, 'name' => $request->device_name],
            [
                'platform' => $request->input('platform', 'unknown'),
                'token_id' => $token->accessToken->id,
                'revoked_at' => null,
                'wipe_requested_at' => null,
            ]
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'device_id' => $device->id,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'locale' => $user->locale,
                'roles' => $user->getRoleNames()->values(),
            ],
            'two_factor_required' => false,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $token = $request->user()->currentAccessToken();

        Device::where('user_id', $user->id)
            ->where('token_id', $token->id)
            ->update(['revoked_at' => now()]);

        $token->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
