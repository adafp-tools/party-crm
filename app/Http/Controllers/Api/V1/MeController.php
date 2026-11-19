<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DuesPlan;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        // Determine the org units this user can access
        $scopeUnits = $user->roleAssignments()
            ->with('orgUnit')
            ->get()
            ->map(fn ($ra) => $ra->org_unit_id)
            ->filter()
            ->unique()
            ->values();

        $duesPlans = DuesPlan::where('is_active', true)->get(['id', 'name', 'amount', 'currency', 'period']);

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'locale' => $user->locale,
                'roles' => $user->getRoleNames()->values(),
                'scope_unit_ids' => $scopeUnits,
                'dues_plans' => $duesPlans,
                'settings' => [
                    'party_name' => Setting::get('party_name'),
                    'party_short_name' => Setting::get('party_short_name'),
                    'currency' => Setting::get('currency'),
                    'locales' => explode(',', Setting::get('locales', 'en')),
                    'default_locale' => Setting::get('default_locale', 'en'),
                    'is_demo' => (bool) Setting::get('is_demo'),
                ],
            ],
        ]);
    }
}
